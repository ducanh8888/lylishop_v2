# STACK CHÍNH THỨC

## WordPress/WooCommerce Commerce Distribution

**Ngày chốt:** 03/08/2026
**Trạng thái:** Implementation-ready
**Mô hình:** Một repository, một website, một shop, một cơ sở dữ liệu
**Thị trường:** Việt Nam
**Nguyên tắc:** Reuse-first, code-managed, minimal-custom

---

# 1. Kết luận kiến trúc

Stack được chốt theo kiến trúc:

```text
Storefront + Child Theme
        │
WordPress + WooCommerce
        │
Curated Plugin Stack
        │
Bedrock + Composer + WP-CLI
        │
PHP 8.3 + MariaDB/MySQL
        │
Hosting/VPS thông thường
```

## Vai trò từng lớp

* **WordPress:** CMS, nội dung, media, user, admin.
* **WooCommerce:** sản phẩm, kho, giỏ hàng, checkout, đơn hàng, khách hàng, hoàn tiền.
* **Storefront:** theme nền tương thích chính thức với WooCommerce.
* **Child theme:** nhận diện thương hiệu và điều chỉnh giao diện.
* **Plugin được kiểm duyệt:** thanh toán, địa chỉ Việt Nam, coupon, bundle, SEO, backup.
* **Bedrock:** quản lý WordPress và plugin bằng Composer.
* **WP-CLI:** cài đặt và cấu hình website bằng code.
* **MU plugin `site-policy`:** khóa quyền, menu và thiết lập kỹ thuật.
* **Shopify:** chỉ là tài liệu tham khảo UX, không tham gia runtime.

Không có:

* Backend commerce tự viết.
* Admin panel tự viết.
* Page builder bên thứ ba.
* Headless frontend.
* Shopify API.
* Đồng bộ Shopify và WooCommerce.
* Microservice.
* Public API platform.

---

# 2. Stack phiên bản được chốt

Các phiên bản dưới đây là **baseline tại ngày 03/08/2026**, không phải đóng băng vĩnh viễn. Phiên bản mới chỉ được cập nhật qua pull request, staging và regression test.

## 2.1. Core và runtime

| Thành phần     |       Phiên bản chốt | Trạng thái          |
| -------------- | -------------------: | ------------------- |
| Roots Bedrock  |               1.31.1 | Bắt buộc            |
| PHP            |                  8.3 | Bắt buộc            |
| MariaDB        |        10.11 trở lên | Ưu tiên             |
| MySQL          |          8.0 trở lên | Thay thế MariaDB    |
| WordPress      |                7.0.2 | Bắt buộc            |
| WooCommerce    |               10.9.4 | Bắt buộc            |
| Storefront     |                4.6.2 | Theme cha           |
| Composer       |                  2.x | Build dependency    |
| WP-CLI         | Bản stable hiện hành | Cài đặt và vận hành |
| DDEV           | Bản stable hiện hành | Môi trường local    |
| GitHub Actions |      Managed service | CI/CD               |

WordPress 7.0.2 là bản stable hiện hành ngày 03/08/2026; WordPress 7.1 vẫn chưa phải bản production. WooCommerce 10.9.4 là nhánh stable tương thích với WordPress 7.0.2.

WordPress và WooCommerce hiện khuyến nghị PHP 8.3 trở lên, MariaDB 10.11 hoặc MySQL 8.0, HTTPS và tối thiểu 256 MB WordPress memory. Stack này đặt mục tiêu **512 MB memory limit** để tránh bóp cổ import, biến thể và tác vụ WooCommerce.

Bedrock quản lý WordPress, plugin và theme bằng Composer, tách secret qua biến môi trường, hỗ trợ cấu hình theo môi trường và đặt public document root trong thư mục `web`.

---

# 3. Theme và giao diện

## 3.1. Theme được chọn

### Theme cha: Storefront 4.6.2

Lý do chọn:

* Do đội ngũ WooCommerce phát triển.
* Tương thích sâu với WooCommerce.
* Ít chức năng thừa.
* Dễ tạo child theme.
* Ít phụ thuộc vào page builder.
* Giảm rủi ro checkout hoặc plugin commerce bị theme can thiệp.
* Có thể thay đổi giao diện đáng kể bằng child theme và CSS.

