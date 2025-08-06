<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Chat Usage Model - Tracks AI usage, costs, and analytics
 *
 * This model represents AI chat usage tracking within the multi-tenant system,
 * providing comprehensive usage analytics, cost tracking, and token consumption
 * monitoring for AI operations.
 *
 * Key features:
 * - Usage tracking and analytics
 * - Cost calculation and monitoring
 * - Token consumption tracking
 * - Provider-specific usage analysis
 * - Operation type classification
 * - Multi-tenant usage isolation
 * - Usage metadata storage
 * - Cost optimization tracking
 * - Performance monitoring
 * - Billing and reporting
 *
 * Operation types:
 * - chat_completion: Standard chat completion
 * - function_call: Function calling operations
 * - code_generation: Code generation tasks
 * - analysis: Data analysis operations
 * - translation: Language translation
 * - summarization: Content summarization
 * - question_answering: Q&A operations
 *
 * Providers:
 * - openai: OpenAI API usage
 * - anthropic: Anthropic Claude usage
 * - google: Google Gemini usage
 * - azure: Azure OpenAI usage
 * - custom: Custom AI provider usage
 *
 * The model provides:
 * - Comprehensive usage tracking
 * - Cost analysis and optimization
 * - Token consumption monitoring
 * - Provider-specific analytics
 * - Multi-tenant usage isolation
 * - Performance metrics
 * - Billing and reporting data
 *
 * @package App\Models
 * @since 1.0.0
 */
class AiChatUsage extends Model
{
    use HasFactory;

    /** @var string The table name for AI chat usage tracking */
    protected $table = 'ai_chat_usage';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'message_id',
        'user_id',
        'team_id',
        'provider',
        'model',
        'tokens_used',
        'cost',
        'operation_type',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'cost' => 'decimal:6',
        'tokens_used' => 'integer'
    ];

    /**
     * Get the chat session associated with this usage record
     *
     * This relationship provides access to the chat session
     * that generated this usage record.
     *
     * @return BelongsTo Relationship to AiChatSession model
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }

    /**
     * Get the chat message associated with this usage record
     *
     * This relationship provides access to the specific chat message
     * that generated this usage record.
     *
     * @return BelongsTo Relationship to AiChatMessage model
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(AiChatMessage::class, 'message_id');
    }

    /**
     * Get the user associated with this usage record
     *
     * This relationship provides access to the user who
     * generated this usage record.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for this usage record
     *
     * This relationship provides access to the team context for
     * the usage record, enabling team-based usage analytics.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for filtering usage by team
     *
     * This scope filters usage records by team ID, providing
     * team-specific usage analytics and tenant isolation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int $teamId The team ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope for filtering usage by user
     *
     * This scope filters usage records by user ID, providing
     * user-specific usage analytics and security validation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int $userId The user ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for comprehensive tenant isolation
     *
     * This scope provides comprehensive tenant isolation by
     * filtering by both user and team context, ensuring proper
     * access control and data separation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int $userId The user ID for security validation
     * @param int|null $teamId Optional team ID for additional tenant isolation
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeTenantIsolated($query, int $userId, ?int $teamId = null)
    {
        $query->where('user_id', $userId);
        
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        
        return $query;
    }

    /**
     * Scope for usage analytics by date range
     *
     * This scope filters usage records by date range,
     * enabling time-based usage analytics and reporting.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param mixed $startDate The start date for the range
     * @param mixed $endDate The end date for the range
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering usage by provider
     *
     * This scope filters usage records by AI provider,
     * enabling provider-specific usage analytics.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $provider The AI provider to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope for filtering usage by operation type
     *
     * This scope filters usage records by operation type,
     * enabling operation-specific usage analytics.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $operationType The operation type to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeForOperationType($query, string $operationType)
    {
        return $query->where('operation_type', $operationType);
    }
} 