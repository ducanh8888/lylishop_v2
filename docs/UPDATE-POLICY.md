# UPDATE POLICY

Nguồn: PLAN.md mục 14, TECH_STACK.md mục 13.

## Không auto-update vô điều kiện trên production

Không cho production tự cập nhật: WordPress major version, WooCommerce major version, theme, plugin ảnh hưởng checkout/payment/order/affiliate (PLAN.md mục 14.1). `AUTOMATIC_UPDATER_DISABLED` và `WP_AUTO_UPDATE_CORE=false` đã đặt ở `config/environments/production.php`.

## Quy trình update

1. Renovate/Dependabot hoặc kiểm tra thủ công phát hiện phiên bản mới.
2. Cập nhật `composer.lock` (không sửa version trực tiếp trong `wp-admin`).
3. Deploy lên local (DDEV) hoặc staging.
4. Chạy smoke test (`tests/smoke/`).
5. Test sản phẩm, giỏ hàng, voucher, checkout, COD, chuyển khoản QR (SePay), email, admin (`tests/checkout/`, `tests/payment/`).
6. Developer review changelog của package vừa update.
7. Backup production (`scripts/backup.sh`) — bắt buộc trước khi deploy.
8. Deploy production (manual approval).
9. Chạy migration (`wp core update-db` qua PHP 8.3).
10. Kiểm tra health (`scripts/health-check.sh`).
11. Có phương án rollback sẵn sàng (symlink `current` về release trước — xem `docs/DEPLOYMENT.md`).

## Ưu tiên bảo mật

Security release nghiêm trọng (WordPress/WooCommerce/plugin) được ưu tiên triển khai trong vòng 24 giờ sau khi vượt qua smoke test (TECH_STACK.md mục 13.2). Không "pin phiên bản" vô thời hạn khi có security release.

Composer trên host hiện là 2.8.10 với các CVE đã biết (`docs/HOSTING-AUDIT.md` mục 7) — build production phải dùng Composer đã self-update (2.10.2+) trên máy dev/CI, không dùng bản hệ thống trên host để build.

## Ma trận test bắt buộc trước mỗi update lớn

Xem TECH_STACK.md mục 15 — sản phẩm (simple/variable/hết hàng/sale), bundle, cart (guest/login), checkout (mobile/desktop/validation SĐT), địa chỉ Việt Nam, shipping, COD, SePay (QR/webhook/duplicate), coupon, stock, refund, email, role, backup, cache, update, mobile.

## Không dùng `wp-admin` để cập nhật production

`DISALLOW_FILE_MODS=true` chặn UI cài/update/xóa plugin-theme trực tiếp trên production (TECH_STACK.md mục 13.1). Mọi thay đổi package đi qua `composer.lock` + deploy pipeline.
