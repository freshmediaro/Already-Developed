<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\App;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiChatFeedback;
use App\Services\AiChatService;
use App\Services\CommandExecutionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * AI Chat Controller - Handles AI chat interactions and command execution
 *
 * This controller manages all AI chat-related operations including message processing,
 * file uploads, feedback collection, and command execution. It provides comprehensive
 * tenant isolation and user authentication for secure AI interactions.
 *
 * Key responsibilities:
 * - Message processing and AI response generation
 * - File upload and analysis for AI processing
 * - Command parsing and execution
 * - Feedback collection and storage
 * - Chat history management
 * - Tenant-aware session management
 *
 * The controller ensures:
 * - Proper tenant isolation for all operations
 * - User authentication and authorization
 * - Input validation and sanitization
 * - Error handling and logging
 * - Secure file storage with tenant separation
 *
 * @package App\Http\Controllers\Api
 * @since 1.0.0
 */
class AiChatController extends Controller
{
    /** @var AiChatService Service for AI message processing and response generation */
    protected AiChatService $aiChatService;

    /** @var CommandExecutionService Service for executing parsed commands */
    protected CommandExecutionService $commandExecutionService;

    /**
     * Initialize the AI Chat Controller with required services
     *
     * @param AiChatService $aiChatService Service for AI operations
     * @param CommandExecutionService $commandExecutionService Service for command execution
     */
    public function __construct(AiChatService $aiChatService, CommandExecutionService $commandExecutionService)
    {
        $this->aiChatService = $aiChatService;
        $this->commandExecutionService = $commandExecutionService;
    }

