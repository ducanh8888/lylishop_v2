<?php
/**
 * Lyli Site Settings — public accessors.
 *
 * Safe getters for the theme/pattern layer. All output is escaped by the
 * caller; these accessors return raw stored values (already sanitized on save).
 */

namespace LyliSiteSettings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get a single Lyli setting value.
 *
 * @param string $key     Setting key without the `lyli_` prefix.
 * @param mixed  $default Fallback when the option is empty.
 * @return mixed
 */
function get_setting(string $key, $default = '')
{
    $key = sanitize_key($key);
    if (! in_array($key, OPTION_KEYS, true)) {
        return $default;
    }
    $value = get_option(OPTION_PREFIX . $key, $default);
    return ($value === '' || $value === false || $value === null) ? $default : $value;
}

/**
 * Footer short introduction (raw; escape on output).
 */
function get_footer_intro()
{
    return (string) get_setting('footer_intro');
}

/**
 * Contact email (already sanitized on save).
 */
function get_contact_email()
{
    return (string) get_setting('contact_email');
}

/**
 * Contact phone (already sanitized on save).
 */
function get_contact_phone()
{
    return (string) get_setting('contact_phone');
}

/**
 * Social/contact URLs.
 */
function get_facebook_url()
{
    return (string) get_setting('facebook_url');
}
function get_instagram_url()
{
    return (string) get_setting('instagram_url');
}
function get_tiktok_url()
{
    return (string) get_setting('tiktok_url');
}
function get_zalo_url()
{
    return (string) get_setting('zalo_url');
}

/**
 * Announcement bar.
 */
function get_announcement()
{
    return (string) get_setting('announcement');
}
function is_announcement_enabled(): bool
{
    return (bool) get_setting('announcement_enabled', false);
}

/**
 * Custom-order CTA defaults.
 */
function get_custom_order_label()
{
    return (string) get_setting('custom_order_label');
}
function get_custom_order_url()
{
    return (string) get_setting('custom_order_url');
}

/**
 * Footer copyright line.
 */
function get_footer_copyright()
{
    return (string) get_setting('footer_copyright');
}