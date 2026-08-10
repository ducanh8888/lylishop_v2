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
* Kiến trúc classic/hybrid — khớp yêu cầu "không FSE làm kiến trúc chính", giảm số biến khi tích hợp Classic Cart/Checkout và Vietnam Store Toolkit. SePay hiện **DEFERRED / OPTIONAL**.
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

**Fallback 1 — Blocksy Free.** Chỉ dùng khi Botiga có vấn đề tương thích thật sự (không phải sở thích thị giác) với: WooCommerce; Classic Cart; Classic Checkout; Vietnam Store Toolkit; plugin commerce đang active; trường cá nhân hóa sản phẩm; product gallery trên mobile.

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

## 9. Quyết định thị giác — trạng thái cập nhật 2026-08-04 (vòng 2)

**Đã chốt (không tự ý mở lại):**

* **Palette binding được founder cập nhật 2026-08-10:** `#7A3B17`, `#FFFCF7`, `#FBEFE5`, `#F6E4E3`, `#E9F1EA`, `#C2C3D2`. Quyết định này thay thế cặp nâu cũ; `#8A4A23` không còn là brand-secondary. Kế hoạch migration: `docs/BRAND-MOBILE-REMEDIATION-PLAN.md`.
* **Cấu trúc navigation chính:** 5 danh mục sản phẩm — Móc khóa len, Gấu bông len, Hoa len, Hộp quà, Đặt mẫu theo yêu cầu — **đã chốt** làm navigation cấp cao duy nhất cho V1. Size S/M/L **không** được dùng làm danh mục cấp cao; chỉ dùng làm attribute/variation/filter/subdivision bên trong danh mục — xem `docs/WEBSITE-REQUIREMENTS.md`.
* **Chính sách publish sản phẩm thiếu ảnh:** đã chốt làm quy tắc vận hành chính thức (không còn "provisional") — xem `docs/WEBSITE-REQUIREMENTS.md`.

**Vẫn còn mở (chi tiết thị giác duy nhất còn lại liên quan ảnh):**

* **Thiết kế placeholder ảnh cụ thể** (hình minh họa, bố cục, có dùng màu token ở trên hay không) cho sản phẩm chưa có ảnh thật — đây là chi tiết thiết kế thị giác **duy nhất** còn treo trong nhóm quyết định ảnh sản phẩm.
* **Hiện trạng cập nhật 2026-08-06:** nhiệm vụ founder cấp phép một lần (xem `docs/PRODUCTION-STATUS.md`) sẽ triển khai thiết kế thị giác V1 đầy đủ trong `shop-child` — trang chủ, template trang, component thật, block pattern Gutenberg được kiểm soát, style Classic Cart/Checkout, responsive/accessibility. Kết quả thực tế kèm bằng chứng sẽ được ghi vào `docs/PRODUCTION-STATUS.md` và báo cáo baseline. Ghi chú lịch sử: mục này từng ghi (2026-08-04) rằng thiết kế thị giác "chưa được thực hiện" — trạng thái đó chỉ còn là lịch sử.

## 10. Điều kiện mở lại quyết định này

Quyết định Botiga Free chỉ nên được xem xét lại (không phải "có thể đổi tùy hứng") khi:

1. `docs/THEME-COMPATIBILITY-GATE.md` ghi nhận một FAIL không thể khắc phục bằng cấu hình, hook nhỏ trong `shop-child`, CSS phạm vi hẹp, hoặc patch tương thích plugin an toàn.
2. Botiga Free ngừng được duy trì (không còn cập nhật bảo mật/tương thích WordPress-WooCommerce trong thời gian dài).
3. Một khoảng trống tính năng được tài liệu hoá đầy đủ khiến Botiga Pro (hoặc theme khác) rẻ hơn/an toàn hơn rõ rệt so với duy trì code tương đương — vẫn cần founder phê duyệt riêng theo mục 7.
4. Founder chủ động quyết định lại, không phụ thuộc điều kiện kỹ thuật.

Không mở lại quyết định chỉ vì sở thích thị giác hoặc vì một theme khác có demo hấp dẫn hơn.

## 11. Design tokens (nền tảng — đã wire vào output từ 2026-08-06)

