# ARCHITECTURE

Nguồn quyết định: `PLAN.md` (sản phẩm) và `TECH_STACK.md` (stack đã chốt). Tài liệu này chỉ tóm tắt và trỏ về, không thay thế hai file gốc. Mâu thuẫn chưa giải quyết giữa hai file gốc được liệt kê ở `docs/HOSTING-AUDIT.md` mục 14 — chưa được tự ý xử lý ở đây.

**Amendment 2026-08-03 — production-only:** một domain, một môi trường production, một database, một kho uploads; không có staging (xem `docs/DEPLOYMENT.md`).

**Theme gate:** dòng "Storefront 4.6.2 + shop-child" trong sơ đồ dưới đây phản ánh lựa chọn tạm ghi trong TECH_STACK.md mục 3.1, nhưng **chưa được founder xác nhận là quyết định cuối cùng**. Không cài đặt, kích hoạt hay phát triển theme cho tới khi `docs/THEME-DECISION-BRIEF.md` được founder chốt — xem tài liệu đó để biết các điểm còn mở (màu sắc, cấu trúc danh mục, classic/block theme).

## Lớp kiến trúc

```text
Storefront 4.6.2 + shop-child (child theme)  ← CHƯA CHỐT, xem Theme gate ở trên
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

* **Cart/Checkout:** Classic (shortcode/template), chưa dùng Cart/Checkout Blocks — xem mâu thuẫn #2 ở `docs/HOSTING-AUDIT.md` mục 14 trước khi thay đổi.
* **HPOS:** tắt khi go-live, chỉ bật sau khi toàn bộ plugin commerce xác nhận tương thích + test đầy đủ trên bản sao dữ liệu kiểm thử cục bộ (không có staging riêng).
* **Editor:** Gutenberg core + WooCommerce blocks đã kiểm thử cho nội dung (không phải Cart/Checkout), không dùng page builder bên thứ ba.

## Vai trò và quyền

Xem `web/app/mu-plugins/site-policy/` — đăng ký role, capability, ẩn menu, khóa kỹ thuật. Chi tiết capability bị khóa: `LOCKED_CAPABILITIES` trong `site-policy.php`. Test tương ứng: `tests/capability/RolesCapabilityTest.php`.

## Môi trường

`WP_ENV` chọn giữa `development` (DDEV local) và `production` — không có `staging` (Amendment 2026-08-03). Override tương ứng nằm trong `config/environments/`. Biến môi trường thật nằm trong `.env` (không commit); mẫu ở `.env.example`.

## Hạ tầng thực tế (đã audit)

Xem toàn bộ `docs/HOSTING-AUDIT.md`. Tóm tắt:

* Domain: `lylishop.online`, tài khoản hoàn toàn mới (chưa có WordPress).
* PHP 8.3 xác nhận qua `/opt/alt/php83/usr/bin/php`, chọn được cho web qua CloudLinux PHP Selector.
* Document root cố định `public_html` — dùng chiến lược symlink `public_html -> current/web`, không cần panel hỗ trợ đổi doc root tùy ý.
* Database phải tạo qua OnePanel UI, không tạo được qua SSH.
* Cron mặc định dùng PHP 8.1 — mọi cron job phải gọi tường minh path PHP 8.3.
