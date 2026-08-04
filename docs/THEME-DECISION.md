# THEME DECISION — Lyli Shop V1

## 1. Trạng thái quyết định

**ACCEPTED — BOTIGA FREE SELECTED FOR V1**

Ngày chốt: 2026-08-04. Người chốt: Founder. Đây là quyết định ràng buộc (binding), thay thế mọi ghi chú tạm về Storefront trong `TECH_STACK.md` mục 3.1 trước đó.

## 2. Theme được chọn

* **Theme cha:** Botiga Free (WordPress.org, tác giả aThemes). Xác nhận tồn tại và đang được duy trì qua WordPress.org Themes API: slug `botiga`, bản 2.4.7, cập nhật lần cuối 22/07/2026, 734.242 lượt tải, đánh giá 98% (2026-08-04).
* **Theme con:** `shop-child` (repo này, `web/app/themes/shop-child/`).

## 3. Kiến trúc

* Classic/hybrid WordPress theme architecture — **không** dùng Full Site Editing làm kiến trúc chính cho V1.
* Botiga Free làm parent, `shop-child` làm child (`Template: botiga`).
* Gutenberg core blocks cho nội dung; block pattern được đóng gói, kiểm soát trong `shop-child`.
* Classic Cart, Classic Checkout — nhất quán với TECH_STACK.md mục 4.1 (không đổi bởi quyết định theme này).
* Không dùng: Elementor, Brizy, WPBakery, Divi, Oxygen, Bricks, UX Builder, hay bất kỳ page builder bên thứ ba nào khác.
* Không dùng WooCommerce Cart hoặc Checkout Blocks trong V1.

## 4. Lý do chọn Botiga Free

