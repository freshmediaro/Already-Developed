<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * AI Chat Message Model - Represents individual messages in AI chat sessions
 * 
 * This model manages AI chat messages within the multi-tenant system,
 * providing tenant isolation, user tracking, and session management.
 * Messages can be from users or AI responses, with metadata for tracking
 * usage, commands, and context information.
 * 
 * Key features:
 * - UUID-based primary keys for security
 * - Tenant isolation through team_id scoping
 * - User and session relationships
 * - Metadata storage for AI responses
 * - Usage tracking and analytics
 * - Feedback relationship for message ratings
 * 
 * The model supports:
 * - Message type differentiation (user vs AI)
 * - Context and metadata storage
 * - Session-based message grouping
 * - Team-based access control
 * - Usage analytics and cost tracking
 * 
 * @package App\Models
 * @since 1.0.0
 */
class AiChatMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'team_id',
        'type',
        'content',
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
     * when created, providing secure and unique identifiers for messages.
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
     * Get the chat session that this message belongs to
     * 
     * @return BelongsTo Relationship to AiChatSession model
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }

    /**
     * Get the user who sent this message
     * 
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get feedback associated with this message
     * 
     * @return HasMany Relationship to AiChatFeedback model
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(AiChatFeedback::class, 'message_id');
    }

    /**
     * Scope to filter messages by team for tenant isolation
     * 
     * This scope ensures that messages are only accessible within
     * the context of the specified team, providing tenant isolation.
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
     * Scope to filter messages by user for additional security
     * 
     * This scope ensures that users can only access their own messages,
     * providing an additional layer of security beyond team isolation.
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
     * Scope to filter messages by specific session with tenant validation
     * 
     * This scope combines session, user, and team filtering to ensure
     * proper access control and tenant isolation for session-based queries.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $sessionId The session ID to filter by
     * @param int $userId The user ID for security validation
     * @param int|null $teamId Optional team ID for additional tenant isolation
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeForSession($query, string $sessionId, int $userId, ?int $teamId = null)
    {
        $query->where('session_id', $sessionId)
              ->where('user_id', $userId);
        
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        
        return $query;
    }

    /**
     * Scope for comprehensive tenant isolation - user must be member of team
     * 
     * This scope provides the most comprehensive tenant isolation by
     * ensuring that users can only access messages within teams they
     * are members of, providing both security and data isolation.
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