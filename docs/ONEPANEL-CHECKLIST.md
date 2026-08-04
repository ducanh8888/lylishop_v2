# ONEPANEL CHECKLIST — Lyli Shop

Hành động thủ công **founder/developer phải tự làm qua giao diện OnePanel hoặc DNS provider** — agent không có quyền hoặc không nên thực hiện các việc này. Cập nhật 2026-08-04 sau khi có bằng chứng thật từ `docs/INSTALLATION-PREPARATION.md`. Không còn mục nào liên quan staging (dự án production-only, xem `docs/DEPLOYMENT.md`).

## Bắt buộc trước khi cài đặt thật

1. **Trỏ DNS `lylishop.online` về hosting account này.** Xác nhận thật (2026-08-04): domain hiện trỏ về `216.198.79.1` (Vercel), **không phải** IP hosting `103.75.184.20`. Founder cần xác nhận đây có phải là cấu hình cố ý hay không (site khác đang chạy ở Vercel?) trước khi đổi DNS — agent không tự ý thay đổi bản ghi DNS.
2. **Chọn PHP 8.3 làm web runtime cho domain (PHP Selector).** Xác nhận thật: web hiện đang chạy PHP 8.1.34, chưa phải 8.3, dù CLI PHP 8.3 đã có sẵn tại `/opt/alt/php83/usr/bin/php`. Vào OnePanel → PHP Selector → chọn 8.3 cho `lylishop.online`.
3. **Tạo database production.** Chỉ tạo được qua OnePanel UI (không có API/CLI cho việc này trên tài khoản này — xác nhận ở `docs/HOSTING-AUDIT.md`). Dùng charset `utf8mb4`, collation `utf8mb4_unicode_ci`.
4. **Tạo user MySQL riêng cho database trên.** Không dùng user chung với site khác nếu tài khoản hosting có nhiều site.
5. **Cấp quyền (grant) chỉ trên database Lyli** — không cấp quyền toàn cục/toàn server cho user này.
6. **Ghi lại DB host thật** (thường `localhost` trên shared hosting dạng này, nhưng cần xác nhận trong màn hình tạo database của panel — không suy đoán).

## Cần xác nhận (đã có bằng chứng một phần, nên double-check qua panel)

7. **Xác nhận SSL cho `lylishop.online`.** Bằng chứng gián tiếp: kết nối HTTPS thẳng tới IP hosting với SNI `lylishop.online` thành công (handshake OK) — nghĩa là panel đã có sẵn vhost/chứng chỉ cho domain này. Vẫn nên xác nhận trực tiếp trong OnePanel → SSL rằng chứng chỉo hợp lệ, chưa hết hạn, và auto-renew (Let's Encrypt hoặc tương đương) đang bật.
8. **Xác nhận module PHP 8.3 web sau khi chuyển ở bước 2.** Đã biết module PHP 8.1 web (`docs/INSTALLATION-PREPARATION.md`) nhưng chưa biết PHP 8.3 web có cùng bộ module hay không — đặc biệt `zip`, `intl`, `imagick`, `opcache`, `sodium` (thiếu ở 8.1). Sau khi chuyển sang 8.3, vào PHP Selector → PHP Extensions để kiểm tra và bật thêm nếu có thể; có fallback cho hầu hết các module này (xem `docs/HOSTING-AUDIT.md` mục 2.2), không phải hard blocker nếu thiếu.

## Tuỳ chọn / theo nhu cầu vận hành

9. **Cron job cho WP-Cron thật** (khi WordPress đã cài) — dùng `/opt/alt/php83/usr/bin/php`, không dùng `php` mặc định (mặc định là 8.1 trong context cron — xác nhận ở `docs/HOSTING-AUDIT.md`).
10. **Backup off-site** (nếu OnePanel có tính năng backup riêng ngoài UpdraftPlus) — cấu hình theo nhu cầu, không bắt buộc để bắt đầu cài đặt.

## Không còn cần thiết (đã giải quyết bằng bằng chứng thật, không cần hành động panel)

* ~~Xác định web server là Apache hay LiteSpeed~~ — đã xác nhận **LiteSpeed thật** qua probe (`docs/INSTALLATION-PREPARATION.md`). Dùng LiteSpeed Cache, không dùng WP Super Cache.
* ~~Kiểm tra symlink có hoạt động qua web server không~~ — đã xác nhận **có** qua probe symlink thật.
* ~~Staging subdomain~~ — không áp dụng, dự án production-only.
