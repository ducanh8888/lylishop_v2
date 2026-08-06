# ARCHITECTURE

Nguồn quyết định: `PLAN.md` (sản phẩm) và `TECH_STACK.md` (stack đã chốt). Tài liệu này chỉ tóm tắt và trỏ về, không thay thế hai file gốc. Mâu thuẫn chưa giải quyết giữa hai file gốc được liệt kê ở `docs/HOSTING-AUDIT.md` mục 14 — chưa được tự ý xử lý ở đây.

**Amendment 2026-08-03 — production-only:** một domain, một môi trường production, một database, một kho uploads; không có staging (xem `docs/DEPLOYMENT.md`).

**Theme decision (2026-08-04 — ACCEPTED, cập nhật vòng 2 cùng ngày):** theme cha V1 là **Botiga Free** (đã xác minh qua wpackagist, `composer.json` đã pin `2.4.7`), kiến trúc classic/hybrid, child theme `shop-child`. Màu nâu chính `#7A3B17` / phụ `#8A4A23`, navigation 5-danh-mục, và chính sách publish sản phẩm thiếu ảnh **đã chốt**. Quyết định đầy đủ: `docs/THEME-DECISION.md`. Trình tự triển khai: `docs/THEME-IMPLEMENTATION-PLAN.md`. Tiêu chí tương thích trước khi coi là "xong": `docs/THEME-COMPATIBILITY-GATE.md`. **Vẫn chưa chốt:** màu nền/kem cụ thể và thiết kế placeholder ảnh — xem `docs/THEME-DECISION-BRIEF.md`. **Cập nhật hiện trạng 2026-08-06:** theo nhiệm vụ founder cấp phép một lần, `shop-child` được triển khai thành storefront V1 đầy đủ (trang chủ, pattern Gutenberg, product archive/single, Classic Cart/Checkout styling, responsive/accessibility) và cài đặt/kích hoạt trên production — kết quả kèm bằng chứng tại `docs/PRODUCTION-STATUS.md` và `docs/PRODUCTION-BASELINE-REPORT.md`. Ghi chú lịch sử: trước đây mục này ghi "chỉ mới có metadata skeleton + design-token foundation" — chỉ còn là lịch sử.

## Lớp kiến trúc

```text
Botiga Free + shop-child (child theme)  ← ACCEPTED 2026-08-04, xem docs/THEME-DECISION.md
        │
WordPress 7.0.2 + WooCommerce 10.9.4
        │
Curated Plugin Stack (docs/PLUGIN-MANIFEST.md)
        │
Bedrock 1.31.1 + Composer 2.x + WP-CLI
        │
PHP 8.3 + MariaDB 10.11+
        │
Shared hosting (OnePanel/CloudLinux, xem docs/HOSTING-AUDIT.md)
```

## Nguyên tắc bất biến (không tự ý đổi)

* Reuse-first: WooCommerce core → WordPress core → theme → plugin ổn định → cấu hình → hook nhỏ → code riêng, theo đúng thứ tự PLAN.md mục 3.
* Bốn nhóm được phép viết code riêng: child theme/theme config, site configuration, deployment scripts, glue code tối thiểu (PLAN.md mục 4). Không có nhóm thứ năm.
* `site-policy` là mu-plugin custom duy nhất bắt buộc, mục tiêu dưới ~300 dòng logic thực (TECH_STACK.md mục 10.2).
* Không sửa WordPress core, WooCommerce core, hay source code plugin bên thứ ba.
* Owner (`shop_owner`) không bao giờ là WordPress Administrator.

## Quyết định V1 đã chốt (TECH_STACK.md mục 4)

* **Theme:** Botiga Free + `shop-child`, classic/hybrid, không FSE làm kiến trúc chính (`docs/THEME-DECISION.md`).
* **Cart/Checkout:** Classic (shortcode/template), chưa dùng Cart/Checkout Blocks — xem mâu thuẫn #2 ở `docs/HOSTING-AUDIT.md` mục 14 trước khi thay đổi.
* **HPOS:** tắt khi go-live, chỉ bật sau khi toàn bộ plugin commerce xác nhận tương thích + test đầy đủ trên bản sao dữ liệu kiểm thử cục bộ (không có staging riêng).
* **Editor:** Gutenberg core + block pattern được kiểm soát cho nội dung (không phải Cart/Checkout), không dùng page builder bên thứ ba.

## Vai trò và quyền

Xem `web/app/mu-plugins/site-policy/` — đăng ký role, capability, ẩn menu, khóa kỹ thuật. Chi tiết capability bị khóa: `LOCKED_CAPABILITIES` trong `site-policy.php`. Test tương ứng: `tests/capability/RolesCapabilityTest.php`.

## Môi trường

`WP_ENV` chọn giữa `development` (DDEV local) và `production` — không có `staging` (Amendment 2026-08-03). Override tương ứng nằm trong `config/environments/`. Biến môi trường thật nằm trong `.env` (không commit); mẫu ở `.env.example`.

## Executable bootstrap (Bedrock entrypoints — TRACKED)

`web/wp-config.php` và `web/index.php` là entrypoint Bedrock được track trong repository (không phải file sinh bởi Composer):

- `web/wp-config.php` — nạp `vendor/autoload.php` → `config/application.php` → `ABSPATH . 'wp-settings.php'`.
- `web/index.php` — frontend bootstrapper chuẩn Bedrock (`WP_USE_THEMES` + `web/wp/wp-blog-header.php`).

Cấu hình thật nằm ở `config/application.php` + `config/environments/`, **không** sửa hai entrypoint này. Kiểm tra bootstrap bắt buộc chạy trong CI: `scripts/validate-bedrock-bootstrap.php` (synthetic `.env`, không DB, không `wp-settings.php`).

## Debug log production (quyết định 2026-08-06)

`WP_DEBUG_LOG = false` trong `config/application.php` và `config/environments/production.php`. Lý do: khi `WP_CONTENT_DIR` trỏ vào `web/app`, một giá trị `true` sẽ tạo `web/app/debug.log` nằm trong document root công khai (LiteSpeed serve `web/` trực tiếp). Chưa có cơ chế log-path không công khai di động cho mô hình shared-host này, nên chọn tắt debug logging production thay vì expose log.

## Hạ tầng thực tế (đã audit)

Xem toàn bộ `docs/HOSTING-AUDIT.md`. Tóm tắt:

* Domain: `lylishop.online`, tài khoản hoàn toàn mới (chưa có WordPress).
* PHP 8.3 xác nhận qua `/opt/alt/php83/usr/bin/php`, chọn được cho web qua CloudLinux PHP Selector.
* Document root cố định `public_html` — dùng chiến lược symlink `public_html -> current/web`, không cần panel hỗ trợ đổi doc root tùy ý.
* Database phải tạo qua OnePanel UI, không tạo được qua SSH.
* Cron mặc định dùng PHP 8.1 — mọi cron job phải gọi tường minh path PHP 8.3.
