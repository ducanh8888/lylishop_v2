# VIETNAM STORE TOOLKIT 1.1.2 — PREFLIGHT

Trạng thái: **PLANNED / PREFLIGHT COMPLETE — CHƯA DEPLOY**

Ngày kiểm tra: 2026-08-10

Repo bắt đầu: `2ffff80a2577a49ae24e963d9516492394192ee2`

Tài liệu này là kết quả nghiên cứu và source audit cho lần triển khai kế tiếp. Không có plugin, option, payment gateway, capability, database hay release production nào được thay đổi trong pre-flight này.

## 1. Nguồn phát hành và Composer

Nguồn chính thức được đối chiếu:

- WordPress.org: `https://wordpress.org/plugins/yoohw-vietnam-store-tools/`
- Source/development: `https://github.com/yoohwz/yoohw-vietnam-store-tools`
- Tài liệu: `https://vietnamstore.org/documentation/`
- Chuyên đề VietQR: `https://vietnamstore.org/vietqr-woocommerce/`
- Release thực tế do WPackagist phân phối: WordPress.org SVN tag `1.1.2` và ZIP `https://downloads.wordpress.org/plugin/yoohw-vietnam-store-tools.1.1.2.zip`

GitHub là repository upstream công khai nhưng không có Git tag `1.1.2` tại thời điểm kiểm tra. Vì vậy source audit dùng đúng ZIP/SVN tag mà Composer sẽ cài, không lấy nhánh `main` làm bằng chứng release.

### Kết quả disposable WSL pre-flight

`composer show --all wpackagist-plugin/yoohw-vietnam-store-tools` xác nhận:

| Thuộc tính | Kết quả |
|---|---|
| Exact version | `1.1.2` tồn tại |
| Composer type | `wordpress-plugin` |
| Source | SVN `tags/1.1.2` |
| Dist | WordPress.org ZIP `1.1.2` |
| Installer dependency | `composer/installers ^1 || ^2` |
| Isolated dry-run với lock hiện tại | Thêm đúng 1 package; 0 update; 0 removal; không conflict |
| Isolated install path | `web/app/plugins/yoohw-vietnam-store-tools` |
| PHP 8.3 lint | PASS cho toàn bộ PHP source 1.1.2 |

Kết luận: phát biểu cũ “không có trên WPackagist/không rõ nguồn” là **STALE**. Lần triển khai kế tiếp có thể pin chính xác:

```json
"wpackagist-plugin/yoohw-vietnam-store-tools": "1.1.2"
```

Không dùng repository tự khai báo, ZIP thủ công hay GitHub `main`.

## 2. Yêu cầu và ma trận tương thích trước triển khai

Metadata trong stable archive 1.1.2: WordPress `>=6.3`, `Tested up to: 7.0`, PHP `>=7.4`, WooCommerce `>=8.9`, tested through WooCommerce `10.9`, stable tag `1.1.2`, `Requires Plugins: woocommerce`. Directory WordPress.org hiện quảng bá tested through 7.0.3; production Lyli 7.0.2 nằm trong cùng nhánh, nhưng đây không phải chứng nhận patch-specific nên kiểm thử chính xác trên site vẫn bắt buộc.

| Thành phần Lyli | Bằng chứng/kết luận pre-flight | Gate lần triển khai kế tiếp |
|---|---|---|
| WordPress 7.0.2 | Nằm trong nhánh WordPress 7.0 mà upstream công bố | Bootstrap + checkout thật, không PHP error mới |
| WooCommerce 10.9.4 | `WC requires >=8.9`, `WC tested up to 10.9` | Xác minh patch 10.9.4 bằng Classic Cart/Checkout/order |
| PHP 8.3 | Cao hơn minimum 7.4; lint bằng PHP 8.3.6 PASS | Bootstrap bằng PHP 8.3 production |
| MariaDB 11.4.10 hiện tại | Không tạo bảng riêng; dùng option, user meta và Woo order API | So sánh DB/options trước-sau activation; không đổi storage mode |
| Botiga 2.4.7 + `shop-child` | Không có theme dependency trực tiếp | Visual checkout/account/email; không override plugin source |
| Bedrock custom paths | Composer install thử vào đúng `web/app/plugins` | Artifact phải chứa plugin; WordPress bootstrap phải list plugin |
| `site-policy` / `shop_owner` | Plugin dùng `manage_woocommerce`; role hiện có capability này | Kiểm tra URL trực tiếp và menu sau deploy; không cấp `manage_options` |
| Classic Cart/Checkout | Upstream và source dùng hook classic; dự án đang dùng Classic | Address, shipping, BACS, validation và place-order regression |
| Legacy order storage | Plugin dùng Woo order API và tuyên bố hỗ trợ HPOS lẫn legacy | Giữ legacy; không bật HPOS trong task này |
| Language packs `vi` | Plugin đóng gói bản dịch tiếng Việt | Xác minh wp-admin/validation/email labels tiếng Việt |
| `DISALLOW_FILE_MODS=true` | Cài bằng immutable Composer artifact, không qua wp-admin | Không hiện/không dùng installer/updater trong admin |
| Immutable release | Không có runtime self-update dependency | Build một release, shared `.env`/uploads, rollback nguyên release |