Storefront 4.6.2 là theme chính thức, được WooCommerce mô tả là theme tích hợp sâu và có nền mã gọn, dễ mở rộng.

### Theme con: `shop-child`

Theme con chỉ chứa:

* Typography.
* Color palette.
* Spacing.
* Product card.
* Header.
* Footer.
* Banner.
* Style cho giỏ hàng và checkout.
* Responsive.
* Một số template override thật sự cần thiết.
* Block patterns và nội dung mẫu.

Không chứa:

* Logic đơn hàng.
* Logic thanh toán.
* Logic voucher.
* Query trực tiếp vào bảng WooCommerce.
* Đăng ký custom order model.
* Business workflow.

## 3.2. Editor

Sử dụng:

* Gutenberg core cho page và post.
* Core blocks.
* WooCommerce product blocks ở những vị trí đã kiểm thử.
* Block patterns được đóng gói sẵn.
* Reusable blocks hoặc synced patterns cho nội dung dùng lại.

Không sử dụng:

* Elementor.
* WPBakery.
* Divi Builder.
* Bricks.
* Oxygen.
* UX Builder.
* Một bộ block companion khổng lồ chỉ để tạo banner và ba cột sản phẩm.

## 3.3. Cách chủ shop chỉnh giao diện

Chủ shop được phép:

* Sửa văn bản.
* Thay ảnh.
* Chọn sản phẩm hiển thị.
* Thay banner.
* Bật hoặc tắt section.
* Sắp xếp một số section.
* Chọn màu trong palette.
* Chỉnh nội dung menu, footer và chính sách.

Chủ shop không được:

* Sửa PHP.
* Chèn JavaScript.
* Chỉnh trực tiếp template.
* Cài page builder.
* Đổi theme cha.
* Tự tạo layout checkout.
* Sửa CSS toàn cục.

---

# 4. Quyết định về Cart, Checkout và HPOS

## 4.1. Cart và Checkout

### Chốt V1: dùng Classic Cart và Classic Checkout

Dùng shortcode/template WooCommerce truyền thống cho:

* Cart.
* Checkout.
* Order confirmation.
* Payment gateway rendering.

Các trang nội dung còn lại vẫn dùng Gutenberg.

### Lý do

Các plugin thanh toán và địa chỉ Việt Nam chưa đồng đều về tuyên bố tương thích với Cart và Checkout Blocks. WooCommerce yêu cầu extension kiểm tra riêng khả năng tương thích với Blocks, HPOS và Site Editor; không nên suy luận rằng plugin chạy với classic checkout thì tự động chạy đúng với block checkout.

Classic checkout giúp:

* SePay dễ tích hợp hơn.
* Plugin địa chỉ Việt Nam ít lỗi hơn.
* Dễ tùy chỉnh validation.
* Dễ debug.
* Giảm số biến trong MVP.
* Không làm mất khả năng chuyển sang Blocks về sau.

## 4.2. HPOS

### Chốt V1: chưa bật HPOS khi go-live

WooCommerce vẫn hỗ trợ chuyển về hệ lưu order truyền thống khi extension chưa tương thích. Extension tương thích HPOS phải sử dụng WooCommerce CRUD/API thay vì truy cập trực tiếp post và post meta.

HPOS chỉ được bật khi:

1. Toàn bộ plugin commerce khai báo hoặc chứng minh tương thích.
2. Chạy migration trên staging.
3. Test đầy đủ checkout, refund, coupon, affiliate và SePay.
4. Chạy song song dữ liệu trong giai đoạn kiểm thử.
5. Có backup và rollback.

Đây là trì hoãn có chủ đích, không phải cấm HPOS vĩnh viễn.

---

# 5. Plugin manifest chính thức

## 5.1. Plugin bắt buộc

