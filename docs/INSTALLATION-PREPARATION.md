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

* **Web PHP hiện tại là 8.1.34, chưa phải 8.3.** *(Ghi chú lịch sử — vẫn còn đúng cho thời điểm 2026-08-04; đã được giải quyết 2026-08-05 khi PHP Selector chuyển sang 8.3, xem "Cập nhật 2026-08-05 — Web PHP re-probe" bên dưới: web xác nhận `PHP_VERSION: 8.3.30`.)* PHP Selector chưa được chuyển sang 8.3 cho domain — cần hành động thủ công qua OnePanel (`docs/ONEPANEL-CHECKLIST.md`). Không thử bypass qua file công khai.
* **Web server là LiteSpeed thật** (không phải Apache+mod_lsapi như suy đoán trước đây) — xác nhận dứt điểm unknown #4 trong `docs/HOSTING-AUDIT.md`. Hệ quả: dùng **LiteSpeed Cache**, không dùng WP Super Cache, theo đúng nguyên tắc TECH_STACK.md mục 8.3.
* Document root xác nhận đúng: `/home/erxwskxohosting/public_html`.
* `open_basedir` rỗng ở web runtime (giống CLI) — không giới hạn traversal giữa site khác trên server dùng chung; đã ghi nhận là rủi ro cần lưu ý ở `docs/HOSTING-AUDIT.md` mục 7, không phải phát hiện mới.
* *(Lịch sử 2026-08-04)* Module thiếu ở PHP 8.1 web (`zip`, `intl`, `imagick`, `opcache`, `sodium`) khớp đúng pattern đã biết ở CLI PHP 8.1. **Chưa biết** module set của PHP 8.3 web vì 8.3 chưa được chọn làm runtime — **đã giải quyết 2026-08-05:** PHP 8.3 web xác nhận PRESENT `curl/mbstring/mysqli/pdo_mysql/dom/xml/gd/exif/fileinfo`, ABSENT `zip/intl/imagick/opcache/sodium` (đều non-blocking) — xem "Cập nhật 2026-08-05".
* *(Lịch sử 2026-08-04)* SSL: **hoạt động** khi kết nối thẳng tới IP hosting với SNI đúng tên miền — vhost/chứng chỉ cho `lylishop.online` đã tồn tại phía server dù DNS công khai chưa trỏ tới. **Cập nhật:** DNS đã trỏ đúng (2026-08-05) và SSL đã hợp lệ cho domain (2026-08-06) — xem `docs/PRODUCTION-STATUS.md`.

## Symlink probe — HOÀN TẤT, XÁC NHẬN LAYOUT DEPLOY KHẢ THI

Tạo file văn bản ngẫu nhiên ngoài `public_html`, tạo symlink tên ngẫu nhiên trong `public_html` trỏ tới file đó, request qua HTTPS (cùng kỹ thuật `--resolve` ở trên) — **web server LiteSpeed đi theo symlink và trả đúng nội dung file đích (HTTP 200)**. Xoá cả symlink và file đích ngay sau đó; xác nhận URL trả 404; xác nhận `index.htm` vẫn không đổi.

**Kết luận:** mô hình deploy `public_html -> apps/lylishop/current/web` (`docs/DEPLOYMENT.md`) được xác nhận khả thi ở tầng web server thật, không chỉ ở tầng filesystem như audit trước — không còn là giả định.

## Còn lại tại 2026-08-04 (lịch sử — xem cập nhật 2026-08-05 bên dưới)

1. ~~DNS `lylishop.online` chưa trỏ vào hosting account này~~ — **ĐÃ GIẢI QUYẾT**, xem mục dưới.
2. ~~Web PHP Selector chưa chọn 8.3~~ — **ĐÃ GIẢI QUYẾT**, xem mục dưới.
3. ~~Module PHP 8.3 web chưa xác minh~~ — **ĐÃ XÁC MINH**, xem mục dưới.
4. Database + user MySQL chưa tạo — **ĐÃ TẠO** (founder), kết nối đã xác minh, xem mục dưới.
5. Màu nền/kem cuối cùng và thiết kế placeholder ảnh — vẫn mở theo `docs/THEME-DECISION-BRIEF.md`, không liên quan cài đặt kỹ thuật.

---

## Cập nhật 2026-08-05 — Credential handoff và full pre-install verification

Task riêng, sau khi founder đã tự thực hiện chuẩn bị OnePanel + DNS. Vẫn không cài WordPress, không tạo database (database đã được founder tạo sẵn trước phiên này), không deploy production, không kích hoạt theme/plugin. Không có giá trị credential nào được in ra, log lại, hay lưu trong repository ở bất kỳ bước nào — chỉ PASS/FAIL và metadata an toàn.

### Web PHP re-probe — PASS, gate đạt

