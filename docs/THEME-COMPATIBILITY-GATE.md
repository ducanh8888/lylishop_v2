# THEME COMPATIBILITY GATE — Botiga Free + shop-child

Danh sách này là gate hồi quy cho lần thay đổi kế tiếp. Các dòng `NOT RUN` bên dưới nói về **Vietnam Toolkit + brand/mobile release đang planned**, không phủ nhận storefront production hiện tại đã public và từng smoke PASS; xem `docs/PRODUCTION-STATUS.md`.

## Nguyên tắc chung

* Mỗi mục có kết quả: **PASS**, **FAIL**, hoặc **NOT RUN**.
* Không được ghi PASS nếu chưa thực sự chạy kiểm tra đó trên một site thật.
* **Sở thích thị giác không phải là FAIL.** Ví dụ "trông chưa đủ pastel" không phải lý do fail — chỉ lỗi chức năng/tương thích mới được tính.
* FAIL chỉ dẫn tới kích hoạt fallback (Blocksy Free → Storefront, `docs/THEME-DECISION.md` mục 6) **sau khi** đã thử hết bốn hướng khắc phục theo đúng thứ tự:
  1. Cấu hình (setting có sẵn của theme/plugin).
  2. Hook nhỏ trong `shop-child` (action/filter, không phải sửa core/plugin).
  3. CSS phạm vi hẹp trong `shop-child`.
  4. Patch tương thích plugin an toàn (không sửa trực tiếp source plugin bên thứ ba — qua hook/filter chính thức của plugin đó).
* Chỉ khi cả 4 hướng trên đều không giải quyết được, FAIL mới được ghi là "không thể khắc phục" và fallback mới được cân nhắc.

## Danh sách kiểm tra

| # | Hạng mục | Cách kiểm tra (bằng chứng) | Kết quả |
|---|---|---|---|
| 1 | WooCommerce activation | Kích hoạt Botiga + WooCommerce cùng lúc, không có PHP fatal/warning trong `wp-content/debug.log` | NOT RUN |
| 2 | Product archive | Trang Shop hiển thị đúng danh sách sản phẩm, không vỡ layout với 5 danh mục hiện hành | NOT RUN |
| 3 | Simple product | Trang chi tiết một simple product render đầy đủ: ảnh, giá, mô tả, nút thêm giỏ | NOT RUN |
| 4 | Variable product | Trang chi tiết variable product: chọn biến thể cập nhật đúng giá/ảnh/tồn kho | NOT RUN |
| 5 | Personalization fields | Trường cá nhân hóa (Có/Không + phụ phí, `docs/PRODUCT-BRIEF.md`) hiển thị và hoạt động đúng trên trang sản phẩm | NOT RUN |
| 6 | Product Bundles | Plugin WooCommerce Product Bundles render đúng trên product page/archive khi kích hoạt (`docs/PLUGIN-MANIFEST.md`) | NOT RUN |
| 7 | Smart Coupons | Áp dụng coupon nâng cao không vỡ giao diện Cart/Checkout | NOT RUN |
| 8 | Vietnam address fields | Trường địa chỉ Việt Nam (Vietnam Store Toolkit hoặc fallback, `docs/PLUGIN-MANIFEST.md`) hiển thị đúng trên Checkout | NOT RUN |
| 9 | BACS/VietQR safe baseline | Toolkit không tự bật BACS/VietQR; khi chưa có merchant data không có QR hoặc gateway mới; SePay là DEFERRED/OPTIONAL | NOT RUN |
| 10 | Classic Cart | Trang `/cart/` (shortcode/template) hoạt động đầy đủ: cập nhật số lượng, xóa sản phẩm, áp coupon | NOT RUN |
| 11 | Classic Checkout | Trang `/checkout/` hoạt động đầy đủ: điền thông tin, chọn vận chuyển, chọn thanh toán, đặt hàng | NOT RUN |
| 12 | Order confirmation | Trang xác nhận đơn hiển thị đúng thông tin đơn hàng sau khi đặt | NOT RUN |
| 13 | Account pages | My Account: đăng nhập, đơn hàng, địa chỉ, sửa thông tin hoạt động đúng giao diện | NOT RUN |
| 14 | Mobile menu | Menu mobile (hamburger/offcanvas) mở/đóng đúng, đủ item theo `docs/WEBSITE-REQUIREMENTS.md` | NOT RUN |
| 15 | Product gallery | Gallery ảnh sản phẩm (zoom/slide nếu có) hoạt động đúng trên desktop và mobile | NOT RUN |
| 16 | One-image products | Layout ổn định khi sản phẩm chỉ có đúng 1 ảnh (`docs/WEBSITE-REQUIREMENTS.md` — chính sách ảnh thiếu) — không vỡ gallery/card | NOT RUN |
| 17 | 4:5 image presentation | Ảnh sản phẩm tỷ lệ 4:5 hiển thị đúng, không bị crop sai hay méo trên card/gallery | NOT RUN |
| 18 | Keyboard navigation | Điều hướng toàn bộ menu, filter, form, Cart/Checkout bằng bàn phím, focus state rõ ràng | NOT RUN |
| 19 | Responsive layout | Home, Shop, Product, Cart, Checkout, Account tại 375/768/1440; không overflow bị che, header/grid/hero/footer đạt plan | NOT RUN |
| 20 | PHP errors | `wp-content/debug.log` sạch (không notice/warning/fatal mới) qua toàn bộ luồng test ở trên | NOT RUN |
| 21 | JavaScript errors | Console trình duyệt sạch lỗi JS qua toàn bộ luồng test ở trên | NOT RUN |
| 22 | Template override warnings | `wp_ tools`/WooCommerce Status → "Templates" không cảnh báo template lỗi thời chưa được `shop-child` cập nhật theo phiên bản WooCommerce hiện hành | NOT RUN |

## Kết luận hiện tại

**Release kế tiếp: toàn bộ 22 mục NOT RUN.** Pre-flight hiện tại không sửa runtime. Gate phải chạy sau deploy private/guarded và trước KEEP; GitHub Actions không quyết định gate. Chi tiết plugin tại `docs/VIETNAM-STORE-TOOLKIT-PREFLIGHT.md`, visual tại `docs/BRAND-MOBILE-REMEDIATION-PLAN.md`.
