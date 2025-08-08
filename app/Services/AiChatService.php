<?php

namespace App\Services;

use Prism\Prism;
use Prism\Responses\TextResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\User;
use App\Models\Team;
use App\Models\App;
use App\Models\AiChatUsage;
use App\Models\AiChatSession;
use App\Models\AiSettings;
use App\Services\Wallet\AiTokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * AI Chat Service - Handles AI message processing and response generation
 * 
 * This service manages the communication between users and AI models,
 * including token management, context preparation, and response formatting.
 * It supports multiple AI providers through the Prism library and includes
 * tenant-aware usage tracking for billing and quota management.
 * 
 * The service provides:
 * - Message processing with context awareness
 * - Token consumption tracking
 * - Multi-provider AI model support
 * - File analysis (images, documents)
 * - Command parsing and execution
 * - Usage analytics and cost calculation
 * 
 * @package App\Services
 * @since 1.0.0
 */
class AiChatService
{
    /** @var Prism AI provider instance for multi-model support */
    protected $prism;
    
    /** @var OpenAI facade for fallback AI operations */
    protected $openai;
    
    /** @var string Default AI model provider (openai, claude, ollama, etc.) */
    protected $defaultModel;
    
    /** @var string Fallback AI model provider */
    protected $fallbackModel;
    
    /** @var AiTokenService Service for managing AI token consumption */
    protected $aiTokenService;

    /**
     * Initialize the AI Chat Service with dependencies
     * 
     * Sets up the Prism multi-provider AI system and configures
     * default/fallback models for reliable AI operations.
     * 
     * @param AiTokenService $aiTokenService Service for token management
     */
    public function __construct(AiTokenService $aiTokenService)
    {
        // Initialize Prism (supports multiple LLM providers)
        $this->prism = new Prism();
        
        // Initialize OpenAI as fallback
        $this->openai = OpenAI::class;
        
        // Set default and fallback models
        $this->defaultModel = config('prism.default_provider', 'openai'); // Can be 'openai', 'claude', 'ollama', etc.
        $this->fallbackModel = 'openai'; // Always use OpenAI as fallback
        
        // Initialize AI token service
        $this->aiTokenService = $aiTokenService;
    }

    /**
     * Process a user message and generate AI response with tenant tracking
     * 
     * This method handles the complete AI chat workflow including token validation,
     * context preparation, AI model communication, and response formatting.
     * It ensures tenant isolation and tracks usage for billing purposes.
     * 
     * The process includes:
     * - Token balance validation and auto-topup
     * - Context preparation with privacy restrictions
     * - AI model communication with fallback support
     * - Response parsing and command extraction
     * - Usage tracking and cost calculation
     * 
     * @param string $message The user's input message to process
     * @param array $context Additional context data including user, team, and system information
     * @param array $history Previous conversation messages for context continuity
     * @return array Response containing AI content, metadata, usage info, and parsed commands
     * @throws Exception When user not found or token validation fails
     * @throws InsufficientTokensException When user lacks required tokens and auto-topup fails
     */
    public function processMessage(string $message, array $context = [], array $history = []): array
    {
        try {
            // Get user for token checking
            $user = isset($context['user']['id']) ? User::find($context['user']['id']) : null;
            if (!$user) {
                throw new Exception('User not found for AI token validation');
            }

            // Estimate token usage for this message
            $estimatedTokens = $this->aiTokenService->estimateTokenUsage($message);
            
            // Check if user has enough AI tokens
            if (!$this->aiTokenService->hasEnoughTokens($user, $estimatedTokens)) {
                // Try auto top-up first
                $team = isset($context['team']['id']) ? Team::find($context['team']['id']) : null;
                $autoTopUpSuccess = $this->aiTokenService->checkAutoTopUp($user, $team);
                
                if (!$autoTopUpSuccess || !$this->aiTokenService->hasEnoughTokens($user, $estimatedTokens)) {
                    return [
                        'error' => 'insufficient_tokens',
                        'message' => 'You have insufficient AI tokens to continue this conversation. Please purchase more tokens.',
                        'tokens_needed' => $estimatedTokens,
                        'current_balance' => $this->aiTokenService->getTokenBalance($user),
                        'auto_top_up_attempted' => $autoTopUpSuccess,
                    ];
                }
            }

            // Get user AI settings
            $aiSettings = $this->getUserAiSettings($context);
            
            // Apply privacy level restrictions
            $context = $this->applyPrivacyRestrictions($context, $aiSettings);
            
            // Prepare the system prompt with context
            $systemPrompt = $this->buildSystemPrompt($context);
            
            // Prepare conversation history
            $messages = $this->buildMessageHistory($history, $systemPrompt, $message);
            
            // Use user's preferred model if not using defaults
            $modelConfig = $this->getModelConfiguration($aiSettings);
            
            // Try primary model first (Prism or user's custom model)
            try {
                $response = $this->sendToPrism($messages, $context, $modelConfig);
                
                // Consume tokens after successful AI response
                $actualTokensUsed = $response['usage']['total_tokens'] ?? $estimatedTokens;
                $tokenConsumed = $this->aiTokenService->consumeTokens($user, $actualTokensUsed, [
                    'team_id' => $context['team']['id'] ?? null,
                    'session_id' => $context['session_id'] ?? null,
                    'message_id' => $context['message_id'] ?? null,
                    'model' => $modelConfig['model'],
                    'operation_type' => 'chat'
                ]);

                if (!$tokenConsumed) {
                    Log::warning('Failed to consume AI tokens after successful response', [
                        'user_id' => $user->id,
                        'tokens' => $actualTokensUsed
                    ]);
                }
                
                $this->trackUsage($response, $context, 'chat');
                Log::info('AI Chat: Using configured model', [
                    'model' => $modelConfig['model'],
                    'provider' => $modelConfig['provider'],
                    'user_id' => $context['user']['id'] ?? null,
                    'team_id' => $context['team']['id'] ?? null,
                    'tokens_consumed' => $actualTokensUsed
                ]);
                return $response;
            } catch (Exception $e) {
                Log::warning('AI Chat: Primary model failed, falling back to OpenAI', [
                    'error' => $e->getMessage(),
                    'user_id' => $context['user']['id'] ?? null,
                    'team_id' => $context['team']['id'] ?? null
                ]);
                
                // Fallback to OpenAI
                $response = $this->sendToOpenAI($messages, $context);
                
                // Consume tokens after successful AI response
                $actualTokensUsed = $response['usage']['total_tokens'] ?? $estimatedTokens;
                $tokenConsumed = $this->aiTokenService->consumeTokens($user, $actualTokensUsed, [
                    'team_id' => $context['team']['id'] ?? null,
                    'session_id' => $context['session_id'] ?? null,
                    'message_id' => $context['message_id'] ?? null,
                    'model' => 'gpt-4',
                    'operation_type' => 'chat'
                ]);

                if (!$tokenConsumed) {
                    Log::warning('Failed to consume AI tokens after successful fallback response', [
                        'user_id' => $user->id,
                        'tokens' => $actualTokensUsed
                    ]);
                }
                
                $this->trackUsage($response, $context, 'chat');
                Log::info('AI Chat: Using OpenAI fallback', [
                    'user_id' => $context['user']['id'] ?? null,
                    'team_id' => $context['team']['id'] ?? null,
                    'tokens_consumed' => $actualTokensUsed
                ]);
                return $response;
            }
            
        } catch (Exception $e) {
            Log::error('AI Chat: All providers failed', ['error' => $e->getMessage()]);
            throw new Exception('AI service unavailable. Please try again later.');
        }
    }