Không có bằng chứng buộc phải bật HPOS, Cart/Checkout Blocks, SePay hoặc carrier connector.

## 3. Bootstrap, hooks và hành vi kích hoạt

- Plugin kiểm tra/load sau WooCommerce và khai báo compatibility cho HPOS cùng Cart/Checkout Blocks.
- Không có `register_activation_hook`, `register_deactivation_hook`, `register_uninstall_hook`, `dbDelta` hoặc custom table.
- Không có REST route riêng. Blocks tích hợp qua WooCommerce Store API hooks.
- Không gọi API dữ liệu hành chính/bank ở runtime; hai dataset được bundle tĩnh.
- Có migration BACS nhỏ chạy một lần trên `init`: đọc `woocommerce_bacs_settings`, chuẩn hóa setting cũ nếu có, cập nhật option và ghi `yoohw_vietnam_store_tools_vietqr_settings_migrated=yes`. Với production hiện tại chưa có `woocommerce_bacs_settings`, activation có thể tạo option rỗng và marker. Backup trước activation là bắt buộc.
- Migration dữ liệu DevVN/GHTK không tự chạy; chỉ chạy từ WooCommerce Status Tools qua nonce/AJAX theo lô.

### Trạng thái hiệu lực ngay sau activation

`is_feature_enabled()` dùng mặc định `yes` khi option chưa tồn tại.

| Tính năng | Mặc định sau activation | Ảnh hưởng tức thời |
|---|---|---|
| Trường địa chỉ Việt Nam | Bật | Đổi cấu trúc/tên/validation địa chỉ Classic Checkout, My Account, cart calculator và admin |
| Chuẩn hóa điện thoại | Bật | Validation và lưu số Việt Nam theo dạng chuẩn |
| Hiển thị vận chuyển cho khách | Bật | Chỉ hiện khi đơn có dữ liệu vận chuyển |
| Công cụ quản lý đơn Việt Nam | Bật | Thêm cột/filter/action vào màn hình đơn |
| Quy trình hóa đơn điện tử | Bật | Thêm workflow quản trị; không tự phát hành/gọi nhà cung cấp |
| Nhận yêu cầu VAT tại checkout | **Tắt** | Không thêm trường VAT cho đơn mới |
| Tra cứu đơn công khai | Option mặc định bật, nhưng không có page/block/shortcode được tạo tự động | Không có endpoint UI công khai cho tới khi owner/developer tự thêm block/shortcode |
| BACS core | **Không được bật** | Mọi payment gateway production vẫn tắt |
| VietQR trong BACS | **Tắt** | Không tải ảnh VietQR.io; cần BACS và tài khoản được owner cấu hình trước |
| Shipping Fee Rules | Method được đăng ký để chọn trong Shipping Zone | Không tự tạo zone, method instance, giá hay rule; không có rate mới nếu chưa cấu hình |
| Carrier integration | Không bundle GHTK/Viettel Post | Không có request mạng tới hãng vận chuyển |
| Bulk migration | Không chạy | Chỉ chạy khi người có quyền chủ động bấm Status Tool |

Do address/phone mặc định bật, activation là thay đổi checkout thật dù chưa nhập dữ liệu merchant. Gate triển khai phải test trước khi **KEEP**; nếu checkout/address lỗi thì rollback release và DB backup.

