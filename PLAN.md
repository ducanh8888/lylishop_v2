# KẾ HOẠCH V2

## Bộ website bán hàng đóng gói từ WordPress và WooCommerce

**Trạng thái:** Product Plan v2
**Hướng tiếp cận:** Reuse-first
**Mô hình:** Một website độc lập cho một shop
**Thị trường:** Việt Nam
**Người dùng cuối:** Chủ shop không biết code
**Người triển khai:** Developer
**Đối thủ tham chiếu:** WordPress + WooCommerce được cài thủ công và vận hành thiếu kiểm soát

> **Amendment 2026-08-03 — hạ tầng production-only:** Dự án dùng đúng một domain (lylishop.online), một môi trường production, một database production, một kho uploads production. Không có staging domain/database/deploy/promotion workflow. Local development và automated checks là cổng kiểm tra duy nhất trước production; mọi deploy production vẫn phải qua quy trình release-based có backup trước khi deploy và rollback (xem `docs/DEPLOYMENT.md`). Các mục nhắc tới "staging" bên dưới đã được cập nhật theo quyết định này; nội dung gốc không bị xóa khỏi lịch sử Git.

> **Amendment 2026-08-10 — workflow hiện hành:** **LOCAL/WSL VALIDATE → BUILD → BACKUP IF PRODUCTION WILL CHANGE → DEPLOY → SMOKE → KEEP OR ROLLBACK.** WSL/local validation là deployment gate; GitHub Actions chỉ cung cấp thông tin, không cần chờ và không tạo commit để kích hoạt CI.

---

# 1. Định nghĩa lại sản phẩm

Sản phẩm không phải là:

* Commerce framework mới.
* CMS mới.
* Bản sao của Shopify.
* Backend bán hàng tự viết.
* Admin panel tự viết.
* Page builder tự viết.
* Plugin platform tự viết.

Sản phẩm là một:

> **WordPress/WooCommerce Commerce Distribution**

Có thể hiểu đơn giản là:

> Một bộ WordPress + WooCommerce đã được lựa chọn theme, plugin, cấu hình, nội dung mẫu, phân quyền, quy trình triển khai, backup và tài liệu vận hành từ trước.

Developer dùng bộ này để triển khai một website riêng cho từng shop.

Chủ shop nhận một website đã hoàn thiện và chỉ sử dụng những chức năng cần thiết trong trang quản trị.

---

# 2. Quyết định kiến trúc chính

## 2.1. WordPress là CMS và nền tảng vận hành

WordPress chịu trách nhiệm:

* Quản lý trang.
* Quản lý bài viết.
* Quản lý hình ảnh.
* Quản lý menu.
* Quản lý người dùng.
* Quản lý theme.
* Quản lý cấu hình website.
* Quản lý nội dung marketing.
* Cung cấp block editor.
* Cung cấp trang quản trị.

Không xây CMS khác.

## 2.2. WooCommerce là lõi bán hàng

WooCommerce chịu trách nhiệm:

* Sản phẩm.
* Biến thể.
* Giá.
* Tồn kho.
* Giỏ hàng.
* Checkout.
* Khách hàng.
* Đơn hàng.
* Hoàn tiền.
* Voucher cơ bản.
* Thuế.
* Phí vận chuyển.
* Thanh toán.
* Báo cáo.
* Review sản phẩm.

WooCommerce tiếp tục là nơi lưu trữ dữ liệu chính của shop.

Không tạo lại product model, order model, cart engine hoặc checkout engine.

WooCommerce hiện đã cung cấp các block template cho trang sản phẩm, danh mục, giỏ hàng, checkout và xác nhận đơn. Các template này có thể được theme tùy chỉnh mà không phải viết lại nghiệp vụ thương mại.

## 2.3. Shopify không tham gia runtime

Shopify không được sử dụng làm backend thứ hai.

Không đồng bộ sản phẩm hoặc đơn hàng giữa Shopify và WooCommerce.

Không phụ thuộc:

* Shopify subscription.
* Shopify API.
* Shopify checkout.
* Shopify hosting.
* Shopify app store.
* Liquid runtime.

Shopify chỉ được dùng làm **nguồn tham chiếu sản phẩm**, bao gồm:

* Cách tổ chức trang quản trị.
* Cách giới hạn tùy chỉnh cho merchant.
* Cách tổ chức theme bằng section và block.
* Cách thiết kế checkout.
* Cách hiển thị sản phẩm và biến thể.
* Cách tổ chức cài đặt theme.
* Cách giảm số lượng quyết định mà chủ shop phải đưa ra.

Shopify tổ chức theme bằng template, section, block và cấu hình schema. Các nguyên tắc này có thể được áp dụng vào WordPress block theme và pattern mà không cần đưa Shopify vào hệ thống.

---

# 3. Nguyên tắc reuse-first

Mọi yêu cầu phải được xử lý theo thứ tự:

