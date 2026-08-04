# INSTALLATION PREPARATION — Lyli Shop

Kết quả của giai đoạn chuẩn bị cài đặt, không kèm credential (2026-08-04). Toàn bộ bằng chứng dưới đây là kết quả thực thi thật (SSH read-only + temp dir + real PHP 8.3/Composer), không phải suy đoán. Không có WordPress được cài, không có database, không deploy production.

## Baseline repository

* HEAD xác nhận: `845853a9442b97298d3acc72643c5d7e0ef131f1`, working tree sạch trước khi bắt đầu.
* SSH alias dùng: `commerce-host` (không phải `lyli-prod` — xem `docs/HOSTING-AUDIT.md` mục 0).

## Composer resolution — PASS

* `composer validate`: PASS (exit 0). Cảnh báo "exact version constraints" trên toàn bộ package — cố ý, theo chính sách pin phiên bản (TECH_STACK.md mục 13.1).
* `composer update --no-install --no-interaction --prefer-dist --no-progress --no-scripts`: PASS. Resolve 23 package (22 runtime + `roave/security-advisories` dev), tất cả đúng version đã pin trong `composer.json`, không có conflict.
* `composer.lock` được tạo, copy về repo, **không tạo `vendor/`** trong quá trình này.
* `composer audit --locked`: PASS — "No security vulnerability advisories found."
* `composer install --dry-run` (dùng `composer.lock` đã commit): PASS — "Verifying lock file contents can be installed on current platform" thành công, 23 package operations, không lỗi.

## Botiga package inspection — HOÀN TẤT

Tải trực tiếp bản zip chính thức `https://downloads.wordpress.org/theme/botiga.2.4.7.zip` (SHA-256 `3116f4febcf063109e6bf3b145466d35d19437ce1d66279011e88e12b7487fd7`) trong thư mục tạm trên `commerce-host`, giải nén và đọc source thật.

Phát hiện chính:

* Botiga tự enqueue `assets/css/styles.min.css` (192KB, CSS thật) qua handle `botiga-style-min`.
* Botiga tự enqueue `get_stylesheet_uri()` qua handle `botiga-style` (hook `wp_enqueue_scripts` priority 12, hàm `botiga_style_css()`) — đây chính là cơ chế WordPress chuẩn để tự động tải `style.css` của theme đang active (kể cả child theme).
* Root `style.css` của Botiga chỉ có 23 dòng/1768 byte — thuần metadata header, không có CSS thật, và Botiga **không** tự enqueue file này.
* Không có thư mục override template `woocommerce/` nào trong theme — Botiga tích hợp WooCommerce hoàn toàn qua action/filter hook (`woocommerce_before_main_content`, `woocommerce_before_shop_loop`, `woocommerce_single_product_summary`, `woocommerce_before_cart`, `woocommerce_checkout_before_order_review_heading`, `woocommerce_account_content`, v.v.), không ghi đè file template `.php`.
* `add_theme_support('woocommerce', ...)` khai báo trong `inc/plugins/woocommerce/woocommerce.php`, hook `after_setup_theme`; kèm `add_filter('woocommerce_enqueue_styles', '__return_empty_array')` (Botiga tắt CSS mặc định của WooCommerce, dùng CSS riêng).
* `readme.txt` xác nhận Botiga có sửa lỗi liên quan "khi child theme active" — child theme là use case đã được kiểm thử, không phải tổ hợp mới lạ.

## Child-theme enqueue — ĐÃ SỬA (bug thật, không phải giả định)

Phát hiện: `shop-child/functions.php` (bản trước) tạo **double-enqueue** — style.css của shop-child bị tải hai lần (một lần tự động bởi Botiga qua handle `botiga-style`, một lần thủ công bởi shop-child qua handle `shop-child`), đồng thời khai một dependency giả vào root `style.css` của Botiga (file rỗng, không có CSS).

Đã sửa: xoá toàn bộ hàm `enqueue_styles()` và hook thủ công trong `shop-child/functions.php`. Không cần enqueue gì thêm — Botiga tự lo. Không thêm CSS thị giác nào, không thêm giá trị màu nào ngoài token đã chốt trước đó.

## Repository static validation — PASS (toàn bộ)

Chạy trên bản sao repo thật trong thư mục tạm trên `commerce-host`, dùng PHP 8.3 thật (`/opt/alt/php83/usr/bin/php`), không phải kiểm tra đếm ngoặc:

| Check | Kết quả |
|---|---|
| `php -l` — 11/11 file `.php` được track | PASS |
| `bash -n` — 7/7 file `.sh` được track | PASS |
| JSON parse — `composer.json` | PASS |
| `composer validate` | PASS |
| `composer install --dry-run` (dùng lock đã commit) | PASS |
| `composer audit --locked` | PASS |
| Secret/sensitivity scan (khóa riêng, link Drive, credential literal) | PASS — sạch |
| XLSX không được track | PASS |
| `vendor/`, `uploads/`, `backups/`, `.env`, dump `.sql*` không được track | PASS |

## Web PHP probe — HOÀN TẤT (phát hiện quan trọng: DNS)

**Phát hiện quan trọng:** `https://lylishop.online` hiện **không trỏ vào tài khoản hosting này**. `nslookup lylishop.online` trả về `216.198.79.1` (dải IP Vercel); request HTTPS bình thường tới domain trả `403 Forbidden` với header `Server: Vercel`, `X-Vercel-Mitigated: deny`. Tài khoản hosting thật (`onehost-cloudhn032505.000nethost.com`) nằm ở `103.75.184.20` — một địa chỉ hoàn toàn khác. **Đây là việc founder cần biết và quyết định trước go-live** — chưa rõ Vercel đang chạy gì (landing page cũ, dự án khác, hay chưa cấu hình) — không tự suy đoán hoặc tự ý xử lý.

