<?php
/**
 * Capability regression test for the site-policy mu-plugin (TECH_STACK.md
 * section 10.2 requirement: "Có unit test hoặc capability test").
 *
 * Requires a WordPress test scaffold (wp-env or the WP PHPUnit test suite) —
 * not runnable until Phase 2 (WordPress install) is complete. Kept here so
 * the assertions are defined before the implementation drifts.
 *
 * Run with: vendor/bin/phpunit --testsuite capability
 */

use PHPUnit\Framework\TestCase;

/**
 * @group capability
 */
final class RolesCapabilityTest extends TestCase
{
    /** @var string[] */
    private const LOCKED_CAPABILITIES = [
        'install_plugins',
        'activate_plugins',
        'delete_plugins',
        'edit_plugins',
        'update_plugins',
        'switch_themes',
        'edit_themes',
        'install_themes',
        'delete_themes',
        'update_themes',
        'update_core',
        'edit_files',
        'edit_users',
        'promote_users',
        'remove_users',
        'manage_options',
    ];

    public function test_shop_owner_never_holds_locked_capabilities(): void
    {
        $role = get_role('shop_owner');
        $this->assertNotNull($role, 'shop_owner role must be registered by site-policy');

        foreach (self::LOCKED_CAPABILITIES as $capability) {
            $this->assertFalse(
                $role->has_cap($capability),
                "shop_owner must not hold '{$capability}' per PLAN.md section 7.5"
            );
        }
    }

    public function test_shop_staff_never_holds_locked_capabilities(): void
    {
        $role = get_role('shop_staff');
        $this->assertNotNull($role, 'shop_staff role must be registered by site-policy');

        foreach (self::LOCKED_CAPABILITIES as $capability) {
            $this->assertFalse(
                $role->has_cap($capability),
                "shop_staff must not hold '{$capability}' per PLAN.md section 7.5"
            );
        }
    }

    public function test_shop_owner_can_manage_woocommerce(): void
    {
        $role = get_role('shop_owner');
        $this->assertTrue($role->has_cap('manage_woocommerce'));
    }

    public function test_shop_staff_can_edit_orders_but_not_manage_woocommerce_settings(): void
    {
        $role = get_role('shop_staff');
        $this->assertTrue($role->has_cap('edit_shop_orders'));
        $this->assertFalse($role->has_cap('manage_woocommerce'));
    }
}