1. WooCommerce core đã có chưa?
2. WordPress core đã có chưa?
3. Theme hiện tại đã hỗ trợ chưa?
4. Plugin ổn định đã có chưa?
5. WooCommerce Marketplace hoặc WordPress Plugin Directory có giải pháp phù hợp chưa?
6. Có thể giải quyết bằng cấu hình không?
7. Có thể giải quyết bằng hook nhỏ không?
8. Chỉ sau đó mới cân nhắc viết mã riêng.

Không tự viết lại chức năng chỉ vì giao diện plugin chưa hoàn hảo.

Không tự viết lại chức năng để tiết kiệm một khoản license nhỏ nhưng tạo thêm nhiều năm bảo trì.

---

# 4. Những phần được phép tự viết

Mã riêng chỉ giới hạn ở bốn nhóm.

## 4.1. Child theme hoặc theme configuration

Dùng để:

* Điều chỉnh nhận diện thương hiệu.
* Điều chỉnh typography.
* Điều chỉnh màu sắc.
* Thay đổi spacing.
* Chuẩn hóa header và footer.
* Tạo style variation.
* Đóng gói pattern.
* Điều chỉnh một số template WooCommerce.

Không đưa nghiệp vụ bán hàng vào theme.

## 4.2. Site configuration

Dùng để:

* Khai báo plugin bắt buộc.
* Khóa các thiết lập quan trọng.
* Thiết lập role.
* Ẩn menu không cần thiết.
* Đặt cấu hình mặc định.
* Thiết lập môi trường development và production (không có staging — xem Amendment 2026-08-03).

## 4.3. Deployment scripts

Dùng để:

* Cài WordPress.
* Cài WooCommerce.
* Cài plugin.
* Kích hoạt theme.
* Import dữ liệu mẫu.
* Tạo owner và staff.
* Cấu hình permalink.
* Cấu hình trang shop.
* Cấu hình cron.
* Clear cache.
* Backup.
* Kiểm tra health.

WP-CLI được thiết kế để tự động hóa các tác vụ như cài và cập nhật plugin, import nội dung, tạo user và thay đổi cấu hình WordPress từ terminal.

## 4.4. Glue code tối thiểu

Chỉ viết khi cần nối các plugin hoặc thay đổi hành vi nhỏ qua:

* WordPress actions.
* WordPress filters.
* WooCommerce hooks.
* WooCommerce block extension points.

WooCommerce chính thức hỗ trợ mở rộng qua hooks và các extension point dành cho Cart và Checkout blocks.

Glue code không được:

* Tạo bảng order riêng.
* Tạo product model riêng.
* Thay WooCommerce checkout.
* Truy cập trực tiếp database của plugin khác nếu plugin có API.
* Sửa source code của WordPress, WooCommerce hoặc plugin.
* Chứa một nghiệp vụ hoàn chỉnh mà plugin hiện có đã xử lý được.

---

# 5. Stack nền tảng

## 5.1. Thành phần bắt buộc

* WordPress.
* WooCommerce.
* Một theme tương thích tốt với WooCommerce.
* Một child theme hoặc style configuration riêng cho shop.
* WP-CLI.
* Composer ở môi trường build.
* Git.
* PHP.
* MySQL hoặc MariaDB.
* Web server Apache hoặc Nginx.
* SSL.

## 5.2. Quản lý dependency

Phương án ưu tiên là dùng Bedrock hoặc cấu trúc tương đương để:

* Quản lý WordPress bằng Composer.
* Quản lý plugin bằng Composer.
* Khóa phiên bản dependency.
* Tách secret khỏi repository.
* Có cấu hình riêng cho từng môi trường.
* Tái tạo website từ source code.

Bedrock cho phép quản lý WordPress và plugin như dependency Composer, thay vì cài plugin thủ công rồi cầu nguyện không ai nhớ mình đã cài phiên bản nào.

## 5.3. Hỗ trợ hosting phổ thông

Không yêu cầu hosting chạy Composer trực tiếp.

Quy trình có thể:

1. Build website trên máy developer sau khi local/WSL validation PASS; CI có thể chạy độc lập để cung cấp thông tin.
2. Chạy Composer trong quá trình build.
3. Tạo release artifact hoàn chỉnh.
4. Upload artifact lên hosting.
5. Chạy migration và WP-CLI nếu hosting hỗ trợ.
6. Nếu hosting không có SSH, dùng script hoặc quy trình deploy tương thích cPanel.

Như vậy:

* Developer vẫn quản lý website bằng code.
* Hosting không cần quá hiện đại.
* Website không bị giới hạn vào một nhà cung cấp.

Không cam kết chạy trên mọi hosting tồn tại trên Trái Đất. Hosting tối thiểu vẫn phải đáp ứng yêu cầu PHP, database, SSL, cron và quyền ghi file phù hợp.

---

# 6. Theme và giao diện

## 6.1. Không tự xây page builder

Sử dụng:

