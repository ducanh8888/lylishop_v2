# PLUGIN MANIFEST

Nguồn: TECH_STACK.md mục 5. Mỗi plugin phải có entry ở đây trước khi được thêm vào `composer.json` hoặc cài thủ công (yêu cầu bắt buộc theo TECH_STACK.md mục 18, tiêu chí #22).

Manifest này phản ánh dependency có thể tái tạo từ `composer.lock`. Plugin phát hiện trực tiếp trên production phải được pin ở đây trước release kế tiếp hoặc được gỡ bằng một thay đổi có kiểm soát.

## Theme (theo cùng kỷ luật manifest như plugin)

| Theme | Version baseline | Nguồn | License | Trạng thái | Ghi chú |
|---|---|---|---|---|---|
| Botiga Free | 2.4.7 | wpackagist (`wpackagist-theme/botiga`) — xác nhận trực tiếp qua `composer show --all` trên `commerce-host` (2026-08-04) | GPL | Đã chốt theme cha V1, đã có trong `composer.json` (`docs/THEME-DECISION.md`) | Bản Pro không được phép mua/dùng (`docs/THEME-DECISION.md`) |
| `shop-child` | 1.3.1 | `web/app/themes/shop-child/` trong repo này | Nội bộ | Child theme presentation; sáu brand token chuẩn tại `theme.json`; một footer semantic tích hợp Botiga; mobile-first Classic Cart/Checkout, Gutenberg patterns, responsive/accessibility — `docs/PRODUCTION-STATUS.md` | `Template: botiga`; gỡ riêng filter palette Botiga 2.4.7; mobile header migration một lần; child stylesheet dùng cache version độc lập |
| Storefront | 4.6.2 | wpackagist (`wpackagist-theme/storefront`, đã có trong `composer.json`) | GPL | Fallback cấp 2, không phải lựa chọn chính | Giữ trong `composer.json` làm baseline tương thích khẩn cấp — xem `docs/THEME-DECISION.md` điều kiện kích hoạt |

## Plugin bắt buộc (Tier A)

| Plugin | Version baseline | Nguồn | License | Bắt buộc/Tùy chọn | Owner bảo trì | Dữ liệu tạo ra | Backup | Gỡ bỏ | Rủi ro tương thích |
|---|---|---|---|---|---|---|---|---|---|
| WooCommerce | 10.9.4 | wpackagist (WordPress.org) | GPL | Bắt buộc | Developer | Sản phẩm, đơn hàng, khách hàng (DB tables + postmeta) | UpdraftPlus DB dump | Deactivate — dữ liệu vẫn giữ trong DB | Thấp, plugin nền của toàn bộ stack |
| Vietnam Store Toolkit for WooCommerce | 1.1.2 | WPackagist `wpackagist-plugin/yoohw-vietnam-store-tools`; WordPress.org stable 1.1.2 | GPLv2+ | **Đã deploy/active**; V1 địa chỉ VN và transfer-payment handoff | Developer pin/deploy; Shop Owner cấu hình business settings | Woo options/order/customer meta; BACS/VietQR, shipping/tracking/invoice data khi owner bật | DB dump + immutable release | Deactivate không xóa dữ liệu; rollback release + DB khi cần | Trung bình — exact pin; defaults address/phone bật; VietQR gửi image request ngoài khi được owner bật |
| `lyli-ghn-connector` | 0.1.0 | `web/app/plugins/lyli-ghn-connector/` trong repo này | Nội bộ | **Đã deploy/active nhưng connector tắt/chưa cấu hình**; provider GHN thủ công cho framework shipment của Toolkit, không có live checkout rate | Developer bảo trì code; Shop Owner nhập credential/chính sách và bật sau test | Option cấu hình + Token không autoload; metadata shipment chuẩn của Toolkit chỉ sinh sau thao tác create | DB dump + immutable release | Tắt connector trước, rồi deactivate; rollback release + DB nếu đã tạo shipment | Trung bình — chưa E2E với GHN; không webhook; không auto-create; không gọi GHN ở checkout |
| AI Engine | 3.7.0 | wpackagist (`ai-engine`) | GPL | Bắt buộc khi dùng MCP Lyli | Developer | Cấu hình AI/MCP và metadata trong DB; credential không nằm trong repo | DB dump | Chỉ deactivate sau khi gỡ MCP Lyli khỏi Codex | Cao — MCP có tool đọc/ghi/xóa rộng; phải dùng allowlist và approval policy |
| SEOPress Free | 10.1 | wpackagist (`wp-seopress`) | GPL | Bắt buộc | Developer | Options riêng (`seopress_*`), meta trên post | Nằm trong DB dump | Deactivate, xóa options qua uninstall | Thấp |
| FluentSMTP | 2.2.95 | wpackagist (`fluent-smtp`) | GPL | Bắt buộc, **cần compatibility test WordPress 7.0.2 trước production** | Developer | Email log (DB table riêng) | DB dump | Deactivate | Trung bình — chưa công bố test WP 7.0.2 (TECH_STACK.md mục 8.2); fallback: WP Mail SMTP 4.9.0 hoặc Post SMTP 3.9.5 |
| WP Super Cache | 3.1.1 | wpackagist (`wp-super-cache`) | GPL | Bắt buộc **nếu web server là Apache** — cần xác nhận qua panel (`docs/HOSTING-AUDIT.md` mục 3.4); nếu LiteSpeed thật, đổi sang LiteSpeed Cache | Developer | Cache file trong `wp-content/cache/` | Không cần backup (regenerable) | Deactivate + xóa cache dir | Thấp, phải loại trừ cart/checkout/my-account khỏi page cache |
| UpdraftPlus | 1.26.6 | wpackagist (`updraftplus`) | GPL | Bắt buộc | Developer | Backup archive (DB + files) | Off-site storage riêng | Deactivate | Thấp |
| WP 2FA | 4.1.0 | wpackagist (`wp-2fa`) | GPL | Bắt buộc cho `developer_admin`, `shop_owner`; khuyến nghị `shop_staff` | Developer | TOTP secret/backup codes trong usermeta | DB dump (đã mã hóa bởi plugin) | Deactivate, user quay lại không 2FA | Thấp |
| Simple History | 5.29.0 | wpackagist (`simple-history`) | GPL | Bắt buộc | Developer | Audit log (DB table riêng) | DB dump | Deactivate | Thấp |
| `site-policy` (MU plugin nội bộ) | 0.1.0 | `web/app/mu-plugins/site-policy/` trong repo này | Nội bộ | Bắt buộc | Developer | Không lưu dữ liệu ngoài role/capability trong DB WP chuẩn | DB dump | Xóa thư mục — role về mặc định WordPress | Thấp — không chứa business logic (giới hạn ở `site-policy.php` mục doc-block) |

## MU plugin editorial nội bộ

| Plugin | Nguồn | Vai trò | Dữ liệu |
|---|---|---|---|
| `lyli-editorial-import` | `web/app/mu-plugins/lyli-editorial-import/` | Import idempotent pages/blog/media/menu/settings; không tạo sản phẩm | WordPress posts, attachments, options và menu; manifest tracking trong option `lyli_editorial_import_manifest` |

## Plugin commerce planned/deferred theo phạm vi

| Extension | Version baseline | Nguồn | License | Trạng thái | Ghi chú |
|---|---|---|---|---|---|
| SePay Gateway | 1.1.23 | Riêng của SePay (sepay.vn) — không trên WPackagist | Thương mại | **DEFERRED / OPTIONAL** | Chỉ xem xét nếu sau này cần automatic bank reconciliation/webhook; không triển khai song song với VietQR của Vietnam Store Toolkit. Giữ nghiên cứu lịch sử, không thuộc V1 transfer UI trước mắt |
| WooCommerce Product Bundles | 8.5.10 | WooCommerce.com Marketplace (private repo/license) | Thương mại | Bắt buộc theo phạm vi (⚠️ PLAN.md xếp Tier B — cần xác nhận) | Cần license key riêng, không commit |
| WooCommerce Smart Coupons | 9.80.0 | WooCommerce.com Marketplace (StoreApps) | Thương mại | Bắt buộc theo phạm vi (⚠️ PLAN.md xếp Tier B — cần xác nhận) | Cần license key riêng, không commit |

## Tùy chọn / Feature flag (Tier C tương ứng optional)

| Extension | Version baseline | Trạng thái mặc định | Điều kiện kích hoạt |
|---|---|---|---|
| Affiliate for WooCommerce | 9.10.0 | **Không kích hoạt mặc định** | Chỉ bật khi shop chốt: attribution, cookie duration, mức commission, điều kiện hủy commission, xử lý hoàn/hủy, chu kỳ đối soát, cách thanh toán affiliate (TECH_STACK.md mục 5.2) |

## Bị loại vĩnh viễn khỏi baseline

Xem danh sách đầy đủ ở TECH_STACK.md mục 16 — Elementor/Divi/Bricks/Jetpack, hai plugin cùng chức năng (SEO/SMTP/cache/security), RMA trong V1, multi-vendor/multi-warehouse/POS/CRM/marketing-automation/AI, Shopify runtime/API.

## Quy tắc cập nhật manifest này

* Mọi plugin mới — kể cả free trên WordPress.org — phải có một dòng ở đây trước khi vào `composer.json`.
* Không có hai plugin cùng giải quyết một chức năng (PLAN.md mục 9.2).
* Version bump đi qua local/WSL validation + regression test trước khi cập nhật baseline ở đây (xem `docs/UPDATE-POLICY.md`). GitHub Actions chỉ cung cấp thông tin.
