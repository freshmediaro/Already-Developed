<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Chat Feedback Model - Manages user feedback for AI chat messages
 *
 * This model represents user feedback for AI chat messages within the
 * multi-tenant system, providing feedback collection, rating systems,
 * and quality improvement tracking.
 *
 * Key features:
 * - Feedback collection and storage
 * - Message rating and evaluation
 * - User feedback tracking
 * - Team-based feedback isolation
 * - Feedback type classification
 * - Comment and metadata storage
 * - Quality improvement tracking
 * - Multi-tenant feedback isolation
 *
 * Feedback types:
 * - positive: Positive feedback and ratings
 * - negative: Negative feedback and issues
 * - neutral: Neutral feedback and comments
 * - bug_report: Bug reports and issues
 * - feature_request: Feature requests and suggestions
 * - quality_issue: Quality-related feedback
 *
 * The model provides:
 * - Comprehensive feedback management
 * - Message-specific feedback tracking
 * - User feedback collection
 * - Team-based feedback isolation
 * - Feedback type classification
 * - Quality improvement analytics
 * - Multi-tenant security
 *
 * @package App\Models
 * @since 1.0.0
 */
class AiChatFeedback extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'team_id',
        'type',
        'comment',
        'metadata'
    ];

    /**
     * Get the AI chat message that this feedback relates to
     *
     * This relationship provides access to the AI chat message
     * that received the feedback.
     *
     * @return BelongsTo Relationship to AiChatMessage model
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(AiChatMessage::class, 'message_id');
    }

    /**
     * Get the user who provided this feedback
     *
     * This relationship provides access to the user who
     * submitted the feedback.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for this feedback
     *
     * This relationship provides access to the team context for
     * the feedback, enabling team-based feedback management.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for filtering feedback by team
     *
     * This scope filters feedback by team ID, providing
     * team-specific feedback access and tenant isolation.
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
     * Scope for filtering feedback by user
     *
     * This scope filters feedback by user ID, providing
     * user-specific feedback access and security validation.
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
} 