* WordPress block editor.
* Site Editor nếu theme hỗ trợ.
* WooCommerce blocks.
* Block patterns.
* Theme style variations.
* Global styles qua `theme.json`.

WordPress block theme sử dụng HTML template và `theme.json`; pattern có thể được đóng gói sẵn trong theme để người dùng chèn hoặc chỉnh sửa mà không cần xây một page builder khác.

## 6.2. Chọn một theme nền

Không tạo theme từ số không.

Quy trình:

1. Chọn từ ba đến năm theme WooCommerce đã trưởng thành.
2. Kiểm tra hiệu năng.
3. Kiểm tra Cart và Checkout blocks.
4. Kiểm tra product template.
5. Kiểm tra khả năng cập nhật.
6. Kiểm tra hỗ trợ child theme.
7. Chọn một theme nền duy nhất.
8. Tạo các preset nhận diện từ theme đó.

Hai hướng theme có thể đánh giá:

### Hướng A: Block theme

Ưu điểm:

* Hiện đại.
* Chỉnh template bằng block.
* Dùng `theme.json`.
* Dùng pattern.
* Dễ đóng gói nhiều style variation.
* Gần với mô hình section/block của Shopify.

### Hướng B: Storefront hoặc classic theme ổn định

Ưu điểm:

* Kiến trúc lâu đời.
* Tương thích WooCommerce cao.
* Dễ tìm tài liệu.
* Phù hợp nếu block theme gây vấn đề với plugin bắt buộc.

Storefront vẫn được WooCommerce mô tả là flagship classic theme; trong khi block theme cho phép chỉnh header, footer, sản phẩm, danh mục, Cart và Checkout thông qua Site Editor.

> **Amendment 2026-08-04 — quyết định V1:** Founder đã chốt Hướng B (classic/hybrid theme) với theme nền **Botiga Free**, không phải Storefront. Lý do, phương án bị loại và điều kiện mở lại quyết định: xem `docs/THEME-DECISION.md`. Mục 6.2 này giữ nguyên làm khung quy trình đánh giá đã dùng, không sửa lại nội dung gốc.

## 6.3. Bộ giao diện bàn giao

Không cung cấp page builder tự do.

Cung cấp từ ba đến năm preset, ví dụ:

* Minimal.
* Fashion.
* Beauty.
* Electronics.
* General retail.

Mỗi preset thay đổi:

* Màu sắc.
* Font.
* Border radius.
* Spacing.
* Button.
* Card sản phẩm.
* Header.
* Footer.
* Banner.
* Bố cục trang chủ.

Chủ shop được phép:

* Đổi logo.
* Đổi màu trong palette đã cho.
* Đổi font trong danh sách đã cho.
* Đổi ảnh.
* Sửa nội dung.
* Bật hoặc tắt section.
* Sắp xếp section được cho phép.
* Chọn sản phẩm hoặc danh mục hiển thị.

Chủ shop không được:

* Chèn JavaScript tùy ý.
* Sửa PHP.
* Chỉnh template hệ thống.
* Chỉnh trực tiếp CSS.
* Thay đổi checkout tùy ý.
* Kéo block vào mọi vị trí vô nghĩa mà editor cho phép.

WooCommerce khuyến cáo không phụ thuộc vào cấu trúc HTML nội bộ hoặc class của block vì chúng có thể thay đổi; giao diện nên ưu tiên Global Styles và API theming được hỗ trợ.

---

# 7. Trang quản trị

## 7.1. Không xây admin panel mới

Tiếp tục dùng:

* WordPress Admin.
* WooCommerce Admin.
* WooCommerce Analytics.
* WordPress Media Library.
* WordPress Users.
* WordPress Site Health.

Không tạo frontend admin bằng React hoặc framework riêng.

## 7.2. Đơn giản hóa admin có sẵn

Sử dụng plugin quản lý admin menu và role đã được kiểm duyệt để:

* Ẩn menu không cần thiết.
* Ẩn plugin khỏi staff.
* Ẩn Tools.
* Ẩn Settings.
* Ẩn Appearance nâng cao.
* Giới hạn quyền chỉnh theme.
* Giới hạn quyền cài hoặc xóa plugin.
* Giới hạn quyền cập nhật hệ thống.
* Tạo dashboard gọn.

## 7.3. Menu dành cho owner

Owner nhìn thấy:

1. Tổng quan.
2. Đơn hàng.
3. Sản phẩm.
4. Kho.
5. Khách hàng.
6. Khuyến mãi.
7. Review.
8. Nội dung website.
9. Báo cáo.
10. Cấu hình shop.

## 7.4. Menu dành cho staff

Staff nhìn thấy:

1. Tổng quan công việc.
2. Đơn hàng.
3. Sản phẩm nếu được cấp quyền.
4. Kho nếu được cấp quyền.
5. Khách hàng nếu được cấp quyền.
6. Review nếu được cấp quyền.

## 7.5. Khóa phần kỹ thuật

Owner và staff không được:

* Cài plugin.
* Xóa plugin.
* Đổi theme nền.
* Sửa code trong admin.
* Chạy update lớn.
* Chỉnh database.
* Chỉnh permalink hệ thống.
* Chỉnh cache.
* Chỉnh SMTP.
* Thay đổi cấu hình bảo mật.

WordPress cho phép vô hiệu hóa trình sửa theme và plugin trong admin; các cấu hình bắt buộc cũng có thể đặt trong must-use plugin, loại plugin mà người dùng không thể vô hiệu hóa từ danh sách plugin thông thường.

---

# 8. Bản đồ chức năng reuse

## 8.1. Sản phẩm

Dùng WooCommerce core cho:

* Simple product.
* Variable product.
* SKU.
* Giá thường.
* Giá khuyến mãi.
* Ảnh.
* Gallery.
* Thuộc tính.
* Danh mục.
* Tag.
* Tồn kho.
* Trọng lượng.
* Kích thước.
* Review.
* Sản phẩm liên quan.

Không viết product manager riêng.

## 8.2. Combo và bundle

Dùng extension có sẵn.

Ưu tiên:

* WooCommerce Product Bundles.
* Extension tương đương đã được kiểm tra tương thích.
* Plugin có cơ chế trừ tồn kho thành phần.
* Plugin hỗ trợ variable product.
* Plugin hoạt động với Cart và Checkout blocks.

WooCommerce Marketplace hiện có sẵn các extension dành cho product bundle, coupon nâng cao và shipment tracking.

Không tự viết bundle engine.

## 8.3. Giỏ hàng và checkout

Dùng:

* WooCommerce Cart block.
* WooCommerce Checkout block.
* Mini Cart block.
* Checkout validation có sẵn.
* Payment method registry có sẵn.
* WooCommerce cart data store.

WooCommerce giữ server làm nguồn dữ liệu chính cho dữ liệu giao dịch quan trọng của Cart và Checkout.

Không làm checkout riêng.

## 8.4. Đơn hàng

Dùng WooCommerce order management cho:

* Chờ thanh toán.
* Đang xử lý.
* Tạm giữ.
* Hoàn thành.
* Đã hủy.
* Đã hoàn tiền.
* Thất bại.
* Ghi chú đơn.
* Tạo đơn thủ công.
* Sửa đơn.
* Hoàn tiền.

Chỉ cài plugin quản lý order status khi luồng thực tế bắt buộc cần thêm trạng thái.

Không tạo 14 trạng thái giống Shopee chỉ để bảng đơn hàng trông bận rộn hơn.

## 8.5. Hoàn tiền và đổi trả

Dùng:

* WooCommerce refund.
* Plugin RMA hoặc Returns có sẵn nếu cần quy trình khách gửi yêu cầu.
* Plugin form nếu chỉ cần yêu cầu đổi trả đơn giản.
* Order notes để theo dõi trao đổi nội bộ.

Không xây workflow engine.

## 8.6. Tồn kho

Dùng WooCommerce stock management.

Chỉ bổ sung plugin khi cần:

* Lịch sử điều chỉnh kho.
* Import hàng loạt.
* Cảnh báo nâng cao.
* Báo cáo tồn kho chi tiết.

Một shop, một kho, nên không cài hệ thống inventory nhiều kho rồi dành ba tháng để tắt bớt tính năng.

## 8.7. Thanh toán

Dùng WooCommerce core cho:

* COD.
* Direct Bank Transfer.

WooCommerce đã có Direct Bank Transfer trong core; đơn sử dụng phương thức này được giữ ở trạng thái chờ để shop kiểm tra thanh toán thủ công.

**Quyết định V1 cập nhật 2026-08-10:** Vietnam Store Toolkit 1.1.2 được chọn làm UI chuyển khoản trước mắt bằng cách mở rộng gateway `bacs` với VietQR. Đây không phải gateway mới, không đối soát ngân hàng, không xác nhận tiền tự động và không tự đánh dấu đơn paid. BACS/VietQR chỉ được owner cấu hình/bật sau khi developer deploy plugin và privacy disclosure đã sẵn sàng. SePay giữ trạng thái **DEFERRED / OPTIONAL**, chỉ xem lại nếu sau này cần automatic reconciliation; không chạy hai QR implementation song song. Xem `docs/VIETNAM-STORE-TOOLKIT-PREFLIGHT.md`.

**Source direction cập nhật 2026-08-11 — chưa deploy:** GHN 0.2.0 có một application lifecycle first-party, Toolkit chỉ là optional adapter. Prototype VietQR custom chưa deploy đã bị gỡ; address và VietQR đều **REUSE-FIRST / chưa chọn nguồn**. Toolkit vẫn nằm trong Composer/runtime cho address fields/data và shipping rules tới controlled cutover. Xem `docs/VIETNAM-TOOLKIT-DECOUPLING.md`.

Dùng plugin có sẵn cho:

