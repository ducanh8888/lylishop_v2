# CREDENTIAL HANDOFF — Lyli Shop

Tài liệu này ghi **tên biến và vị trí file**, không bao giờ ghi giá trị thật. Không có credential nào được tạo, đọc, hay lưu trong repository ở bất kỳ thời điểm nào. Credential được cấp ngoài GitHub và ghi trực tiếp qua `commerce-host`.

## File secret sản xuất (chưa tồn tại)

```
/home/erxwskxohosting/apps/lylishop/shared/.env
```

File này **chưa được tạo**. Nó sẽ được founder/developer ghi trực tiếp trên host qua SSH (`commerce-host`) khi bắt đầu cài đặt thật — không bao giờ đi qua Git, không bao giờ nằm trong release artifact (xem `.gitignore`, `docs/DEPLOYMENT.md` mục "Giữ nguyên dữ liệu chia sẻ").

## Biến môi trường cần trong tương lai

Tên biến khớp với `.env.example` đã có trong repo — không đổi tên ở đây.

| Biến | Mục đích | Nguồn giá trị thật |
|---|---|---|
| `WP_ENV` | `production` (không có `staging`) | Cố định, không phải secret |
| `WP_HOME` | `https://lylishop.online` | Cố định, không phải secret |
| `WP_SITEURL` | `${WP_HOME}/wp` | Cố định, không phải secret |
| `DB_NAME` | Tên database WooCommerce | Founder tạo qua OnePanel (`docs/ONEPANEL-CHECKLIST.md`) |
| `DB_USER` | User MySQL cho database trên | Founder tạo qua OnePanel |
| `DB_PASSWORD` | Mật khẩu user MySQL trên | Founder tạo qua OnePanel, không chia sẻ ngoài kênh an toàn |
| `DB_HOST` | Thường là `localhost` trên shared hosting này | Xác nhận khi database được tạo |
| `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`, `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT` | WordPress unique keys/salts | Sinh ngẫu nhiên lúc cài đặt (không phải do founder cung cấp — có thể agent tự sinh bằng WP-CLI hoặc trình sinh salt, không cần founder nhập tay) |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASSWORD` | FluentSMTP | Founder cung cấp khi cấu hình email |
| `SEPAY_API_KEY`, `SEPAY_WEBHOOK_SECRET` | Cổng thanh toán SePay | Founder cung cấp khi đăng ký SePay |
| `DISALLOW_FILE_MODS`, `DISALLOW_FILE_EDIT` | `true` trên production | Cố định, không phải secret |

## Không thuộc phạm vi `.env` nhưng cũng là credential tương lai

* Mật khẩu tài khoản WordPress Administrator (`developer_admin`) — tạo lúc `wp core install`, không lưu trong `.env`.
* License key của plugin thương mại (WooCommerce Product Bundles, Smart Coupons, SePay Gateway, Vietnam Store Toolkit nếu trả phí) — lưu theo cơ chế riêng của từng plugin, không lưu trong repo (`docs/PLUGIN-MANIFEST.md`).
* Thông tin tài khoản ngân hàng cho BACS/SePay — cấu hình trong WooCommerce settings sau khi cài, không phải biến môi trường.
* Backup-service credentials (nếu UpdraftPlus dùng remote storage) — cấu hình trong plugin, không lưu trong repo.

## Quy tắc

* Không commit `.env` thật — `.gitignore` đã chặn `.env` và `.env.*` (trừ `.env.example`).
* Không dán giá trị thật vào bất kỳ file Markdown, script, hay commit message nào.
* Credential được nhập trực tiếp trên `commerce-host` (qua `nano`/`vi`/`scp` một file cục bộ không commit) khi tới bước cài đặt thật — không qua GitHub dưới bất kỳ hình thức nào, kể cả GitHub Secrets, trừ khi có quyết định riêng cho phép sau này.
* Agent thực hiện bước cài đặt sau này **xác nhận kết nối database bằng cách kiểm tra kết quả kết nối (thành công/thất bại)**, không đọc lại hay in ra giá trị `DB_PASSWORD`.
