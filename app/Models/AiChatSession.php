<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * AI Chat Session Model - Manages AI chat sessions and conversation history
 *
 * This model represents AI chat sessions within the multi-tenant system,
 * providing session management, conversation history, and tenant isolation.
 * Each session contains multiple messages and maintains context for AI
 * conversations.
 *
 * Key features:
 * - UUID-based primary keys for security
 * - Tenant isolation through team_id scoping
 * - User and team relationships
 * - Message collection management
 * - Session metadata storage
 * - Multi-tenant session isolation
 * - Conversation history tracking
 * - Session lifecycle management
 *
 * Session management:
 * - Session creation and initialization
 * - Message collection and ordering
 * - Context preservation across messages
 * - Session metadata and configuration
 * - Team-based access control
 * - User-specific session isolation
 *
 * The model provides:
 * - Secure session identification
 * - Comprehensive tenant isolation
 * - Message relationship management
 * - Session metadata storage
 * - Access control and security
 * - Conversation history tracking
 *
 * @package App\Models
 * @since 1.0.0
 */
class AiChatSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'title',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array'
    ];

    /** @var bool Disable auto-incrementing for UUID primary keys */
    public $incrementing = false;
    
    /** @var string Primary key type for UUID support */
    protected $keyType = 'string';

    /**
     * Boot the model and set up UUID generation
     *
     * This method ensures that new models automatically get UUID primary keys
     * when created, providing secure and unique identifiers for chat sessions.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user who owns this chat session
     *
     * This relationship provides access to the user who created
     * and owns the chat session.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for this chat session
     *
     * This relationship provides access to the team context for
     * the chat session, enabling team-based access control.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the messages in this chat session
     *
     * This relationship provides access to all messages within
     * the chat session, ordered by creation time.
     *
     * @return HasMany One-to-many relationship with AiChatMessage model
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'session_id');
    }

    /**
     * Scope for filtering sessions by team
     *
     * This scope filters chat sessions by team ID, providing
     * team-specific session access and tenant isolation.
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
     * Scope for filtering sessions by user
     *
     * This scope filters chat sessions by user ID, providing
     * user-specific session access and security validation.
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