| Plugin                  | Phiên bản baseline | Chức năng                       | Trạng thái            |
| ----------------------- | -----------------: | ------------------------------- | --------------------- |
| WooCommerce             |             10.9.4 | Commerce core                   | Bắt buộc              |
| Vietnam Store Toolkit   |              1.1.2 | Địa chỉ và shipping Việt Nam    | Bắt buộc, phải audit  |
| SePay Gateway           |             1.1.23 | VietQR và xác nhận chuyển khoản | Bắt buộc              |
| SEOPress Free           |               10.1 | SEO                             | Bắt buộc              |
| FluentSMTP              |             2.2.95 | Gửi và log email                | Bắt buộc, phải test   |
| WP Super Cache          |              3.1.1 | Page cache                      | Bắt buộc theo hosting |
| UpdraftPlus             |             1.26.6 | Backup và restore               | Bắt buộc              |
| WP 2FA                  |              4.1.0 | 2FA cho tài khoản quản trị      | Bắt buộc              |
| Simple History          |             5.29.0 | Audit log                       | Bắt buộc              |
| `site-policy` MU plugin |             Nội bộ | Role, menu, khóa kỹ thuật       | Bắt buộc              |

## 5.2. Plugin thương mại bắt buộc theo phạm vi

| Extension                   | Phiên bản baseline | Chức năng                     | Trạng thái   |
| --------------------------- | -----------------: | ----------------------------- | ------------ |
| WooCommerce Product Bundles |             8.5.10 | Combo và bundle               | Bắt buộc     |
| WooCommerce Smart Coupons   |             9.80.0 | BOGO, credit, coupon nâng cao | Bắt buộc     |
| Affiliate for WooCommerce   |             9.10.0 | Affiliate và referral         | Feature flag |

Product Bundles hỗ trợ bundle từ nhiều sản phẩm, theo dõi tồn kho thành phần, sản phẩm biến thể, Cart/Checkout Blocks và HPOS. Smart Coupons bổ sung BOGO, store credit, auto-apply và điều kiện coupon nâng cao.

Affiliate for WooCommerce cung cấp tracking affiliate, referral, commission và quản lý đối tác trong WooCommerce; phiên bản hiện tại khai báo tương thích với WooCommerce 10.9, WordPress 7.0 và HPOS.

Affiliate được đưa vào manifest nhưng mặc định **không kích hoạt** cho đến khi shop chốt:

* Cách attribution.
* Cookie duration.
* Mức commission.
* Điều kiện hủy commission.
* Cách xử lý đơn hoàn hoặc hủy.
* Chu kỳ đối soát.
* Cách thanh toán affiliate.

---

# 6. Địa chỉ và phí vận chuyển Việt Nam

## 6.1. Plugin được chọn

### Vietnam Store Toolkit for WooCommerce 1.1.2

Plugin được chọn vì hỗ trợ:

* Dữ liệu hành chính Việt Nam theo mô hình hai cấp.
* 34 tỉnh, thành phố.
* 3.321 xã, phường.
* Classic checkout.
* Checkout Blocks.
* Validation số điện thoại.
* Shipping rule theo địa phương.
* Shipping rule theo giá trị giỏ hàng.
* Shipping rule theo trọng lượng.
* Shipping class.
* Miễn phí vận chuyển.
* COD.
* Tracking.
* Yêu cầu xuất hóa đơn VAT.
* HPOS.
* CSV.
* Theo dõi đơn thủ công.

Plugin được cập nhật ngày 30/07/2026 và công bố hỗ trợ WordPress 7.0.2, nhưng mức độ sử dụng thực tế còn rất thấp.

## 6.2. Điều kiện bắt buộc trước production

Do plugin còn mới, phải thực hiện:

1. Review source code.
2. Kiểm tra capability và nonce.
3. Kiểm tra escaping và sanitization.
4. Kiểm tra AJAX endpoint.
5. Kiểm tra query database.
6. Kiểm tra frontend asset loading.
7. Kiểm tra Classic Checkout.
8. Kiểm tra SePay.
9. Kiểm tra Product Bundles.
10. Kiểm tra Smart Coupons.
11. Kiểm tra export/import địa chỉ.
12. Kiểm tra cập nhật dữ liệu hành chính.

Nếu không vượt qua audit, chuyển sang fallback.

## 6.3. Fallback

### Vietnam Checkout for WooCommerce 2.1.6 + bản Pro shipping

Ưu điểm:

* Khoảng 10.000 website đang sử dụng.
* Trưởng thành hơn.
* Có shipping rule theo tỉnh, huyện, xã, giá trị và trọng lượng.