* Tạo QR theo tổng tiền.
* Gắn mã đơn vào nội dung chuyển khoản.
* VietQR.
* Xác nhận chuyển khoản tự động.
* Webhook từ nhà cung cấp thanh toán.

Nguyên tắc:

* Không tự kết nối trực tiếp hàng chục ngân hàng.
* Không lưu thông tin thẻ.
* Không viết payment gateway nếu đã có plugin ổn định.
* Chỉ dùng một plugin QR hoặc banking reconciliation.

## 8.8. Vận chuyển

Dùng WooCommerce Shipping Zones và shipping methods cho:

* Phí cố định.
* Miễn phí vận chuyển.
* Nhận tại cửa hàng.
* Phí theo khu vực.

Dùng plugin shipping rule có sẵn cho:

* Phí theo tỉnh hoặc quận.
* Phí theo trọng lượng.
* Phí theo giá trị đơn.
* Điều kiện miễn phí vận chuyển.
* Policy gần với Shopee Express.

Giai đoạn đầu không tích hợp hãng vận chuyển.

Khi cần tích hợp:

* Chọn plugin chính thức hoặc plugin từ nhà cung cấp vận chuyển.
* Không tự viết aggregator vận chuyển.
* Không tự duy trì bảng địa chỉ toàn quốc khi đã có plugin được cập nhật.

## 8.9. Voucher và khuyến mãi

Dùng WooCommerce coupon core cho:

* Giảm phần trăm.
* Giảm số tiền.
* Giới hạn thời gian.
* Giới hạn lượt dùng.
* Giới hạn sản phẩm.
* Giới hạn danh mục.
* Giá trị đơn tối thiểu.
* Miễn phí vận chuyển.

Dùng plugin nâng cao khi cần:

* Buy X Get Y.
* Voucher riêng cho khách.
* Voucher theo role.
* Voucher tự động.
* Coupon link.
* Loyalty coupon.
* Quantity discount.

Không tự xây promotion rule engine.

## 8.10. Khách hàng và đăng nhập

Dùng WordPress và WooCommerce cho:

* Tài khoản.
* Địa chỉ.
* Lịch sử đơn hàng.
* Quên mật khẩu.
* Trang My Account.
* Guest checkout.

Dùng plugin có sẵn cho:

* OTP.
* Đăng nhập số điện thoại.
* Google login.
* Facebook login.

Chỉ bật những phương thức thực sự cần.

## 8.11. Review

Dùng WooCommerce review core.

Chỉ bổ sung plugin khi cần:

* Upload ảnh.
* Review reminder.
* Verified purchase nâng cao.
* Q&A.
* Chống spam review.
* Import review.

Không tạo review service riêng.

## 8.12. Affiliate và referral

Dùng một plugin affiliate/referral hoàn chỉnh.

Plugin phải hỗ trợ:

* Link affiliate.
* Coupon affiliate.
* Attribution.
* Commission.
* Approval.
* Payout report.
* Referral customer.
* Chống tự giới thiệu.
* Hủy commission khi hoàn đơn.

Không tự viết attribution và commission engine. Tiền thật là nơi những đoạn code “đơn giản thôi” bắt đầu có luật sư.

## 8.13. SEO

Dùng plugin SEO hiện có cho:

* Meta title.
* Meta description.
* Canonical.
* Sitemap.
* Open Graph.
* Structured data.
* Redirect.
* Product schema.

Không tự viết SEO framework.

## 8.14. Analytics

Dùng:

* WooCommerce Analytics.
* Google Analytics integration.
* Search Console.
* Một plugin tracking nếu cần.

Không làm dashboard BI riêng trong V1.

## 8.15. Email

Dùng:

* WooCommerce email templates.
* SMTP plugin.
* Email logging plugin.
* Email template customizer nếu cần.

Không xây notification service.

---

# 9. Chính sách plugin

## 9.1. Plugin được phép

Plugin chỉ được thêm nếu:

* Giải quyết một yêu cầu đã xác định.
* Có lịch sử cập nhật rõ ràng.
* Tương thích phiên bản WordPress và WooCommerce hiện tại.
* Tương thích Cart và Checkout blocks nếu liên quan.
* Không trùng chức năng với plugin khác.
* Có tài liệu.
* Có cơ chế export hoặc gỡ bỏ hợp lý.
* Không mã hóa dữ liệu theo cách gây khóa nhà cung cấp.
* Không yêu cầu SaaS nếu không cần thiết.
* Không gây tải frontend trên mọi trang một cách vô lý.

## 9.2. Một chức năng, một plugin chính

Ví dụ:

* Một plugin SEO.
* Một plugin cache.
* Một plugin security.
* Một plugin backup.
* Một plugin affiliate.
* Một plugin QR.
* Một plugin discount nâng cao.
* Một plugin social login.

Không cài ba plugin cùng tối ưu ảnh, rồi ngạc nhiên khi ảnh sản phẩm biến thành tem thư.

## 9.3. Phân loại plugin

### Tier A: Bắt buộc