## 4. Capability và bản đồ wp-admin

| Màn hình/thao tác | URL/điểm vào | Capability plugin/WooCommerce dùng | `shop_owner` hiện tại |
|---|---|---|---|
| Vietnam store + feature switches | `/wp/wp-admin/admin.php?page=yoohw-vietnam-store` | `manage_woocommerce` + nonce khi lưu | Có |
| Direct Bank Transfer / VietQR | `/wp/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs` | WooCommerce settings: `manage_woocommerce` | Có |
| Shipping zones/rules | `/wp/wp-admin/admin.php?page=wc-settings&tab=shipping` | `manage_woocommerce` | Có |
| Tracking settings | WooCommerce Settings → Shipping → Shipment tracking | `manage_woocommerce` | Có |
| Email settings | WooCommerce Settings → Emails | `manage_woocommerce` | Có |
| Vận đơn/hóa đơn trên order | WooCommerce → Orders → Edit | `edit_shop_order`/`edit_shop_orders`, nonce | Có |
| Sửa địa chỉ trực tiếp trong WordPress user profile | Users → Edit User | `manage_woocommerce` + `edit_user` cho target user | Cố ý không có; không cấp `edit_users` chỉ để mở chức năng này |
| DevVN scan/migrate | WooCommerce → Status → Tools | `manage_woocommerce`, nonce | Có về capability |

`site-policy` hiện không ẩn menu Vietnam store hay WooCommerce Settings; `shop_owner` đã có `manage_woocommerce` và order capabilities. Vì vậy **không cần cấp capability mới** và tuyệt đối không cấp `manage_options`.

Không cần menu whitelist hay capability mới để owner truy cập các màn hình cấu hình. Điều chỉnh an toàn nhỏ nhất được lên kế hoạch chỉ dành cho hai DevVN migration tools: `site-policy` filter `woocommerce_debug_tools` để bỏ keys `yoohw_vietnam_store_tools_devvn_migration_dry_run` và `yoohw_vietnam_store_tools_devvn_migration` đối với role `shop_owner`, đồng thời chặn sớm AJAX action `yoohw_vietnam_store_tools_devvn_migration_step` cho đúng role đó. Cần cả hai lớp vì source 1.1.2 enqueue nonce trên trang Status Tools và AJAX chỉ kiểm tra `manage_woocommerce`. Trang Vietnam store, BACS, shipping, tracking và order operations vẫn mở. Đây là hardening thao tác migration phá huỷ, không phải mở rộng quyền; developer/admin vẫn dùng được tool.

`shop_owner` vẫn không được: install/activate/delete/update plugin/theme/core, file editor, `manage_options`, hoặc quản lý developer/admin users.

## 5. Data, option, meta và uninstall

Plugin dùng dữ liệu WordPress/WooCommerce hiện hữu:

- Options `yoohw_vietnam_store_tools_*` cho 5 feature switch, VAT request, tracking lookup/templates/carriers và migration marker.
- `woocommerce_bacs_settings` cùng `woocommerce_bacs_accounts` cho BACS/VietQR; shipping-method instance settings nằm trong WooCommerce shipping zone options.
- Order meta `_vck_*` cho VAT/shipment/address migration và `_yoohw_vietnam_store_tools_*` cho invoice/tracking/fulfillment.
- User meta chỉ phát sinh khi chuẩn hóa/migrate profile address/phone.
- PDF/XML hóa đơn dùng WordPress Media attachments; email hóa đơn có thể đính kèm các file này.

Deactivate ngừng hook nhưng không xóa options/meta/attachments. Plugin không có uninstall cleanup. Gỡ package khỏi Composer cũng không xóa dữ liệu. Rollback đầy đủ phải dùng immutable release trước đó và DB backup pre-deploy; quyết định dọn dữ liệu về sau phải là migration riêng, không chạy tự động.

## 6. Source audit: security, privacy và hiệu năng

### Security controls quan sát được

