# BACKUP & RESTORE

Nguồn: PLAN.md mục 12, TECH_STACK.md mục 8.4. Chi tiết khả năng thực tế của host: `docs/HOSTING-AUDIT.md` mục 9.

**Amendment 2026-08-03:** Không có staging database/domain. Restore-test dùng một database/thư mục kiểm thử cục bộ (DDEV) hoặc một schema riêng trên cùng host, tách biệt khỏi database production đang phục vụ site thật — không bao giờ restore-test bằng cách ghi đè trực tiếp lên database production đang chạy.

## Nguyên tắc

Repository (Git) **không** thay thế database backup, và database backup **không** thay thế repository. Một bên chứa code, bên kia chứa dữ liệu khách hàng thật.

## Thành phần backup

* Database toàn bộ qua `wp db export` với runtime Bedrock/PHP 8.3; WP-CLI gọi `mysqldump` bằng credentials đã được WordPress nạp, không `source` hoặc in `.env` trong shell.
* `shared/uploads/` (media library thật, không nằm trong release artifact).
* `shared/languages/` (WordPress/WooCommerce language packs dùng chung qua các release; có thể tải lại từ nguồn chính thức nhưng cần giữ trong full backup để rollback nhanh).
* Theme (`shop-child`), mu-plugin (`site-policy`) — đã có trong Git, nhưng vẫn chụp cùng bản backup toàn phần để restore nhanh không cần rebuild.
* Cấu hình cần thiết (`.env` thật, không commit).
* Dữ liệu do plugin tạo ra (audit log Simple History, email log FluentSMTP, v.v — theo `docs/PLUGIN-MANIFEST.md`).
* Manifest phiên bản (`composer.lock`, `docs/PLUGIN-MANIFEST.md`).

## Lịch backup (TECH_STACK.md mục 8.4)

| Loại | Tần suất |
|---|---|
| Database | Hằng ngày |
| Full backup (DB + uploads + config) | Hằng tuần |
| Backup trước mỗi deploy | Mỗi lần, bắt buộc |
| Off-site copy | Giữ ít nhất một bản, không chỉ backup của hosting |

UpdraftPlus (`docs/PLUGIN-MANIFEST.md`) đảm nhiệm backup theo lịch. Backup có kiểm soát trước thay đổi dùng `scripts/production-backup.sh`: `wp db export` qua `/opt/alt/php83/usr/bin/php /usr/bin/wp --path=<current>/web/wp`, kiểm tra gzip và archive uploads. Script không parse `.env` như shell vì giá trị runtime không bắt buộc là shell-safe.

## Quy trình restore

1. Xác định bản backup cần khôi phục (theo timestamp trong `shared/backups/`).
2. Restore trước tiên trên **local (DDEV)** hoặc một database/thư mục kiểm thử riêng trên cùng host — không có staging domain (`docs/HOSTING-AUDIT.md` mục 13) — không bao giờ restore thẳng đè lên database production đang phục vụ site thật để "thử".
3. `wp db import` hoặc nạp trực tiếp qua `mysql` client.
4. Giải nén `uploads` vào đúng `shared/uploads/`.
5. Chạy `scripts/production-health-check.sh` để xác nhận site hoạt động trên bản restore.
6. Chỉ sau khi xác nhận, mới restore lên production thật qua `scripts/production-rollback.sh` hoặc quy trình restore tương ứng (nếu đây là restore khắc phục sự cố, không phải kiểm thử định kỳ) — luôn kèm backup của trạng thái production hiện tại trước khi ghi đè.

## Kiểm thử restore định kỳ

Bắt buộc theo PLAN.md mục 12 — không coi backup chưa từng restore-test là backup đáng tin cậy. Lịch kiểm thử cụ thể (hằng tháng/hằng quý) do developer/owner thống nhất khi vận hành thật bắt đầu — chưa chốt trong tài liệu này vì chưa có dữ liệu thật để test.

## Không commit

Password, database credentials, API key, SMTP credentials, banking credentials, production salts, license secret, backup production, dữ liệu khách hàng — xem `.gitignore` (mục backup/uploads/secrets đã loại trừ).
