# THEME IMPLEMENTATION PLAN — Botiga Free + shop-child

Mô tả trình tự triển khai **trong tương lai**. Không có bước nào dưới đây đã được thực thi trong tác vụ này (repository-only task — xem `docs/THEME-DECISION.md`). Trước khi bắt đầu bước 1, `docs/THEME-DECISION.md` phải ở trạng thái ACCEPTED (đã đúng) và không có blocker mới trong `docs/HOSTING-AUDIT.md`.

## Trình tự 14 bước

### Required V1

1. **Xác minh nguồn gói Botiga và bản phát hành tương thích hiện hành.**
   Đã xác minh một phần trong quyết định này: WordPress.org Themes API xác nhận theme tồn tại (slug `botiga`, bản 2.4.7, cập nhật 22/07/2026). **Chưa xác minh trực tiếp qua wpackagist** — endpoint `wpackagist.org/packages/wpackagist-theme/botiga.json` và `wpackagist.org/p2/wpackagist-theme/botiga.json` trả về `403 Forbidden` (Cloudflare bot protection) khi truy vấn qua `curl`/WebFetch trong tác vụ này; endpoint gốc `wpackagist.org/packages.json` phản hồi bình thường (200 OK), xác nhận đây là repository Composer hợp lệ đang hoạt động. Bước này cần lặp lại bằng cách chạy `composer show wpackagist-theme/botiga --all` thật (Composer tự thương lượng với Cloudflare khác cách `curl` thông thường) trên máy có Composer, hoặc trong CI, trước khi sang bước 2.
2. **Cập nhật Composer manifest.**
   Thêm `"wpackagist-theme/botiga": "<version xác nhận ở bước 1>"` vào `composer.json`; xoá hoặc giữ lại `wpackagist-theme/storefront` tuỳ quyết định — khuyến nghị **giữ lại** vì Storefront là fallback cấp 2 chính thức (`docs/THEME-DECISION.md` mục 6), không phải rác cần dọn. Không pin version chưa xác minh (xem bước 1).
3. **Chuẩn hoá metadata `shop-child`.**
   `Theme Name`, `Template: botiga`, text domain, version placeholder, mô tả — đã thực hiện một phần trong tác vụ này (xem phần "Child-theme preparation" của báo cáo cuối). Hoàn thiện: `Requires at least`, `Requires PHP`, `Tags` nếu cần.
4. **Enqueue asset cha/con đúng cách.**
   `wp_enqueue_style` cho style cha (Botiga) làm dependency của style con, tôn trọng cấu trúc asset thật của Botiga (không giả định tên file `style.css` là toàn bộ CSS cha — cần kiểm tra Botiga có tách CSS theo module hay không).
5. **Đưa design token vào.**
   Biến CSS custom properties hoặc cấu hình tương đương cho màu, khoảng cách — **chỉ sau khi** màu chính (`#7A3B17` vs `#8A4A23`) được founder chốt (`docs/THEME-DECISION-BRIEF.md` mục 15.1).
6. **Đăng ký typography.**
   Fraunces (heading), Be Vietnam Pro (body/CTA), Aristotelica Pro (chỉ nơi logo yêu cầu) — self-hosted hoặc qua phương án đã duyệt, không phụ thuộc Google Fonts runtime nếu chính sách privacy/hiệu năng yêu cầu khác (cần xác nhận riêng, chưa có quyết định).
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