Vì DNS công khai không trỏ đúng chỗ, probe được xác minh bằng kỹ thuật chuẩn "test trước khi chuyển DNS": `curl --resolve lylishop.online:443:103.75.184.20 ...` — ép kết nối HTTPS thẳng tới IP hosting trong khi vẫn giữ SNI/Host là `lylishop.online`. Kết nối SSL thành công (xác nhận panel đã có sẵn vhost/chứng chỉ cho domain này dù DNS chưa trỏ tới) và probe PHP thực thi bình thường.

Probe: tạo file PHP tên ngẫu nhiên 40 ký tự hex, chỉ xuất JSON các trường được phép, không có phpinfo/session/cookie/env/credential. Xoá ngay sau khi đọc kết quả; xác minh URL trả 404 sau khi xoá; xác nhận `index.htm` không đổi cả kích thước lẫn SHA-256 trước/sau (`1256e062c3ee11e5e43106e173489e2e533ca1ac2d174844ad470b9c9fb668b9`, 1036 byte).

### Kết quả probe (đã lọc, không có dữ liệu nhạy cảm)

```json
{
    "PHP_VERSION": "8.1.34",
    "PHP_SAPI": "litespeed",
    "DOCUMENT_ROOT": "/home/erxwskxohosting/public_html",
    "SERVER_SOFTWARE": "LiteSpeed",
    "memory_limit": "512M",
    "max_execution_time": "3000",
    "max_input_time": "3000",
    "max_input_vars": "3000",
    "upload_max_filesize": "10240M",
    "post_max_size": "512M",
    "open_basedir": "",
    "extensions": {
        "curl": true, "mbstring": true, "mysqli": true, "pdo_mysql": true,
        "dom": true, "xml": true, "gd": true,
        "zip": false, "intl": false, "imagick": false, "opcache": false, "sodium": false,
        "exif": true, "fileinfo": true
    }
}
```

### Diễn giải

* **Web PHP hiện tại là 8.1.34, chưa phải 8.3.** PHP Selector chưa được chuyển sang 8.3 cho domain — cần hành động thủ công qua OnePanel (`docs/ONEPANEL-CHECKLIST.md`). Không thử bypass qua file công khai.
* **Web server là LiteSpeed thật** (không phải Apache+mod_lsapi như suy đoán trước đây) — xác nhận dứt điểm unknown #4 trong `docs/HOSTING-AUDIT.md`. Hệ quả: dùng **LiteSpeed Cache**, không dùng WP Super Cache, theo đúng nguyên tắc TECH_STACK.md mục 8.3.
* Document root xác nhận đúng: `/home/erxwskxohosting/public_html`.
* `open_basedir` rỗng ở web runtime (giống CLI) — không giới hạn traversal giữa site khác trên server dùng chung; đã ghi nhận là rủi ro cần lưu ý ở `docs/HOSTING-AUDIT.md` mục 7, không phải phát hiện mới.
* Module thiếu ở PHP 8.1 web (`zip`, `intl`, `imagick`, `opcache`, `sodium`) khớp đúng pattern đã biết ở CLI PHP 8.1. **Chưa biết** module set của PHP 8.3 web vì 8.3 chưa được chọn làm runtime — sẽ cần probe lại (hoặc kiểm tra qua panel) sau khi PHP Selector được đổi.
* SSL: **hoạt động** khi kết nối thẳng tới IP hosting với SNI đúng tên miền — vhost/chứng chỉ cho `lylishop.online` đã tồn tại phía server dù DNS công khai chưa trỏ tới.

## Symlink probe — HOÀN TẤT, XÁC NHẬN LAYOUT DEPLOY KHẢ THI

Tạo file văn bản ngẫu nhiên ngoài `public_html`, tạo symlink tên ngẫu nhiên trong `public_html` trỏ tới file đó, request qua HTTPS (cùng kỹ thuật `--resolve` ở trên) — **web server LiteSpeed đi theo symlink và trả đúng nội dung file đích (HTTP 200)**. Xoá cả symlink và file đích ngay sau đó; xác nhận URL trả 404; xác nhận `index.htm` vẫn không đổi.

**Kết luận:** mô hình deploy `public_html -> apps/lylishop/current/web` (`docs/DEPLOYMENT.md`) được xác nhận khả thi ở tầng web server thật, không chỉ ở tầng filesystem như audit trước — không còn là giả định.

## Còn lại (blocker trước go-live thật, không chặn việc chuẩn bị repo)

1. **DNS `lylishop.online` chưa trỏ vào hosting account này** (đang trỏ Vercel) — founder cần xác nhận và xử lý trước go-live; không tự ý thay đổi.
2. Web PHP Selector chưa chọn 8.3 (đang 8.1.34) — hành động panel thủ công.
3. Module PHP 8.3 web chưa xác minh (chỉ mới biết PHP 8.1 web).
4. Database + user MySQL chưa tạo — chỉ tạo được qua OnePanel.
5. Màu nền/kem cuối cùng và thiết kế placeholder ảnh — vẫn mở theo `docs/THEME-DECISION-BRIEF.md`, không liên quan cài đặt kỹ thuật.

## Ranh giới credential (nhắc lại, không đổi)

Không có bất kỳ credential nào (database, SMTP, SePay, ngân hàng, admin password, license key, backup service) được yêu cầu, đọc, truyền hay lưu trong quá trình chuẩn bị này. Không có `.env` thật nào được tạo. Danh sách biến môi trường cần trong tương lai: `docs/CREDENTIAL-HANDOFF.md`.