Nhược điểm:

* Trang plugin hiện chỉ công bố kiểm thử đến phiên bản WordPress cũ hơn.
* Phải xác minh lại dữ liệu hành chính mới.
* Không có tuyên bố rõ ràng về Checkout Blocks.
* Cần kiểm thử kỹ trên WordPress 7.0.2 và WooCommerce 10.9.4.

Fallback vẫn dùng Classic Checkout.

---

# 7. Thanh toán

## 7.1. COD

Dùng WooCommerce Cash on Delivery core.

Không cần plugin riêng.

## 7.2. Chuyển khoản thủ công

Dùng WooCommerce Direct Bank Transfer core làm phương án dự phòng.

Đơn thanh toán bằng chuyển khoản sẽ được giữ ở trạng thái chờ cho đến khi nhân viên xác nhận.

## 7.3. VietQR và xác nhận tự động

### Plugin: SePay Gateway 1.1.23

Dùng để:

* Hiển thị QR theo đúng số tiền.
* Gắn mã đơn vào nội dung chuyển khoản.
* Nhận webhook giao dịch.
* Tự động xác nhận đơn.
* Cập nhật trạng thái sau khi nhận tiền.
* Hỗ trợ nhiều ngân hàng Việt Nam.

SePay công bố hỗ trợ kết nối hơn 30 ngân hàng, xác nhận giao dịch qua webhook và cập nhật đơn trong khoảng vài giây. Plugin hiện có hơn 2.000 lượt cài đặt, nhưng chưa tuyên bố rõ tương thích Cart/Checkout Blocks hoặc HPOS trên trang plugin.

## 7.4. Quy tắc triển khai

* Bật COD.
* Bật SePay.
* Giữ BACS nhưng đặt sau SePay.
* Không bật QR tích hợp sẵn của Vietnam Store Toolkit.
* Không có hai QR gateway cùng lúc.
* Webhook SePay phải có secret.
* Endpoint webhook phải log kết quả nhưng không log secret.
* Xử lý webhook phải idempotent.
* Không tự đánh dấu paid chỉ dựa trên nội dung do browser gửi lên.
* Khi SePay lỗi, đơn chuyển về luồng xác nhận thủ công.

---

# 8. SEO, email, cache và backup

## 8.1. SEO

### Chọn SEOPress Free 10.1

Lý do:

* Đủ meta title, description, canonical, sitemap và social metadata.
* Modular.
* White-label.
* Không cần hệ sinh thái add-on nặng.
* Giao diện có thể rút gọn cho chủ shop.
* Phiên bản hiện tại đã được kiểm thử với WordPress 7.0.2.

Chỉ bật:

* Titles và metadata.
* XML sitemap.
* Open Graph.
* Product schema phù hợp.
* Redirect nếu thực sự cần.

Tắt:

* AI.
* Analytics nội bộ không dùng.
* Module trùng Google Analytics.
* Các module không liên quan commerce.

## 8.2. Email

### Chọn FluentSMTP 2.2.95

Lý do:

* Có thể đưa credentials vào `wp-config.php` hoặc biến môi trường.
* Có email log.
* Hỗ trợ nhiều provider.
* Không cần bản Pro để gửi SMTP cơ bản.
* Phù hợp cách triển khai bằng code.

FluentSMTP hỗ trợ lưu credential bằng cấu hình thay vì bắt buộc nhập trong database, đồng thời có cơ chế xóa log theo thời gian. Phiên bản hiện tại chưa công bố kiểm thử đến WordPress 7.0.2, nên phải vượt qua compatibility test trước production.

Fallback:

1. WP Mail SMTP 4.9.0.
2. Post SMTP 3.9.5.

Hai lựa chọn fallback đều đã công bố kiểm thử với WordPress 7.0.2.

## 8.3. Cache

### Mặc định: WP Super Cache 3.1.1

Dùng cho Apache hoặc Nginx hosting phổ thông.

WP Super Cache hiện có hơn một triệu lượt cài đặt và đã được kiểm thử với WordPress 7.0.2.

Nếu hosting chạy LiteSpeed:

* Không cài WP Super Cache.
* Dùng LiteSpeed Cache.
* Chỉ dùng một cache plugin.