`shop-child/theme.json` là nguồn token chuẩn; frontend/editor CSS tham chiếu các biến preset WordPress thay vì lặp lại palette trong PHP. Child theme gỡ đúng filter `botiga_filter_theme_json_data_theme` vì Botiga 2.4.7 dùng filter này để ghi đè toàn bộ palette child bằng palette Customizer. **Triển khai 2026-08-10:** sáu màu dưới đây đã active trên `shop-child` 1.3.0, commit `1db61fbcccc92cc9f199ff8423d393a1fe5a1726`, release `20260810145039`.

### Màu — binding 2026-08-10, đã triển khai

| Token Gutenberg/runtime | Hex | Vai trò |
|---|---|---|
| `lyli-primary` | `#7A3B17` | Heading, CTA, accent/border nhỏ; mục tiêu 5–15%, không làm nền lớn |
| `lyli-warm-white` | `#FFFCF7` | Nền chính/warm whitespace; mục tiêu 35–55% |
| `lyli-cream` | `#FBEFE5` | Nền/card phụ; mục tiêu 20–40% |
| `lyli-blush` | `#F6E4E3` | Accent hồng phấn thưa; mục tiêu 5–15% |
| `lyli-sage` | `#E9F1EA` | Accent xanh sage thưa; mục tiêu 5–15% |
| `lyli-lavender` | `#C2C3D2` | Accent tím xám tiết chế; mục tiêu 5–15% |

### Lịch sử candidate — đã superseded

Hai candidate dưới đây là lịch sử quyết định trước 2026-08-10, không còn là lựa chọn mở:

* Candidate A (`11_Brand_Guideline`): **đã được founder chốt** cùng `#7A3B17` thành sáu màu binding ở trên.
* Candidate B (`16_Brand_System_Detail`): warm beige `#F4ECE5`, blush pink `#E8CFCF`, cream `#FFFDF9`, light gray `#F6F5F3`, text `#3D312B`, border `#DDD7D0`.

`#8A4A23` chỉ được giữ trong lịch sử Git/tài liệu before-state; đã retire khỏi palette công khai, frontend/editor CSS và pattern source. Text/muted/border/error/success tồn tại ngoài sáu màu với nhãn **FUNCTIONAL / ACCESSIBILITY NEUTRALS**, không phải brand colors.

### Typography — đã triển khai và owner-editable

* Heading: **Fraunces SemiBold (`600`)**.
* Body: **Be Vietnam Pro Regular (`400`)**; CTA/button: **Be Vietnam Pro Medium (`500`)**.
* `shop-child/theme.json` đăng ký hai preset Gutenberg với tên **Fraunces — Tiêu đề** và **Be Vietnam Pro — Nội dung & CTA**; shop owner có thể chọn font/weight trên block được hỗ trợ trong WP Admin.
* Aristotelica Pro: chỉ nơi asset logo chính thức yêu cầu — **không commit file font Aristotelica Pro vào repository** (bản quyền, chỉ dùng trong asset logo đã có sẵn ngoài repo).
* Fraunces/Be Vietnam Pro **không commit file font nào** vào repository — tải qua Google Fonts lúc runtime; fallback an toàn (serif/sans-serif hệ thống) trong stack. Chi tiết triển khai và bằng chứng production: `docs/TYPOGRAPHY-IMPLEMENTATION-2026-08-08.md`.

### Không thuộc phạm vi bước này

* *(Lịch sử, hết hiệu lực 2026-08-06)* Không có CSS component nào được viết (button, card, header thật) — **đã thay đổi:** component V1 đầy đủ trong `shop-child`.
* Không có file font nhị phân nào được thêm vào repository — **vẫn đúng:** font Fraunces/Be Vietnam Pro tải qua Google Fonts (runtime), không commit binary; Aristotelica Pro vẫn chỉ dùng qua asset logo ngoài repo nếu có.
* Không có màu nền/kem cuối cùng nào được chọn — **hết hiệu lực 2026-08-10:** founder đã chốt sáu màu và runtime đã triển khai tại release `20260810145039`.
* Không có thiết kế placeholder ảnh nào được tạo — **vẫn đúng:** chưa tạo placeholder ảnh; sản phẩm thiếu ảnh thật giữ draft theo chính sách đã chốt.
