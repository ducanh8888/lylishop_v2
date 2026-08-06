# ONEPANEL CHECKLIST — Lyli Shop

Hành động thủ công **founder/developer phải tự làm qua giao diện OnePanel hoặc DNS provider** — agent không có quyền hoặc không nên thực hiện các việc này. Cập nhật 2026-08-05 sau khi có bằng chứng thật từ `docs/INSTALLATION-PREPARATION.md`. Không còn mục nào liên quan staging (dự án production-only, xem `docs/DEPLOYMENT.md`).

## Bắt buộc trước khi go-live công khai thật (còn mở)

1. ~~**Phát hành/gia hạn chứng chỉ SSL thật cho `lylishop.online`.**~~ — **ĐÃ XONG.** Xác nhận thật (2026-08-05): domain trước đây phục vụ một **chứng chỉ self-signed placeholder** (`subject=CN=localhost`, `issuer=CN=localhost`, không có SAN). Xác nhận lại (2026-08-06): **SSL đã hợp lệ cho `lylishop.online`** — Let's Encrypt/AutoSSL với SAN đúng domain, xác minh qua Python ssl + curl `--resolve` (xem `docs/PRODUCTION-STATUS.md`). Không còn là mục bắt buộc mở; chỉ giữ lại yêu cầu theo dõi auto-renew của panel.

## Cần founder xác nhận (không phải lỗi kỹ thuật, chỉ là quyết định)

2. ~~**Collation database cần founder xác nhận** (`utf8mb4_general_ci` so với khuyến nghị `utf8mb4_unicode_ci`).~~ — **ĐÃ GIẢI QUYẾT.** Xác nhận lại (2026-08-06): schema production Lyli có `DEFAULT_CHARACTER_SET_NAME=utf8mb4` / `DEFAULT_COLLATION_NAME=utf8mb4_unicode_ci` — đúng khuyến nghị ban đầu. Không cần đổi; database sẵn sàng cho `wp core install`.

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
