<#
.SYNOPSIS
    Read-only remote hosting audit. Safe to re-run at any time.
.DESCRIPTION
    Re-runs the checks documented in docs/HOSTING-AUDIT.md against the shared hosting
    account over SSH. Makes NO changes on the remote host: no file writes, no database
    writes, no plugin/theme installs. Any temporary test artifact it creates (symlink,
    lock file) is removed before the script exits.

    Uses the SSH host alias configured in ~/.ssh/config. Defaults to 'commerce-host'
    because that is the alias actually present on this machine — 'lyli-prod' does not
    exist in ~/.ssh/config as of this audit (see docs/HOSTING-AUDIT.md section 0).
.PARAMETER SshAlias
    SSH config Host alias to connect through. Default: commerce-host.
#>

param(
    [string]$SshAlias = 'commerce-host'
)

$ErrorActionPreference = 'Stop'

Write-Host "Auditing $SshAlias (read-only) ..." -ForegroundColor Cyan

$remoteScript = @'
set -u
echo "=== IDENTITY ==="
whoami; id; echo "$SHELL"; pwd

echo "=== DISK ==="
df -h . 2>&1
du -sh ~ 2>&1

echo "=== DOMAINS (opcli) ==="
opcli domains 2>&1
opcli panel_info 2>&1

echo "=== EXISTING WORDPRESS INSTALLS ==="
find ~ -maxdepth 4 -iname "wp-config.php" 2>/dev/null
find ~ -maxdepth 4 -iname "wp-login.php" 2>/dev/null
echo "(empty above = no existing WordPress found)"

echo "=== SYMLINKS ==="
cd ~ && ln -s public_html symlink_audit_tmp 2>&1 \
  && readlink symlink_audit_tmp \
  && rm -f symlink_audit_tmp \
  && echo "symlinks: OK"

echo "=== TOOLING ==="
which rsync zip unzip curl wget tar mysqldump crontab 2>&1

echo "=== PHP 8.3 ==="
/opt/alt/php83/usr/bin/php -v
/opt/alt/php83/usr/bin/php -m | sort

echo "=== COMPOSER (via PHP 8.3) ==="
/opt/alt/php83/usr/bin/php /usr/local/bin/composer --version 2>&1

echo "=== WP-CLI (via PHP 8.3) ==="
/opt/alt/php83/usr/bin/php /usr/bin/wp --info 2>&1

echo "=== CRON ==="
crontab -l 2>&1
echo "default cron PHP:"; php -v 2>&1 | head -1
'@

ssh $SshAlias $remoteScript