* Theme WooCommerce miễn phí, đang được duy trì tích cực (cập nhật 22/07/2026 — rất gần thời điểm quyết định, không phải theme bị bỏ rơi).
* Kiến trúc classic/hybrid — khớp yêu cầu "không FSE làm kiến trúc chính", giảm rủi ro tương thích với Classic Cart/Checkout và các plugin thương mại Việt Nam (SePay, Vietnam Store Toolkit) vốn chưa công bố rõ hỗ trợ Blocks/FSE (xem `docs/HOSTING-AUDIT.md` mục 14, mâu thuẫn #2 gốc).
* Không phụ thuộc page builder — khớp nguyên tắc "không tự xây/không phụ thuộc page builder" của PLAN.md mục 6.1 và TECH_STACK.md mục 3.2.
* Có bản miễn phí đầy đủ để xây dựng V1 mà không cần license trả phí — khớp nguyên tắc reuse-first, minimal-custom.
* Hỗ trợ child theme theo chuẩn WordPress (`Template:` header), không cần cơ chế riêng.

Đây là quyết định của founder; lý do trên ghi lại **để tài liệu hoá quyết định**, không phải kết quả một quy trình đánh giá kỹ thuật độc lập được thực hiện trong tác vụ này (không có install/test thật nào chạy — xem `docs/THEME-COMPATIBILITY-GATE.md` cho việc kiểm chứng thực tế còn phải làm).

## 5. Phương án bị loại

| Phương án | Lý do loại |
|---|---|
| Storefront (ghi tạm trước đó trong TECH_STACK.md) | Không còn là lựa chọn chính; giữ lại làm **fallback cấp 2** (xem mục 6). |
| Elementor / Brizy / WPBakery / Divi / Oxygen / Bricks / UX Builder | Page builder bên thứ ba — cấm theo TECH_STACK.md mục 3.2 và PLAN.md mục 6.1, không riêng gì quyết định theme này. |
| Full Site Editing làm kiến trúc chính V1 | Rủi ro tương thích Classic Cart/Checkout và plugin thương mại Việt Nam chưa xác nhận Blocks/FSE; có thể xem lại sau khi các plugin đó xác nhận tương thích. |
| Blossom Shop / Ona / Blossom Floral / Blossom Beauty | **Chỉ là tham khảo thị giác** (serif mềm, khoảng trắng rộng, nhịp section kiểu boutique, nền pastel, cảm giác handmade tiết chế, layout lấy sản phẩm làm trung tâm) — không dùng làm nền tảng V1, không copy layout/code/asset/nội dung demo của các theme này. |
| Botiga Pro | Không được phép mua/dùng — xem mục 7. |

## 6. Fallback hierarchy

**Fallback 1 — Blocksy Free.** Chỉ dùng khi Botiga có vấn đề tương thích thật sự (không phải sở thích thị giác) với: WooCommerce; Classic Cart; Classic Checkout; SePay; plugin địa chỉ/vận chuyển Việt Nam; Product Bundles; Smart Coupons; trường cá nhân hóa sản phẩm; product gallery trên mobile.

**Fallback 2 — Storefront.** Chỉ dùng nếu cả Botiga và Blocksy đều gặp vấn đề tương thích thương mại không chấp nhận được — baseline tương thích khẩn cấp.

Điều kiện kích hoạt chi tiết, dựa trên bằng chứng: `docs/THEME-COMPATIBILITY-GATE.md`. **Không đổi theme chỉ vì theme khác có nhiều tùy chọn hơn hoặc demo đẹp hơn.**

## 7. Chính sách nâng cấp trả phí

**Botiga Pro không được phép mua, tải, yêu cầu, hoặc thiết kế theo hướng cần nó.**

Chỉ cân nhắc nâng cấp trả phí sau này nếu **có tài liệu** ghi rõ một khoảng trống tính năng cụ thể, và tính năng trả phí đó rẻ hơn/an toàn hơn rõ rệt so với việc duy trì code tự viết tương đương. Quyết định nâng cấp (nếu có) vẫn cần founder phê duyệt riêng, không tự động phát sinh từ tài liệu này.

## 8. Ràng buộc (constraints)

Nhắc lại — không đổi bởi quyết định theme này:

* `shop-child` chỉ chứa: brand design token, typography, spacing, style header/footer, trình bày trang chủ, product card, style archive sản phẩm, style trang sản phẩm, style Classic Cart, style Classic Checkout, responsive behavior, block pattern Gutenberg được kiểm soát, template override WooCommerce có lý do hẹp và được ghi lại.
* `shop-child` **không** chứa: logic thanh toán, order workflow, logic coupon, logic bundle, query trực tiếp bảng WooCommerce, custom commerce model, logic thay thế plugin, deployment credentials, production content.
* Không sửa WordPress core, WooCommerce core, hay source code Botiga/Blocksy/Storefront.
* Không cài đặt WordPress, không tạo database, không deploy production, không sửa `public_html` trong phạm vi quyết định này.

## 9. Quyết định thị giác chưa giải quyết (không tự chốt hộ)

* **Màu nâu chính:** `#7A3B17` so với `#8A4A23` — vẫn mở, xem `docs/THEME-DECISION-BRIEF.md` mục 15.1.
* **Chốt tuyệt đối cấu trúc navigation:** hướng đi dẫn đầu là 5 danh mục sản phẩm (không phải Size S/M/L) đã ghi nhận, nhưng chưa phải phê duyệt 100% — xem `docs/THEME-DECISION-BRIEF.md` mục 15.2 và `docs/WEBSITE-REQUIREMENTS.md`.
* **Thiết kế placeholder ảnh cuối cùng** cho sản phẩm thiếu ảnh — quy tắc vận hành đã có (`docs/WEBSITE-REQUIREMENTS.md`), nhưng hình ảnh/màu placeholder cụ thể chưa chốt.
* Việc build thiết kế thị giác cuối cùng (màu, trang chủ, template trang) **chưa được thực hiện trong tác vụ này** — chỉ chuẩn bị metadata skeleton của `shop-child`.

## 10. Điều kiện mở lại quyết định này

Quyết định Botiga Free chỉ nên được xem xét lại (không phải "có thể đổi tùy hứng") khi:

1. `docs/THEME-COMPATIBILITY-GATE.md` ghi nhận một FAIL không thể khắc phục bằng cấu hình, hook nhỏ trong `shop-child`, CSS phạm vi hẹp, hoặc patch tương thích plugin an toàn.
2. Botiga Free ngừng được duy trì (không còn cập nhật bảo mật/tương thích WordPress-WooCommerce trong thời gian dài).
3. Một khoảng trống tính năng được tài liệu hoá đầy đủ khiến Botiga Pro (hoặc theme khác) rẻ hơn/an toàn hơn rõ rệt so với duy trì code tương đương — vẫn cần founder phê duyệt riêng theo mục 7.
4. Founder chủ động quyết định lại, không phụ thuộc điều kiện kỹ thuật.

Không mở lại quyết định chỉ vì sở thích thị giác hoặc vì một theme khác có demo hấp dẫn hơn.
