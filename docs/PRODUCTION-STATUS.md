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

## Trạng thái hiện tại (2026-08-10)

| Mục | Kết quả |
|---|---|
| Deployed source commit | `1d7bb7b93a241eaf4448e8f1e70f5ccc2d0853c6` (GHN connector; prior cascade implementation `ae0e28d4431110ac13981e2f0325a47ff1289c51`) |
| Deployment gate | WSL/local validators; GitHub Actions chỉ cung cấp thông tin, không chặn deploy |
| Domain/DNS | `lylishop.online` → `103.75.184.20` — đúng hosting |
| SSL/public routes | HTTPS hợp lệ; Home, Shop, Cart, My Account và trang đăng nhập admin trả HTTP 200; Checkout có session sản phẩm trả 200, còn giỏ trống chuyển về Cart đúng hành vi WooCommerce |
| Web PHP | `8.3.30` (LiteSpeed) |
| WordPress/storefront | WordPress 7.0.2 đã cài; `shop-child` active trên Botiga 2.4.7; WooCommerce 10.9.4 active |
| Plugin runtime | AI Engine 3.7.0, WooCommerce 10.9.4, Vietnam Store Toolkit 1.1.2 và `lyli-ghn-connector` 0.1.0 active; connector GHN tắt/chưa cấu hình; aThemes Starter Sites đã gỡ khỏi Composer/artifact |
| MU plugin / CLI | Bedrock autoloader active; Lyli settings hook có mặt; `wp lyli bootstrap` và `wp lyli editorial` khả dụng |
| Code policy | `DISALLOW_FILE_MODS=true`, `DISALLOW_FILE_EDIT=true`; nội dung/cấu hình cửa hàng vẫn chỉnh trong WP Admin |
| WP-CLI path trên host | `--path=apps/lylishop/current/web/wp` |
| `public_html` | Symlink → `apps/lylishop/current/web`; bản provider cũ giữ tại `shared/rollback/provider-public_html-20260807135123` |
| Theme integration | `shop-child` 1.3.1; sáu brand token chính thức từ `theme.json`; Fraunces/Be Vietnam Pro giữ weight `600/400/500`; Botiga runtime palette/CSS generated được reconcile một lần; mobile header là logo + cart + hamburger, search/account trong offcanvas; Gutenberg content không bị overwrite |
| Admin locale | Site và tài khoản vận hành dùng `vi`; WordPress core + WooCommerce language packs nằm tại `shared/languages` và được dùng lại qua release |
| `apps/lylishop/current` | → `releases/20260810210244` |
| Artifact | `release-20260810210129.tar.gz`; SHA-256 `e1cdfc5bc9fa56605799f2c48fa0d9f202a9ce590128d848112d71f6ab08db05` |
| Release rollback | `releases/20260810190111`; full pre-editorial rollback `releases/20260807205828` |
| Backup gần nhất | `shared/backups/20260810210321/{database.sql.gz,uploads.tar.gz}` (`gzip -t`/`tar -tzf` PASS); full backup trước đó `shared/backups/20260810185536/{database.sql.gz,uploads.tar.gz}` |
| `.env` | `shared/.env` mode 600, ngoài `public_html`, owner đúng |
| Baseline content | 5 blog, 25 ảnh nguồn, 9 trang editorial/policy publish và 2 sản phẩm thật đã có trước rollout này; không tạo test product/order; promotion tắt |
| Mốc đạt được | 1–5; chưa đạt commerce launch readiness |

## Toolkit + brand/mobile rollout (2026-08-10)

| Workstream | Trạng thái |
|---|---|
| Repo khi bắt đầu implementation | `main`/`origin/main` cùng `86a43a332cadbda703b393b6584224f9318add3a`; working tree sạch |
| Vietnam Store Toolkit 1.1.2 | **DEPLOYED / ACTIVE** từ exact Composer lock tại `web/app/plugins/yoohw-vietnam-store-tools` |
| Payment V1 | Mọi gateway (`bacs`, `cheque`, `cod`) tắt; VietQR tắt; không có bank account; owner chưa nhập merchant data. VietQR vẫn là chuyển khoản xác nhận thủ công; SePay **DEFERRED / OPTIONAL** |
| Activation defaults | Address, phone, shipment display, order management và electronic-invoice workflow effective mặc định `yes` nhưng chưa ghi option; VAT request tắt; không tạo shipping method/rate mới |
| Founder palette | Sáu màu `#7A3B17`, `#FFFCF7`, `#FBEFE5`, `#F6E4E3`, `#E9F1EA`, `#C2C3D2` đã triển khai; `#8A4A23` đã retire khỏi palette/CSS owner-facing |
| Mobile remediation | CSS mobile-first đã triển khai: 375px category/product 2 cột, H1 khoảng 31px và hero visual 240–280px; 768px 3 cột/controlled stack; 1025px+ giữ desktop 5/4 cột; blanket `overflow-x:hidden` đã gỡ |
| Viewport evidence | Source/runtime CSS và public asset 1.3.0 PASS; browser inventory trống nên chưa có screenshot/rendered visual sign-off 375/768/1440. Không rollback vì không có functional/security regression; cần visual review thủ công sau handoff |
| Quyền owner | Policy đã PASS bằng user `shop_owner` tạm rồi xóa: main page/BACS/shipping mở, `manage_options` và quyền kỹ thuật bị từ chối, DevVN tools ẩn + AJAX deny. Runtime thật hiện chỉ có một `administrator`; cần provision/chuyển đúng tài khoản vận hành sang `shop_owner` trước owner handoff |
| Checkout regression | Session với variation thật: Classic Checkout 200, Province/Ward/Phone hiện đúng; Cart/My Account/order admin không fatal; không tạo order |
| Security smoke | `.env`/`.git` 403, SQL/backup URL 404, uploads directory 403; SSL verify 0; public pages không lộ PHP warning/fatal |
| Workflow | LOCAL/WSL VALIDATE → BUILD → BACKUP IF PRODUCTION WILL CHANGE → DEPLOY → SMOKE → KEEP OR ROLLBACK; GitHub Actions informational only |

