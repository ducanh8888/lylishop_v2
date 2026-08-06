<?php
/**
 * Plugin Name: Lyli Site Bootstrap
 * Description: Idempotent one-time site structure bootstrap via WP-CLI (`wp lyli bootstrap init`). Creates initial pages/categories/menu only when absent; never overwrites owner-edited content; supports --dry-run and requires --apply.
 * Version: 1.0.0
 * Author: lylishop developer
 * Text Domain: lyli-site-bootstrap
 */

namespace LyliBootstrap;

if (! defined('ABSPATH')) {
    exit;
}

const BOOTSTRAP_OPTION    = 'lyli_bootstrap_seeded';
const BOOTSTRAP_VERSION   = '1';
const PRIMARY_MENU        = 'Lyli Shop Primary';
const FRONT_PAGE_TITLE    = 'Trang chủ';
const SHOP_PAGE_TITLE     = 'Cửa hàng';

/**
 * Page definitions. Legal/policy pages are created as DRAFT (unpublished)
 * and are only linked publicly once real content is approved by the owner.
 */
const INITIAL_PAGES = [
    'front'   => FRONT_PAGE_TITLE,
    'shop'    => SHOP_PAGE_TITLE,
    'cart'    => 'Giỏ hàng',
    'checkout' => 'Thanh toán',
    'account' => 'Tài khoản',
    'about'   => 'Giới thiệu',
    'contact' => 'Liên hệ',
    'custom_order' => 'Đặt mẫu theo yêu cầu',
    'policy_shipping'  => 'Chính sách vận chuyển',
    'policy_returns'   => 'Chính sách đổi trả',
    'policy_privacy'   => 'Chính sách bảo mật',
    'policy_terms'     => 'Điều khoản',
];

/** Policy pages stay draft (unpublished) until approved content exists. */
const DRAFT_PAGES = [
    'policy_shipping',
    'policy_returns',
    'policy_privacy',
    'policy_terms',
];

/** Top-level product categories (navigation source of truth — THEME-DECISION.md §9). */
const INITIAL_CATEGORIES = [
    'Móc khóa len',
    'Gấu bông len',
    'Hoa len',
    'Hộp quà',
    'Đặt mẫu theo yêu cầu',
];

/**
 * Register the WP-CLI command.
 */
if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('lyli bootstrap', __NAMESPACE__ . '\\BootstrapCommand');
}

/**
 * Bootstrap command implementation.
 */
class BootstrapCommand
{
    /**
     * Seed initial site structure once.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Show what would be created without changing anything.
     *
     * [--apply]
     * : Actually apply the changes. Required for any write.
     *
     * [--force]
     * : Ignore the "already seeded" flag and re-run idempotently.
     *
     * ## EXAMPLES
     *
     *     wp lyli bootstrap init --dry-run
     *     wp lyli bootstrap init --apply
     */
    public function init(array $args, array $assoc_args): void
    {
        $dry_run = ! empty($assoc_args['dry-run']);
        $apply   = ! empty($assoc_args['apply']);
        $force   = ! empty($assoc_args['force']);

        if (! $dry_run && ! $apply) {
            \WP_CLI::error('Refusing to write: pass --apply to apply changes, or --dry-run to preview.');
        }

        $seeded = get_option(BOOTSTRAP_OPTION);
        if ($seeded && ! $force) {
            \WP_CLI::log('Bootstrap already seeded (flag = ' . esc_attr((string) $seeded) . '). Use --force to re-run idempotently.');
            return;
        }

        $report = [];

        foreach (INITIAL_PAGES as $key => $title) {
            $page_id = $this->find_page_by_title($title);
            $status  = $page_id ? 'exists' : 'create';
            $report['pages'][$key] = [
                'title'  => $title,
                'status' => $status,
                'id'     => $page_id ? (int) $page_id : null,
            ];

            if ($status === 'create' && $apply) {
                $new_id = wp_insert_post([
                    'post_type'    => 'page',
                    'post_status'  => in_array($key, DRAFT_PAGES, true) ? 'draft' : 'publish',
                    'post_title'   => $title,
                    'post_content' => '',
                ]);
                if (is_wp_error($new_id)) {
                    \WP_CLI::warning(sprintf('Could not create page %s: %s', $title, $new_id->get_error_message()));
                    continue;
                }
                $report['pages'][$key]['id'] = (int) $new_id;
            }
        }

        // Menus: create primary menu only when absent.
        $menu_exists = wp_get_nav_menu_object(PRIMARY_MENU);
        $report['menu']['name']   = PRIMARY_MENU;
        $report['menu']['status'] = $menu_exists ? 'exists' : 'create';
        if ($apply && ! $menu_exists) {
            $menu_id = wp_create_nav_menu(PRIMARY_MENU);
            if (is_wp_error($menu_id)) {
                \WP_CLI::warning('Could not create primary menu: ' . $menu_id->get_error_message());
            } else {
                $report['menu']['id'] = (int) $menu_id;
                $this->assign_menu_locations($menu_id, $report);
            }
        }

        // Product categories (WooCommerce taxonomy product_cat).
        $report['categories'] = [];
        foreach (INITIAL_CATEGORIES as $cat_name) {
            $term = term_exists($cat_name, 'product_cat');
            $status = $term ? 'exists' : 'create';
            $report['categories'][] = ['name' => $cat_name, 'status' => $status];
            if ($apply && ! $term) {
                $res = wp_insert_term($cat_name, 'product_cat');
                if (is_wp_error($res)) {
                    \WP_CLI::warning(sprintf('Could not create category %s: %s', $cat_name, $res->get_error_message()));
                }
            }
        }

        // WooCommerce pages assignment.
        if ($apply && function_exists('wc_create_pages')) {
            $report['wc_pages'] = $this->assign_wc_pages($report['pages']);
        }

        // Front page: set Home only when not intentionally set.
        if ($apply) {
            $current_front = (int) get_option('page_on_front', 0);
            if ($current_front === 0) {
                $front_id = isset($report['pages']['front']['id']) ? (int) $report['pages']['front']['id'] : $this->find_page_by_title(FRONT_PAGE_TITLE);
                update_option('show_on_front', 'page');
                update_option('page_on_front', $front_id);
                $report['front_page'] = ['set' => true, 'id' => $front_id];
            } else {
                $report['front_page'] = ['set' => false, 'existing_id' => $current_front];
            }
        } else {
            $report['front_page'] = ['status' => 'dry-run'];
        }

        // Mark seeded (only when applied).
        if ($apply) {
            update_option(BOOTSTRAP_OPTION, 'bootstrapped-v' . BOOTSTRAP_VERSION);
        }

        $this->render_report($report, $dry_run);
    }

