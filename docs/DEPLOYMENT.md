# DEPLOYMENT

## Production state — 2026-08-07

- Public release `20260807135123`, source `719a032`; WordPress 7.0.2, WooCommerce, Botiga and `shop-child` are active.
- Bedrock maps `web/app/plugins` and `web/app/mu-plugins`; the root MU loader starts `roots/bedrock-autoloader`. Use WP-CLI `--path=<release>/web/wp` on this host.
- Local WSL validation is the deployment gate; GitHub Actions is informational. Workflow: **LOCAL VALIDATE → BUILD → BACKUP IF PRODUCTION STATE WILL CHANGE → DEPLOY → SMOKE TEST → PUBLIC OR ROLLBACK**.
- Gutenberg pages/patterns and WP Admin → Lyli Shop settings are owner-editable. Payments remain disabled; products and legal-policy content await owner input.

Chi tiết audit hạ tầng: `docs/HOSTING-AUDIT.md`. Tài liệu này mô tả quy trình, không lặp lại số liệu audit.

**Amendment 2026-08-03 — production-only:** Dự án có đúng một domain (lylishop.online), một môi trường production, một database production, một kho uploads production. Không có staging domain, staging database, staging deployment hay staging promotion workflow. Local development và automated checks (CI) là cổng kiểm tra duy nhất trước production. Không có staging **không** có nghĩa là được sửa production trực tiếp, thủ công — mọi thay đổi vẫn đi qua quy trình release-based bên dưới.

## Nguyên tắc

* Composer chạy ở **máy dev hoặc CI**, không chạy build nặng lặp lại trên shared host (`docs/HOSTING-AUDIT.md` mục 12).
* Host chỉ nhận **release artifact đã build sẵn** (có `vendor/`, `web/wp/`, asset đã compile) rồi chạy WP-CLI để migrate/config.
* Production cho tài khoản `administrator` cài/update plugin-theme và dùng file editor trong `wp-admin`; `shop_owner`/`shop_staff` vẫn bị khóa. Thay đổi trực tiếp phải được đồng bộ lại vào source/Composer trước release kế tiếp để tránh bị ghi đè.
* SSH dùng biến `SSH_HOST_ALIAS`, mặc định `commerce-host` — không hard-code host/IP/port/username/private-key path trong bất kỳ script nào (xem `docs/HOSTING-AUDIT.md` mục 0 về vì sao `commerce-host` chứ không phải `lyli-prod`).
* Production **không** clone/pull từ GitHub trừ khi có quyết định riêng cho phép sau này — release đến production qua artifact upload (rsync/scp), không qua `git pull` trên host.
* Mọi script production (`scripts/production-*.sh`) mặc định **dry-run**; chỉ thực thi thật khi truyền cờ xác nhận rõ ràng (`--apply`, và với thao tác nguy hiểm hơn còn yêu cầu gõ xác nhận bằng tay).

## Cấu trúc release trên host

```text
/home/erxwskxohosting/
├── public_html -> apps/lylishop/current/web     # chỉ đổi 1 lần lúc go-live đầu tiên
└── apps/lylishop/
    ├── releases/<timestamp>/web/
    ├── shared/{.env, uploads/, logs/, backups/}
    └── current -> releases/<timestamp>
```

Symlink switching được ưu tiên vì đã xác nhận hoạt động trên host này (`docs/HOSTING-AUDIT.md` mục 8) và cho phép chuyển release/rollback gần như tức thời mà không cần panel hỗ trợ đổi document root. Nếu một hosting khác trong tương lai không cho phép symlink an toàn, dùng fallback rsync có kiểm soát ở mục "Fallback không dùng symlink" bên dưới.

## Quy trình deploy production (10 bước bắt buộc)