Probe mới (tên file ngẫu nhiên 41 ký tự hex, xoá ngay sau khi đọc, xác nhận 404 sau xoá, `index.htm` xác nhận không đổi cả trước/sau — 1036 byte, cùng SHA-256 như mục audit trước) xác nhận:

* **`PHP_VERSION: 8.3.30`** — PHP Selector đã được chuyển sang 8.3 thành công. Gate "web PHP phải là 8.3.x" **đạt**.
* `PHP_SAPI: litespeed`, `SERVER_SOFTWARE: LiteSpeed` — xác nhận lại LiteSpeed.
* `DOCUMENT_ROOT: /home/erxwskxohosting/public_html` — không đổi.
* `memory_limit 512M`, `max_execution_time/max_input_time/max_input_vars 3000`, `upload_max_filesize 10240M`, `post_max_size 512M`, `open_basedir` rỗng — giống hệt cấu hình đã ghi nhận ở PHP 8.1 web, chỉ đổi version.

Extension matrix (PHP 8.3 web, phân loại theo yêu cầu):

| Extension | Trạng thái | Phân loại |
|---|---|---|
| curl, mbstring, mysqli, pdo_mysql, dom, xml, gd, exif, fileinfo | PRESENT | — |
| zip | ABSENT | RECOMMENDED BUT NON-BLOCKING (Composer tự fallback qua `unzip` binary) |
| intl | ABSENT | RECOMMENDED BUT NON-BLOCKING (không bắt buộc cho WP/WC core) |
| imagick | ABSENT | RECOMMENDED BUT NON-BLOCKING (GD có sẵn làm fallback xử lý ảnh) |
| opcache | ABSENT | RECOMMENDED BUT NON-BLOCKING (chỉ ảnh hưởng hiệu năng) |
| sodium | ABSENT | RECOMMENDED BUT NON-BLOCKING (WordPress core tự bundle `sodium_compat`) |

**Không có REQUIRED BLOCKER nào.**

### DNS và SSL — DNS đã đúng, SSL CHƯA hợp lệ

