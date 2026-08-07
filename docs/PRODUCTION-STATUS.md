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

## Trạng thái hiện tại (2026-08-07)

| Mục | Kết quả |
|---|---|
| Deployed source commit | `ae16b451e896c0a40b16767f6c9daf220ab3d8d6` |
| Deployment gate | WSL/local validators; GitHub Actions chỉ cung cấp thông tin, không chặn deploy |
| Domain/DNS | `lylishop.online` → `103.75.184.20` — đúng hosting |
| SSL/public routes | HTTPS hợp lệ; Home, Shop, Cart, Checkout, My Account và trang đăng nhập admin trả HTTP 200 |
| Web PHP | `8.3.30` (LiteSpeed) |
| WordPress/storefront | WordPress 7.0.2 đã cài; `shop-child` active trên Botiga 2.4.7; WooCommerce 10.9.4 active |
| Plugin drift | AI Engine 3.7.0 và aThemes Starter Sites 1.4.2 đã được pin vào Composer; release build từ Git snapshot sạch |
| MU plugin / CLI | Bedrock autoloader active; Lyli settings hook có mặt; `wp lyli bootstrap init --dry-run` chạy thành công |
| Code policy | `DISALLOW_FILE_MODS=true`, `DISALLOW_FILE_EDIT=true`; nội dung/cấu hình cửa hàng vẫn chỉnh trong WP Admin |
| WP-CLI path trên host | `--path=apps/lylishop/current/web/wp` |
| `public_html` | Symlink → `apps/lylishop/current/web`; bản provider cũ giữ tại `shared/rollback/provider-public_html-20260807135123` |
| `apps/lylishop/current` | → `releases/20260807183254` |
| Release rollback | `releases/20260807164746` |
| Backup gần nhất | `shared/backups/20260807183505/database.sql.gz` (qua `gzip -t`) |
| `.env` | `shared/.env` mode 600, ngoài `public_html`, owner đúng |
| Baseline content | 8 trang publish, 4 policy draft, 5 danh mục sản phẩm, 0 sản phẩm; mọi payment gateway tắt |
| Mốc đạt được | 1–5; chưa đạt commerce launch readiness |

## Bản ghi cấp phép một lần (2026-08-06)

Founder cấp phép **một lần** cho nhiệm vụ "admin-editable storefront implementation + production installation + guarded public cutover" gồm: sửa tài liệu roadmap/repo, triển khai storefront, commit/push, build/upload/extract release mới, backup mới, validate/update secrets an toàn, sinh salts nếu thiếu, `wp core install`, tạo bảng DB, tạo admin, kích hoạt theme/plugin approved, cấu hình WooCommerce baseline, tạo trang/menu/danh mục/options, tạo + chuyển `current`, maintenance mode, thay `public_html` bằng symlink, xoá `index.htm` sau backup, public cutover, rollback tự động nếu gate bắt buộc fail.

Chính sách phê duyệt chung cho lần deploy sau **không** bị suy yếu. Bản ghi này không tái sử dụng làm ủy quyền cho nhiệm vụ tương lai.

## Nhật ký lịch sử

- **2026-08-03:** xác lập production-only (một domain, một DB, một uploads).
- **2026-08-04:** chốt Botiga Free 2.4.7 + `shop-child`; tokens `#7A3B17`/`#8A4A23`.
- **2026-08-05:** DNS đúng, PHP web 8.3, DB tạo xong, `.env` upload mode 600, privilege probe PASS, SSL chưa hợp lệ.
- **2026-08-06 (trước nhiệm vụ này):** phát hiện thiếu `web/wp-config.php`/`web/index.php` → sửa Bedrock bootstrap (commit `f1f6049`), CI xanh; SSL đã hợp lệ; schema collation xác nhận `utf8mb4_unicode_ci`; release baseline `20260806144016-f1f6049` build/upload/extract xong.
- **2026-08-06–07:** hoàn tất storefront V1, cài WordPress/WooCommerce, bootstrap nội dung nền và public cutover; release `20260807164746` được giữ làm rollback.
- **2026-08-07:** phase ổn định kiến trúc: pin plugin drift vào Composer, clean-snapshot artifact, khôi phục production code locks, gỡ Botiga capability shim, deploy release `20260807183254`; MCP Lyli và public smoke test PASS.
