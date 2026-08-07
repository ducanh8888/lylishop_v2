# UPDATE POLICY

Nguồn: PLAN.md mục 14, TECH_STACK.md mục 13.

**Amendment 2026-08-03:** Không có staging. Local development (DDEV) và automated checks là bước kiểm tra duy nhất trước production; quy trình dưới đây đã cập nhật lại bước 3.

## Không auto-update vô điều kiện trên production

Không cho production tự cập nhật: WordPress major version, WooCommerce major version, theme, plugin ảnh hưởng checkout/payment/order/affiliate (PLAN.md mục 14.1). `AUTOMATIC_UPDATER_DISABLED` và `WP_AUTO_UPDATE_CORE=false` đã đặt ở `config/environments/production.php`.

## Quy trình update

1. Renovate/Dependabot hoặc kiểm tra thủ công phát hiện phiên bản mới.
2. Cập nhật `composer.lock` (không sửa version trực tiếp trong `wp-admin`).
3. Deploy lên local (DDEV) và chạy `scripts/production-preflight.sh` (dry-run) trên máy dev/CI — không có staging riêng.
4. Chạy smoke test (`tests/smoke/`).
5. Test sản phẩm, giỏ hàng, voucher, checkout, COD, chuyển khoản QR (SePay), email, admin (`tests/checkout/`, `tests/payment/`).
6. Developer review changelog của package vừa update.
7. Backup production (`scripts/production-backup.sh`) — bắt buộc trước khi deploy.
8. Deploy production theo quy trình 10 bước ở `docs/DEPLOYMENT.md` (manual approval, maintenance mode, release bất biến, rollback sẵn sàng).
9. Chạy migration (`wp core update-db` qua PHP 8.3).
10. Kiểm tra health (`scripts/production-health-check.sh`).
11. Có phương án rollback sẵn sàng (`scripts/production-rollback.sh` — symlink `current` về release trước, xem `docs/DEPLOYMENT.md`).

## Chính sách nâng cấp theme (2026-08-04)

Theme V1 là Botiga Free (`docs/THEME-DECISION.md`). **Botiga Pro không được phép mua, tải hay thiết kế theo hướng cần nó.** Chỉ cân nhắc nâng cấp trả phí sau này nếu có tài liệu ghi rõ một khoảng trống tính năng cụ thể, và bản trả phí đó rẻ hơn/an toàn hơn rõ rệt so với việc tự viết/duy trì code tương đương — quyết định này vẫn cần founder phê duyệt, không tự động.

Đổi theme nền (kể cả sang Blocksy Free hoặc Storefront ở `docs/THEME-DECISION.md`) chỉ diễn ra khi có FAIL đã ghi lại trong `docs/THEME-COMPATIBILITY-GATE.md` mà không thể khắc phục bằng cấu hình, hook nhỏ trong `shop-child`, CSS phạm vi hẹp, hoặc patch tương thích an toàn cho plugin — không đổi theme chỉ vì theme khác có nhiều tùy chọn hơn hoặc demo đẹp hơn.

## Ưu tiên bảo mật

Security release nghiêm trọng (WordPress/WooCommerce/plugin) được ưu tiên triển khai trong vòng 24 giờ sau khi vượt qua smoke test (TECH_STACK.md mục 13.2). Không "pin phiên bản" vô thời hạn khi có security release.

Composer trên host hiện là 2.8.10 với các CVE đã biết (`docs/HOSTING-AUDIT.md` mục 7) — build production phải dùng Composer đã self-update (2.10.2+) trên máy dev/CI, không dùng bản hệ thống trên host để build.

## Ma trận test bắt buộc trước mỗi update lớn

Xem TECH_STACK.md mục 15 — sản phẩm (simple/variable/hết hàng/sale), bundle, cart (guest/login), checkout (mobile/desktop/validation SĐT), địa chỉ Việt Nam, shipping, COD, SePay (QR/webhook/duplicate), coupon, stock, refund, email, role, backup, cache, update, mobile.

## Không dùng `wp-admin` để cập nhật code production

`DISALLOW_FILE_MODS=true` và `DISALLOW_FILE_EDIT=true` chặn cài/update/xóa hoặc sửa mã plugin-theme trực tiếp trên production. Mọi thay đổi package đi qua version pin trong `composer.lock`, clean build và release artifact.