* **DNS A record:** `lylishop.online` → `103.75.184.20` (đúng IP hosting) — **đã được founder xử lý**, không còn trỏ Vercel. AAAA: không có bản ghi (bình thường). NS: `sapa.vclouddns.com`.
* **SSL: CHƯA hợp lệ** *(trạng thái 2026-08-05, đã lỗi thời — xem cập nhật 2026-08-06 bên dưới).* Chứng chỉ hiện phục vụ tại `lylishop.online:443` là **self-signed placeholder** (`subject=CN=localhost`, `issuer=CN=localhost`, không có SAN extension) — không phải chứng chỉ thật cho domain. Nhiều khả năng do hệ thống AutoSSL/Let's Encrypt của panel cần DNS trỏ đúng trước khi phát hành được, và DNS chỉ mới đúng gần đây. **Cần hành động panel:** trigger phát hành/gia hạn SSL (Let's Encrypt hoặc tương đương) cho domain qua OnePanel — xem `docs/ONEPANEL-CHECKLIST.md`. **Cập nhật 2026-08-06: SSL ĐÃ HỢP LỆ** — xác minh thật (Python ssl + curl `--resolve https://lylishop.online`) cho `lylishop.online` với SAN đúng domain (Let's Encrypt/AutoSSL). Mục "SSL phải hợp lệ" đã đạt; chi tiết bằng chứng tại `docs/PRODUCTION-STATUS.md`.
* Vercel edge (`216.198.79.1`) vẫn phản hồi `403` khi ép Host header `lylishop.online` — cấu hình phía Vercel không bị đụng tới, chỉ là quan sát read-only, không có hành động nào thực hiện trên Vercel.
* `public_html` xác nhận vẫn chỉ có `index.htm` — không có file nào khác còn sót lại.

### `.env` production — ĐÃ UPLOAD

* Đường dẫn: `/home/erxwskxohosting/apps/lylishop/shared/.env`.
* Thư mục `shared/` tạo với quyền `700`; file `.env` quyền `600`; owner `erxwskxohosting:erxwskxohosting`; xác nhận là regular file, không phải symlink; resolved path nằm ngoài `public_html`.
* Không có `.env` nào tồn tại từ trước tại đường dẫn này (không có conflict, không ghi đè gì).
* Toàn vẹn transfer xác nhận PASS bằng cách so sánh SHA-256 hai phía — **giá trị hash không được in ra** ở bất kỳ đâu, chỉ kết quả so khớp.
* Parse các key bắt buộc: `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`, `WP_ENV`, `WP_HOME`, `WP_SITEURL` — **tất cả present và non-empty (PASS)**.
* `WP_ENV` = `production` ✓ khớp yêu cầu. `WP_HOME` = `https://lylishop.online` ✓ khớp yêu cầu. `WP_SITEURL` (dạng nguồn `${WP_HOME}/wp`) resolve thành `https://lylishop.online/wp` ✓ khớp yêu cầu.

### Kết nối database — PASS

* Kết nối: **PASS**. Máy chủ: **MariaDB 11.4.10-MariaDB-cll-lve-log**.
* Database đã chọn: **PASS**.
* Charset: `utf8mb4`. Collation: `utf8mb4_general_ci` *(trạng thái 2026-08-05 — đã lỗi thời, xem cập nhật 2026-08-06 cuối tài liệu)* — **lưu ý lịch sử:** phát hiện này đọc server default mà không chọn đúng schema; xác nhận lại 2026-08-06 trên đúng schema Lyli cho kết quả `utf8mb4` / `utf8mb4_unicode_ci` — **đúng khuyến nghị ban đầu, không cần đổi**.
* Số bảng ban đầu: **0** — safety gate PASS ("zero application tables, probe authorized").
* **Privilege probe: PASS toàn bộ** — CREATE TABLE, INSERT, SELECT (xác minh đúng giá trị ghi vào), UPDATE, CREATE INDEX, ALTER TABLE, DROP TABLE đều thành công trên một bảng tên ngẫu nhiên (prefix `privtest_` + 16 hex ngẫu nhiên), giới hạn trong đúng database Lyli. Không test CREATE/DROP DATABASE, quyền toàn cục, FILE/SUPER, hay truy cập database khác.
* Sau khi DROP, số bảng quay lại đúng **0** — xác nhận dọn dẹp sạch, không còn bảng tạm nào.
* Không có `DB_PASSWORD`, DSN đầy đủ, hay thông báo lỗi mang credential nào được in ra trong toàn bộ quá trình.

### Repository re-validation — PASS toàn bộ (lặp lại độc lập với lần trước)

| Check | Kết quả |
|---|---|
| `composer validate` | PASS |
| `composer install --dry-run` (từ `composer.lock` đã commit) | PASS |
| `composer audit --locked` | PASS — không có advisory |
| `php -l` — 11/11 file `.php` | PASS |
| `bash -n` — 7/7 file `.sh` | PASS |
| JSON parse — `composer.json` | PASS |
| YAML parse — `.github/workflows/validate.yml` (công cụ local, remote không có PyYAML) | PASS |
| Secret/sensitivity scan | PASS — sạch |
| `.env`, `vendor/`, XLSX không được track | PASS |

### CI — quan sát thật, không suy đoán

Không có commit mới trước bước này nên không có run mới cần chờ — dùng lại run đã quan sát cho đúng HEAD hiện tại (`2dad3aae41...`): **run ID `30915464847`, conclusion `success`**, xác nhận lại qua GitHub API tại thời điểm viết báo cáo này (vẫn `completed`/`success`, không phải cache cũ).

### Bug tìm thấy trong review deployment readiness — ĐÃ SỬA

`scripts/production-deploy.sh` dùng `--path=~/${RELEASE_PATH}/web/wp` cho lệnh `wp core update-db` (2 chỗ) — sai so với quy ước Bedrock: `--path` của WP-CLI phải trỏ vào thư mục chứa `wp-config.php` (tức `web/`), không phải `web/wp` (chỉ chứa core WordPress, không có `wp-config.php` của Bedrock). Các lệnh `wp maintenance-mode` khác trong cùng file đã dùng đúng `.../web`. Đã sửa cả 2 chỗ về `--path=~/${RELEASE_PATH}/web` — sửa hẹp, không đổi logic khác của script.

### Còn lại sau phiên 2026-08-05

1. ~~**SSL chưa hợp lệ cho domain**~~ — **ĐÃ GIẢI QUYẾT 2026-08-06:** SSL hợp lệ cho `lylishop.online` (xác minh Python ssl + curl `--resolve`; SAN đúng domain). Chi tiết: `docs/PRODUCTION-STATUS.md`.
2. ~~Xác nhận với founder về collation `utf8mb4_general_ci`~~ — **ĐÃ GIẢI QUYẾT 2026-08-06:** schema Lyli thực tế là `utf8mb4_unicode_ci` (probe đúng schema; KQ cũ là server default). Không cần đổi.
3. Màu nền/kem cuối cùng và thiết kế placeholder ảnh — vẫn mở theo `docs/THEME-DECISION-BRIEF.md`, không liên quan cài đặt kỹ thuật.

## Ranh giới credential (nhắc lại, không đổi)

Không có giá trị `DB_NAME`/`DB_USER`/`DB_PASSWORD`/`DB_HOST` nào được in ra, log lại hay lưu trong repository ở bất kỳ bước nào của phiên 2026-08-05. `.env` thật đã được upload lên `commerce-host` (ngoài repository, ngoài `public_html`) — không copy vào repo, không copy vào release artifact. Danh sách biến môi trường: `docs/CREDENTIAL-HANDOFF.md`.