* WooCommerce.
* SEO.
* Backup.
* SMTP.
* Cache.
* Security hoặc hardening.
* Admin role/menu control.

### Tier B: Theo yêu cầu shop

* QR payment.
* Bundle.
* Advanced discount.
* Affiliate.
* Social login.
* Review ảnh.
* RMA.
* Shipping rules.

### Tier C: Không mặc định cài

* Page builder.
* Popup suite.
* CRM.
* Marketing automation.
* Live chat.
* Loyalty.
* Push notification.
* AI.
* Multi-vendor.
* Multi-warehouse.

## 9.4. Plugin manifest

Mỗi website có một manifest ghi rõ:

* Plugin name.
* Mục đích.
* Nguồn.
* License.
* Version.
* Trạng thái bắt buộc hay tùy chọn.
* Owner bảo trì.
* Dữ liệu plugin tạo ra.
* Cách backup.
* Cách gỡ bỏ.
* Rủi ro tương thích.

---

# 10. Cấu hình bằng code

## 10.1. Những thứ phải nằm trong repository

* Phiên bản WordPress.
* Phiên bản WooCommerce.
* Danh sách plugin.
* Phiên bản plugin.
* Theme.
* Child theme.
* MU plugin cấu hình.
* WP-CLI scripts.
* Cấu hình môi trường mẫu.
* Deployment scripts.
* Database migration nếu có.
* Nội dung mẫu.
* Tài liệu vận hành.
* Test checklist.

## 10.2. Những thứ không commit

* Password.
* Database credentials.
* API keys.
* SMTP credentials.
* Banking credentials.
* Production salts.
* License secrets.
* Backup production.
* Customer data.

WordPress hỗ trợ phân biệt môi trường qua cấu hình môi trường; dự án này chỉ dùng hai môi trường thật — local development và production — theo Amendment 2026-08-03, không có staging riêng.

## 10.3. Setup script

Một lệnh hoặc một chuỗi lệnh tự động phải thực hiện:

1. Cài dependencies.
2. Tạo cấu hình.
3. Kết nối database.
4. Cài WordPress.
5. Cài WooCommerce.
6. Kích hoạt plugin.
7. Kích hoạt theme.
8. Tạo các trang WooCommerce.
9. Cấu hình permalink.
10. Cấu hình locale Việt Nam.
11. Cấu hình tiền tệ VND.
12. Cấu hình timezone.
13. Tạo owner.
14. Tạo staff.
15. Import nội dung mẫu.
16. Cấu hình payment.
17. Cấu hình shipping.
18. Flush rewrite.
19. Clear cache.
20. Chạy health check.

Một số secret hoặc thông tin shop vẫn được nhập sau deploy:

* Logo.
* Domain.
* Email.
* Số điện thoại.
* Địa chỉ.
* Tài khoản ngân hàng.
* API key của dịch vụ ngoài.
* Nội dung chính sách riêng.

---

# 11. Cấu trúc repository đề xuất

```text
commerce-site/
├── composer.json
├── composer.lock
├── .env.example
├── config/
│   ├── environments/
│   ├── roles/
│   ├── shop-defaults/
│   └── plugin-manifest/
├── web/
│   └── app/
│       ├── themes/
│       │   └── shop-child/
│       └── mu-plugins/
│           └── site-policy/
├── scripts/
│   ├── install.sh
│   ├── configure.sh
│   ├── seed.sh
│   ├── deploy.sh
│   ├── backup.sh
│   ├── restore.sh
│   └── health-check.sh
├── content/
│   ├── pages/
│   ├── policies/
│   ├── menus/
│   └── sample-products/
├── tests/
│   ├── smoke/
│   ├── checkout/
│   └── regression/
└── docs/
    ├── DEPLOYMENT.md
    ├── ADMIN-HANDBOOK.md
    ├── BACKUP-RESTORE.md
    ├── UPDATE-POLICY.md
    └── PLUGIN-REGISTER.md
```

Đây là repository của **một website**, không phải multi-site platform.

Có thể tạo repository template để tái sử dụng cho website khác, nhưng mỗi website sau khi tạo sẽ có:

* Repository riêng.
* Database riêng.
* Domain riêng.
* License riêng.
* Backup riêng.
* Release cycle riêng.

---

# 12. Backup và khôi phục

Dùng plugin backup đã có, kết hợp backup hosting nếu có.

Backup gồm:

* Database.
* Uploads.
* Theme.
* MU plugin.
* Cấu hình cần thiết.
* Dữ liệu plugin.
* Manifest phiên bản.

Cần hỗ trợ:

* Backup tự động.
* Backup trước update.
* Backup off-site.
* Tải backup thủ công.
* Restore lên môi trường kiểm thử cục bộ (không có staging riêng — xem Amendment 2026-08-03).
* Restore production.
* Kiểm thử restore định kỳ.

Repository không thay thế database backup.

Database backup không thay thế repository.

