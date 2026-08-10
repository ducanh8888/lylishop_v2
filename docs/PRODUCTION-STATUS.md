# PRODUCTION STATUS — Lyli Shop

Tài liệu trạng thái production thực tế. Cập nhật theo từng nhiệm vụ có bằng chứng; không suy đoán.

## Tầng trạng thái

Hệ thống dùng các mốc sau (thứ tự tăng dần):

| Mốc | Ý nghĩa |
|---|---|
| 1. Infrastructure ready | Domain, DNS, SSL, web PHP, database sẵn sàng; `.env` bảo mật ngoài `public_html` |
| 2. Bedrock bootstrap repaired | `web/wp-config.php` + `web/index.php` được track; bootstrap validator xác nhận chạy được |
| 3. Admin-editable storefront implementation | `shop-child` V1 + `lyli-site-settings` MU plugin + block patterns + bootstrap tooling hoàn tất trong repo; local/WSL gate PASS tại thời điểm deploy |
| 4. Production installation | WordPress cài đặt trên release thật; theme/plugin approved active; WooCommerce baseline; cấu trúc trang/danh mục |
| 5. Public baseline | `current` → release; `public_html` → `current/web`; maintenance deactivated; HTTPS công khai hoạt động; có thể chỉnh qua WP Admin |
| 6. Commerce launch readiness | Thanh toán, vận chuyển, email giao dịch, chính sách pháp lý, sản phẩm thật và kiểm thử đặt hàng thật được cấu hình và được founder phê duyệt riêng |

> Mốc 6 yêu cầu phê duyệt riêng và **không** thuộc phạm vi nhiệm vụ hiện tại.

## Trạng thái hiện tại (2026-08-08)

| Mục | Kết quả |
|---|---|
| Deployed source commit | `8b7dc92388a186d58a34484c3c9971f8aa935c6a` |
| Deployment gate | WSL/local validators; GitHub Actions chỉ cung cấp thông tin, không chặn deploy |
| Domain/DNS | `lylishop.online` → `103.75.184.20` — đúng hosting |
| SSL/public routes | HTTPS hợp lệ; Home, Shop, Cart, Checkout, My Account và trang đăng nhập admin trả HTTP 200 |
| Web PHP | `8.3.30` (LiteSpeed) |
| WordPress/storefront | WordPress 7.0.2 đã cài; `shop-child` active trên Botiga 2.4.7; WooCommerce 10.9.4 active |
| Plugin runtime | AI Engine 3.7.0 và WooCommerce 10.9.4 active; aThemes Starter Sites đã deactivate và gỡ khỏi Composer/artifact vì không có runtime dependency |
| MU plugin / CLI | Bedrock autoloader active; Lyli settings hook có mặt; `wp lyli bootstrap` và `wp lyli editorial` khả dụng |
| Code policy | `DISALLOW_FILE_MODS=true`, `DISALLOW_FILE_EDIT=true`; nội dung/cấu hình cửa hàng vẫn chỉnh trong WP Admin |
| WP-CLI path trên host | `--path=apps/lylishop/current/web/wp` |
| `public_html` | Symlink → `apps/lylishop/current/web`; bản provider cũ giữ tại `shared/rollback/provider-public_html-20260807135123` |
| Theme integration | Giữ Gutenberg storefront gốc; editorial gallery/FAQ/contact/blog cards; header desktop hai hàng; Fraunces/Be Vietnam Pro có tên rõ trong editor và weight semantic `600/400/500` |
| Admin locale | Site và tài khoản vận hành dùng `vi`; WordPress core + WooCommerce language packs nằm tại `shared/languages` và được dùng lại qua release |
| `apps/lylishop/current` | → `releases/20260808132816` |
| Release rollback | `releases/20260808001500`; full pre-editorial rollback `releases/20260807205828` |
| Backup gần nhất | `shared/backups/20260808152708/database.sql.gz` (`gzip -t` PASS); full backup gần nhất `shared/backups/20260808001000/{database.sql.gz,uploads.tar.gz}` |
| `.env` | `shared/.env` mode 600, ngoài `public_html`, owner đúng |
| Baseline content | 5 blog, 25 ảnh nguồn, 9 trang editorial/policy publish, 0 sản phẩm; promotion tắt; mọi payment gateway tắt |
| Mốc đạt được | 1–5; chưa đạt commerce launch readiness |

## Pre-flight kế tiếp — PLANNED, chưa deployed (2026-08-10)

