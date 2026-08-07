<?php
/**
 * Plugin Name: Lyli Content Import
 * Description: Idempotent, source-manifested import command for the approved LyliShop handoff package.
 * Version: 1.0.0
 * Author: lylishop developer
 */

namespace LyliContentImport;

if (! defined('ABSPATH')) {
    exit;
}

const DATA_FILE = __DIR__ . '/data/lyli-content.json';
const SOURCE_META = '_lyli_source_path';
const PRODUCT_META = '_lyli_source_product';

if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/inc/command.php';
    \WP_CLI::add_command('lyli content', __NAMESPACE__ . '\ContentCommand');
}