- Màn hình/settings dùng `manage_woocommerce`; thao tác order dùng order capability.
- Form admin và order actions có nonce; AJAX BACS/migration yêu cầu authenticated capability + nonce.
- AJAX ward cho guest có nonce, sanitize/validate province và chỉ trả dữ liệu tĩnh.
- Input được xử lý bằng Woo/WordPress sanitizers; output admin/frontend được escape có hệ thống.
- XML kiểm tra bằng `DOMDocument` với `LIBXML_NONET`; upload chỉ chấp nhận cặp trường PDF/XML và dùng `media_handle_upload`.
- Không thấy telemetry, analytics pixel, runtime `wp_remote_*` hay REST endpoint riêng.

### Các bề mặt cần kiểm soát

- Tra cứu đơn dùng order number + exact billing email/phone, nonce, honeypot và chỉ trả trạng thái/vận đơn; không có rate limit. Không publish block/shortcode trước khi có rate-limit/WAF decision.
- PDF/XML hóa đơn là Media attachment thông thường trong public uploads. File có thể chứa dữ liệu cá nhân/doanh nghiệp. Không dùng workflow upload này cho dữ liệu thật trước khi có phương án private storage/access-controlled delivery.
- File dữ liệu địa chỉ khoảng 849 KB, được load khi cần; frontend lazy-load ward và browser cache. Vẫn phải đo checkout TTFB/memory trên host.
- Admin assets chủ yếu chỉ load ở screen liên quan; icon menu nhỏ được thêm ở admin chung.

## 7. BACS/VietQR — quyết định kiến trúc V1

**CURRENT:** tất cả payment gateway production đang tắt; BACS chưa có merchant settings.

**PLANNED:** Vietnam Store Toolkit là giải pháp chuyển khoản V1 trước mắt, nhưng lần triển khai kế tiếp chỉ cài + activate plugin. Owner cấu hình và bật sau.

- VietQR mở rộng gateway WooCommerce core `bacs`; **không tạo gateway mới**.
- Plugin chỉ tạo/hiển thị thông tin chuyển khoản và ảnh QR.
- Plugin **không** kết nối giao dịch ngân hàng, không đối soát tự động, không xác nhận tiền về và không tự đánh dấu order paid.
- Trạng thái order BACS vẫn do WooCommerce core và quy trình xác nhận thủ công kiểm soát (thông thường On hold cho tới khi shop xác nhận).
- VietQR chỉ hoạt động sau khi owner tự cấu hình account/BIN/holder, bật BACS và bật VietQR.
- Không triển khai hai QR song song.

**DEFERRED / OPTIONAL:** SePay được giữ trong lịch sử nghiên cứu, chỉ xem xét lại nếu shop sau này cần automatic bank reconciliation/webhook. SePay không thuộc lần triển khai kế tiếp và không còn là bắt buộc cho transfer UI V1.

## 8. Privacy và external service

Khi VietQR được bật, browser của khách/admin hoặc email client tải ảnh từ `https://img.vietqr.io/`. URL có thể chứa bank BIN, số tài khoản, QR template, số tiền, nội dung chuyển khoản và tên chủ tài khoản.

Gate trước khi owner bật VietQR:

1. Bổ sung privacy-policy disclosure về VietQR.io/CASSO, dữ liệu nằm trong image URL, mục đích và thời điểm request.
2. Owner kiểm tra chấp thuận điều khoản/privacy của nhà cung cấp.
3. Không đưa bank data vào repo, docs, logs hay command output.
4. Chỉ bật một QR implementation.

Pre-flight này không bật VietQR và không gửi dữ liệu tới VietQR.io.

## 9. Risk matrix

