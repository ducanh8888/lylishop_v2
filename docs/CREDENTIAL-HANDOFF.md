# CREDENTIAL HANDOFF — Lyli Shop

Tài liệu này ghi **tên biến và vị trí file**, không bao giờ ghi giá trị thật. Không có credential nào được tạo, đọc, hay lưu trong repository ở bất kỳ thời điểm nào. Credential được cấp ngoài GitHub và ghi trực tiếp qua `commerce-host`.

## File secret sản xuất — ĐÃ UPLOAD (2026-08-05)

```
/home/erxwskxohosting/apps/lylishop/shared/.env
```

**Trạng thái:** đã tồn tại trên `commerce-host`, upload trực tiếp từ file cục bộ `C:\Users\ADMIN\.secrets\lylishop-prod.env` (không đi qua Git, không nằm trong repository, không nằm trong bất kỳ release artifact nào — xem `.gitignore`, `docs/DEPLOYMENT.md` mục "Giữ nguyên dữ liệu chia sẻ").

| Thuộc tính | Giá trị |
|---|---|
| Đường dẫn | `/home/erxwskxohosting/apps/lylishop/shared/.env` |
| Owner | `erxwskxohosting:erxwskxohosting` |
| Quyền file | `600` |
| Quyền thư mục `shared/` | `700` |
| Loại | Regular file (không phải symlink) |
| Nằm ngoài `public_html`? | Có, xác nhận qua resolved path |
| Toàn vẹn transfer | Xác nhận PASS (so sánh SHA-256 hai phía nội bộ — giá trị hash không được ghi vào tài liệu này hay bất kỳ đâu) |
| Required keys present/non-empty | Tất cả `DB_NAME`/`DB_USER`/`DB_PASSWORD`/`DB_HOST`/`WP_ENV`/`WP_HOME`/`WP_SITEURL` — PASS |

Chi tiết đầy đủ quá trình xác minh: `docs/INSTALLATION-PREPARATION.md` mục "Cập nhật 2026-08-05".

## Biến môi trường cần trong tương lai

Tên biến khớp với `.env.example` đã có trong repo — không đổi tên ở đây.

| Biến | Mục đích | Nguồn giá trị thật |
|---|---|---|
| `WP_ENV` | `production` (không có `staging`) | Cố định, không phải secret |
| `WP_HOME` | `https://lylishop.online` | Cố định, không phải secret |
| `WP_SITEURL` | `${WP_HOME}/wp` | Cố định, không phải secret |
| `DB_NAME` | Tên database WooCommerce | **Đã tạo bởi founder, đã có trong `.env` production** (2026-08-05) — giá trị không ghi ở đây |
| `DB_USER` | User MySQL cho database trên | **Đã tạo, đã có trong `.env` production** |
| `DB_PASSWORD` | Mật khẩu user MySQL trên | **Đã tạo, đã có trong `.env` production** — không chia sẻ ngoài kênh an toàn |
| `DB_HOST` | Host kết nối MySQL/MariaDB | **Đã có trong `.env` production** — kết nối đã xác minh PASS (`docs/INSTALLATION-PREPARATION.md`) |
| `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`, `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT` | WordPress unique keys/salts | Sinh ngẫu nhiên lúc cài đặt (không phải do founder cung cấp — có thể agent tự sinh bằng WP-CLI hoặc trình sinh salt, không cần founder nhập tay) |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASSWORD` | FluentSMTP | Founder cung cấp khi cấu hình email |
| `SEPAY_API_KEY`, `SEPAY_WEBHOOK_SECRET` | Cổng thanh toán SePay | Founder cung cấp khi đăng ký SePay |
| `DISALLOW_FILE_MODS`, `DISALLOW_FILE_EDIT` | `true` trên production | Cố định code theo release; không phải secret |

## Không thuộc phạm vi `.env` nhưng cũng là credential tương lai

* WP_ADMIN_EMAIL / WP_ADMIN_USER / WP_ADMIN_PASSWORD — **installation inputs** chỉ dùng cho `wp core install` (được đọc từ `.env` lúc cài, như `docs/PRODUCTION-INSTALL-RUNBOOK.md` bước 8), **không phải** hằng số cấu hình runtime của WordPress và không được đọc bởi `config/application.php`. Không bao giờ in hoặc expose giá trị của chúng.
* Mật khẩu tài khoản WordPress Administrator — được truyền qua `WP_ADMIN_PASSWORD` ở bước cài đặt, không lưu dưới bất kỳ hình thức nào trong repository.
* License key của plugin thương mại (WooCommerce Product Bundles, Smart Coupons, SePay Gateway, Vietnam Store Toolkit nếu trả phí) — lưu theo cơ chế riêng của từng plugin, không lưu trong repo (`docs/PLUGIN-MANIFEST.md`).
* Thông tin tài khoản ngân hàng cho BACS/SePay — cấu hình trong WooCommerce settings sau khi cài, không phải biến môi trường.
* Backup-service credentials (nếu UpdraftPlus dùng remote storage) — cấu hình trong plugin, không lưu trong repo.

## Quy tắc

* Không commit `.env` thật — `.gitignore` đã chặn `.env` và `.env.*` (trừ `.env.example`).
* Không dán giá trị thật vào bất kỳ file Markdown, script, hay commit message nào.
* Credential được nhập trực tiếp trên `commerce-host` (qua `nano`/`vi`/`scp` một file cục bộ không commit) khi tới bước cài đặt thật — không qua GitHub dưới bất kỳ hình thức nào, kể cả GitHub Secrets, trừ khi có quyết định riêng cho phép sau này.
* Agent thực hiện bước cài đặt sau này **xác nhận kết nối database bằng cách kiểm tra kết quả kết nối (thành công/thất bại)**, không đọc lại hay in ra giá trị `DB_PASSWORD`.
