<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AI Token Package Model - Manages AI token packages and pricing
 *
 * This model represents AI token packages available for purchase within the
 * multi-tenant system, including pricing, features, discounts, and package
 * lifecycle management.
 *
 * Key features:
 * - AI token package management
 * - Pricing and discount calculations
 * - Package feature management
 * - Subscription and recurring packages
 * - Package expiration and validity
 * - Featured and recommended packages
 * - Package type classification
 * - Metadata and configuration
 * - Package lifecycle management
 * - Value and savings calculations
 *
 * Package types:
 * - starter: Entry-level packages for new users
 * - standard: Regular usage packages
 * - premium: High-volume usage packages
 * - enterprise: Team and organization packages
 * - custom: Custom-configured packages
 *
 * Package features:
 * - Priority AI processing
 * - Advanced model access
 * - Custom model training
 * - API access and integration
 * - Team collaboration features
 * - Analytics and reporting
 * - Support and assistance
 *
 * The model provides:
 * - Comprehensive package management
 * - Dynamic pricing calculations
 * - Feature validation and access
 * - Package recommendations
 * - Value optimization
 * - Expiration management
 * - Subscription handling
 *
 * @package App\Models\Wallet
 * @since 1.0.0
 */
class AiTokenPackage extends Model
{
    use HasFactory;

