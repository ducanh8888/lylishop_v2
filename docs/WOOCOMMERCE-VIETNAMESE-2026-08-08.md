# WooCommerce tiếng Việt — 2026-08-08

## Nguyên nhân

WordPress đã lưu site locale `vi`, nhưng production chỉ có language pack `en_US` cho WordPress core và WooCommerce. Tài khoản vận hành duy nhất không có locale riêng, vì vậy giao diện quản trị kế thừa `vi` nhưng thiếu file dịch và vẫn hiển thị nhiều chuỗi tiếng Anh.

## Thay đổi production

- Tạo kho bền vững `apps/lylishop/shared/languages` và nối `apps/lylishop/current/web/app/languages` vào đó.
- Cài language pack WordPress 7.0.2 `vi`, gồm `.mo`, `.l10n.php` và JSON translations cho JavaScript admin.
- Cài language pack WooCommerce 10.9.4 `vi` vào `shared/languages/plugins`.
- Đặt locale tài khoản vận hành ID `1` thành `vi`, không đổi username, password hoặc role.
- Không sửa đơn hàng, sản phẩm, payment gateway, nội dung hoặc cấu hình thương mại.

Language packs là runtime data, không commit binary vào Git. `scripts/production-deploy.sh` đã được cập nhật để mọi release sau tiếp tục nối `shared/languages`; deploy code sẽ không làm mất bản dịch.

## Backup và xác minh

- Backup trước thay đổi: `shared/backups/20260808152708/database.sql.gz`; `gzip -t` PASS.
- Core locale runtime: `vi`; `Dashboard` dịch thành `Trang quản trị`.
- WooCommerce textdomain: `Orders` dịch thành `Đơn hàng`; `Products` dịch thành `Sản phẩm`.
- WP-CLI liệt kê `vi` ở trạng thái `active` cho cả core và WooCommerce.
- MCP Lyli xác nhận site option `WPLANG=vi` và đúng tài khoản vận hành production.

Một số tên riêng, thuật ngữ do plugin bên thứ ba cung cấp hoặc chuỗi chưa được cộng đồng WordPress dịch có thể vẫn còn tiếng Anh. Không nên sửa trực tiếp file translation; cập nhật bằng language pack chính thức.

## Vận hành

Chủ shop đăng xuất và đăng nhập lại hoặc tải lại wp-admin để phiên hiện tại nhận locale mới. Ngôn ngữ cá nhân có thể kiểm tra tại **Thành viên → Hồ sơ → Ngôn ngữ: Tiếng Việt**. Các lần cập nhật WooCommerce cần chạy cập nhật language pack trong `shared/languages` và kiểm tra lại ba chuỗi đại diện nêu trên.