1. **Pre-deploy validation** (máy dev/CI) — `scripts/validate-local.ps1` + `scripts/production-preflight.sh` (dry-run mặc định): `composer validate`, `composer install --no-dev --optimize-autoloader`, build asset theme nếu có, PHP syntax check, kiểm tra secret không bị commit.
2. **Backup database production** — `scripts/production-backup.sh` (chạy trên host qua `SSH_HOST_ALIAS`): `mysqldump` vào `shared/backups/pre-deploy/<timestamp>/database.sql.gz`.
3. **Backup uploads/config production nếu áp dụng** — cùng script: tar `shared/uploads/` và `shared/.env` (chỉ để khôi phục, không đưa vào release artifact hay Git).
4. **Bật maintenance mode** — `wp maintenance-mode activate` qua PHP 8.3 trên release đang chạy (`current`), trước khi đụng tới file.
5. **Upload release bất biến (immutable)** — đóng gói bằng `scripts/build-artifact.sh`, rsync/scp lên `releases/<timestamp>/` — không sửa trực tiếp một release đã tồn tại.
6. **Giữ nguyên dữ liệu chia sẻ (shared persistent data)** — symlink `shared/.env` → `releases/<timestamp>/.env`, symlink `shared/uploads` → `releases/<timestamp>/web/app/uploads`; database không bị ghi đè bởi việc deploy code.
7. **Migration qua WP-CLI** — `wp core update-db`, cấu hình cần thiết, flush rewrite, clear cache — luôn qua `/opt/alt/php83/usr/bin/php /usr/bin/wp` (không dùng `php` mặc định 8.1).
8. **Health check** — `scripts/production-health-check.sh`: HTTP 200 trang chủ, `wp core is-installed`, `wp plugin list --status=active`, xác nhận cart/checkout/my-account không bị page-cache.
9. **Tắt maintenance mode** — chỉ sau khi health check qua; đổi symlink `current` sang release mới ngay trước hoặc cùng lúc tắt maintenance mode để giảm downtime.
10. **Rollback sẵn sàng** — `scripts/production-rollback.sh`: trỏ lại `current` về release trước; nếu migration đã chạy và không tương thích ngược, khôi phục database từ backup bước 2 trước khi trỏ lại release cũ.

Mọi script ở trên mặc định dry-run và yêu cầu `--apply` (hoặc xác nhận gõ tay với thao tác ghi đè database) — không script nào tự chạy thật trong quá trình chuẩn bị này.

## Fallback không dùng symlink (nếu một hosting khác không cho phép)

Nếu môi trường hosting không hỗ trợ symlink an toàn cho document root:

1. Giữ nguyên bước backup (2–3) và maintenance mode (4) như trên.
2. Thay bước 5–6 bằng rsync có loại trừ: `rsync -a --delete --exclude=.env --exclude=uploads/ --exclude=.git/ release/ current-path/`, với `uploads/` và `.env` được rsync riêng theo hướng một chiều từ `shared/` vào, không bị `--delete` xóa mất.
3. Trước khi rsync, tạo bản nén rollback của thư mục hiện tại (`tar` toàn bộ document root hiện có) vào `shared/backups/pre-deploy/<timestamp>/current-snapshot.tar.gz`.
4. Rollback = giải nén lại `current-snapshot.tar.gz` đè lên document root.

Host hiện tại (`docs/HOSTING-AUDIT.md`) đã xác nhận symlink hoạt động, nên fallback này chỉ là phương án dự phòng, không phải quy trình chính.

## Trước khi deploy production lần đầu (chưa làm ở phase này)

Theo `docs/HOSTING-AUDIT.md` mục 15 (Stop Conditions), xác nhận qua OnePanel trước:

1. PHP Selector cho domain đã chọn PHP 8.3.
2. Database + user MySQL đã tạo (charset `utf8mb4`, collation `utf8mb4_unicode_ci`).
3. Xác nhận web server thật (Apache+mod_lsapi hay LiteSpeed) để chọn đúng cache plugin.
4. Mâu thuẫn PLAN.md/TECH_STACK.md ở `docs/HOSTING-AUDIT.md` mục 14 đã được người chốt PLAN xác nhận.
5. Theme nền đã chốt (Botiga Free, `docs/THEME-DECISION.md`, ACCEPTED 2026-08-04) **và** đã vượt qua toàn bộ `docs/THEME-COMPATIBILITY-GATE.md` — chưa chạy gate này ở phase hiện tại, chỉ mới chốt lựa chọn theme.

## CI/CD (khi thiết lập)

Theo TECH_STACK.md mục 14 — GitHub Actions: `composer validate` → `composer install --no-dev` → PHP syntax check → PHPCS → kiểm tra secret → kiểm tra plugin manifest → build artifact → production-preflight tự động trên mọi commit vào `main` → production luôn cần manual approval trước khi deploy thật. Production không tự động pull từ GitHub; pipeline CI đẩy artifact lên host qua SSH, host không kéo code về.
