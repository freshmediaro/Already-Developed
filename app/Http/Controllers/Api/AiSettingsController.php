<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * AI Settings Controller - Manages AI configuration and privacy settings
 *
 * This controller handles all AI-related settings including model configuration,
 * API key management, privacy levels, and provider-specific settings for users
 * and teams within the multi-tenant system.
 *
 * Key features:
 * - AI model configuration and selection
 * - API key management and validation
 * - Privacy level configuration
 * - Provider-specific settings
 * - Default vs custom configuration
 * - API key testing and validation
 * - Settings reset and restoration
 * - Team-specific AI configurations
 *
 * Supported operations:
 * - Retrieve current AI settings
 * - Update AI configuration
 * - Test API key validity
 * - Get available models and providers
 * - Configure privacy levels
 * - Reset settings to defaults
 * - Validate provider configurations
 *
 * Privacy levels:
 * - public: Only access public data, no command execution
 * - private: Access private team data, no command execution
 * - agent: Full access including command execution capabilities
 *
 * The controller provides:
 * - RESTful API endpoints for AI settings management
 * - Comprehensive validation and error handling
 * - API key security and encryption
 * - Provider-specific configuration
 * - Team-specific settings isolation
 * - Privacy and security controls
 *
 * @package App\Http\Controllers\Api
 * @since 1.0.0
 */
