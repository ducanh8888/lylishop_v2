# THEME IMPLEMENTATION PLAN — Botiga Free + shop-child

Kế hoạch gốc được giữ để theo dõi quyết định; trạng thái hoàn thành được ghi ngay trên từng bước và đối chiếu với `docs/PRODUCTION-STATUS.md`.

## Trình tự 14 bước

### Required V1

1. **Xác minh nguồn gói Botiga và bản phát hành tương thích hiện hành — HOÀN TẤT 2026-08-04.**
   Xác minh trực tiếp qua wpackagist bằng `/opt/alt/php83/usr/bin/php /usr/local/bin/composer show wpackagist-theme/botiga --all` chạy trong thư mục tạm trên `commerce-host` (đã xoá sau khi xong): package resolve thành công, danh sách đầy đủ 1.0.0 → 2.4.7, dist là `https://downloads.wordpress.org/theme/botiga.2.4.7.zip`, source SVN chính thức, không cần credentials. Bản `2.4.7` có sẵn và là bản mới nhất.
2. **Cập nhật Composer manifest — HOÀN TẤT 2026-08-04.**
   `"wpackagist-theme/botiga": "2.4.7"` đã thêm vào `composer.json`, pin phiên bản chính xác (nhất quán với cách pin các package khác trong file). Giữ lại `wpackagist-theme/storefront` vì là fallback cấp 2 chính thức (`docs/THEME-DECISION.md` mục 6), không phải rác cần dọn. `composer.json` đã validate bằng Composer thật trên `commerce-host` (PHP 8.3) — xem báo cáo validation.
3. **Chuẩn hoá metadata `shop-child`.**
   `Theme Name`, `Template: botiga`, text domain, version placeholder, mô tả — đã thực hiện một phần trong tác vụ trước (xem `docs/THEME-DECISION.md`). Còn thiếu: `Requires at least`, `Requires PHP`, `Tags` nếu cần — chưa làm, không bắt buộc để tiếp tục các bước sau.
4. **Enqueue asset cha/con đúng cách.**
   `wp_enqueue_style` cho style cha (Botiga) làm dependency của style con — đã có khung enqueue tối thiểu trong `functions.php` (handle `botiga-parent`); tôn trọng cấu trúc asset thật của Botiga (không giả định tên file `style.css` là toàn bộ CSS cha — cần kiểm tra Botiga có tách CSS theo module hay không, chỉ xác nhận được sau khi cài đặt thật, chưa làm ở bước này).
5. **Đưa design token vào — NỀN TẢNG ĐÃ GHI, CHƯA WIRE VÀO OUTPUT.**
   Màu nâu chính/phụ đã chốt 2026-08-04 (vòng 2): `#7A3B17` primary, `#8A4A23` secondary/soft. **Hoàn tất 2026-08-07:** token nằm tại `web/app/themes/shop-child/theme.json`, được WordPress resolve thành preset cho frontend/Gutenberg; CSS child chỉ dùng alias semantic. Filter palette của Botiga 2.4.7 đã được gỡ có kiểm soát để không ghi đè token child.
6. **Đăng ký typography — HOÀN TẤT 2026-08-08.**
   Fraunces SemiBold `600` cho heading; Be Vietnam Pro Regular `400` cho body và Medium `500` cho CTA/button. Hai family được đăng ký trong `theme.json` để shop owner chọn trong Gutenberg; tải qua Google Fonts runtime, không commit binary. Bằng chứng: `docs/TYPOGRAPHY-IMPLEMENTATION-2026-08-08.md`.
7. **Header và mobile navigation.**
   Theo `docs/WEBSITE-REQUIREMENTS.md` (Logo, Shop, Bộ sưu tập, Giới thiệu, tìm kiếm, tài khoản, giỏ hàng — sticky).
8. **Homepage pattern.**
   Hero, danh mục, sản phẩm bán chạy, USP/lý do chọn Lyli, CTA — theo `docs/WEBSITE-REQUIREMENTS.md`.
9. **Product card và archive.**
   Layout 5-danh-mục (hướng dẫn đầu, `docs/WEBSITE-REQUIREMENTS.md`), xử lý ổn định khi sản phẩm chỉ có 1 ảnh (chính sách ảnh thiếu, cùng tài liệu).
10. **Trang sản phẩm.**
    Gallery, biến thể, trường cá nhân hóa, CTA — theo `docs/WEBSITE-REQUIREMENTS.md` mục Product Page.
11. **Style Classic Cart và Classic Checkout.**
    Chỉ CSS trình bày; không đổi hành vi/validation của Classic Cart/Checkout.
12. **Test tương thích plugin.**
    Chạy toàn bộ `docs/THEME-COMPATIBILITY-GATE.md`.
13. **Kiểm tra accessibility và responsive.**
    Bàn phím, độ tương phản, breakpoint mobile/tablet/desktop — cũng nằm trong compatibility gate.
14. **Chuẩn bị production release.**
    Build artifact theo `docs/DEPLOYMENT.md`, không tự chạy deploy thật ở bước này.

### Later enhancement (không thuộc phạm vi bắt buộc V1)

* Thêm style variation/preset màu bổ sung sau khi có dữ liệu thị giác đầy đủ hơn.
* Tối ưu hiệu năng nâng cao (lazy-load nâng cao, critical CSS tách theo template) nếu đo đạc thật cho thấy cần thiết.
* Mở rộng block pattern ngoài tập đã kiểm soát ban đầu, nếu chủ shop cần thêm section.
* Xem xét bật Cart/Checkout Blocks — chỉ sau khi mọi plugin thương mại xác nhận tương thích (không thuộc phạm vi quyết định theme này, xem TECH_STACK.md mục 4.1).

### Explicitly prohibited

* Cài đặt hoặc mua Botiga Pro (`docs/THEME-DECISION.md` mục 7).
* Dùng bất kỳ page builder bên thứ ba nào (Elementor, Brizy, WPBakery, Divi, Oxygen, Bricks, UX Builder).
* Dùng Full Site Editing làm kiến trúc chính V1.
* Copy layout, code, asset hoặc nội dung demo từ Blossom Shop/Ona/Blossom Floral/Blossom Beauty — chỉ tham khảo ý tưởng thị giác (`docs/THEME-DECISION.md` mục 5).
* Sửa WordPress core, WooCommerce core, hoặc source code Botiga/Blocksy/Storefront.
* Đưa business logic (thanh toán, order, coupon, bundle) vào `shop-child`.
* Chốt màu, navigation cuối, hoặc thiết kế placeholder ảnh thay founder.
* Cài đặt WordPress thật, tạo database, hoặc deploy production như một phần của việc chuẩn bị theme.