Các URL không được page cache:

* Cart.
* Checkout.
* My Account.
* Order received.
* WooCommerce API.
* SePay webhook.
* Trang có session hoặc nonce cá nhân.

## 8.4. Backup

### Chọn UpdraftPlus 1.26.6

Dùng cho:

* Database backup.
* Upload backup.
* Plugin/theme backup.
* Restore.
* Migration cơ bản.
* Backup theo lịch.
* Lưu off-site.

Phiên bản 1.26.6 được phát hành ngày 23/07/2026.

Chính sách:

* Database backup hằng ngày.
* Full backup hằng tuần.
* Backup trước mỗi deploy.
* Giữ ít nhất một bản off-site.
* Kiểm thử restore trên staging định kỳ.
* Không coi backup của hosting là bản duy nhất.

---

# 9. Bảo mật và audit log

## 9.1. WP 2FA 4.1.0

Bắt buộc 2FA đối với:

* `developer_admin`.
* `shop_owner`.

Khuyến nghị cho:

* `shop_staff`.

WP 2FA hỗ trợ TOTP, email code, backup code và policy theo role; phiên bản hiện tại được kiểm thử với WordPress 7.0.2.

## 9.2. Simple History 5.29.0

Log:

* Login.
* Thay đổi nội dung.
* Thay đổi user.
* Thay đổi plugin.
* Thay đổi option.
* Tác vụ WP-CLI.
* Tác vụ REST API.
* Thay đổi đơn hàng quan trọng khi được hỗ trợ.

Simple History hiện hỗ trợ log thay đổi qua admin, WP-CLI và REST, đồng thời đã bổ sung tương thích WordPress 7.0.

## 9.3. Không dùng security suite nặng mặc định

Không cài sẵn:

* Wordfence full suite.
* Sucuri plugin.
* All In One WP Security.
* Hai hoặc ba plugin chống brute force cùng lúc.

Bảo mật baseline gồm:

* HTTPS.
* 2FA.
* Strong password.
* Least privilege.
* Tắt file editor.
* Tắt cài plugin trên production.
* Backup off-site.
* Rate limiting ở hosting hoặc reverse proxy.
* WAF của hosting nếu có.
* Dependency scanning.
* Security update nhanh.
* Audit log.

---

# 10. Role và admin panel

## 10.1. Role

### `developer_admin`

* WordPress Administrator.
* Chỉ developer giữ.
* Có quyền plugin, theme, update, backup và cấu hình kỹ thuật.

### `shop_owner`

Dựa trên Shop Manager nhưng bổ sung quyền:

* Sản phẩm.
* Đơn hàng.
* Coupon.
* Khách hàng.
* Review.
* Báo cáo.
* Nội dung.
* Một số cấu hình shop được whitelist.

Không có quyền:

* Cài hoặc xóa plugin.
* Đổi theme.
* Sửa file.
* Update WordPress.
* Update WooCommerce.
* Chỉnh SMTP.
* Chỉnh cache.
* Chỉnh security.
* Chỉnh webhook.
* Quản lý developer admin.

### `shop_staff`

Chỉ có:

* Xem và xử lý đơn.
* Tạo đơn thủ công.
* Quản lý sản phẩm và tồn kho khi được cấp.
* Xem khách hàng cần xử lý.
* Xem review.
* Ghi order note.

Không xem:

* Plugin.
* Theme.
* Tools.
* Site Health kỹ thuật.
* SEO toàn cục.
* Backup.
* User administrator.
* Payment secret.

## 10.2. `site-policy` MU plugin

Đây là phần custom duy nhất bắt buộc.

Phạm vi:

* Đăng ký role và capability.
* Ẩn menu theo role.
* Tắt file editor.
* Tắt plugin/theme installation với shop user.
* Chặn truy cập URL admin kỹ thuật.
* Thiết lập default option.
* Khóa một số cấu hình production.
* Hiển thị dashboard widget công việc.

Giới hạn:

* Không chứa business logic.
* Không can thiệp cart hoặc checkout.
* Không truy cập bảng order trực tiếp.
* Không thay WooCommerce service.
* Mục tiêu dưới khoảng 300 dòng logic thực tế.
* Có unit test hoặc capability test.

