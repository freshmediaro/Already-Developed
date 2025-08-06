<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App Store Review Model - Manages app store reviews and ratings
 *
 * This model represents reviews and ratings for applications in the app store
 * marketplace within the multi-tenant system, providing review management,
 * rating systems, and quality control.
 *
 * Key features:
 * - Review and rating management
 * - Quality control and approval workflow
 * - Verified purchase tracking
 * - Helpful vote system
 * - Version-specific reviews
 * - Multi-tenant review isolation
 * - Review moderation and approval
 * - Rating calculation and display
 * - Review metadata and tracking
 * - User feedback collection
 *
 * Review features:
 * - Star rating system (1-5 stars)
 * - Review title and content
 * - Verified purchase status
 * - Approval workflow
 * - Helpful vote tracking
 * - Version-specific reviews
 * - User ownership validation
 * - Team-based review management
 *
 * The model provides:
 * - Comprehensive review management
 * - Rating system implementation
 * - Quality control workflows
 * - User feedback collection
 * - Multi-tenant isolation
 * - Review moderation tools
 *
 * @package App\Models
 * @since 1.0.0
 */
class AppStoreReview extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'app_store_app_id',
        'user_id',
        'team_id',
        'rating',
        'title',
        'review',
        'is_verified_purchase',
        'is_approved',
        'approved_by',
        'approved_at',
        'helpful_count',
        'version_reviewed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'helpful_count' => 'integer',
    ];

    /**
     * Get the app that this review is for
     *
     * This relationship provides access to the app store app
     * that this review relates to.
     *
     * @return BelongsTo Relationship to AppStoreApp model
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(AppStoreApp::class, 'app_store_app_id');
    }

    /**
     * Get the user who wrote this review
     *
     * This relationship provides access to the user who
     * submitted the review.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for this review
     *
     * This relationship provides access to the team context for
     * the review, enabling team-based review management.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the admin user who approved this review
     *
     * This relationship provides access to the admin user who
     * approved the review.
     *
     * @return BelongsTo Relationship to User model (admin approver)
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for approved reviews
     *
     * This scope filters reviews to only include those
     * that have been approved and are publicly visible.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for filtering reviews by rating
     *
     * This scope filters reviews by specific star rating,
     * enabling rating-based review queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int $rating The star rating to filter by (1-5)
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for verified purchase reviews
     *
     * This scope filters reviews to only include those
     * from users who have verified purchases of the app.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeVerifiedPurchases($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Get the star display for this review
     *
     * This method returns a string representation of the star rating
     * using filled stars (★) and empty stars (☆).
     *
     * @return string The star display string (e.g., "★★★★☆")
     */
    public function getStarDisplay(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Check if a user can edit this review
     *
     * This method validates whether a user has permission to
     * edit this review based on ownership.
     *
     * @param User $user The user to check permissions for
     * @return bool True if the user can edit the review, false otherwise
     */
    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Increment the helpful count for this review
     *
     * This method increases the helpful vote count by 1,
     * typically called when a user marks the review as helpful.
     */
    public function incrementHelpfulCount(): void
    {
        $this->increment('helpful_count');
    }
} 