class AiSettingsController extends Controller
{
    /**
     * Get AI settings for the current user and team
     *
     * This method retrieves the current AI settings for the authenticated user,
     * including model configuration, privacy levels, and effective settings
     * that combine custom and default configurations.
     *
     * @param Request $request The HTTP request (may include team_id)
     * @return JsonResponse Response containing AI settings and configuration
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);

            $settings = AiSettings::getOrCreateForUser($user->id, $teamId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'use_defaults' => $settings->use_defaults,
                    'custom_model' => $settings->custom_model,
                    'privacy_level' => $settings->privacy_level,
                    'has_api_key' => !empty($settings->api_key),
                    'preferences' => $settings->preferences ?? [],
                    'effective_settings' => $settings->getEffectiveSettings()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get AI settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve AI settings'
            ], 500);
        }
    }

    /**
     * Update AI settings for the current user and team
     *
     * This method updates AI settings including model selection, API keys,
     * privacy levels, and custom preferences. It validates all inputs and
     * ensures proper security for sensitive data like API keys.
     *
     * Request parameters:
     * - use_defaults: Whether to use platform defaults
     * - custom_model: Custom AI model name
     * - api_key: Encrypted API key for AI provider
     * - privacy_level: Privacy level (public, private, agent)
     * - preferences: Additional AI preferences
     *
     * @param Request $request The HTTP request with AI settings data
     * @return JsonResponse Response indicating success or failure
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);

            $validator = Validator::make($request->all(), [
                'use_defaults' => 'boolean',
                'custom_model' => 'nullable|string|max:100',
                'api_key' => 'nullable|string|max:500',
                'privacy_level' => 'required|in:public,private,agent',
                'preferences' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $settings = AiSettings::getOrCreateForUser($user->id, $teamId);
            
            // Update only provided fields
            if ($request->has('use_defaults')) {
                $settings->use_defaults = $request->boolean('use_defaults');
            }
            
            if ($request->has('custom_model')) {
                $settings->custom_model = $request->input('custom_model');
            }
            
            if ($request->has('api_key') && !empty($request->input('api_key'))) {
                $settings->api_key = $request->input('api_key');
            }
            
            if ($request->has('privacy_level')) {
                $settings->privacy_level = $request->input('privacy_level');
            }
            
            if ($request->has('preferences')) {
                $settings->preferences = $request->input('preferences', []);
            }

            $settings->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'use_defaults' => $settings->use_defaults,
                    'custom_model' => $settings->custom_model,
                    'privacy_level' => $settings->privacy_level,
                    'has_api_key' => !empty($settings->api_key),
                    'preferences' => $settings->preferences ?? [],
                    'effective_settings' => $settings->getEffectiveSettings()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update AI settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $teamId ?? null,
                'request_data' => $request->except(['api_key'])
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update AI settings'
            ], 500);
        }
    }

    /**
     * Test API key for a specific model
     */
    public function testApiKey(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string',
                'api_key' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $model = $request->input('model');
            $apiKey = $request->input('api_key');
            
            // Get provider from model
            $provider = $this->getProviderFromModel($model);
            
            // Test the API key based on provider
            $testResult = $this->performApiKeyTest($provider, $model, $apiKey);

            return response()->json([
                'success' => $testResult['success'],
                'message' => $testResult['message'],
                'provider' => $provider,
                'model' => $model
            ]);

        } catch (Exception $e) {
            Log::error('API key test failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'model' => $request->input('model')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to test API key'
            ], 500);
        }
    }

    /**
     * Get available AI models
     */
    public function getModels(): JsonResponse
    {
        try {
            $models = [
                'openai' => [
                    ['id' => 'gpt-4', 'name' => 'GPT-4', 'description' => 'Most capable model, best for complex tasks'],
                    ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo', 'description' => 'Faster and more efficient'],
                    ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'description' => 'Fast and cost-effective']
                ],
                'anthropic' => [
                    ['id' => 'claude-3-opus', 'name' => 'Claude 3 Opus', 'description' => 'Advanced reasoning capabilities'],
                    ['id' => 'claude-3-sonnet', 'name' => 'Claude 3 Sonnet', 'description' => 'Balanced performance and speed'],
                    ['id' => 'claude-3-haiku', 'name' => 'Claude 3 Haiku', 'description' => 'Fast and lightweight']
                ],
                'google' => [
                    ['id' => 'gemini-pro', 'name' => 'Gemini Pro', 'description' => 'Multimodal AI capabilities']
                ],
                'meta' => [
                    ['id' => 'llama-3-70b', 'name' => 'Llama 3 70B', 'description' => 'Open source, high performance']
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $models
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get AI models', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve AI models'
            ], 500);
        }
    }

    /**
     * Get privacy levels information
     */
    public function getPrivacyLevels(): JsonResponse
    {
        try {
            $levels = [
                [
                    'id' => 'public',
                    'name' => 'Public',
                    'description' => 'Access only website data for answers',
                    'features' => ['Website content access', 'Public data only', 'No file access', 'No app data access']
                ],
                [
                    'id' => 'private',
                    'name' => 'Private',
                    'description' => 'Access files and all app info for detailed answers',
                    'features' => ['Full file access', 'App data access', 'Reports access', 'Analytics data', 'No action execution']
                ],
                [
                    'id' => 'agent',
                    'name' => 'Agent',
                    'description' => 'Take actions on your behalf when requested',
                    'features' => ['Full system access', 'Action execution', 'Command running', 'File modifications', 'App control']
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $levels
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get privacy levels', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve privacy levels'
            ], 500);
        }
    }

    /**
     * Reset AI settings to defaults
     */
    public function reset(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);

            $settings = AiSettings::getOrCreateForUser($user->id, $teamId);
            $settings->update([
                'use_defaults' => true,
                'custom_model' => null,
                'api_key' => null,
                'privacy_level' => 'public',
                'preferences' => []
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI settings reset to defaults',
                'data' => [
                    'use_defaults' => true,
                    'custom_model' => null,
                    'privacy_level' => 'public',
                    'has_api_key' => false,
                    'preferences' => [],
                    'effective_settings' => $settings->getEffectiveSettings()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to reset AI settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $teamId ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to reset AI settings'
            ], 500);
        }
    }

    /**
     * Private helper methods
     */
    private function getTeamIdFromRequest(Request $request): ?int
    {
        $teamId = $request->input('team_id') ?? $request->header('X-Team-ID');
        return $teamId ? (int) $teamId : null;
    }

    private function getProviderFromModel(string $model): string
    {
        $providers = [
            'gpt-' => 'openai',
            'claude-' => 'anthropic',
            'gemini-' => 'google',
            'llama-' => 'meta'
        ];

        foreach ($providers as $prefix => $provider) {
            if (str_starts_with($model, $prefix)) {
                return $provider;
            }
        }

        return 'openai';
    }

    private function performApiKeyTest(string $provider, string $model, string $apiKey): array
    {
        // Simple validation tests based on provider
        switch ($provider) {
            case 'openai':
                if (!str_starts_with($apiKey, 'sk-')) {
                    return ['success' => false, 'message' => 'OpenAI API keys should start with "sk-"'];
                }
                break;
            
            case 'anthropic':
                if (!str_starts_with($apiKey, 'sk-ant-')) {
                    return ['success' => false, 'message' => 'Anthropic API keys should start with "sk-ant-"'];
                }
                break;
            
            case 'google':
                if (strlen($apiKey) < 30) {
                    return ['success' => false, 'message' => 'Google API keys are typically longer'];
                }
                break;
        }

        // For demonstration purposes, we'll just validate the format
        // In production, you'd make actual API calls to test the key
        if (strlen($apiKey) < 10) {
            return ['success' => false, 'message' => 'API key appears to be too short'];
        }

        return ['success' => true, 'message' => 'API key format appears valid'];
    }
} 