Một bên chứa code, bên kia chứa thứ khách hàng đã nhập lúc hai giờ sáng rồi quên mất.

---

# 13. Bảo mật

Tận dụng WordPress hardening, hosting và plugin bảo mật có sẵn.

Yêu cầu:

* SSL.
* Strong password.
* Rate limiting.
* Chống brute force.
* Giới hạn login attempt.
* Tắt file editor.
* Khóa cài plugin với owner và staff.
* Tách environment secret.
* Cập nhật có kiểm soát.
* Backup trước update.
* File permission phù hợp.
* Không lưu dữ liệu thẻ.
* Logging thao tác quan trọng.
* CAPTCHA hoặc anti-spam ở form cần thiết.
* 2FA cho owner nếu plugin được chọn hỗ trợ.

WordPress cung cấp hướng dẫn hardening và khuyến nghị bảo vệ `wp-config.php`, file permission cũng như vô hiệu hóa trình chỉnh sửa mã trong admin.

Không cài nhiều security plugin chồng lên nhau.

---

# 14. Cập nhật và phát hành

## 14.1. Không auto-update vô điều kiện

Không cho production tự cập nhật:

* WordPress major version.
* WooCommerce major version.
* Theme.
* Plugin ảnh hưởng checkout.
* Plugin payment.
* Plugin order.
* Plugin affiliate.

## 14.2. Quy trình update

1. Renovate, Dependabot hoặc kiểm tra thủ công phát hiện phiên bản mới.
2. Cập nhật lockfile.
3. Deploy lên local (môi trường phát triển) và chạy automated checks — không có staging riêng.
4. Chạy smoke test.
5. Test sản phẩm.
6. Test giỏ hàng.
7. Test voucher.
8. Test checkout.
9. Test COD.
10. Test chuyển khoản QR.
11. Test email.
12. Test admin.
13. Backup production.
14. Deploy production.
15. Chạy migration.
16. Kiểm tra health.
17. Có phương án rollback.

WooCommerce khuyến nghị kiểm thử extension với các phiên bản mới của PHP, WordPress, WooCommerce và những extension đang hoạt động.

---

# 15. Control plane vận hành

Không xây control plane riêng.

Dùng kết hợp:

* Hosting control panel.
* WP-CLI.
* WordPress Site Health.
* WooCommerce Status.
* Backup plugin.
* Security plugin.
* SMTP log.
* Error log.
* Uptime monitor bên ngoài.
* Git và CI/CD.

Developer có thể thực hiện:

* Deploy.
* Rollback.
* Backup.
* Restore.
* Clear cache.
* Update plugin.
* Update theme.
* Chạy database migration.
* Kiểm tra cron.
* Kiểm tra WooCommerce status.
* Kiểm tra dung lượng.
* Kiểm tra email.
* Kiểm tra lỗi PHP.

Chủ shop không nhìn thấy control plane kỹ thuật.

---

# 16. Phạm vi MVP mới

## 16.1. Bao gồm

* WordPress.
* WooCommerce.
* Một theme nền.
* Một child theme hoặc style preset.
* Trang chủ.
* Trang shop.
* Trang sản phẩm.
* Danh mục.
* Giỏ hàng.
* Checkout.
* My Account.
* Sản phẩm đơn giản.
* Sản phẩm biến thể.
* Một kho.
* Coupon cơ bản.
* COD.
* Chuyển khoản.
* QR thanh toán qua plugin.
* Phí vận chuyển theo rule.
* Nhận hàng tại cửa hàng.
* Đơn hàng.
* Tạo đơn thủ công.
* Refund.
* Review.
* Owner.
* Staff.
* SEO plugin.
* Cache.
* SMTP.
* Backup.
* Security baseline.
* Admin menu rút gọn.
* Cài đặt bằng code.
* Local development và automated checks trước production (không có staging riêng).
* Quy trình update.
* Tài liệu bàn giao.

## 16.2. Tùy chọn cài thêm

* Bundle.
* Buy X Get Y.
* Advanced coupon.
* Affiliate.
* Referral.
* Social login.
* OTP.
* Review ảnh.
* Return/RMA.
* Xác nhận chuyển khoản tự động.
* Tích hợp vận chuyển.
* Tracking nâng cao.

## 16.3. Không làm

* Commerce backend mới.
* Custom admin panel.
* Custom page builder.
* Custom cart.
* Custom checkout.
* Custom order model.
* Custom product model.
* Custom payment engine.
* Custom shipping engine.
* Custom affiliate engine.
* Custom analytics platform.
* Shopify backend.
* Đồng bộ WooCommerce với Shopify.
* Headless frontend.
* Mobile app.
* Multi-tenant.
* Multi-vendor.
* Multi-store.
* Multi-warehouse.
* Public API platform.
* AI.
* Marketplace plugin riêng.

---

# 17. Các giai đoạn thực hiện

## Giai đoạn 1: Chọn stack

