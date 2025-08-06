<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * AI Settings Model - Manages AI configuration and privacy settings for users and teams
 *
 * This model represents AI configuration settings for users and teams within the
 * multi-tenant system. It manages privacy levels, model preferences, API keys,
 * and command execution permissions for AI chat functionality.
 *
 * Key features:
 * - User and team-specific AI configuration
 * - Privacy level management (public, private, agent)
 * - Custom model and API key configuration
 * - Default vs custom settings management
 * - Command execution permission control
 * - Tenant isolation and access control
 * - Encrypted API key storage
 * - Provider detection from model names
 *
 * Privacy levels:
 * - public: Only access public data, no command execution
 * - private: Access private team data, no command execution
 * - agent: Full access including command execution capabilities
 *
 * The model provides:
 * - Privacy level validation and control
 * - API key management with encryption
 * - Model and provider configuration
 * - Command execution permission checking
 * - Tenant-isolated settings management
 * - Default settings fallback
 *
 * @package App\Models
 * @since 1.0.0
 */
class AiSettings extends Model
{
    use HasFactory;

    /** @var string The table name for AI settings */
    protected $table = 'ai_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'use_defaults',
        'custom_model',
        'api_key',
        'privacy_level',
        'preferences'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'use_defaults' => 'boolean',
        'preferences' => 'array',
        'api_key' => 'encrypted'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key'
    ];

    /**
     * Get the user who owns these AI settings
     *
     * This relationship provides access to the user who owns
     * the AI settings, enabling user-specific configuration management.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for these AI settings
     *
     * This relationship provides access to the team context for
     * the AI settings, enabling team-specific configuration management.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for filtering settings by user
     *
     * This scope filters AI settings by user ID, providing
     * user-specific configuration access and management.
     *
     * @param Builder $query The query builder
     * @param int $userId The user ID to filter by
     * @return Builder The filtered query
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering settings by team
     *
     * This scope filters AI settings by team ID, providing
     * team-specific configuration access and management.
     *
     * @param Builder $query The query builder
     * @param int|null $teamId The team ID to filter by (null for global settings)
     * @return Builder The filtered query
     */
    public function scopeForTeam(Builder $query, ?int $teamId): Builder
    {
        if ($teamId) {
            return $query->where('team_id', $teamId);
        }
        return $query->whereNull('team_id');
    }

    /**
     * Scope for comprehensive tenant isolation
     *
     * This scope provides comprehensive tenant isolation by
     * filtering by both user and team context, ensuring proper
     * access control and data separation.
     *
     * @param Builder $query The query builder
     * @param int $userId The user ID for security validation
     * @param int|null $teamId Optional team ID for additional tenant isolation
     * @return Builder The filtered query
     */
    public function scopeTenantIsolated(Builder $query, int $userId, ?int $teamId = null): Builder
    {
        $query->where('user_id', $userId);
        if ($teamId) {
            $query->where('team_id', $teamId);
        } else {
            $query->whereNull('team_id');
        }
        return $query;
    }

    /**
     * Get the decrypted API key for AI provider access
     *
     * This method returns the decrypted API key for use with
     * AI providers, ensuring secure key management.
     *
     * @return string|null The decrypted API key or null if not set
     */
    public function getDecryptedApiKey(): ?string
    {
        return $this->api_key;
    }

    /**
     * Check if the settings use default configuration
     *
     * This method determines whether the AI settings use the
     * platform's default configuration or custom settings.
     *
     * @return bool True if using defaults, false if using custom settings
     */
    public function isDefaultMode(): bool
    {
        return $this->use_defaults;
    }

    /**
     * Check if the user can execute AI commands
     *
     * This method validates whether the user has permission to
     * execute AI-generated commands based on their privacy level.
     * Only 'agent' privacy level allows command execution.
     *
     * @return bool True if user can execute commands, false otherwise
     */
    public function canExecuteCommands(): bool
    {
        return $this->privacy_level === 'agent';
    }

    /**
     * Check if the user can access private data
     *
     * This method validates whether the user has permission to
     * access private team data based on their privacy level.
     * Both 'private' and 'agent' levels allow private data access.
     *
     * @return bool True if user can access private data, false otherwise
     */
    public function canAccessPrivateData(): bool
    {
        return in_array($this->privacy_level, ['private', 'agent']);
    }

    /**
     * Check if the user can only access public data
     *
     * This method validates whether the user is restricted to
     * public data only based on their privacy level.
     * Only 'public' level restricts to public data only.
     *
     * @return bool True if user can only access public data, false otherwise
     */
    public function canAccessOnlyPublicData(): bool
    {
        return $this->privacy_level === 'public';
    }

    /**
     * Get or create AI settings for a user and team
     *
     * This method ensures that every user and team combination has
     * AI settings by creating default settings if they don't exist.
     * It provides a convenient way to access AI configuration.
     *
     * @param int $userId The user ID to get settings for
     * @param int|null $teamId Optional team ID for team-specific settings
     * @return self The AI settings instance (created or existing)
     */
    public static function getOrCreateForUser(int $userId, ?int $teamId = null): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'team_id' => $teamId
            ],
            [
                'use_defaults' => true,
                'privacy_level' => 'public',
                'preferences' => []
            ]
        );
    }

    /**
     * Get effective AI settings considering defaults and custom configuration
     *
     * This method returns the effective AI configuration by combining
     * custom settings with platform defaults. It determines the actual
     * model, provider, and privacy settings to use for AI operations.
     *
     * The method handles:
     * - Default vs custom model selection
     * - Provider detection from model names
     * - Privacy level validation
     * - API key availability checking
     *
     * @return array Effective AI settings including model, provider, and privacy level
     */
    public function getEffectiveSettings(): array
    {
        if ($this->use_defaults) {
            return [
                'model' => config('ai-chat.default_model', 'gpt-4'),
                'provider' => config('ai-chat.default_provider', 'openai'),
                'privacy_level' => $this->privacy_level,
                'use_defaults' => true
            ];
        }

        return [
            'model' => $this->custom_model,
            'provider' => $this->getProviderFromModel($this->custom_model),
            'privacy_level' => $this->privacy_level,
            'use_defaults' => false,
            'has_api_key' => !empty($this->api_key)
        ];
    }

    /**
     * Determine AI provider from model name
     *
     * This method analyzes the model name to determine which AI
     * provider it belongs to, supporting common model naming patterns
     * from major AI providers.
     *
     * Supported providers:
     * - OpenAI: Models starting with 'gpt-'
     * - Anthropic: Models starting with 'claude-'
     * - Google: Models starting with 'gemini-'
     * - Meta: Models starting with 'llama-'
     *
     * @param string|null $model The model name to analyze
     * @return string The detected provider name (defaults to 'openai')
     */
    private function getProviderFromModel(?string $model): string
    {
        if (!$model) return 'openai';
        
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
} 