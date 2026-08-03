<?php
/**
 * Technical lockdown per PLAN.md section 7.5 / 13 and TECH_STACK.md section 10.1.
 * File editor and file-mod constants are the enforced source of truth
 * (config/environments/production.php); this module adds defense-in-depth
 * for any environment where those constants are not yet defined, and revokes
 * locked capabilities from any role that should never hold them.
 */

namespace SitePolicy\Lockdown;

function apply(): void
{
    if (! defined('DISALLOW_FILE_EDIT')) {
        define('DISALLOW_FILE_EDIT', true);
    }

    revoke_locked_capabilities();
}

function revoke_locked_capabilities(): void
{
    foreach ([\SitePolicy\ROLE_OWNER, \SitePolicy\ROLE_STAFF] as $role_name) {
        $role = get_role($role_name);
        if (! $role) {
            continue;
        }

        foreach (\SitePolicy\LOCKED_CAPABILITIES as $cap) {
            if ($role->has_cap($cap)) {
                $role->remove_cap($cap);
            }
        }
    }
}
