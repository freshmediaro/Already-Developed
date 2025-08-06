<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * App Store Category Model - Manages app store categories and classification
 *
 * This model represents categories for organizing applications in the app store
 * marketplace within the multi-tenant system, providing hierarchical category
 * management and app classification.
 *
 * Key features:
 * - Category hierarchy management
 * - App classification and organization
 * - Category metadata and styling
 * - Sort order and display management
 * - Active/inactive category status
 * - Icon and color customization
 * - Slug generation and SEO optimization
 * - Parent-child relationships
 * - App count tracking
 * - Category navigation
 *
 * Category hierarchy:
 * - Root categories: Top-level categories without parents
 * - Subcategories: Categories with parent relationships
 * - Nested categories: Multi-level category structures
 * - Category trees: Hierarchical category organization
 *
 * Category features:
 * - Name and description management
 * - Icon and color customization
 * - Sort order for display positioning
 * - Active status for visibility control
 * - Slug generation for URL-friendly names
 * - Parent-child relationships
 *
 * The model provides:
 * - Comprehensive category management
 * - Hierarchical organization
 * - App classification
 * - SEO optimization
 * - Visual customization
 * - Navigation structure
 *
 * @package App\Models
 * @since 1.0.0
 */
class AppStoreCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'parent_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model and set up automatic behaviors
     *
     * This method initializes the model with automatic slug generation
     * and sort order assignment when creating new categories.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (!$category->slug) {
                $category->slug = Str::slug($category->name);
            }
            if (!$category->sort_order) {
                $category->sort_order = static::max('sort_order') + 1;
            }
        });
    }

    /**
     * Get the apps that belong to this category
     *
     * This relationship provides access to all apps that are
     * classified under this category.
     *
     * @return BelongsToMany Many-to-many relationship with AppStoreApp model
     */
    public function apps(): BelongsToMany
    {
        return $this->belongsToMany(AppStoreApp::class, 'app_store_app_categories');
    }

    /**
     * Get the parent category of this category
     *
     * This relationship provides access to the parent category
     * in the hierarchical category structure.
     *
     * @return BelongsTo Relationship to parent AppStoreCategory model
     */
    public function parent()
    {
        return $this->belongsTo(AppStoreCategory::class, 'parent_id');
    }

    /**
     * Get the child categories of this category
     *
     * This relationship provides access to all child categories
     * that have this category as their parent.
     *
     * @return HasMany One-to-many relationship with child AppStoreCategory models
     */
    public function children()
    {
        return $this->hasMany(AppStoreCategory::class, 'parent_id');
    }

    /**
     * Scope for active categories
     *
     * This scope filters categories to only include those
     * that are currently active and visible.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for root categories
     *
     * This scope filters categories to only include root categories
     * (categories without a parent).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for ordering categories by sort order
     *
     * This scope orders categories by their sort order,
     * providing consistent display ordering.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The ordered query
     */
    public function scopeOrderedBySortOrder($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the icon URL for this category
     *
     * This method returns the full URL for the category's icon,
     * handling both local storage and external URLs.
     *
     * @return string The icon URL
     */
    public function getIconUrl(): string
    {
        if (!$this->icon) {
            return asset('img/categories/default-category.png');
        }

        if (Str::startsWith($this->icon, ['http://', 'https://'])) {
            return $this->icon;
        }

        return asset("storage/app-store/categories/{$this->icon}");
    }

    /**
     * Get the count of approved and published apps in this category
     *
     * This method returns the number of apps that are both approved
     * and published within this category.
     *
     * @return int The number of approved and published apps
     */
    public function getAppsCount(): int
    {
        return $this->apps()->approved()->published()->count();
    }
} 