Chi tiết: `docs/VIETNAM-STORE-TOOLKIT-PREFLIGHT.md` và `docs/BRAND-MOBILE-REMEDIATION-PLAN.md`.

## GHN connector rollout (2026-08-10)

| Mục | Trạng thái |
|---|---|
| Architecture verdict | **BUILD LYLI GHN CONNECTOR**; ShipDepot 1.2.19 bị từ chối do CVE-2025-31866/CWE-862 chưa có patched release; hai plugin GHN lịch sử chỉ dùng làm reference |
| Runtime | Plugin nội bộ 0.1.0 active trong release `20260810210244`; owner đã lưu Token/ShopId và bật connector. Validation task đã chuyển lựa chọn từ Production sang **Test** qua normal settings handler trước request đầu tiên; live rate/webhook vẫn không tồn tại và shipment GHN vẫn bằng 0 |
| Address/fee | Manual Create dùng tên Province/Ward hai cấp + `is_new_to_address=true`; live fee deferred vì Toolkit code và GHN WardID v2 chưa có mapping runtime contract ổn định; checkout tiếp tục dùng Toolkit shipping rules |
| Security | Settings và shipment mutations dùng `manage_woocommerce`, nonce/order validation; Token option không autoload và không render lại; không webhook/public AJAX/REST; không retry create; không tự đổi Woo order status |
| Owner access | Menu **WooCommerce → Kết nối GHN** đăng ký đúng capability; form mask Token, secret audit và server-side denial cho Settings/Create/Sync/Cancel/Print đều PASS. Production chưa có account `shop_owner`, nên owner đang cấu hình bằng account khác và vẫn cần provision đúng role |
| Smoke | WordPress/WooCommerce/Toolkit/theme vẫn active; Home/Shop/Cart/Checkout/Account/login HTTP 200; checkout còn Province/Ward của Toolkit; `.env` 403, SQL/backup 404, directory listing denied; không lộ PHP warning/fatal |
| Validation boundary | Owner xác nhận Token lấy từ `5sao.ghn.dev`. Probe độc lập không qua connector: `GET dev-online-gateway.../master-data/province` với saved Token vẫn bị GHN trả `HTTP 401 / code 401 / Token is not valid` trong 259 ms; connector `detail-by-client-code` trước đó trả cùng kết quả. Chẩn đoán: **GHN staging credential/account provisioning**, không phải connector. Dừng trước ShopId/Preview/Create; không tạo Woo test order, vận đơn hoặc giao dịch thật |

Chi tiết và checklist owner: `docs/GHN-INTEGRATION-PREFLIGHT.md`, `docs/GHN-OWNER-SETUP.md`.

## Storefront color cascade correction (2026-08-10)

- Root cause: MCP/direct `theme_mods_shop-child` writes had the approved values, but did not fire Botiga's `customize_save_after` regeneration hook. Shared `uploads/botiga/custom-styles.css` therefore still declared `--bt-color-button-bg:#FF524D` and `--bt-color-button-bg-hover:#E80600`.
- Winning stale rules came from Botiga base selectors such as `.wp-block-button .wp-block-button__link:not(.has-background)` and `ul.products li.product .button:not(.has-background)`, whose specificity exceeded the earlier generic child rules. Additional CSS was empty; live Gutenberg content had no inline button colors. The only DB hits were in trashed customize changeset `133`.
- Canonical fix: `theme.json` remains the literal token source; the child maps those values through Botiga's documented `botiga_color_palettes` filter, and versioned option `lyli_theme_runtime_version=1` invokes `Botiga_Custom_CSS::update_custom_css_file()` once after migrating only missing/known-stale values. Later owner choices are not reset on requests.
- Cache/generation: Botiga regenerated the shared file and flushed its supported cache hooks; WP Super Cache is inactive and public responses showed no page-cache header. Cache-busted URLs became `custom-styles.css?ver=1786363047` and child `style.css?ver=1786363263`; no hosting-wide destructive purge was used.
- Runtime evidence: generated variables now resolve to brown `#7A3B17`, hover `#5B2B12`, warm-white text, and matching borders. `#FF524D`, `#E80600`, and `#8A4A23` each count `0` in generated CSS and public HTML across Home, Shop, Product, Cart, Checkout, and Account; all routes returned 200 with no exposed PHP warning/fatal.
- Visual limitation: the integrated browser inventory was empty, so computed-style screenshots at 375/768/1440 could not be captured. Source-order/specificity, generated CSS, live assets, route rendering, MCP options, and responsive source invariants were verified; manual rendered sign-off remains pending.

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
- **2026-08-10:** triển khai commit `1db61fbcccc92cc9f199ff8423d393a1fe5a1726`, release `20260810145039`: activate Vietnam Store Toolkit 1.1.2, sáu-color `theme.json`, mobile-first CSS/header, DevVN migration guard. Backup `20260810145157`; public functional/security smoke PASS; rendered visual sign-off và tài khoản `shop_owner` thật còn chờ handoff.
- **2026-08-10:** triển khai GHN connector commit `1d7bb7b93a241eaf4448e8f1e70f5ccc2d0853c6`, release `20260810210244`, backup `20260810210321`; plugin active nhưng connector tắt/chưa cấu hình, không Token/ShopId/rate/shipment; public/security smoke PASS, rollback `20260810190111` giữ nguyên.