| Workstream | Trạng thái |
|---|---|
| Repo khi bắt đầu pre-flight | `main`/`origin/main` cùng `2ffff80a2577a49ae24e963d9516492394192ee2`; working tree sạch |
| Vietnam Store Toolkit 1.1.2 | **PREFLIGHT COMPLETE / PLANNED**; exact WPackagist resolution và source audit PASS; chưa thêm Composer, chưa install/activate production |
| Payment V1 | Toolkit VietQR sẽ mở rộng BACS cho chuyển khoản thủ công; BACS/VietQR hiện vẫn tắt/chưa cấu hình; SePay **DEFERRED / OPTIONAL** |
| Founder palette | Sáu màu `#7A3B17`, `#FFFCF7`, `#FBEFE5`, `#F6E4E3`, `#E9F1EA`, `#C2C3D2` là binding; runtime hiện vẫn là palette cũ, migration **PLANNED** |
| Mobile remediation | Mobile-first pass 375/768/1440 **PLANNED**; chưa đổi CSS/theme mods/runtime |
| Quyền owner | Runtime hiện có `manage_woocommerce`, không có `manage_options` hay quyền cài/xóa plugin/theme; đủ cho UI toolkit/BACS sau deploy |
| Workflow | LOCAL/WSL VALIDATE → BUILD → BACKUP IF PRODUCTION WILL CHANGE → DEPLOY → SMOKE → KEEP OR ROLLBACK; GitHub Actions informational only |

Chi tiết: `docs/VIETNAM-STORE-TOOLKIT-PREFLIGHT.md` và `docs/BRAND-MOBILE-REMEDIATION-PLAN.md`.

## Bản ghi cấp phép một lần (2026-08-06)

Founder cấp phép **một lần** cho nhiệm vụ "admin-editable storefront implementation + production installation + guarded public cutover" gồm: sửa tài liệu roadmap/repo, triển khai storefront, commit/push, build/upload/extract release mới, backup mới, validate/update secrets an toàn, sinh salts nếu thiếu, `wp core install`, tạo bảng DB, tạo admin, kích hoạt theme/plugin approved, cấu hình WooCommerce baseline, tạo trang/menu/danh mục/options, tạo + chuyển `current`, maintenance mode, thay `public_html` bằng symlink, xoá `index.htm` sau backup, public cutover, rollback tự động nếu gate bắt buộc fail.

Chính sách phê duyệt chung cho lần deploy sau **không** bị suy yếu. Bản ghi này không tái sử dụng làm ủy quyền cho nhiệm vụ tương lai.

## Nhật ký lịch sử

- **2026-08-03:** xác lập production-only (một domain, một DB, một uploads).
- **2026-08-04:** chốt Botiga Free 2.4.7 + `shop-child`; palette provisional khi đó là `#7A3B17`/`#8A4A23` (đã superseded 2026-08-10).
- **2026-08-05:** DNS đúng, PHP web 8.3, DB tạo xong, `.env` upload mode 600, privilege probe PASS, SSL chưa hợp lệ.
- **2026-08-06 (trước nhiệm vụ này):** phát hiện thiếu `web/wp-config.php`/`web/index.php` → sửa Bedrock bootstrap (commit `f1f6049`), CI xanh; SSL đã hợp lệ; schema collation xác nhận `utf8mb4_unicode_ci`; release baseline `20260806144016-f1f6049` build/upload/extract xong.
- **2026-08-06–07:** hoàn tất storefront V1, cài WordPress/WooCommerce, bootstrap nội dung nền và public cutover; release `20260807164746` được giữ làm rollback.
- **2026-08-07:** phase ổn định kiến trúc: pin plugin drift vào Composer, clean-snapshot artifact, khôi phục production code locks, gỡ Botiga capability shim, deploy release `20260807183254`; MCP Lyli và public smoke test PASS.
- **2026-08-07:** phase tích hợp theme: loại footer kép, giữ một footer semantic của Botiga, chuyển token sang `theme.json`, chặn Botiga ghi đè palette và thêm cache version riêng cho child stylesheet; deploy release `20260807185540`.
- **2026-08-07:** phase dọn storefront: xóa khối CSS legacy trùng lặp (923 → 494 dòng), nâng `shop-child` lên 1.2.0 và gỡ aThemes Starter Sites khỏi runtime; deploy release `20260807190413`.
- **2026-08-07:** sửa Botiga Dashboard tương thích với `DISALLOW_FILE_MODS`: loại tab/menu Starter Sites khi importer không có hook, giữ nguyên code locks và deploy release `20260807205828`.
- **2026-08-08:** bổ sung editorial content theo cấu trúc Gutenberg hiện có: 5 blog, 25 ảnh, 4 policy public, menu nguồn; giữ 0 sản phẩm và promotion tắt; deploy release `20260808001500`. Chi tiết tại `docs/EDITORIAL-CONTENT-IMPORT-2026-08-08.md`.
- **2026-08-08:** chuẩn hóa typography theo `11_Brand_Guideline`: Fraunces SemiBold cho heading, Be Vietnam Pro Regular/Medium cho body/CTA; preset hiện rõ trong Gutenberg; deploy release `20260808132816`. Chi tiết tại `docs/TYPOGRAPHY-IMPLEMENTATION-2026-08-08.md`.
- **2026-08-08:** cài WordPress core/WooCommerce language pack `vi`, đặt locale tài khoản vận hành thành `vi` và chuyển language packs sang `shared/languages` để bền qua release. Chi tiết tại `docs/WOOCOMMERCE-VIETNAMESE-2026-08-08.md`.
- **2026-08-10:** pre-flight docs-only xác nhận Vietnam Store Toolkit exact 1.1.2 resolve qua WPackagist, audit source/default/capability/privacy; founder chốt sáu màu và mobile-first remediation plan. Không đổi production/runtime.
