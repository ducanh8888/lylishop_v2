# ONEPANEL CHECKLIST — Lyli Shop

Hành động thủ công **founder/developer phải tự làm qua giao diện OnePanel hoặc DNS provider** — agent không có quyền hoặc không nên thực hiện các việc này. Cập nhật 2026-08-05 sau khi có bằng chứng thật từ `docs/INSTALLATION-PREPARATION.md`. Không còn mục nào liên quan staging (dự án production-only, xem `docs/DEPLOYMENT.md`).

## Bắt buộc trước khi go-live công khai thật (còn mở)

1. **Phát hành/gia hạn chứng chỉ SSL thật cho `lylishop.online`.** Xác nhận thật (2026-08-05): domain hiện phục vụ một **chứng chỉ self-signed placeholder** (`subject=CN=localhost`, `issuer=CN=localhost`, không có SAN) — không phải chứng chỉ hợp lệ cho tên miền. DNS đã trỏ đúng (xem mục "Đã giải quyết" bên dưới), nên hệ thống AutoSSL/Let's Encrypt của panel giờ có thể chạy domain validation — vào OnePanel → SSL, trigger phát hành/gia hạn chứng chỉ cho `lylishop.online`, xác nhận auto-renew bật.

## Cần founder xác nhận (không phải lỗi kỹ thuật, chỉ là quyết định)

2. **Collation database thực tế là `utf8mb4_general_ci`**, khác với khuyến nghị ban đầu `utf8mb4_unicode_ci` trong tài liệu trước đây. Không chặn cài đặt — WordPress/WooCommerce chạy bình thường với `utf8mb4_general_ci`. Founder xác nhận giữ nguyên hay đổi lại trước khi chạy `wp core install` (đổi collation sau khi đã có dữ liệu sẽ phức tạp hơn).

## Tuỳ chọn / theo nhu cầu vận hành

3. **Cron job cho WP-Cron thật** (khi WordPress đã cài) — dùng `/opt/alt/php83/usr/bin/php`, không dùng `php` mặc định (mặc định vẫn là 8.1 trong context cron, độc lập với PHP Selector của web — xác nhận ở `docs/HOSTING-AUDIT.md`).
4. **Backup off-site** (nếu OnePanel có tính năng backup riêng ngoài UpdraftPlus) — cấu hình theo nhu cầu, không bắt buộc để bắt đầu cài đặt.

## Đã giải quyết (bằng chứng thật, không cần hành động panel nữa)

* ~~Trỏ DNS `lylishop.online` về hosting account~~ — **ĐÃ XONG** (founder). Xác nhận 2026-08-05: A record trỏ đúng `103.75.184.20`.
* ~~Chọn PHP 8.3 làm web runtime~~ — **ĐÃ XONG** (founder). Xác nhận 2026-08-05: probe web trả về `PHP_VERSION: 8.3.30`.
* ~~Tạo database production + user MySQL + grant~~ — **ĐÃ XONG** (founder). Xác nhận 2026-08-05: kết nối PASS, database rỗng (0 bảng), privilege probe (CREATE/INSERT/SELECT/UPDATE/CREATE INDEX/ALTER/DROP) PASS toàn bộ trong đúng phạm vi database Lyli.
* ~~Xác nhận module PHP 8.3 web~~ — **ĐÃ XÁC MINH** 2026-08-05: cùng bộ module như PHP 8.1 web (thiếu `zip`/`intl`/`imagick`/`opcache`/`sodium`, đều có fallback, không phải hard blocker) — chi tiết ở `docs/INSTALLATION-PREPARATION.md`.
* ~~Xác định web server là Apache hay LiteSpeed~~ — đã xác nhận **LiteSpeed thật**. Dùng LiteSpeed Cache, không dùng WP Super Cache.
* ~~Kiểm tra symlink có hoạt động qua web server không~~ — đã xác nhận **có**.
* ~~Staging subdomain~~ — không áp dụng, dự án production-only.