    /**
     * Process user message and generate AI response with tenant isolation
     *
     * This method handles the complete AI chat workflow including message validation,
     * session management, AI processing, command parsing, and response storage.
     * It ensures proper tenant isolation and user authentication throughout the process.
     *
     * The workflow includes:
     * - Input validation and sanitization
     * - Session creation or retrieval with tenant isolation
     * - User message storage with metadata
     * - AI context preparation with team-specific data
     * - AI response generation with fallback support
     * - Command parsing from AI response
     * - AI response storage with usage tracking
     *
     * @param Request $request The HTTP request containing message and context
     * @return JsonResponse JSON response with AI content, session info, and commands
     * @throws \Exception When AI processing fails or validation errors occur
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:4000',
            'context' => 'sometimes|array',
            'history' => 'sometimes|array',
            'session_id' => 'sometimes|string|exists:ai_chat_sessions,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid input', 'details' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            $teamId = $request->input('context.team.id', $user->current_team_id);
            
            // Get or create chat session
            $session = $this->getOrCreateSession($request->input('session_id'), $user->id, $teamId);
            
            // Store user message
            $userMessage = AiChatMessage::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'team_id' => $teamId,
                'type' => 'user',
                'content' => $request->input('message'),
                'metadata' => [
                    'context' => $request->input('context', []),
                    'timestamp' => now()->toISOString()
                ]
            ]);

            // Prepare context for AI
            $context = $this->prepareAiContext($request->all(), $user, $teamId);
            
            // Get AI response
            $aiResponse = $this->aiChatService->processMessage(
                $request->input('message'),
                $context,
                $request->input('history', [])
            );

            // Parse potential commands from AI response
            $commands = $this->parseCommands($aiResponse['content']);
            
            // Store AI response
            $aiMessage = AiChatMessage::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'team_id' => $teamId,
                'type' => 'ai',
                'content' => $aiResponse['content'],
                'metadata' => [
                    'model' => $aiResponse['model'] ?? 'unknown',
                    'commands' => $commands,
                    'timestamp' => now()->toISOString(),
                    'usage' => $aiResponse['usage'] ?? []
                ]
            ]);

            // Prepare response
            $response = [
                'content' => $aiResponse['content'],
                'session_id' => $session->id,
                'message_id' => $aiMessage->id,
                'metadata' => [
                    'commands' => $commands
                ]
            ];

            // If there are commands, add execution metadata
            if (!empty($commands)) {
                $response['metadata']['command'] = $commands[0]; // First command for immediate execution
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process message',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Execute a parsed command with tenant isolation and user validation
     *
     * This method handles command execution after parsing from AI responses.
     * It validates the command structure, ensures user permissions, and
     * executes the command through the command execution service.
     *
     * The method provides:
     * - Command structure validation
     * - User authentication and team context
     * - Secure command execution with tenant isolation
     * - Error handling and response formatting
     *
     * @param Request $request The HTTP request containing command details
     * @return JsonResponse JSON response with command execution results
     * @throws \Exception When command execution fails or validation errors occur
     */
    public function executeCommand(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'command' => 'required|string',
            'app' => 'sometimes|string',
            'action' => 'required|string',
            'parameters' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid command', 'details' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            $teamId = $user->current_team_id;
            
            $result = $this->commandExecutionService->execute(
                $request->input('command'),
                $request->input('app'),
                $request->input('action'),
                $request->input('parameters', []),
                $user,
                $teamId
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Command execution failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Command could not be executed'
            ], 500);
        }
    }

    /**
     * Upload file for AI analysis with tenant-specific storage
     *
     * This method handles file uploads for AI analysis including images, documents,
     * and other file types. It ensures secure storage with tenant isolation and
     * provides AI analysis capabilities for uploaded content.
     *
     * Features:
     * - File validation and size limits
     * - Tenant-specific storage paths
     * - AI analysis for images and documents
     * - Secure file handling with access controls
     * - Metadata tracking for uploaded files
     *
     * @param Request $request The HTTP request containing the file and type
     * @return JsonResponse JSON response with file info and analysis results
     * @throws \Exception When file upload fails or analysis errors occur
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|in:image,file,document'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid file', 'details' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            $teamId = $request->input('validated_team_id');
            $file = $request->file('file');
            $type = $request->input('type');
            
            // Store file with tenant isolation
            $storagePath = $this->getTenantStoragePath($user->id, $teamId);
            $path = $file->store($storagePath, 'private');
            
            // Prepare context for AI analysis
            $context = $request->input('ai_context', []);
            $context['session_id'] = $request->input('session_id');
            $context['message_id'] = $request->input('message_id');
            
            // Analyze file if it's an image or document
            $analysis = null;
            if ($type === 'image') {
                $analysis = $this->aiChatService->analyzeImage($file, $context);
            } elseif ($type === 'document') {
                $analysis = $this->aiChatService->analyzeDocument($file, $context);
            }

            return response()->json([
                'success' => true,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'type' => $type,
                'analysis' => $analysis,
                'tenant_info' => [
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                    'tenant_id' => tenant('id')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'File upload failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to upload file'
            ], 500);
        }
    }

    /**
     * Store user feedback for AI responses with tenant isolation
     *
     * This method collects and stores user feedback for AI responses including
     * likes, dislikes, and reports. It ensures proper tenant isolation and
     * provides metadata tracking for feedback analysis.
     *
     * The method handles:
     * - Feedback type validation (like, dislike, report)
     * - Tenant-isolated message access
     * - Feedback storage with metadata
     * - User agent and IP tracking for security
     *
     * @param Request $request The HTTP request containing feedback details
     * @return JsonResponse JSON response confirming feedback storage
     * @throws \Exception When feedback storage fails or validation errors occur
     */
    public function sendFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'messageId' => 'required|string|exists:ai_chat_messages,id',
            'type' => 'required|in:like,dislike,report'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid feedback', 'details' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            $teamId = $user->current_team_id;
            
            // Use tenant-isolated scope to find message
            $message = AiChatMessage::tenantIsolated($user->id, $teamId)
                ->where('id', $request->input('messageId'))
                ->firstOrFail();

            // Store feedback with tenant isolation
            AiChatFeedback::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'team_id' => $teamId,
                'type' => $request->input('type'),
                'comment' => $request->input('comment'),
                'metadata' => [
                    'timestamp' => now()->toISOString(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip()
                ]
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to save feedback',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Retrieve chat history with tenant isolation and pagination
     *
     * This method retrieves chat history for the authenticated user with proper
     * tenant isolation. It supports session-specific filtering and pagination
     * for efficient data retrieval.
     *
     * Features:
     * - Tenant-isolated message access
     * - Session-specific filtering
     * - Pagination support
     * - Related data loading (sessions, feedback)
     * - Security validation for session access
     *
     * @param Request $request The HTTP request containing filter parameters
     * @return JsonResponse JSON response with chat history
     * @throws \Exception When history retrieval fails or validation errors occur
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $user->current_team_id;
            $sessionId = $request->input('session_id');
            $limit = $request->input('limit', 50);

            // Use tenant-isolated scope for messages
            $query = AiChatMessage::tenantIsolated($user->id, $teamId)
                ->with(['session', 'feedback'])
                ->orderBy('created_at', 'desc')
                ->limit($limit);

            if ($sessionId) {
                // Ensure session belongs to user/team before filtering
                $session = AiChatSession::tenantIsolated($user->id, $teamId)
                    ->where('id', $sessionId)
                    ->firstOrFail();
                
                $query->where('session_id', $sessionId);
            }

            $messages = $query->get();

            return response()->json($messages);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get history',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get available commands for the current user and team context
     *
     * This method retrieves the list of available commands that the user
     * can execute based on their permissions and team context. It provides
     * command metadata for UI display and validation.
     *
     * @param Request $request The HTTP request
     * @return JsonResponse JSON response with available commands
     * @throws \Exception When command retrieval fails
     */
    public function getAvailableCommands(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $user->current_team_id;
            
            $commands = $this->commandExecutionService->getAvailableCommands($user, $teamId);

            return response()->json($commands);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get commands',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get or create a chat session with tenant isolation
     *
     * This method ensures that chat sessions are properly isolated by tenant
     * and user. It either retrieves an existing session or creates a new one
     * with proper tenant context.
     *
     * The method validates:
     * - Session ownership by user and team
     * - Tenant isolation for session access
     * - Session creation with proper metadata
     *
     * @param string|null $sessionId Optional existing session ID
     * @param int $userId The user ID for session ownership
     * @param int|null $teamId Optional team ID for team-specific sessions
     * @return AiChatSession The retrieved or created session
     * @throws \Exception When session creation fails or validation errors occur
     */
    private function getOrCreateSession(?string $sessionId, int $userId, ?int $teamId): AiChatSession
    {
        if ($sessionId) {
            // Use tenant-isolated scope to ensure session belongs to user and team
            $session = AiChatSession::tenantIsolated($userId, $teamId)
                ->where('id', $sessionId)
                ->first();
            
            if ($session) {
                return $session;
            }
        }

        return AiChatSession::create([
            'user_id' => $userId,
            'team_id' => $teamId,
            'title' => 'New Chat',
            'metadata' => [
                'started_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Prepare comprehensive context for AI processing
     *
     * This method aggregates all relevant context data including user information,
     * team details, available applications, and system capabilities. It provides
     * the AI with comprehensive context for better response generation.
     *
     * The context includes:
     * - User profile and preferences
     * - Team information and user role
     * - Available applications and capabilities
     * - System permissions and features
     * - Tenant-specific configuration
     *
     * @param array $requestData The raw request data
     * @param User $user The authenticated user
     * @param int|null $teamId Optional team ID for team-specific context
     * @return array Formatted context array for AI processing
     */
    private function prepareAiContext(array $requestData, User $user, ?int $teamId): array
    {
        $context = $requestData['context'] ?? [];
        
        // Add user context
        $context['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => $user->timezone ?? 'UTC'
        ];

        // Add team context
        if ($teamId) {
            $team = $user->teams()->find($teamId);
            if ($team) {
                $context['team'] = [
                    'id' => $team->id,
                    'name' => $team->name,
                    'role' => $team->pivot->role ?? 'member'
                ];
            }
        }

        // Add tenant-specific installed apps context
        $installedApps = App::where('installed', true)
            ->when($teamId, function ($query) use ($teamId) {
                // If team-specific apps exist, filter by team
                return $query->where(function ($q) use ($teamId) {
                    $q->whereNull('team_id')
                      ->orWhere('team_id', $teamId);
                });
            })
            ->get(['app_id', 'name', 'category']);
        $context['available_apps'] = $installedApps->toArray();

        // Add system capabilities
        $context['capabilities'] = [
            'can_create_blog_posts' => $installedApps->contains('app_id', 'blog'),
            'can_manage_products' => $installedApps->contains('app_id', 'products-manager'),
            'can_manage_orders' => $installedApps->contains('app_id', 'orders-manager'),
            'can_send_emails' => $installedApps->contains('app_id', 'email'),
            'can_manage_contacts' => $installedApps->contains('app_id', 'contacts'),
            'can_access_analytics' => true, // Always available
            'can_manage_settings' => true, // Always available
        ];

        return $context;
    }

    /**
     * Parse commands from AI response content
     *
     * This method extracts structured commands from AI response text using
     * multiple pattern matching strategies. It supports various command
     * formats and provides structured output for command execution.
     *
     * Supported patterns:
     * - [COMMAND:command_text]
     * - {command: "command_text"}
     * - execute: command_text
     *
     * @param string $content The AI response content to parse
     * @return array Array of parsed and structured commands
     */
    private function parseCommands(string $content): array
    {
        $commands = [];
        
        // Look for command patterns in AI response
        $patterns = [
            '/\[COMMAND:([^\]]+)\]/i',
            '/\{command:\s*"([^"]+)"\}/i',
            '/execute:\s*([^\n]+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $command) {
                    $commands[] = trim($command);
                }
            }
        }

        // Parse structured commands
        $structuredCommands = [];
        foreach ($commands as $command) {
            $parsed = $this->parseCommand($command);
            if ($parsed) {
                $structuredCommands[] = $parsed;
            }
        }

        return $structuredCommands;
    }

    /**
     * Parse individual command into structured format
     *
     * This method parses individual command strings into structured format
     * with action, app, and parameters. It supports parameter extraction
     * and command mapping to specific applications.
     *
     * Command format examples:
     * - "create_blog_post title='AI in Business' content='...' category='tech'"
     * - "update_product id=123 price=999"
     * - "send_email to='user@example.com' subject='Hello' body='...'"
     *
     * @param string $command The command string to parse
     * @return array|null Structured command array or null if invalid
     */
    private function parseCommand(string $command): ?array
    {
        // Examples:
        // "create_blog_post title='AI in Business' content='...' category='tech'"
        // "update_product id=123 price=999"
        // "send_email to='user@example.com' subject='Hello' body='...'"
        
        $parts = explode(' ', $command, 2);
        $action = $parts[0];
        $parameters = [];

        if (isset($parts[1])) {
            // Parse parameters
            preg_match_all("/(\w+)=(['\"])(.*?)\\2/", $parts[1], $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $parameters[$match[1]] = $match[3];
            }
        }

        // Map commands to apps and actions
        $commandMap = [
            'create_blog_post' => ['app' => 'blog', 'action' => 'create'],
            'update_blog_post' => ['app' => 'blog', 'action' => 'update'],
            'create_product' => ['app' => 'products-manager', 'action' => 'create'],
            'update_product' => ['app' => 'products-manager', 'action' => 'update'],
            'delete_product' => ['app' => 'products-manager', 'action' => 'delete'],
            'create_order' => ['app' => 'orders-manager', 'action' => 'create'],
            'update_order' => ['app' => 'orders-manager', 'action' => 'update'],
            'send_email' => ['app' => 'email', 'action' => 'send'],
            'create_contact' => ['app' => 'contacts', 'action' => 'create'],
            'update_contact' => ['app' => 'contacts', 'action' => 'update'],
            'launch_app' => ['app' => 'system', 'action' => 'launch'],
            'show_notification' => ['app' => 'system', 'action' => 'notify']
        ];

        if (isset($commandMap[$action])) {
            return [
                'command' => $action,
                'app' => $commandMap[$action]['app'],
                'action' => $commandMap[$action]['action'],
                'parameters' => $parameters
            ];
        }

        return null;
    }

    /**
     * Generate tenant-specific storage path for AI file uploads
     *
     * This method creates secure, tenant-isolated storage paths for AI-related
     * file uploads. It ensures proper separation between tenants and teams
     * for secure file storage.
     *
     * Path structure:
     * - Base: ai-chat/tenant-{tenant_id}
     * - Team-specific: ai-chat/tenant-{tenant_id}/team-{team_id}/user-{user_id}
     * - User-specific: ai-chat/tenant-{tenant_id}/user-{user_id}
     *
     * @param int $userId The user ID for user-specific storage
     * @param int|null $teamId Optional team ID for team-specific storage
     * @return string The tenant-specific storage path
     */
    private function getTenantStoragePath(int $userId, ?int $teamId): string
    {
        $tenantId = tenant('id');
        $basePath = "ai-chat/tenant-{$tenantId}";
        
        if ($teamId) {
            return "{$basePath}/team-{$teamId}/user-{$userId}";
        }
        
        return "{$basePath}/user-{$userId}";
    }
} 