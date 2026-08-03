# DEPLOYMENT

Chi tiết audit hạ tầng: `docs/HOSTING-AUDIT.md`. Tài liệu này mô tả quy trình, không lặp lại số liệu audit.

## Nguyên tắc

* Composer chạy ở **máy dev hoặc CI**, không chạy build nặng lặp lại trên shared host (`docs/HOSTING-AUDIT.md` mục 12).
* Host chỉ nhận **release artifact đã build sẵn** (có `vendor/`, `web/wp/`, asset đã compile) rồi chạy WP-CLI để migrate/config.
* Production đặt `DISALLOW_FILE_MODS=true` — không cài/update plugin qua `wp-admin` (TECH_STACK.md mục 13.1).
* Alias SSH thật trên máy dev hiện tại là `commerce-host` (không phải `lyli-prod` — xem `docs/HOSTING-AUDIT.md` mục 0).

## Cấu trúc release trên host

```text
/home/erxwskxohosting/
├── public_html -> apps/lylishop/current/web     # chỉ đổi 1 lần lúc go-live đầu tiên
└── apps/lylishop/
    ├── releases/<timestamp>/web/
    ├── shared/{.env, uploads/, logs/, backups/}
    └── current -> releases/<timestamp>
```

Lý do chọn mô hình này: `docs/HOSTING-AUDIT.md` mục 8 (chưa xác nhận panel cho đổi document root tùy ý, nhưng symlink đã xác nhận hoạt động).

## Quy trình deploy (staging hoặc production)

1. **Build** (máy dev hoặc CI): `composer validate`, `composer install --no-dev --optimize-autoloader`, build asset theme nếu có, PHP syntax check (`scripts/validate-local.ps1` / `.sh`).
2. **Đóng gói artifact**: `scripts/build-artifact.sh` — tar toàn bộ trừ `.env`, `uploads/`, `.git/`.
3. **Backup trước deploy**: `scripts/backup.sh` chạy trên host — dump DB + tar `shared/uploads` vào `shared/backups/pre-deploy/<timestamp>/`.
4. **Upload**: rsync/scp artifact lên `releases/<timestamp>/` (script `scripts/staging-deploy.sh`, thủ công/manual approval cho production theo TECH_STACK.md mục 14 Deploy).
5. **Liên kết shared state**: symlink `shared/.env` → `releases/<timestamp>/.env`, symlink `shared/uploads` → `releases/<timestamp>/web/app/uploads`.
6. **Migrate**: `wp core update-db`, `wp option ...`, flush rewrite, clear cache — tất cả qua `/opt/alt/php83/usr/bin/php /usr/bin/wp` (không dùng `php` mặc định 8.1).
7. **Switch**: đổi symlink `current` sang release mới.
8. **Health check**: `scripts/health-check.sh` — kiểm tra HTTP 200 trang chủ, `wp core is-installed`, `wp plugin list --status=active`, cart/checkout không bị cache.
9. **Rollback nếu lỗi**: trỏ lại `current` về release trước, không cần đụng `public_html`.

## Trước khi staging deploy đầu tiên (chưa làm ở phase này)

Theo `docs/HOSTING-AUDIT.md` mục 15 (Stop Conditions), xác nhận qua OnePanel trước:

1. PHP Selector cho domain đã chọn PHP 8.3.
2. Database + user MySQL đã tạo (charset `utf8mb4`, collation `utf8mb4_unicode_ci`).
3. Xác nhận web server thật (Apache+mod_lsapi hay LiteSpeed) để chọn đúng cache plugin.
4. Ba mâu thuẫn PLAN.md/TECH_STACK.md ở `docs/HOSTING-AUDIT.md` mục 14 đã được người chốt PLAN xác nhận (không chặn scaffold, nhưng chặn cấu hình Cart/Checkout và Bundle/Coupon thật).

## CI/CD (khi thiết lập)

Theo TECH_STACK.md mục 14 — GitHub Actions: `composer validate` → `composer install --no-dev` → PHP syntax check → PHPCS → kiểm tra secret → kiểm tra plugin manifest → build artifact → deploy staging tự động → production cần manual approval.