* Chọn cấu trúc WordPress.
* Chọn theme nền.
* Chọn plugin bắt buộc.
* Chọn plugin tùy chọn.
* Kiểm tra license.
* Kiểm tra block compatibility.
* Lập plugin manifest.
* Xác định hosting baseline.

**Đầu ra:** Approved Stack Manifest.

## Giai đoạn 2: Tạo bộ cài bằng code

* Tạo repository.
* Thiết lập Composer.
* Thiết lập environment.
* Thiết lập WP-CLI.
* Tạo install script.
* Tạo configuration script.
* Tạo seed data.
* Tạo role owner và staff.
* Tạo plugin lockfile.

**Đầu ra:** Website có thể dựng lại từ repository.

## Giai đoạn 3: Dựng giao diện

* Cấu hình theme.
* Tạo child theme nếu cần.
* Tạo style preset.
* Tạo pattern.
* Thiết lập trang chủ.
* Thiết lập product page.
* Thiết lập archive.
* Thiết lập Cart và Checkout.
* Thiết lập mobile layout.

**Đầu ra:** Storefront giao diện hoàn chỉnh bằng thành phần có sẵn (theme nền thực tế là Botiga Free theo quyết định 2026-08-04, xem `docs/THEME-DECISION.md` — "Storefront" ở đây mang nghĩa chung "mặt tiền cửa hàng", không phải tên theme).

## Giai đoạn 4: Cấu hình commerce

* Sản phẩm.
* Biến thể.
* Kho.
* Voucher.
* COD.
* Bank transfer.
* QR.
* Shipping rules.
* Local pickup.
* Refund.
* Review.
* Customer account.

**Đầu ra:** Có thể chạy trọn luồng đặt hàng.

## Giai đoạn 5: Rút gọn admin

* Ẩn menu.
* Tạo role.
* Khóa phần kỹ thuật.
* Tối giản dashboard.
* Chuẩn hóa màn hình xử lý đơn.
* Viết hướng dẫn owner.
* Viết hướng dẫn staff.

**Đầu ra:** Chủ shop không phải vận hành một bảng điều khiển phản ứng hạt nhân.

## Giai đoạn 6: Vận hành

* Backup.
* Restore.
* SMTP.
* Logging.
* Security.
* Cache.
* Monitoring.
* Local/WSL validation trước production; GitHub Actions informational only (không có staging riêng).
* Update workflow.
* Rollback workflow.

**Đầu ra:** Website có thể bảo trì mà không cần nghi lễ gọi hồn developer cũ.

## Giai đoạn 7: QA và bàn giao

* Test desktop.
* Test mobile.
* Test checkout.
* Test COD.
* Test QR.
* Test voucher.
* Test refund.
* Test stock.
* Test email.
* Test backup.
* Test restore.
* Test owner role.
* Test staff role.
* Kiểm tra hiệu năng.
* Kiểm tra SEO.
* Bàn giao tài liệu.

**Đầu ra:** Website production-ready.

---

# 18. Tiêu chí hoàn thành

Website được coi là đạt khi:

1. Có thể dựng lại từ repository và cấu hình môi trường.
2. Không cần cài plugin thủ công từng cái.
3. Không cần bấm lại hàng chục thiết lập sau mỗi lần deploy.
4. Chủ shop chỉ thấy các chức năng cần vận hành.
5. Staff không thể làm hỏng cấu hình kỹ thuật.
6. Khách có thể hoàn thành đơn hàng trên mobile.
7. COD hoạt động.
8. Chuyển khoản và QR hoạt động.
9. Voucher hoạt động.
10. Tồn kho được cập nhật đúng.
11. Email đơn hàng được gửi đúng.
12. Backup có thể restore.
13. Update có kiểm tra local/WSL và rollback (không có staging riêng; CI chỉ thông tin).
14. Không sửa WordPress core.
15. Không sửa WooCommerce core.
16. Không sửa trực tiếp plugin bên thứ ba.
17. Không có hai plugin trùng chức năng.
18. Không có Shopify dependency.
19. Website có thể di chuyển sang hosting khác.
20. Mọi phần custom đều nhỏ, có tài liệu và có lý do tồn tại.

---

# 19. Tuyên bố phạm vi cuối

Sản phẩm cuối cùng là:

> Một bộ triển khai WordPress/WooCommerce được quản lý bằng code, sử dụng theme và plugin có sẵn, có cấu hình an toàn, admin rút gọn, quy trình backup, update và bàn giao hoàn chỉnh cho một shop Việt Nam.

Giá trị của sản phẩm không nằm ở việc viết nhiều code.

Giá trị nằm ở:

* Chọn đúng thành phần.
* Loại bỏ thành phần thừa.
* Khóa cấu hình.
* Chuẩn hóa triển khai.
* Chuẩn hóa giao diện.
* Chuẩn hóa vận hành.
* Chuẩn hóa cập nhật.
* Giảm khả năng chủ shop tự phá website.
* Giảm thời gian developer phải cài đặt lại cùng một thứ.