    private function find_page_by_title(string $title): int
    {
        $query = new \WP_Query([
            'post_type'   => 'page',
            'title'       => $title,
            'post_status' => 'any',
            'fields'      => 'ids',
        ]);
        return $query->have_posts() ? (int) $query->posts[0] : 0;
    }

    private function assign_menu_locations(int $menu_id, array &$report): void
    {
        $locations = get_registered_nav_menus();
        if (empty($locations)) {
            $report['menu']['locations'] = 'none-registered';
            return;
        }
        $assign = [];
        foreach (array_keys($locations) as $loc) {
            $assign[$loc] = $menu_id;
        }
        set_theme_mod('nav_menu_locations', $assign);
        $report['menu']['locations'] = array_keys($locations);
    }

    private function assign_wc_pages(array $page_report): array
    {
        $needed = [
            'shop'     => 'woocommerce_shop_page_id',
            'cart'     => 'woocommerce_cart_page_id',
            'checkout' => 'woocommerce_checkout_page_id',
            'account'  => 'woocommerce_myaccount_page_id',
        ];

        $result = [];
        foreach ($needed as $key => $option_name) {
            $existing = (int) get_option($option_name, 0);
            if ($existing) {
                $result[$key] = ['status' => 'exists', 'id' => $existing];
                continue;
            }
            $page_id = isset($page_report[$key]['id']) ? (int) $page_report[$key]['id'] : $this->find_page_by_title(INITIAL_PAGES[$key]);
            if ($page_id) {
                update_option($option_name, $page_id);
                $result[$key] = ['status' => 'assigned', 'id' => $page_id];
            } else {
                $result[$key] = ['status' => 'missing'];
            }
        }
        return $result;
    }

    private function render_report(array $report, bool $dry_run): void
    {
        $mode = $dry_run ? 'DRY-RUN (no changes)' : 'APPLIED';
        \WP_CLI::log('== Lyli bootstrap: ' . $mode . ' ==');

        if (! empty($report['pages'])) {
            \WP_CLI::log('Pages:');
            foreach ($report['pages'] as $key => $p) {
                \WP_CLI::log(sprintf('  %-18s %-8s id=%s', $key, $p['status'], $p['id'] ?: '-'));
            }
        }
        if (! empty($report['menu'])) {
            $locations = ! empty($report['menu']['locations']) ? implode(',', (array) $report['menu']['locations']) : '-';
            \WP_CLI::log(sprintf('Menu: %-10s id=%s locations=%s', $report['menu']['status'], $report['menu']['id'] ?? '-', $locations));
        }
        if (! empty($report['categories'])) {
            \WP_CLI::log('Categories:');
            foreach ($report['categories'] as $cat) {
                \WP_CLI::log('  ' . $cat['name'] . ' => ' . $cat['status']);
            }
        }
        if (! empty($report['wc_pages'])) {
            \WP_CLI::log('WooCommerce pages:');
            foreach ($report['wc_pages'] as $key => $w) {
                \WP_CLI::log('  ' . $key . ' => ' . $w['status'] . ' id=' . ($w['id'] ?? '-'));
            }
        }
        if (! empty($report['front_page'])) {
            $front_status = $report['front_page']['status'] ?? 'recorded';
            \WP_CLI::log('Front page: ' . $front_status);
        }
    }
}