| Mức | Phát hiện | Bằng chứng | Giảm thiểu/gate |
|---|---|---|---|
| HIGH | Activation thay đổi checkout/address/phone mặc định | Feature options mặc định `yes` | Backup; test Classic Checkout địa chỉ VN/khách cũ/My Account; rollback nếu regression |
| HIGH | PDF/XML hóa đơn trong public Media | `media_handle_upload`, attachment ID lưu order meta, email attachment | Không dùng dữ liệu thật trước private-delivery design; giới hạn quyền order; privacy review |
| MEDIUM | Plugin rất mới/ít triển khai | WordPress.org: dưới 10 active installs, chưa có review | Pin exact 1.1.2, source audit, focused regression, immutable rollback |
| MEDIUM | VietQR gửi merchant/order fields qua image URL | Source xây `img.vietqr.io` URL; upstream privacy note | Giữ tắt; disclosure trước enable; owner tự nhập dữ liệu |
| MEDIUM | Không có automatic reconciliation | Source/upstream xác nhận display-only | SOP xác nhận ngân hàng thủ công; cân nhắc SePay sau nếu thật sự cần |
| MEDIUM | Public order lookup không rate-limit | Nonce/honeypot + matching contact, không throttle | Không tạo page; thêm WAF/rate limit trước khi public |
| MEDIUM | `shop_owner` có thể thấy DevVN migration tools | Tool dùng `manage_woocommerce` | Site-policy chặn đúng hai mutating tool cho owner; admin/developer mới chạy |
| MEDIUM | Dataset địa chỉ lớn | PHP data file khoảng 849 KB; lazy load | Đo TTFB/memory checkout, xác nhận cache/lazy load, rollback nếu vượt budget |
| MEDIUM | Không có uninstall cleanup | Không có uninstall/deactivation hooks | DB backup; inventory options/meta; cleanup chỉ bằng migration được duyệt |
| LOW | BACS init migration ghi marker/settings | `update_option` một lần trên `init` | Snapshot option trước/sau; backup trước activate |
| LOW | Shipping method đăng ký nhưng chưa cấu hình | Chỉ xuất hiện để thêm vào zone; không tự tạo instance/rate | Owner tự cấu hình zone/rates trong task khác |
| LOW | Admin/AJAX input surface | Capability, nonce, sanitize/escape quan sát được | Focused negative-permission and nonce smoke test |
| LOW | HPOS/legacy | Compatibility declaration + Woo order API | Giữ legacy; không đổi HPOS |
| LOW | Runtime external calls ngoài VietQR | Không có `wp_remote_*`; datasets tĩnh | Giữ VietQR tắt cho tới privacy gate |

Không có blocker Composer/source làm cấm triển khai. Hai điều kiện **không được bỏ qua** là checkout regression gate sau activation và không dùng file hóa đơn thật khi chưa giải quyết private delivery.

## 10. Ownership boundary

Developer ở lần triển khai kế tiếp chỉ:

- thêm dependency exact, cập nhật lock, build/deploy/activate;
- xác nhận plugin load, admin screen truy cập được, Classic Checkout không fatal/regression;
- thực hiện site-policy hardening nhỏ nhất cho owner nếu cần;
- giữ toàn bộ payment/shipping/VAT/tracking merchant settings chưa cấu hình.

Shop owner sau đó tự nhập qua wp-admin: số tài khoản, chủ tài khoản, merchant VietQR, giá/rule vận chuyển, chính sách COD, thông tin VAT, invoice provider credentials, tracking credentials và payment text. Không ghi các giá trị này trong code hay docs.

## 11. Exact implementation gate

Lần được ủy quyền tiếp theo phải chạy đúng trình tự:

1. Thêm `wpackagist-plugin/yoohw-vietnam-store-tools:1.1.2` vào Composer.
2. Cập nhật `composer.lock`; xác nhận chỉ thêm package dự kiến.
3. Thực hiện token sáu màu đã chốt.
4. Thực hiện responsive mobile-first theo `BRAND-MOBILE-REMEDIATION-PLAN.md`.
5. Chỉ chỉnh `site-policy` ở mức nhỏ nhất để `shop_owner` dùng Vietnam store/BACS an toàn; không cấp `manage_options`.
6. Local focused validation trong WSL.
7. Build đúng một immutable release.
8. Tạo đúng một pre-deploy backup và kiểm tra restore artifact.
9. Deploy rồi activate Vietnam Store Toolkit.
10. Không nhập bank/shipping/VAT/tracking merchant values.
11. Xác minh `shop_owner` mở được Vietnam store và BACS/settings cần thiết; không mở quyền kỹ thuật cấm.
12. Xác minh checkout/address không regression trên Classic flow.
13. Visual check 375/768/1440.
14. Giữ BACS/VietQR disabled/unconfigured cho tới khi owner tự nhập và bật.
15. Smoke test.
16. KEEP hoặc ROLLBACK release + DB theo gate.

Workflow chuẩn: **LOCAL/WSL VALIDATE → BUILD → BACKUP IF PRODUCTION WILL CHANGE → DEPLOY → SMOKE → KEEP OR ROLLBACK**. GitHub Actions chỉ cung cấp thông tin.