Owner không dùng tài khoản WordPress Administrator. Nếu không, toàn bộ nỗ lực “admin đơn giản” sẽ kết thúc vào lúc họ cài một plugin popup vì xem được video hướng dẫn trên TikTok.

---

# 11. Hoàn tiền, đổi hàng và trả hàng

## 11.1. Không cài RMA plugin trong V1

WooCommerce core được dùng cho:

* Full refund.
* Partial refund.
* Restock.
* Order notes.
* Sửa đơn theo trạng thái.
* Tạo đơn thủ công thay thế.

Quy trình đổi hàng:

1. Khách liên hệ shop.
2. Staff xác minh đơn.
3. Staff thêm order note.
4. Xử lý hoàn hàng.
5. Điều chỉnh tồn kho.
6. Tạo đơn thay thế hoặc sửa đơn khi hợp lệ.
7. Refund chênh lệch nếu cần.
8. Ghi đầy đủ lịch sử.

Lý do không chọn plugin RMA:

* Số lượng đơn ban đầu chưa chứng minh cần workflow RMA riêng.
* Plugin chính thức Returns and Warranty Requests có chức năng đầy đủ nhưng đánh giá người dùng hiện thấp.
* Cài RMA ngay sẽ thêm một data model và admin workflow trước khi có nhu cầu thực tế.

## 11.2. Điều kiện nâng cấp

Chỉ bổ sung RMA khi:

* Số yêu cầu đổi trả đủ lớn.
* Staff bỏ sót yêu cầu.
* Cần khách tự upload bằng chứng.
* Cần SLA và trạng thái xử lý.
* Cần warranty theo sản phẩm.
* Cần portal theo dõi return.

Khi đó đánh giá lại:

1. Returns and Warranty Requests.
2. Smart Refunder nếu chỉ cần refund request.
3. Một extension RMA đã vượt qua compatibility test.

Smart Refunder hiện hỗ trợ khách gửi yêu cầu full hoặc partial refund trực tiếp từ My Account và staff duyệt trong WooCommerce, nhưng nó không thay thế đầy đủ workflow đổi hàng và nhận hàng hoàn.

---

# 12. Cấu trúc repository

```text
commerce-site/
├── composer.json
├── composer.lock
├── auth.json.example
├── .env.example
├── config/
│   ├── application.php
│   └── environments/
│       ├── development.php
│       ├── staging.php
│       └── production.php
├── web/
│   ├── app/
│   │   ├── mu-plugins/
│   │   │   └── site-policy/
│   │   ├── plugins/
│   │   └── themes/
│   │       └── shop-child/
│   ├── wp/
│   └── index.php
├── scripts/
│   ├── bootstrap.sh
│   ├── install.sh
│   ├── configure-shop.sh
│   ├── create-roles.sh
│   ├── seed-content.sh
│   ├── backup.sh
│   ├── restore.sh
│   ├── smoke-test.sh
│   └── deploy.sh
├── content/
│   ├── pages/
│   ├── policies/
│   ├── patterns/
│   └── sample-products/
├── tests/
│   ├── capability/
│   ├── checkout/
│   ├── payment/
│   └── smoke/
└── docs/
    ├── ARCHITECTURE.md
    ├── PLUGIN-MANIFEST.md
    ├── DEPLOYMENT.md
    ├── UPDATE-POLICY.md
    ├── BACKUP-RESTORE.md
    ├── OWNER-HANDBOOK.md
    └── STAFF-HANDBOOK.md
```

---

# 13. Quản lý dependency và update

## 13.1. Quy tắc

* Mọi plugin có thể quản lý bằng Composer phải nằm trong `composer.json`.
* Phiên bản thực tế khóa trong `composer.lock`.
* Plugin thương mại được tải từ private Composer repository hoặc private artifact storage.
* Không commit license key.
* Không cập nhật plugin trực tiếp trong `wp-admin`.
* Production đặt `DISALLOW_FILE_MODS=true`.
* Plugin không có nguồn build tái tạo được thì không được đưa vào stack.

## 13.2. Update workflow