    /**
     * Send message to Prism AI provider (supports multiple LLM providers)
     * 
     * This method handles communication with the Prism library which supports
     * multiple AI providers including OpenAI, Claude, Ollama, and others.
     * It configures the request with user preferences and system prompts.
     * 
     * @param array $messages Formatted conversation messages for AI processing
     * @param array $context User and system context for AI understanding
     * @param array $modelConfig Model configuration including provider, API key, and settings
     * @return array Formatted AI response with content, usage, and metadata
     * @throws Exception When Prism communication fails or response is invalid
     */
    protected function sendToPrism(array $messages, array $context, array $modelConfig = []): array
    {
        // Prepare the prompt for Prism
        $prompt = $this->convertMessagesToPrompt($messages);
        
        // Get model to use (user preference or default)
        $model = $modelConfig['model'] ?? $this->defaultModel;
        $apiKey = $modelConfig['api_key'] ?? null;
        
        // Configure Prism request
        $prismRequest = $this->prism
            ->text()
            ->using($model);
            
        // Set API key if provided by user
        if ($apiKey && !$modelConfig['use_defaults']) {
            $prismRequest->withApiKey($apiKey);
        }
        
        $prismRequest
            ->withSystemPrompt($this->buildSystemPrompt($context))
            ->withTemperature(0.7)
            ->withMaxTokens(2000);

        // Add tools/functions if available
        if ($this->shouldUseTools($context)) {
            $prismRequest->withTools($this->getAvailableTools($context));
        }

        // Send request
        $response = $prismRequest->generate($prompt);

        return $this->formatPrismResponse($response);
    }

    /**
     * Send message to OpenAI as fallback provider
     * 
     * This method handles communication with OpenAI when the primary
     * AI provider fails or is unavailable. It ensures reliable AI
     * service through fallback mechanisms.
     * 
     * @param array $messages Formatted conversation messages for OpenAI
     * @param array $context User and system context for AI understanding
     * @return array Formatted OpenAI response with content, usage, and metadata
     * @throws Exception When OpenAI communication fails or response is invalid
     */
    protected function sendToOpenAI(array $messages, array $context): array
    {
        // Convert messages to OpenAI format
        $openaiMessages = $this->convertToOpenAIFormat($messages);
        
        // Add system prompt
        array_unshift($openaiMessages, [
            'role' => 'system',
            'content' => $this->buildSystemPrompt($context)
        ]);

        // Configure OpenAI request
        $response = $this->openai::chat()->create([
            'model' => 'gpt-4',
            'messages' => $openaiMessages,
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'tools' => $this->shouldUseTools($context) ? $this->getOpenAITools($context) : null,
        ]);

        return $this->formatOpenAIResponse($response);
    }

