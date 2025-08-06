<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Desktop Configuration Model - Manages user desktop preferences and UI settings
 *
 * This model represents desktop configuration settings for users within the
 * multi-tenant system. It manages theme preferences, UI customization,
 * notification settings, and desktop layout preferences for the desktop
 * application interface.
 *
 * Key features:
 * - Theme and accent color customization
 * - Wallpaper and desktop icon management
 * - Notification and sound preferences
 * - Window animation and transparency effects
 * - Desktop layout and icon arrangement
 * - Language and localization settings
 * - Analytics and crash reporting preferences
 * - User and team-specific configurations
 *
 * Supported themes:
 * - auto: Automatic theme based on system preference
 * - light: Light theme for bright environments
 * - dark: Dark theme for low-light environments
 *
 * The model provides:
 * - User preference management
 * - Desktop UI customization
 * - Notification and sound control
 * - Accessibility and performance settings
 * - Multi-language support
 * - Privacy and analytics controls
 *
 * @package App\Models
 * @since 1.0.0
 */
class DesktopConfiguration extends Model
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
        'theme',
        'accent_color',
        'wallpaper',
        'desktop_icons_enabled',
        'desktop_auto_arrange',
        'desktop_icon_size',
        'notifications_enabled',
        'sound_enabled',
        'language',
        'analytics_enabled',
        'crash_reports_enabled',
        'window_animations',
        'transparency_effects',
        'desktop_layout',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'desktop_icons_enabled' => 'boolean',
        'desktop_auto_arrange' => 'boolean',
        'notifications_enabled' => 'boolean',
        'sound_enabled' => 'boolean',
        'analytics_enabled' => 'boolean',
        'crash_reports_enabled' => 'boolean',
        'window_animations' => 'boolean',
        'transparency_effects' => 'boolean',
        'desktop_layout' => 'array',
    ];

    /**
     * Get the user who owns this desktop configuration
     *
     * This relationship provides access to the user who owns
     * the desktop configuration, enabling user-specific preference management.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for this desktop configuration
     *
     * This relationship provides access to the team context for
     * the desktop configuration, enabling team-specific preference management.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get default desktop configuration values
     *
     * This method provides the default configuration values for new
     * desktop configurations, ensuring consistent initial settings
     * across all users and teams.
     *
     * Default settings include:
     * - Auto theme detection
     * - Blue accent color
     * - Default wallpaper
     * - Enabled desktop icons and notifications
     * - Medium icon size
     * - Enabled window animations and transparency
     * - English language
     * - Disabled analytics and crash reports
     *
     * @return array Associative array of default configuration values
     */
    public static function getDefaults(): array
    {
        return [
            'theme' => 'auto',
            'accent_color' => 'blue',
            'wallpaper' => 'default',
            'desktop_icons_enabled' => true,
            'desktop_auto_arrange' => false,
            'desktop_icon_size' => 'medium',
            'notifications_enabled' => true,
            'sound_enabled' => true,
            'language' => 'en',
            'analytics_enabled' => false,
            'crash_reports_enabled' => false,
            'window_animations' => true,
            'transparency_effects' => true,
            'desktop_layout' => [],
        ];
    }
} 