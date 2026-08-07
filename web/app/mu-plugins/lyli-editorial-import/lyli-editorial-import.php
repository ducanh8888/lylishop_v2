<?php
/**
 * Plugin Name: Lyli Editorial Import
 * Description: Idempotent import of editable pages, blog posts, media, menus, and settings from the approved LyliShop handoff.
 * Version: 1.0.0
 * Author: lylishop developer
 */

namespace LyliEditorialImport;

if (! defined('ABSPATH')) {
    exit;
}

const DATA_FILE = __DIR__ . '/data/editorial-content.json';
const SOURCE_META = '_lyli_source_path';

if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/inc/command.php';
    \WP_CLI::add_command('lyli editorial', __NAMESPACE__ . '\EditorialCommand');
}
