# BACKUP & RESTORE

Nguồn: PLAN.md mục 12, TECH_STACK.md mục 8.4. Chi tiết khả năng thực tế của host: `docs/HOSTING-AUDIT.md` mục 9.

## Nguyên tắc

Repository (Git) **không** thay thế database backup, và database backup **không** thay thế repository. Một bên chứa code, bên kia chứa dữ liệu khách hàng thật.

## Thành phần backup

* Database (toàn bộ, qua `mysqldump` — xác nhận có sẵn trên host tại `docs/HOSTING-AUDIT.md` mục 2.5).
* `shared/uploads/` (media library thật, không nằm trong release artifact).
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

UpdraftPlus (`docs/PLUGIN-MANIFEST.md`) đảm nhiệm backup theo lịch sau khi WordPress được cài. Trước khi WordPress tồn tại, `scripts/backup.sh` dùng `mysqldump` + `rsync`/`tar` trực tiếp qua SSH.

## Quy trình restore

1. Xác định bản backup cần khôi phục (theo timestamp trong `shared/backups/`).
2. Restore trước tiên trên **staging** hoặc một database/thư mục riêng trên cùng host (chưa có staging domain riêng — xem `docs/HOSTING-AUDIT.md` mục 13) — không restore thẳng lên production.
3. `wp db import` hoặc nạp trực tiếp qua `mysql` client.
4. Giải nén `uploads` vào đúng `shared/uploads/`.
5. Chạy `scripts/health-check.sh` để xác nhận site hoạt động trên bản restore.
6. Chỉ sau khi xác nhận, mới restore lên production thật (nếu đây là restore khắc phục sự cố, không phải kiểm thử định kỳ).

## Kiểm thử restore định kỳ

Bắt buộc theo PLAN.md mục 12 — không coi backup chưa từng restore-test là backup đáng tin cậy. Lịch kiểm thử cụ thể (hằng tháng/hằng quý) do developer/owner thống nhất khi vận hành thật bắt đầu — chưa chốt trong tài liệu này vì chưa có dữ liệu thật để test.

## Không commit

Password, database credentials, API key, SMTP credentials, banking credentials, production salts, license secret, backup production, dữ liệu khách hàng — xem `.gitignore` (mục backup/uploads/secrets đã loại trừ).