1. Renovate hoặc Dependabot tạo pull request.
2. Composer resolve dependency.
3. CI build website mới.
4. Deploy staging.
5. Chạy regression tests.
6. Developer review changelog.
7. Backup production.
8. Deploy release artifact.
9. Chạy WP-CLI migration.
10. Chạy smoke test.
11. Theo dõi log.
12. Rollback nếu thất bại.

Security release nghiêm trọng được ưu tiên triển khai trong vòng 24 giờ sau khi vượt qua smoke test. WordPress 7.0.2 chính là một security release xử lý các lỗi mức critical và high, nên không thể “pin phiên bản” theo nghĩa bỏ mặc nó đến mùa quýt.

---

# 14. CI/CD

## Pipeline bắt buộc

### Build

* `composer validate`
* `composer install --no-dev`
* PHP syntax check.
* PHPCS cho code nội bộ.
* Kiểm tra secret.
* Kiểm tra plugin manifest.
* Tạo release artifact.

### Test

* Cài WordPress bằng WP-CLI.
* Kích hoạt plugin.
* Tạo dữ liệu mẫu.
* Test capability.
* Test guest checkout.
* Test logged-in checkout.
* Test COD.
* Test BACS.
* Test SePay.
* Test coupon.
* Test bundle.
* Test refund.
* Test stock.
* Test email.
* Test mobile viewport.

WP-CLI hỗ trợ cài core, plugin, theme, user, role, option, database, cron và import/export, phù hợp để biến toàn bộ setup thành script thay vì một nghi lễ bấm nút truyền đời.

### Deploy

* Tự động deploy staging.
* Production cần manual approval.
* Database không bị ghi đè khi deploy code.
* Upload không được đóng gói vào release.
* Backup trước migration.
* Release theo thư mục versioned nếu hosting cho phép.
* Symlink hoặc rollback về release trước.

---

# 15. Ma trận kiểm thử bắt buộc

| Nhóm     | Trường hợp phải test                               |
| -------- | -------------------------------------------------- |
| Sản phẩm | Simple, variable, hết hàng, sale                   |
| Bundle   | Tồn kho thành phần, biến thể, coupon               |
| Cart     | Guest, login, thay số lượng, xóa sản phẩm          |
| Checkout | Mobile, desktop, validation số điện thoại          |
| Địa chỉ  | 34 tỉnh/thành, xã/phường, local pickup             |
| Shipping | Theo vùng, trọng lượng, giá trị đơn, free shipping |
| COD      | Đặt đơn, email, trạng thái                         |
| SePay    | QR, đúng số tiền, webhook, duplicate webhook       |
| Coupon   | Percentage, fixed, BOGO, auto-apply                |
| Stock    | Trừ kho, hủy đơn, refund, restock                  |
| Refund   | Full, partial, manual payment                      |
| Email    | Customer, owner, SMTP failure                      |
| Role     | Owner, staff, developer admin                      |
| Backup   | Database, uploads, restore staging                 |
| Cache    | Cart, checkout, account không bị cache             |
| Update   | Core, WooCommerce, payment, theme                  |
| Mobile   | Product, cart, checkout, account                   |

---

# 16. Plugin và công nghệ bị loại

Không dùng trong baseline:

* Shopify runtime.
* Shopify API.
* Hydrogen.
* Headless WordPress.
* Next.js storefront.
* Elementor.
* Divi.
* Bricks.
* Jetpack.
* Hai plugin SEO.
* Hai plugin SMTP.
* Hai plugin cache.
* Hai plugin security.
* Plugin checkout builder.
* Plugin order status nếu chưa có nhu cầu thật.
* Plugin RMA trong V1.
* Plugin multi-vendor.
* Plugin multi-warehouse.
* Plugin POS.
* Plugin CRM.
* Plugin marketing automation.
* Plugin AI.
* Plugin database cleaner tự động.
* Code snippets được nhập trực tiếp qua admin.
* Sửa `functions.php` tùy tiện.
* Sửa WordPress core.
* Sửa WooCommerce core.
* Sửa plugin bên thứ ba.
* Auto-update production không qua staging.

---

# 17. Thứ tự triển khai

## Phase 1: Foundation