    /**
     * Build comprehensive system prompt with context for AI processing
     * 
     * This method aggregates all relevant context data including user settings,
     * team information, available commands, and system state. It applies
     * privacy restrictions based on user settings and prepares the prompt
     * in the format expected by the AI model.
     * 
     * The context includes:
     * - User preferences and permissions
     * - Team-specific data and settings
     * - Available system commands and tools
     * - Current application state
     * - Privacy and security restrictions
     * 
     * @param array $context Raw context data from request
     * @return string Formatted system prompt for AI model consumption
     */
    protected function buildSystemPrompt(array $context): string
    {
        $user = $context['user'] ?? [];
        $team = $context['team'] ?? [];
        $capabilities = $context['capabilities'] ?? [];
        $availableApps = $context['available_apps'] ?? [];

        $prompt = "You are an advanced AI assistant called 'Alien Intelligence' integrated into a comprehensive desktop operating system. ";
        $prompt .= "You can help users with questions, execute commands, and control applications.\n\n";

        // User context
        if (!empty($user)) {
            $prompt .= "USER CONTEXT:\n";
            $prompt .= "- Name: " . ($user['name'] ?? 'Unknown') . "\n";
            $prompt .= "- Email: " . ($user['email'] ?? 'Unknown') . "\n";
            if (isset($team['name'])) {
                $prompt .= "- Current Team: " . $team['name'] . " (Role: " . ($team['role'] ?? 'member') . ")\n";
            }
            $prompt .= "\n";
        }

        // Available applications
        if (!empty($availableApps)) {
            $prompt .= "AVAILABLE APPLICATIONS:\n";
            foreach ($availableApps as $app) {
                $prompt .= "- " . $app['name'] . " (" . $app['app_id'] . "): " . ucfirst($app['category']) . " app\n";
            }
            $prompt .= "\n";
        }

        // Capabilities
        $prompt .= "SYSTEM CAPABILITIES:\n";
        $prompt .= "You can execute the following types of commands:\n\n";

        if ($capabilities['can_create_blog_posts'] ?? false) {
            $prompt .= "BLOG MANAGEMENT:\n";
            $prompt .= "- Create blog posts: [COMMAND:create_blog_post title='Title' content='Content' category='category']\n";
            $prompt .= "- Update blog posts: [COMMAND:update_blog_post id=123 title='New Title' content='New Content']\n\n";
        }

        if ($capabilities['can_manage_products'] ?? false) {
            $prompt .= "PRODUCT MANAGEMENT:\n";
            $prompt .= "- Create products: [COMMAND:create_product name='Product Name' price=99.99 description='Description']\n";
            $prompt .= "- Update products: [COMMAND:update_product id=123 name='New Name' price=199.99]\n";
            $prompt .= "- Delete products: [COMMAND:delete_product id=123]\n\n";
        }

        if ($capabilities['can_manage_orders'] ?? false) {
            $prompt .= "ORDER MANAGEMENT:\n";
            $prompt .= "- Create orders: [COMMAND:create_order customer_id=123 items='[...]' total=299.99]\n";
            $prompt .= "- Update orders: [COMMAND:update_order id=456 status='shipped']\n\n";
        }

        if ($capabilities['can_send_emails'] ?? false) {
            $prompt .= "EMAIL MANAGEMENT:\n";
            $prompt .= "- Send emails: [COMMAND:send_email to='user@example.com' subject='Subject' body='Body']\n\n";
        }

        if ($capabilities['can_manage_contacts'] ?? false) {
            $prompt .= "CONTACT MANAGEMENT:\n";
            $prompt .= "- Create contacts: [COMMAND:create_contact name='John Doe' email='john@example.com' phone='+1234567890']\n";
            $prompt .= "- Update contacts: [COMMAND:update_contact id=123 name='Jane Doe']\n\n";
        }

        $prompt .= "SYSTEM CONTROLS:\n";
        $prompt .= "- Launch applications: [COMMAND:launch_app app_id='app-name']\n";
        $prompt .= "- Show notifications: [COMMAND:show_notification message='Message' type='info']\n\n";

        $prompt .= "COMMAND FORMAT:\n";
        $prompt .= "Use [COMMAND:action param1='value1' param2='value2'] format for commands.\n";
        $prompt .= "Always provide helpful explanations before and after commands.\n";
        $prompt .= "If a user asks about marketing, reports, analytics, or business insights, provide detailed analysis based on available data.\n\n";

        $prompt .= "INTERACTION GUIDELINES:\n";
        $prompt .= "- Be helpful, informative, and proactive\n";
        $prompt .= "- Suggest relevant actions and commands\n";
        $prompt .= "- Explain complex concepts clearly\n";
        $prompt .= "- Ask clarifying questions when needed\n";
        $prompt .= "- Provide examples and best practices\n";
        $prompt .= "- Handle business queries with expertise\n";
        $prompt .= "- Always maintain a professional but friendly tone\n";

        return $prompt;
    }

    /**
     * Build message history for AI conversation
     */
    protected function buildMessageHistory(array $history, string $systemPrompt, string $currentMessage): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Add recent history (last 10 messages)
        $recentHistory = array_slice($history, -10);
        foreach ($recentHistory as $msg) {
            if (isset($msg['type']) && isset($msg['content'])) {
                $role = $msg['type'] === 'user' ? 'user' : 'assistant';
                $messages[] = ['role' => $role, 'content' => $msg['content']];
            }
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $currentMessage];