    /** @var string The table name for AI token packages */
    protected $table = 'ai_token_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'token_amount',
        'price',
        'discount_percentage',
        'is_active',
        'is_featured',
        'sort_order',
        'features',
        'validity_days',
        'is_recurring',
        'package_type',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_amount' => 'integer',
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
        'validity_days' => 'integer',
        'is_recurring' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Scope for active AI token packages
     *
     * This scope filters AI token packages to only include
     * those that are currently active and available for purchase.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured AI token packages
     *
     * This scope filters AI token packages to only include
     * those that are marked as featured for promotional display.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for filtering packages by type
     *
     * This scope filters AI token packages by package type,
     * enabling type-specific queries and recommendations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $type The package type to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('package_type', $type);
    }

    /**
     * Scope for ordered AI token packages
     *
     * This scope orders AI token packages by sort order and price,
     * providing consistent display ordering.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The ordered query
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Calculate the price per token for this package
     *
     * This method calculates the cost per AI token for this package,
     * providing a metric for value comparison between packages.
     *
     * @return float The price per token (0 if no tokens)
     */
    public function getPricePerToken(): float
    {
        return $this->token_amount > 0 ? $this->price / $this->token_amount : 0;
    }

    /**
     * Get the discounted price for this package
     *
     * This method calculates the final price after applying any
     * discount percentage to the original price.
     *
     * @return float The discounted price (same as original if no discount)
     */
    public function getDiscountedPrice(): float
    {
        if ($this->discount_percentage > 0) {
            return $this->price * (1 - ($this->discount_percentage / 100));
        }
        return $this->price;
    }

    /**
     * Calculate the savings amount for this package
     *
     * This method calculates the total savings amount when a discount
     * is applied to the package price.
     *
     * @return float The savings amount (0 if no discount)
     */
    public function getSavings(): float
    {
        return $this->price - $this->getDiscountedPrice();
    }

    /**
     * Check if this package has an expiration period
     *
     * This method determines whether the package has a validity period
     * after which the tokens would expire.
     *
     * @return bool True if the package has an expiration period, false otherwise
     */
    public function isExpiring(): bool
    {
        return $this->validity_days !== null;
    }

    /**
     * Get the expiry date for this package
     *
     * This method calculates the expiry date based on the purchase date
     * and the package's validity period.
     *
     * @param \DateTime|null $purchaseDate The purchase date (defaults to current date)
     * @return \DateTime|null The expiry date or null if no expiration
     */
    public function getExpiryDate(\DateTime $purchaseDate = null): ?\DateTime
    {
        if (!$this->isExpiring()) {
            return null;
        }

        $purchaseDate = $purchaseDate ?? new \DateTime();
        return $purchaseDate->add(new \DateInterval("P{$this->validity_days}D"));
    }

    /**
     * Check if this package has a specific feature
     *
     * This method validates whether the package includes a specific
     * feature in its feature list.
     *
     * @param string $feature The feature to check for
     * @return bool True if the package has the feature, false otherwise
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return in_array($feature, $features);
    }

    /**
     * Get the list of features for this package
     *
     * This method returns an array of features associated with the package.
     *
     * @return array<int, string> The list of features
     */
    public function getFeaturesList(): array
    {
        return $this->features ?? [];
    }

    /**
     * Check if this package is a subscription or recurring package
     *
     * This method determines whether the package is a subscription
     * or a recurring package based on its type.
     *
     * @return bool True if it's a subscription or recurring package, false otherwise
     */
    public function isSubscription(): bool
    {
        return $this->package_type === 'subscription' || $this->is_recurring;
    }

    /**
     * Check if this package is a bulk package
     *
     * This method determines whether the package is a bulk package.
     *
     * @return bool True if it's a bulk package, false otherwise
     */
    public function isBulkPackage(): bool
    {
        return $this->package_type === 'bulk';
    }

    /**
     * Check if this package is a one-time package
     *
     * This method determines whether the package is a one-time package.
     *
     * @return bool True if it's a one-time package, false otherwise
     */
    public function isOneTime(): bool
    {
        return $this->package_type === 'one_time';
    }

    // Static methods for common queries
    /**
     * Get all active AI token packages, ordered by sort order and price
     *
     * This method retrieves all AI token packages that are currently active,
     * ordered by their sort order and price.
     *
     * @return \Illuminate\Database\Eloquent\Collection The collection of active packages
     */
    public static function getActivePackages(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->ordered()->get();
    }

    /**
     * Get all featured AI token packages, ordered by sort order and price
     *
     * This method retrieves all AI token packages that are currently active
     * and featured, ordered by their sort order and price.
     *
     * @return \Illuminate\Database\Eloquent\Collection The collection of featured packages
     */
    public static function getFeaturedPackages(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->featured()->ordered()->get();
    }

    /**
     * Get AI token packages by a specific type, ordered by sort order and price
     *
     * This method retrieves AI token packages of a given type that are currently active,
     * ordered by their sort order and price.
     *
     * @param string $type The package type to filter by
     * @return \Illuminate\Database\Eloquent\Collection The collection of packages by type
     */
    public static function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->byType($type)->ordered()->get();
    }

    /**
     * Get the recommended AI token package
     *
     * This method retrieves the AI token package that is currently active
     * and featured, ordered by sort order and price.
     *
     * @return self|null The recommended package or null if none found
     */
    public static function getRecommendedPackage(): ?self
    {
        return static::active()->featured()->ordered()->first();
    }

    /**
     * Get the cheapest AI token package
     *
     * This method retrieves the AI token package with the lowest price
     * from all currently active packages.
     *
     * @return self|null The cheapest package or null if none found
     */
    public static function getCheapestPackage(): ?self
    {
        return static::active()->orderBy('price')->first();
    }

    /**
     * Get the best value AI token package
     *
     * This method finds the AI token package with the lowest price per token
     * from all currently active packages.
     *
     * @return self|null The best value package or null if none found
     */
    public static function getBestValuePackage(): ?self
    {
        // Find package with lowest price per token
        return static::active()
            ->get()
            ->sortBy(function ($package) {
                return $package->getPricePerToken();
            })
            ->first();
    }

    // Format methods for display
    /**
     * Get the formatted price for this package
     *
     * This method formats the package price as a string with a dollar sign.
     *
     * @return string The formatted price string
     */
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the formatted token amount for this package
     *
     * This method formats the token amount as a string with 'tokens' suffix.
     *
     * @return string The formatted token amount string
     */
    public function getFormattedTokenAmount(): string
    {
        return number_format($this->token_amount) . ' tokens';
    }

    /**
     * Get the formatted price per token for this package
     *
     * This method formats the price per token as a string with a dollar sign
     * and four decimal places.
     *
     * @return string The formatted price per token string
     */
    public function getFormattedPricePerToken(): string
    {
        return '$' . number_format($this->getPricePerToken(), 4) . ' per token';
    }

    /**
     * Get the badge text for this package
     *
     * This method determines the text to display on the package's badge
     * based on its featured status, discount percentage, and bulk status.
     *
     * @return string|null The badge text or null if no badge is applicable
     */
    public function getBadgeText(): ?string
    {
        if ($this->is_featured) {
            return 'Most Popular';
        }
        
        if ($this->discount_percentage > 0) {
            return number_format($this->discount_percentage, 0) . '% Off';
        }
        
        if ($this->isBulkPackage()) {
            return 'Bulk Discount';
        }
        
        return null;
    }
} 