1. Tạo Bedrock repository.
2. Cấu hình Composer.
3. Cấu hình DDEV.
4. Cài WordPress 7.0.2.
5. Cài WooCommerce 10.9.4.
6. Cài Storefront.
7. Tạo `shop-child`.
8. Tạo `site-policy`.

## Phase 2: Plugin audit

1. Audit Vietnam Store Toolkit.
2. Test SePay.
3. Test Product Bundles.
4. Test Smart Coupons.
5. Test FluentSMTP.
6. Chốt plugin lockfile.
7. Viết plugin manifest.

## Phase 3: Commerce configuration

1. Tạo trang WooCommerce.
2. Cấu hình VND.
3. Cấu hình Việt Nam.
4. Cấu hình COD.
5. Cấu hình BACS.
6. Cấu hình SePay.
7. Cấu hình shipping.
8. Cấu hình coupon.
9. Cấu hình bundle.
10. Cấu hình email.

## Phase 4: Theme

1. Header.
2. Footer.
3. Trang chủ.
4. Product archive.
5. Product detail.
6. Cart.
7. Checkout.
8. My Account.
9. Chính sách.
10. Mobile layout.

## Phase 5: Admin và role

1. Developer admin.
2. Shop owner.
3. Shop staff.
4. Menu whitelist.
5. Dashboard công việc.
6. Khóa plugin và theme.
7. Khóa phần kỹ thuật.

## Phase 6: Operations

1. Backup.
2. Restore.
3. SMTP.
4. Cache.
5. Security.
6. Log.
7. Monitoring.
8. CI/CD.
9. Staging.
10. Rollback.

## Phase 7: QA và go-live

1. Chạy ma trận test.
2. Import sản phẩm thật.
3. Test đơn thật giá nhỏ.
4. Test chuyển khoản thật.
5. Test refund.
6. Test backup và restore.
7. Chốt production configuration.
8. Bàn giao owner/staff.

---

# 18. Tiêu chí hoàn thành

Stack được coi là hoàn thành khi:

1. Website có thể dựng lại từ repository.
2. Không cần cài plugin thủ công.
3. Không cần cấu hình lại từng màn hình sau deploy.
4. Plugin và version được khóa.
5. Owner không phải Administrator.
6. Staff không truy cập cấu hình kỹ thuật.
7. Checkout mobile hoạt động.
8. COD hoạt động.
9. SePay hoạt động.
10. BACS fallback hoạt động.
11. Shipping rule Việt Nam hoạt động.
12. Bundle trừ kho chính xác.
13. Coupon nâng cao hoạt động.
14. Refund và restock chính xác.
15. Email được gửi và log.
16. Cart/checkout không bị cache.
17. Backup có thể restore.
18. Production có rollback.
19. Không sửa core hoặc plugin.
20. Không có Shopify dependency.
21. Tổng plugin baseline được giữ ở mức tối thiểu đã chốt.
22. Mọi plugin bổ sung phải có entry trong `PLUGIN-MANIFEST.md`.

---

# 19. Stack cuối cùng dạng ngắn

```text
CORE
├── Roots Bedrock 1.31.1
├── WordPress 7.0.2
├── WooCommerce 10.9.4
├── PHP 8.3
├── MariaDB 10.11+ / MySQL 8.0+
└── Storefront 4.6.2 + shop-child

VIETNAM COMMERCE
├── Vietnam Store Toolkit 1.1.2 [AUDIT REQUIRED]
├── SePay Gateway 1.1.23
├── WooCommerce COD
├── WooCommerce BACS
├── Product Bundles 8.5.10
└── Smart Coupons 9.80.0

OPERATIONS
├── SEOPress 10.1
├── FluentSMTP 2.2.95 [COMPATIBILITY TEST]
├── WP Super Cache 3.1.1
├── UpdraftPlus 1.26.6
├── WP 2FA 4.1.0
├── Simple History 5.29.0
└── site-policy MU plugin

OPTIONAL / FEATURE FLAG
└── Affiliate for WooCommerce 9.10.0

ARCHITECTURE DECISIONS
├── Classic Cart + Checkout
├── HPOS disabled initially
├── Gutenberg for content
├── No page builder
├── No RMA plugin in V1
├── No wp-admin updates
├── Composer-locked dependencies
└── Staging-gated production releases
```