        return $messages;
    }

    /**
     * Convert messages to simple prompt for Prism
     */
    protected function convertMessagesToPrompt(array $messages): string
    {
        $prompt = "";
        
        foreach ($messages as $message) {
            if ($message['role'] === 'system') continue; // System prompt handled separately
            
            $role = $message['role'] === 'user' ? 'Human' : 'Assistant';
            $prompt .= "{$role}: {$message['content']}\n\n";
        }
        
        $prompt .= "Assistant: ";
        
        return $prompt;
    }

    /**
     * Convert messages to OpenAI format
     */
    protected function convertToOpenAIFormat(array $messages): array
    {
        return $messages;
    }

    /**
     * Format Prism response
     */
    protected function formatPrismResponse($response): array
    {
        if ($response instanceof TextResponse) {
            return [
                'content' => $response->text,
                'model' => $this->defaultModel,
                'usage' => $response->usage ?? []
            ];
        }

        // Handle other response types
        return [
            'content' => (string) $response,
            'model' => $this->defaultModel,
            'usage' => []
        ];
    }

    /**
     * Format OpenAI response
     */
    protected function formatOpenAIResponse($response): array
    {
        $content = $response->choices[0]->message->content ?? '';
        
        // Handle function calls
        if (isset($response->choices[0]->message->tool_calls)) {
            $toolCalls = $response->choices[0]->message->tool_calls;
            // Process tool calls and append results to content
            foreach ($toolCalls as $toolCall) {
                $content .= "\n[COMMAND:" . $toolCall->function->name . " " . $toolCall->function->arguments . "]";
            }
        }

        return [
            'content' => $content,
            'model' => $response->model ?? 'gpt-4-turbo-preview',
            'usage' => [
                'prompt_tokens' => $response->usage->prompt_tokens ?? 0,
                'completion_tokens' => $response->usage->completion_tokens ?? 0,
                'total_tokens' => $response->usage->total_tokens ?? 0
            ]
        ];
    }

    /**
     * Analyze uploaded image
     */
    public function analyzeImage(UploadedFile $file): ?array
    {
        try {
            // Use vision model for image analysis
            $base64Image = base64_encode(file_get_contents($file->getPathname()));
            
            $response = $this->openai::chat()->create([
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analyze this image and describe what you see in detail.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$file->getMimeType()};base64,{$base64Image}"
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 500
            ]);

            return [
                'description' => $response->choices[0]->message->content,
                'type' => 'image_analysis'
            ];

        } catch (Exception $e) {
            Log::error('Image analysis failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Analyze uploaded document
     */
    public function analyzeDocument(UploadedFile $file): ?array
    {
        try {
            // Extract text from document (basic implementation)
            $content = file_get_contents($file->getPathname());
            
            // For PDFs and other complex formats, you'd use specialized libraries
            // This is a simplified version
            
            $response = $this->openai::chat()->create([
                'model' => 'gpt-4-turbo-preview',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Analyze the following document content and provide a summary and key insights.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Document content: " . substr($content, 0, 4000) // Limit content
                    ]
                ],
                'max_tokens' => 500
            ]);

            return [
                'summary' => $response->choices[0]->message->content,
                'type' => 'document_analysis'
            ];

        } catch (Exception $e) {
            Log::error('Document analysis failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if tools should be used
     */
    protected function shouldUseTools(array $context): bool
    {
        $capabilities = $context['capabilities'] ?? [];
        
        // Use tools if user has any app capabilities
        return !empty(array_filter($capabilities));
    }

    /**
     * Get available tools for function calling
     */
    protected function getAvailableTools(array $context): array
    {
        $tools = [];
        $capabilities = $context['capabilities'] ?? [];

        if ($capabilities['can_create_blog_posts'] ?? false) {
            $tools[] = [
                'name' => 'create_blog_post',
                'description' => 'Create a new blog post',
                'parameters' => [
                    'title' => 'string',
                    'content' => 'string',
                    'category' => 'string'
                ]
            ];
        }

        if ($capabilities['can_manage_products'] ?? false) {
            $tools[] = [
                'name' => 'create_product',
                'description' => 'Create a new product',
                'parameters' => [
                    'name' => 'string',
                    'price' => 'number',
                    'description' => 'string'
                ]
            ];
        }

        // Add more tools based on capabilities...

        return $tools;
    }

    /**
     * Get OpenAI tools format
     */
    protected function getOpenAITools(array $context): array
    {
        $tools = [];
        $capabilities = $context['capabilities'] ?? [];

        if ($capabilities['can_create_blog_posts'] ?? false) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'create_blog_post',
                    'description' => 'Create a new blog post',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'content' => ['type' => 'string'],
                            'category' => ['type' => 'string']
                        ],
                        'required' => ['title', 'content']
                    ]
                ]
            ];
        }

        // Add more OpenAI function definitions...

        return $tools;
    }

    /**
     * Track AI usage for billing and analytics with tenant isolation
     */
    protected function trackUsage(array $response, array $context, string $operationType): void
    {
        try {
            $usage = $response['usage'] ?? [];
            $userId = $context['user']['id'] ?? null;
            $teamId = $context['team']['id'] ?? null;
            $sessionId = $context['session_id'] ?? null;
            $messageId = $context['message_id'] ?? null;

            if (!$userId) {
                Log::warning('Cannot track AI usage: user_id not found in context');
                return;
            }

            $tokensUsed = $usage['total_tokens'] ?? 0;
            $model = $response['model'] ?? 'unknown';
            $provider = $response['provider'] ?? 'openai';

            // Calculate cost based on model and provider
            $cost = $this->calculateCost($tokensUsed, $model, $provider);

            AiChatUsage::create([
                'session_id' => $sessionId,
                'message_id' => $messageId,
                'user_id' => $userId,
                'team_id' => $teamId,
                'provider' => $provider,
                'model' => $model,
                'tokens_used' => $tokensUsed,
                'cost' => $cost,
                'operation_type' => $operationType,
                'metadata' => [
                    'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                    'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to track AI usage', [
                'error' => $e->getMessage(),
                'user_id' => $context['user']['id'] ?? null,
                'team_id' => $context['team']['id'] ?? null
            ]);
        }
    }

    /**
     * Calculate cost for AI usage based on model and provider
     */
    protected function calculateCost(int $tokens, string $model, string $provider): float
    {
        // Pricing per 1K tokens (as of 2024)
        $pricing = [
            'openai' => [
                'gpt-4-turbo-preview' => 0.01,
                'gpt-4-vision-preview' => 0.01,
                'gpt-3.5-turbo' => 0.0015,
            ],
            'anthropic' => [
                'claude-3-sonnet-20240229' => 0.003,
                'claude-3-haiku-20240307' => 0.00025,
            ],
            'groq' => [
                'llama3-70b-8192' => 0.00059,
                'llama3-8b-8192' => 0.00005,
            ]
        ];

        $rate = $pricing[$provider][$model] ?? 0.001; // Default fallback rate
        return ($tokens / 1000) * $rate;
    }

    protected function prepareAiContext(User $user, ?int $teamId): array
    {
        $context = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'locale' => $user->locale ?? 'en',
            ],
            'team' => $teamId ? [
                'id' => $teamId,
                'name' => Team::find($teamId)?->name,
                'settings' => $this->getTeamSettings($teamId),
                'member_count' => $this->getTeamMemberCount($teamId),
            ] : null,
            'tenant' => [
                'id' => tenant('id'),
                'domain' => tenant('domain'),
                'created_at' => tenant('created_at'),
            ],
            'ecosystem' => [
                'ecommerce' => $this->getEcommerceContext($teamId),
                'analytics' => $this->getAnalyticsContext($teamId),
                'files' => $this->getFileSystemContext($teamId),
                'notifications' => $this->getNotificationContext($teamId),
                'translations' => $this->getTranslationContext($teamId),
                'seo' => $this->getSeoContext($teamId),
                'widgets' => $this->getWidgetContext($user->id, $teamId),
                'search' => $this->getSearchContext($teamId),
                'payments' => $this->getPaymentContext($teamId),
                'blog' => $this->getBlogContext($teamId),
                'contacts' => $this->getContactContext($teamId),
                'system' => $this->getSystemContext($teamId),
                'sitebuilder' => $this->getSitebuilderContext($teamId),
            ],
            'installedApps' => App::where('installed', true)
                ->where(function ($query) use ($teamId) {
                    $query->whereNull('team_id')
                          ->orWhere('team_id', $teamId);
                })
                ->get(['app_id', 'name', 'category', 'version'])
                ->toArray(),
            'availableCommands' => $this->getAvailableCommands($user, $teamId),
            'preferences' => $user->preferences ?? [],
            'activeSession' => [
                'session_count' => AiChatSession::where('user_id', $user->id)->count(),
                'last_activity' => AiChatSession::where('user_id', $user->id)->latest('updated_at')->value('updated_at'),
            ],
            'timestamp' => now()->toISOString(),
        ];
        
        return $context;
    }

    protected function getEcommerceContext(?int $teamId): array
    {
        try {
            // Try Aimeos integration first
            if (class_exists('\Aimeos\Shop\Facades\Shop')) {
                $context = \Aimeos\Shop\Facades\Shop::context();
                $productManager = \Aimeos\MShop::create($context, 'product');
                $orderManager = \Aimeos\MShop::create($context, 'order');
                
                $productSearch = $productManager->filter();
                $orderSearch = $orderManager->filter();
                
                return [
                    'enabled' => true,
                    'platform' => 'Aimeos',
                    'total_products' => $productManager->aggregate($productSearch, 'product.id')->count(),
                    'total_orders' => $orderManager->aggregate($orderSearch, 'order.id')->count(),
                    'currencies' => ['USD', 'EUR', 'GBP'], // From config
                    'payment_methods' => ['stripe', 'paypal'], // From config
                    'shipping_methods' => $this->getShippingMethods(),
                    'categories' => $this->getProductCategories(),
                    'low_stock_threshold' => 10,
                    'features' => [
                        'inventory_management' => true,
                        'multi_currency' => true,
                        'discount_system' => true,
                        'subscription_support' => true,
                    ]
                ];
            }
            
            // Fallback to generic e-commerce info
            return [
                'enabled' => false,
                'platform' => 'Generic',
                'message' => 'E-commerce system not fully configured',
                'available_commands' => ['get_product_stats', 'create_product', 'manage_inventory']
            ];
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'E-commerce context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getAnalyticsContext(?int $teamId): array
    {
        try {
            $context = [
                'enabled' => config('ai-chat.integrations.analytics.enabled', true),
                'providers' => [],
                'last_updated' => $this->getLastAnalyticsUpdate($teamId),
                'features' => []
            ];

            // Google Analytics integration
            if (class_exists('\Spatie\Analytics\Analytics')) {
                $context['providers'][] = 'Google Analytics';
                $context['features']['google_analytics'] = true;
                $context['tracking_id'] = config('analytics.view_id');
            }

            // SEO Tools integration
            if (class_exists('\Artesaos\SEOTools\Facades\SEOMeta')) {
                $context['providers'][] = 'SEO Tools';
                $context['features']['seo_optimization'] = true;
                $context['features']['meta_management'] = true;
            }

            // Self-hosted analytics (Plausible/Umami)
            if (config('services.plausible.enabled')) {
                $context['providers'][] = 'Plausible';
                $context['features']['privacy_focused'] = true;
            }

            $context['available_metrics'] = [
                'page_views', 'unique_visitors', 'bounce_rate', 'session_duration',
                'traffic_sources', 'device_types', 'geographic_data', 'conversion_rates'
            ];

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'Analytics context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getFileSystemContext(?int $teamId): array
    {
        try {
            $tenantPath = "tenant-{$teamId}";
            $disk = Storage::disk('local');
            
            $context = [
                'enabled' => true,
                'platform' => 'Laravel Storage + ElFinder',
                'tenant_path' => $tenantPath,
                'features' => [
                    'tenant_isolation' => true,
                    'file_sharing' => true,
                    'version_control' => false,
                    'image_optimization' => class_exists('\Intervention\Image\ImageManager'),
                ]
            ];

            if ($disk->exists($tenantPath)) {
                $files = $disk->allFiles($tenantPath);
                $directories = $disk->allDirectories($tenantPath);
                
                $context['statistics'] = [
                    'total_files' => count($files),
                    'total_directories' => count($directories),
                    'total_size' => $this->calculateDirectorySize($disk, $tenantPath),
                    'file_types' => $this->getFileTypeDistribution($disk, $tenantPath),
                ];
            } else {
                $context['statistics'] = [
                    'total_files' => 0,
                    'total_directories' => 0,
                    'total_size' => 0,
                    'file_types' => [],
                ];
            }

            // ElFinder integration
            if (class_exists('\Barryvdh\Elfinder\ElfinderServiceProvider')) {
                $context['file_manager'] = 'ElFinder';
                $context['features']['web_interface'] = true;
                $context['features']['drag_drop'] = true;
            }

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'File system context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getNotificationContext(?int $teamId): array
    {
        try {
            $context = [
                'enabled' => true,
                'channels' => [],
                'features' => [],
                'statistics' => []
            ];

            // Real-time notifications (Reverb)
            if (class_exists('\Laravel\Reverb\ReverbServiceProvider')) {
                $context['channels'][] = 'Real-time (Reverb)';
                $context['features']['real_time'] = true;
                $context['features']['websockets'] = true;
            }

            // SMS notifications
            if (class_exists('\Prgayman\LaravelSms\SmsServiceProvider')) {
                $context['channels'][] = 'SMS';
                $context['features']['sms_campaigns'] = true;
                $context['sms_providers'] = config('sms.drivers', []);
            }

            // Email notifications
            $context['channels'][] = 'Email';
            $context['features']['email_campaigns'] = true;
            $context['mail_driver'] = config('mail.default');

            // Push notifications
            if (config('services.pusher.key')) {
                $context['channels'][] = 'Push Notifications';
                $context['features']['push_notifications'] = true;
            }

            // Get notification statistics
            if ($teamId) {
                $context['statistics'] = [
                    'total_sent' => DB::table('notifications')
                        ->where('notifiable_type', 'App\\Models\\Team')
                        ->where('notifiable_id', $teamId)
                        ->count(),
                    'unread_count' => DB::table('notifications')
                        ->where('notifiable_type', 'App\\Models\\Team')
                        ->where('notifiable_id', $teamId)
                        ->whereNull('read_at')
                        ->count(),
                ];
            }

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'Notification context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getTranslationContext(?int $teamId): array
    {
        try {
            $context = [
                'enabled' => true,
                'features' => [],
                'supported_languages' => [],
                'statistics' => []
            ];

            // Laravel Localization
            if (class_exists('\Mcamara\LaravelLocalization\LaravelLocalization')) {
                $context['features']['route_localization'] = true;
                $context['features']['locale_detection'] = true;
                $context['supported_languages'] = config('laravellocalization.supportedLocales', []);
            }

            // Translation management
            if (class_exists('\MohmmedAshraf\LaravelTranslations\TranslationsServiceProvider')) {
                $context['features']['translation_management'] = true;
                $context['features']['translation_ui'] = true;
            }

            // AI Translation capability
            $context['features']['ai_translation'] = true;
            $context['ai_providers'] = ['OpenAI', 'Anthropic', 'Google Translate'];

            // Get translation statistics
            if (DB::getSchemaBuilder()->hasTable('translations')) {
                $context['statistics'] = [
                    'total_translations' => DB::table('translations')->count(),
                    'languages_count' => DB::table('translations')->distinct('locale')->count(),
                    'missing_translations' => DB::table('translations')->whereNull('value')->count(),
                ];
            }

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'Translation context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getSeoContext(?int $teamId): array
    {
        try {
            $context = [
                'enabled' => true,
                'tools' => [],
                'features' => [],
                'metrics' => []
            ];

            // SEO Tools package
            if (class_exists('\Artesaos\SEOTools\Facades\SEOMeta')) {
                $context['tools'][] = 'Laravel SEO Tools';
                $context['features']['meta_management'] = true;
                $context['features']['open_graph'] = true;
                $context['features']['twitter_cards'] = true;
            }

            // Schema.org support
            if (class_exists('\Spatie\SchemaOrg\Schema')) {
                $context['tools'][] = 'Schema.org';
                $context['features']['structured_data'] = true;
                $context['features']['rich_snippets'] = true;
            }

            // Image optimization
            if (class_exists('\Spatie\LaravelImageOptimizer\ImageOptimizerServiceProvider')) {
                $context['features']['image_optimization'] = true;
            }

            $context['seo_features'] = [
                'sitemap_generation' => true,
                'robots_txt' => true,
                'canonical_urls' => true,
                'meta_optimization' => true,
                'keyword_analysis' => true,
                'content_optimization' => true,
            ];

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'SEO context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getWidgetContext(int $userId, ?int $teamId): array
    {
        try {
            $context = [
                'enabled' => true,
                'framework' => 'GridStack.js + Laravel Widgets',
                'features' => [
                    'drag_drop' => true,
                    'responsive_layout' => true,
                    'widget_library' => true,
                    'custom_widgets' => true,
                ]
            ];

            // Get user's widgets
            if (DB::getSchemaBuilder()->hasTable('widgets')) {
                $userWidgets = DB::table('widgets')
                    ->where('user_id', $userId)
                    ->where(function ($query) use ($teamId) {
                        $query->whereNull('team_id')->orWhere('team_id', $teamId);
                    })
                    ->get(['id', 'name', 'type', 'position']);

                $context['user_widgets'] = $userWidgets->toArray();
                $context['widget_count'] = $userWidgets->count();
            }

            // Available widget types
            $context['available_types'] = [
                'chart' => 'Data visualization charts',
                'stats' => 'Statistics and KPIs',
                'calendar' => 'Calendar and events',
                'weather' => 'Weather information',
                'tasks' => 'Task management',
                'notes' => 'Quick notes',
                'analytics' => 'Analytics dashboard',
                'social' => 'Social media feeds',
                'news' => 'News and updates',
                'custom' => 'Custom HTML/Vue components'
            ];

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'Widget context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getSearchContext(?int $teamId): array
    {
        try {
            $context = [
                'enabled' => true,
                'driver' => config('scout.driver', 'collection'),
                'features' => [],
                'searchable_models' => []
            ];

            // Laravel Scout integration
            if (class_exists('\Laravel\Scout\ScoutServiceProvider')) {
                $context['features']['full_text_search'] = true;
                $context['features']['faceted_search'] = true;
                
                if (config('scout.driver') === 'meilisearch') {
                    $context['search_engine'] = 'Meilisearch';
                    $context['features']['typo_tolerance'] = true;
                    $context['features']['instant_search'] = true;
                } elseif (config('scout.driver') === 'algolia') {
                    $context['search_engine'] = 'Algolia';
                    $context['features']['real_time_search'] = true;
                }
            }

            // Command palette search
            $context['features']['command_palette'] = true;
            $context['features']['global_search'] = true;

            // Searchable content types
            $searchableModels = [
                'products' => 'E-commerce products',
                'orders' => 'Customer orders',
                'customers' => 'Customer database',
                'content' => 'Website content',
                'blog_posts' => 'Blog articles',
                'files' => 'File system',
                'contacts' => 'Contact directory'
            ];

            foreach ($searchableModels as $model => $description) {
                $modelClass = "App\\Models\\" . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($model));
                if (class_exists($modelClass)) {
                    $context['searchable_models'][$model] = $description;
                }
            }

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'Search context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getPaymentContext(?int $teamId): array
    {
        try {
            $context = [
                'enabled' => true,
                'gateways' => [],
                'features' => [],
                'currencies' => ['USD', 'EUR', 'GBP']
            ];

            // Stripe integration
            if (config('services.stripe.key')) {
                $context['gateways'][] = 'Stripe';
                $context['features']['credit_cards'] = true;
                $context['features']['subscriptions'] = true;
            }

            // PayPal integration
            if (config('services.paypal.client_id')) {
                $context['gateways'][] = 'PayPal';
                $context['features']['paypal_payments'] = true;
            }

            // Wallet system
            if (class_exists('\Bavix\Wallet\WalletServiceProvider')) {
                $context['features']['wallet_system'] = true;
                $context['features']['multi_wallet'] = true;
                $context['features']['transactions'] = true;
            }

            // Omnipay support
            if (class_exists('\Omnipay\Omnipay')) {
                $context['features']['multiple_gateways'] = true;
                $context['features']['omnipay_support'] = true;
            }

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'Payment context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getBlogContext(?int $teamId): array
    {
        try {
            $context = [
                'enabled' => true,
                'features' => [
                    'post_management' => true,
                    'category_system' => true,
                    'tag_system' => true,
                    'comment_system' => false,
                    'seo_optimization' => true,
                ]
            ];

            // Canvas blog integration
            if (class_exists('\Canvas\Canvas')) {
                $context['platform'] = 'Canvas Blog';
                $context['features']['rich_editor'] = true;
                $context['features']['media_library'] = true;
            }

            // Get blog statistics
            if (DB::getSchemaBuilder()->hasTable('blog_posts')) {
                $context['statistics'] = [
                    'total_posts' => DB::table('blog_posts')->where('team_id', $teamId)->count(),
                    'published_posts' => DB::table('blog_posts')->where('team_id', $teamId)->where('status', 'published')->count(),
                    'draft_posts' => DB::table('blog_posts')->where('team_id', $teamId)->where('status', 'draft')->count(),
                ];
            }

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'Blog context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getContactContext(?int $teamId): array
    {
        try {
            $context = [
                'enabled' => true,
                'features' => [
                    'contact_management' => true,
                    'company_tracking' => true,
                    'import_export' => true,
                    'search_filter' => true,
                ]
            ];

            // Get contact statistics
            if (DB::getSchemaBuilder()->hasTable('contacts')) {
                $context['statistics'] = [
                    'total_contacts' => DB::table('contacts')->where('team_id', $teamId)->count(),
                    'companies' => DB::table('contacts')->where('team_id', $teamId)->whereNotNull('company')->distinct('company')->count(),
                    'recent_contacts' => DB::table('contacts')->where('team_id', $teamId)->where('created_at', '>=', now()->subDays(30))->count(),
                ];
            }

            return $context;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => 'Contact context unavailable: ' . $e->getMessage()
            ];
        }
    }

    protected function getSystemContext(?int $teamId): array
    {
        return [
            'enabled' => true,
            'features' => [
                'health_monitoring' => true,
                'backup_system' => true,
                'cache_management' => true,
                'app_management' => true,
                'performance_monitoring' => true,
            ],
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
                'timezone' => config('app.timezone'),
            ],
            'performance' => [
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
                'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
                'uptime' => $this->getSystemUptime(),
            ]
        ];
    }

    protected function getAvailableCommands(User $user, ?int $teamId): array
    {
        $commands = [
            'ecommerce' => [
                'get_product_stats' => 'Get product statistics and inventory info',
                'create_product' => 'Create new products in the catalog',
                'update_inventory' => 'Manage stock levels and inventory',
                'get_order_summary' => 'Retrieve order analytics and summaries',
                'process_refund' => 'Handle customer refunds',
                'get_sales_analytics' => 'Get sales performance data',
                'manage_customer' => 'Customer management operations',
                'update_pricing' => 'Update product pricing',
                'manage_discounts' => 'Create and manage discounts',
                'get_inventory_alerts' => 'Get low stock alerts',
            ],
            'files' => [
                'list_files' => 'Browse and list tenant files',
                'upload_file' => 'Upload and organize files',
                'delete_file' => 'Delete files with proper permissions',
                'share_file' => 'Share files within team',
                'get_storage_stats' => 'Get storage usage analytics',
                'organize_files' => 'Auto-organize files by type/date',
            ],
            'analytics' => [
                'get_website_stats' => 'Get website performance analytics',
                'seo_analysis' => 'Perform SEO audit and recommendations',
                'generate_sitemap' => 'Generate XML sitemap',
                'optimize_images' => 'Optimize images for performance',
                'get_keywords' => 'Get keyword analysis and suggestions',
            ],
            'notifications' => [
                'send_notification' => 'Send real-time notifications',
                'send_sms' => 'Send SMS marketing campaigns',
                'send_email_campaign' => 'Send email marketing campaigns',
                'get_notification_stats' => 'Get communication analytics',
            ],
            'translations' => [
                'translate_content' => 'AI-powered content translation',
                'get_supported_languages' => 'Get list of supported languages',
                'add_translation' => 'Add manual translations',
                'export_translations' => 'Export translation files',
            ],
            'widgets' => [
                'create_widget' => 'Create custom dashboard widgets',
                'update_dashboard' => 'Update dashboard layout',
                'get_widget_data' => 'Refresh widget data',
                'arrange_widgets' => 'Auto-arrange dashboard widgets',
            ],
            'search' => [
                'search_content' => 'Search across all content types',
                'index_content' => 'Manage search indexes',
                'get_search_stats' => 'Get search analytics',
            ],
            'blog' => [
                'create_blog_post' => 'Create new blog posts',
                'update_blog_post' => 'Update existing blog posts',
                'publish_blog_post' => 'Publish blog posts',
                'get_blog_stats' => 'Get blog analytics',
            ],
            'contacts' => [
                'create_contact' => 'Create new contacts',
                'update_contact' => 'Update contact information',
                'search_contacts' => 'Search contact database',
                'export_contacts' => 'Export contact lists',
            ],
            'system' => [
                'system_health' => 'Check system health and performance',
                'backup_data' => 'Backup system data',
                'clear_cache' => 'Clear application cache',
                'generate_report' => 'Generate custom reports',
            ]
        ];

        // Filter commands based on user permissions and installed apps
        $filteredCommands = [];
        foreach ($commands as $category => $categoryCommands) {
            $filteredCommands[$category] = [];
            foreach ($categoryCommands as $command => $description) {
                if ($this->userCanExecuteCommand($user, $command, $teamId)) {
                    $filteredCommands[$category][$command] = $description;
                }
            }
            // Remove empty categories
            if (empty($filteredCommands[$category])) {
                unset($filteredCommands[$category]);
            }
        }

        return $filteredCommands;
    }

    // Helper methods
    protected function getTeamSettings(?int $teamId): array
    {
        if (!$teamId) return [];
        
        try {
            if (class_exists('\Rawilk\Settings\Settings')) {
                return app(\Rawilk\Settings\Settings::class)->allForTeam($teamId);
            }
            
            return DB::table('team_settings')->where('team_id', $teamId)->pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getTeamMemberCount(?int $teamId): int
    {
        if (!$teamId) return 0;
        
        return DB::table('team_user')->where('team_id', $teamId)->count();
    }

    protected function getShippingMethods(): array
    {
        return ['standard', 'express', 'overnight', 'pickup'];
    }

    protected function getProductCategories(): array
    {
        try {
            if (class_exists('\Aimeos\Shop\Facades\Shop')) {
                $context = \Aimeos\Shop\Facades\Shop::context();
                $manager = \Aimeos\MShop::create($context, 'catalog');
                $search = $manager->filter();
                $items = $manager->search($search);
                
                return $items->getName()->toArray();
            }
        } catch (\Exception $e) {
            // Fall back to default categories
        }
        
        return ['Electronics', 'Clothing', 'Books', 'Home & Garden'];
    }

    protected function getLastAnalyticsUpdate(?int $teamId): ?string
    {
        try {
            return Cache::get("analytics_last_update_{$teamId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function calculateDirectorySize($disk, string $path): int
    {
        try {
            $files = $disk->allFiles($path);
            return array_sum(array_map(fn($file) => $disk->size($file), $files));
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getFileTypeDistribution($disk, string $path): array
    {
        try {
            $files = $disk->allFiles($path);
            $distribution = [];
            
            foreach ($files as $file) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $extension = strtolower($extension ?: 'no-extension');
                $distribution[$extension] = ($distribution[$extension] ?? 0) + 1;
            }
            
            return $distribution;
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getSystemUptime(): string
    {
        try {
            if (function_exists('sys_getloadavg') && is_readable('/proc/uptime')) {
                $uptime = file_get_contents('/proc/uptime');
                $uptime = explode(' ', $uptime)[0];
                return gmdate('H:i:s', $uptime);
            }
            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    protected function userCanExecuteCommand(User $user, string $command, ?int $teamId): bool
    {
        try {
            // Check if user has permission to execute this command
            if (method_exists($user, 'hasPermissionTo') && !$user->hasPermissionTo("execute.{$command}")) {
                return false;
            }
            
            // Check app-specific requirements
            $commandAppMap = [
                'create_product' => 'ecommerce',
                'get_website_stats' => 'analytics',
                'send_sms' => 'communication',
                'create_blog_post' => 'blog',
                'backup_data' => 'system',
                'createPage' => 'sitebuilder',
                'updatePageContent' => 'sitebuilder',
                'changePageColors' => 'sitebuilder',
                'updatePageTexts' => 'sitebuilder',
                'publishPage' => 'sitebuilder',
                'createPageTemplate' => 'sitebuilder',
            ];
            
            $requiredApp = $commandAppMap[$command] ?? null;
            if ($requiredApp) {
                return App::where('app_id', $requiredApp)
                    ->where('installed', true)
                    ->where(function ($query) use ($teamId) {
                        $query->whereNull('team_id')->orWhere('team_id', $teamId);
                    })
                    ->exists();
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getSitebuilderContext(?int $teamId): array
    {
        try {
            $config = config('ai-chat.integrations.sitebuilder', []);
            
            if (!($config['enabled'] ?? false)) {
                return [
                    'enabled' => false,
                    'message' => 'Sitebuilder (Laravel Grapes) is not enabled'
                ];
            }

            // Get page statistics for the team
            $pageStats = DB::table('pages')
                ->where('team_id', $teamId)
                ->selectRaw('
                    COUNT(*) as total_pages,
                    SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published_pages,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_pages,
                    MAX(updated_at) as last_updated
                ')
                ->first();

            // Get recent pages
            $recentPages = DB::table('pages')
                ->where('team_id', $teamId)
                ->select('id', 'name', 'slug', 'status', 'updated_at')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray();

            return [
                'enabled' => true,
                'platform' => 'Laravel Grapes (GrapesJS)',
                'builder_url' => url($config['builder_prefix'] ?? 'sitebuilder') . '/front-end-builder',
                'total_pages' => $pageStats->total_pages ?? 0,
                'published_pages' => $pageStats->published_pages ?? 0,
                'draft_pages' => $pageStats->draft_pages ?? 0,
                'last_updated' => $pageStats->last_updated,
                'recent_pages' => $recentPages,
                'features' => [
                    'drag_drop_editor' => true,
                    'responsive_design' => true,
                    'custom_css' => true,
                    'template_system' => true,
                    'multilingual' => true,
                    'seo_optimization' => true,
                ],
                'supported_languages' => $config['languages'] ?? ['en'],
                'grapesjs_config' => [
                    'canvas_height' => $config['grapesjs']['canvas_styles']['height'] ?? '100vh',
                    'background_color' => $config['grapesjs']['canvas_styles']['background'] ?? '#ffffff',
                    'panels_enabled' => $config['grapesjs']['panels'] ?? [],
                ],
                'available_commands' => [
                    'createPage' => 'Create new HTML page with GrapesJS',
                    'updatePageContent' => 'Modify page content and layout',
                    'changePageColors' => 'Update color scheme',
                    'updatePageTexts' => 'Modify text content',
                    'publishPage' => 'Publish page to live site',
                    'createPageTemplate' => 'Create reusable templates',
                    'clonePage' => 'Duplicate existing pages',
                ],
                'storage_info' => [
                    'templates_path' => storage_path('app/sitebuilder/templates'),
                    'pages_path' => storage_path('app/sitebuilder'),
                    'tenant_isolated' => true,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get sitebuilder context', ['error' => $e->getMessage(), 'team_id' => $teamId]);
            return [
                'enabled' => false,
                'error' => 'Failed to retrieve sitebuilder information'
            ];
        }
    }

    /**
     * Get user AI settings with fallback to defaults
     */
    protected function getUserAiSettings(array $context): AiSettings
    {
        $userId = $context['user']['id'] ?? null;
        $teamId = $context['team']['id'] ?? null;

        if (!$userId) {
            // Return default settings if no user context
            return new AiSettings([
                'use_defaults' => true,
                'privacy_level' => 'public'
            ]);
        }

        return AiSettings::getOrCreateForUser($userId, $teamId);
    }

    /**
     * Get model configuration based on user settings
     */
    protected function getModelConfiguration(AiSettings $aiSettings): array
    {
        $effectiveSettings = $aiSettings->getEffectiveSettings();
        
        return [
            'model' => $effectiveSettings['model'],
            'provider' => $effectiveSettings['provider'],
            'use_defaults' => $effectiveSettings['use_defaults'],
            'api_key' => !$effectiveSettings['use_defaults'] ? $aiSettings->getDecryptedApiKey() : null
        ];
    }

    /**
     * Apply privacy level restrictions to context
     */
    protected function applyPrivacyRestrictions(array $context, AiSettings $aiSettings): array
    {
        $privacyLevel = $aiSettings->privacy_level;

        switch ($privacyLevel) {
            case 'public':
                // Remove sensitive data, keep only public website info
                $context['privacy_restricted'] = true;
                unset($context['ecosystem']['files']);
                unset($context['ecosystem']['analytics']);
                unset($context['ecosystem']['payments']);
                unset($context['ecosystem']['contacts']);
                unset($context['installedApps']);
                $context['privacy_message'] = 'Access limited to public website data only.';
                break;

            case 'private':
                // Allow access to all data but no command execution
                $context['can_execute_commands'] = false;
                $context['privacy_message'] = 'Full data access enabled, command execution disabled.';
                break;

            case 'agent':
                // Full access including command execution
                $context['can_execute_commands'] = true;
                $context['privacy_message'] = 'Full agent mode: data access and command execution enabled.';
                break;
        }

        $context['privacy_level'] = $privacyLevel;
        return $context;
    }
} 