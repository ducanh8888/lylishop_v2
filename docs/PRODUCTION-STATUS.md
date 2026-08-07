# PRODUCTION STATUS — Lyli Shop

Cập nhật: 2026-08-07. Đây là trạng thái production thực tế, không phải kế hoạch.

## Runtime hiện tại

| Mục | Trạng thái |
|---|---|
| Public site | `https://lylishop.online` đang public, HTTPS hợp lệ |
| Deployed source commit | `98ef36dd8783fac43d0afedb904b68f18080214b` |
| Active release | `apps/lylishop/releases/20260807220304` |
| `current` | trỏ tới release `20260807220304` |
| Public document root | `public_html` → `apps/lylishop/current/web` |
| Rollback release | `apps/lylishop/releases/20260807214157` |
| Pre-content backup | `apps/lylishop/shared/backups/20260807214542` |
| WordPress | 7.0.2, locale `vi`, timezone `Asia/Ho_Chi_Minh` |
| Storefront | WooCommerce 10.9.4; Botiga 2.4.7; `shop-child` active |
| WP-CLI trên host | `/opt/alt/php83/usr/bin/php /usr/bin/wp --path=apps/lylishop/current/web/wp` |
| Deployment gate | WSL/local validation; GitHub Actions chỉ cung cấp thông tin |

## Nội dung đã publish

- 9 sản phẩm thật từ handoff, đúng slug/giá/ảnh/mô tả/FAQ; sản phẩm ở chế độ catalogue-only và không thể checkout.
- 5 bài blog thật; 63/63 ảnh nguồn tải công khai thành công.
- Trang chủ, Giới thiệu, Liên hệ, Đặt mẫu theo yêu cầu, Blog, Chính sách bảo mật và Điều khoản đã publish.
- 5 danh mục sản phẩm, menu chính và header hai hàng đã cấu hình.
- Shipping và Returns vẫn là bản nháp vì chưa có nội dung nguồn được duyệt.
- WooCommerce dùng pretty permalink `/%postname%/`; chế độ Coming Soon đã tắt để public catalogue.
- Mọi payment gateway vẫn tắt.

## Runtime và bảo mật

- Bedrock ánh xạ đúng `web/app/plugins` và `web/app/mu-plugins`; Roots Bedrock Autoloader nạp các MU plugin nội bộ.
- Lệnh `wp lyli` có cả `bootstrap` và `content`; Lyli settings/admin hooks hoạt động.
- Shop Owner chỉnh được Gutenberg, sản phẩm, bài viết, menu, logo/Customizer và Lyli Shop settings; không có quyền sửa/cài code plugin/theme.
- `.env` mode 600, public request trả 403; SQL và directory listing không public.
- `shared` mode 711 chỉ cho traverse; `shared/uploads` và các thư mục ảnh mode 755 để web server phục vụ media; `.env` vẫn không đọc được.
- Home, Shop, Cart, My Account và admin login trả HTTP 200, không có PHP fatal. Checkout với giỏ trống chuyển về Cart theo WooCommerce.
- Viewport emulation 375px: document width bằng viewport width, không có horizontal overflow.

## Chưa phải commerce launch hoàn chỉnh

Cần chủ shop chốt shipping, returns, vận chuyển, email giao dịch, phương thức thanh toán và chạy thử đơn hàng thật trước khi bật checkout. Catalogue hiện dùng liên hệ/Zalo và không nhận thanh toán trực tuyến.

## Mốc liên quan

- Commit `40635df`: nhập gói nội dung handoff và importer idempotent.
- Commit `98ef36d`: sửa responsive mobile và backup qua WP-CLI, không shell-source `.env`.
- Release `20260807220304`: release public hiện tại sau smoke/security gate.
