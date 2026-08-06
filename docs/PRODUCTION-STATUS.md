# PRODUCTION STATUS — Lyli Shop

Tài liệu trạng thái production thực tế. Cập nhật theo từng nhiệm vụ có bằng chứng; không suy đoán.

## Tầng trạng thái

Hệ thống dùng các mốc sau (thứ tự tăng dần):

| Mốc | Ý nghĩa |
|---|---|
| 1. Infrastructure ready | Domain, DNS, SSL, web PHP, database sẵn sàng; `.env` bảo mật ngoài `public_html` |
| 2. Bedrock bootstrap repaired | `web/wp-config.php` + `web/index.php` được track; CI validator xác nhận bootstrap chạy được |
| 3. Admin-editable storefront implementation | `shop-child` V1 + `lyli-site-settings` MU plugin + block patterns + bootstrap tooling hoàn tất trong repo; CI xanh |
| 4. Production installation | WordPress cài đặt trên release thật; theme/plugin approved active; WooCommerce baseline; cấu trúc trang/danh mục |
| 5. Public baseline | `current` → release; `public_html` → `current/web`; maintenance deactivated; HTTPS công khai hoạt động; có thể chỉnh qua WP Admin |
| 6. Commerce launch readiness | Thanh toán, vận chuyển, email giao dịch, chính sách pháp lý, sản phẩm thật và kiểm thử đặt hàng thật được cấu hình và được founder phê duyệt riêng |

> Mốc 6 yêu cầu phê duyệt riêng và **không** thuộc phạm vi nhiệm vụ hiện tại.

## Trạng thái hiện tại (2026-08-06)

| Mục | Kết quả |
|---|---|
| HEAD repository | `f1f604953e2450a9da8575caa0801fc0e0310944` (trước release-source commit của nhiệm vụ này) |
| Working tree | Sạch trước khi bắt đầu nhiệm vụ |
| CI gần nhất cho HEAD | Run `31111208923` — `success` (bao gồm bước Bedrock bootstrap validation) |
| Domain/DNS | `lylishop.online` → `103.75.184.20` — đúng hosting |
| SSL | Hợp lệ cho `lylishop.online` (xác minh Python ssl + curl `--resolve`) |
| Web PHP | `8.3.30` (LiteSpeed) |
| Database | MariaDB `11.4.10`; schema Lyli `utf8mb4` / `utf8mb4_unicode_ci`; **0 bảng** (chưa cài WordPress) |
| `public_html` | Vẫn chỉ có `index.htm` (1036 B, SHA-256 `1256e0…68b9`) + `.htaccess` mặc định của hosting |
| `apps/lylishop/current` | Chưa tồn tại (chưa cutover) |
| Release baseline cũ | `releases/20260806144016-f1f6049` (15.253 file; được giữ làm code-rollback) |
| Backup retained | `shared/backups/pre-install/20260806132310` |
| `.env` | `shared/.env` mode 600, ngoài `public_html`, owner đúng |
| Mốc đạt được | 1 (Infrastructure ready) + 2 (Bedrock bootstrap repaired) |

## Bản ghi cấp phép một lần (2026-08-06)

Founder cấp phép **một lần** cho nhiệm vụ "admin-editable storefront implementation + production installation + guarded public cutover" gồm: sửa tài liệu roadmap/repo, triển khai storefront, commit/push, build/upload/extract release mới, backup mới, validate/update secrets an toàn, sinh salts nếu thiếu, `wp core install`, tạo bảng DB, tạo admin, kích hoạt theme/plugin approved, cấu hình WooCommerce baseline, tạo trang/menu/danh mục/options, tạo + chuyển `current`, maintenance mode, thay `public_html` bằng symlink, xoá `index.htm` sau backup, public cutover, rollback tự động nếu gate bắt buộc fail.

Chính sách phê duyệt chung cho lần deploy sau **không** bị suy yếu. Bản ghi này không tái sử dụng làm ủy quyền cho nhiệm vụ tương lai.

## Nhật ký lịch sử

- **2026-08-03:** xác lập production-only (một domain, một DB, một uploads).
- **2026-08-04:** chốt Botiga Free 2.4.7 + `shop-child`; tokens `#7A3B17`/`#8A4A23`.
- **2026-08-05:** DNS đúng, PHP web 8.3, DB tạo xong, `.env` upload mode 600, privilege probe PASS, SSL chưa hợp lệ.
- **2026-08-06 (trước nhiệm vụ này):** phát hiện thiếu `web/wp-config.php`/`web/index.php` → sửa Bedrock bootstrap (commit `f1f6049`), CI xanh; SSL đã hợp lệ; schema collation xác nhận `utf8mb4_unicode_ci`; release baseline `20260806144016-f1f6049` build/upload/extract xong.
- **2026-08-06 (nhiệm vụ này):** đang triển khai storefront V1 + installation + public baseline — kết quả cuối được ghi ở `docs/PRODUCTION-BASELINE-REPORT.md`.