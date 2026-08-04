# THEME DECISION BRIEF — Lyli Shop

**Trạng thái: THEME nền + kiến trúc ĐÃ CHỐT (2026-08-04) — xem `docs/THEME-DECISION.md`.** Founder đã chọn Botiga Free + `shop-child`, kiến trúc classic/hybrid. Tài liệu này **vẫn còn giá trị** cho các điểm chưa chốt: màu chính, cấu trúc navigation, chính sách ảnh sản phẩm thiếu (mục 15 bên dưới đã cập nhật trạng thái từng điểm). Đây vẫn là tài liệu tổng hợp bằng chứng từ workbook, không phải nơi tự ý quyết định thay founder.

Nguồn: `00_AI_Project_Context`, `06_Website`, `09_Product`, `11_Brand_Guideline`, `16_Brand_System_Detail`, `18_NHẬT KÝ QUYẾT ĐỊNH TOÀN DỰ ÁN` (đọc 2026-08-03). Không có link Google Drive hay dữ liệu khách hàng nào được sao chép vào đây.

---

## 1. Đối tượng khách hàng (target audience)

16–24 tuổi; chủ yếu học sinh, sinh viên, người trẻ thu nhập dưới 15 triệu/tháng; tập trung khu vực Hà Nội; mua cho bản thân, bạn bè, người yêu, gia đình. (Nguồn: `00_AI_Project_Context`, `16_Brand_System_Detail` — Brand Foundation.)

## 2. Tính cách thương hiệu (brand personality)

"Một cô gái nhẹ nhàng, khéo tay, vui vẻ, chú ý chi tiết và luôn làm mọi thứ thật chỉn chu." Cảm xúc thương hiệu: chuyên nghiệp, gần gũi, ấm áp. Tone of voice: thân thiện, chuyên nghiệp, nhiệt tình (theo `16_Brand_System_Detail`); ấm áp, gần gũi, nhẹ nhàng, chân thật, dễ thương vừa đủ (theo `11_Brand_Guideline`). Cả hai nguồn nhất quán về sắc thái, không mâu thuẫn. USP: sự chỉn chu. Phản ứng mong muốn: "Đáng yêu quá!"

## 3. Phong cách thị giác (visual style)

Cả hai nguồn thống nhất: **tối giản kiểu Hàn Quốc**, kết hợp **vintage thủ công** (`11_Brand_Guideline`) / dễ thương nhưng không trẻ con (`16_Brand_System_Detail`); sạch, mềm mại, hiện đại, nhiều khoảng trắng, **sản phẩm thật là trung tâm**. Pastel nhẹ, sáng.

Nguyên tắc thiết kế đã chốt: không rẻ tiền, không rối mắt, đúng màu thương hiệu, trung thực với sản phẩm; khi phân vân giữa hai phương án tương đương, chọn phương án đơn giản hơn nhưng vẫn tinh tế.

Màu bị loại hoàn toàn (đã chốt, không tranh cãi): neon, gradient cầu vồng, đỏ tươi, tím đậm.

## 4. Typography

Thống nhất giữa hai nguồn:
* **Logo/tagline:** Aristotelica Pro (chỉ dùng trong logo).
* **Heading:** Fraunces (SemiBold theo `11_Brand_Guideline`).
* **Body & CTA:** Be Vietnam Pro (Regular/Medium).
* Tối đa 3 font; không dùng toàn chữ hoa; không kéo giãn font; không dùng font hoạt hình cho nội dung.

## 5. Toàn bộ color token hiện có trong workbook

**⚠️ Xem mục "Mâu thuẫn cần founder quyết định — #1" ngay bên dưới trước khi dùng bảng này.**

Nguồn A — `11_Brand_Guideline` (không có cột trạng thái "Đã chốt" riêng, nhưng được `DEC-006` trong `18_NHẬT KÝ QUYẾT ĐỊNH TOÀN DỰ ÁN` gọi là "chuẩn hiện hành"):

| Vai trò | Hex | Ghi chú tỷ lệ dùng |
|---|---|---|
| Nâu thương hiệu | `#7A3B17` | Logo, tiêu đề, chữ chính, CTA — khoảng 10–15% |
| Kem nền chính | `#FFFCF7` | Nền chủ đạo — khoảng 55–65% |
| Kem đào nhạt | `#FBEFE5` | Nền phụ/khối nội dung — khoảng 15–20% |
| Hồng phấn | `#F6E4E3` | Điểm nhấn cảm xúc — dùng ít |
| Xanh lá nhạt | `#E9F1EA` | Điểm nhấn phụ — dùng ít |
| Xanh lam pha xám | `#C2C3D2` | Cân bằng bảng màu — không dùng cho đoạn chữ dài |

Nguồn B — `16_Brand_System_Detail`, mục Design System, mỗi dòng đánh dấu trạng thái **"Đã chốt"** riêng trong chính sheet đó:

| Vai trò | Hex | Ghi chú tỷ lệ dùng |
|---|---|---|
| Màu chính (Lyli Brown) | `#8A4A23` | — |
| Warm Beige | `#F4ECE5` | — |
| Blush Pink | `#E8CFCF` | — |
| Cream | `#FFFDF9` | — |
| Light Gray | `#F6F5F3` | — |
| Text | `#3D312B` | — |
| Border | `#DDD7D0` | — |
| Tỷ lệ gợi ý | — | 60% nền sáng/kem, 30% nâu, 10% hồng nhấn |

Hai bảng màu này **khác nhau hoàn toàn về mã màu**, không chỉ riêng màu nâu chính — kem nền, hồng nhấn và các màu phụ khác cũng có mã hex khác nhau giữa hai nguồn.

## 6. Homepage blueprint

Theo `16_Brand_System_Detail` (Bản nháp): Hero; danh mục nổi bật; sản phẩm bán chạy; bộ sưu tập mới; lý do chọn Lyli; review; FAQ; social feed; footer.

Theo `06_Website` (WEB-01): Hero, danh mục, USP, CTA.

Hai danh sách không mâu thuẫn nhưng độ chi tiết khác nhau — `16_Brand_System_Detail` liệt kê nhiều section hơn (review, social feed, FAQ) chưa xuất hiện trong tracker `06_Website`.

## 7. Header và footer

* **Header (Bản nháp):** Logo; Shop; Bộ sưu tập; Giới thiệu; tìm kiếm; tài khoản; giỏ hàng. Sticky, nền trắng, gọn.
* **Footer (Bản nháp):** Logo; giới thiệu ngắn; chính sách; liên hệ; Facebook; Instagram; TikTok; email.

## 8. Yêu cầu trang sản phẩm

Ảnh/video; tên; giá; mô tả; biến thể; số lượng; thêm vào giỏ; mua ngay; sản phẩm liên quan (`16_Brand_System_Detail`, Bản nháp). Bổ sung từ `06_Website` WEB-08: gallery, giá, size, CTA.

## 9. Yêu cầu trang danh mục (collection)

Banner; bộ lọc; sắp xếp; danh sách sản phẩm; phân trang (`16_Brand_System_Detail`, Bản nháp).

**⚠️ Xem mục "Mâu thuẫn cần founder quyết định — #2"** — tên trang danh mục cụ thể trong `06_Website` (Size S/M/L) không khớp cấu trúc danh mục 5 nhóm hiện hành.

## 10. Yêu cầu mobile

Mobile-first; tối đa 3 click tới trang sản phẩm; CTA rõ ràng; không popup gây phiền ngay khi khách mới vào site (`16_Brand_System_Detail` — SEO & UX).

## 11. Ràng buộc ảnh sản phẩm

* Kích thước ảnh mặc định cho post/sản phẩm: 1080×1350px (tỷ lệ 4:5). Nền dùng `#FFFCF7`/`#FBEFE5` (theo `11_Brand_Guideline` — thuộc bảng màu nguồn A, xem mục 5 về mâu thuẫn màu).
* Sản phẩm chiếm khoảng 60–70% khung hình (`11_Brand_Guideline`, ngữ cảnh ảnh social) hoặc 70–80% (`16_Brand_System_Detail`, ngữ cảnh Photography tổng quát) — hai con số khác nhau, chưa rõ có phải cùng áp dụng cho ảnh trên website hay chỉ riêng ảnh mạng xã hội.
* Ảnh AI hỗ trợ phải giữ đúng 90–95% (`11_Brand_Guideline`) hoặc 90–99% (`16_Brand_System_Detail`) đặc điểm sản phẩm thật — hai ngưỡng khác nhau, cùng nguyên tắc chung là không đổi màu/hình dáng/tỷ lệ/chi tiết thật.
* Không dùng ảnh có watermark hoặc chưa rõ quyền sử dụng làm ảnh sản phẩm chính công khai.
* Logo trên ảnh: nhỏ, cách mép 60–80px (`11_Brand_Guideline`).

## 12. Yêu cầu cá nhân hóa

Cá nhân hóa là core value và trường dữ liệu bắt buộc kiểm tra trên từng sản phẩm (`09_Product`: Có/Không + phụ phí bằng số VND, tách riêng khỏi giá cơ bản). Trang "Trang cá nhân hóa" (WEB-09) chỉ có tên và mô tả ngắn "Quy trình và phạm vi tùy chỉnh" trong tracker — **workbook chưa đặc tả nội dung/luồng cụ thể** (form, quy trình duyệt mẫu, thời gian phản hồi). Không tự suy đoán thêm ở đây.

## 13. Yêu cầu chỉnh sửa nội dung (content-editing)

Không tìm thấy yêu cầu content-editing riêng cho website trong 6 sheet đã đọc, ngoài quy tắc chung ở PLAN.md mục 6.3 (chủ shop được sửa văn bản/ảnh/bật-tắt section trong phạm vi cho phép, không code, không CSS trực tiếp). Không có bổ sung nào từ workbook mâu thuẫn với quy tắc này.

## 14. Yêu cầu tương thích theme

Suy ra từ Design System (`16_Brand_System_Detail`) — áp dụng cho Botiga Free + `shop-child` (đã chốt, `docs/THEME-DECISION.md`), kiểm chứng thực tế nằm ở `docs/THEME-COMPATIBILITY-GATE.md`:
* Bo góc mềm cho card/button; shadow nhẹ; card nền trắng; khoảng cách thoáng; icon rounded outline; hover nhẹ.
* Cần hỗ trợ palette tùy biến (dù chọn bảng màu nào ở mục 5) và tối đa 3 font tùy biến (Fraunces, Be Vietnam Pro, Aristotelica Pro chỉ cho logo).
* Cần tương thích WooCommerce (đã là ràng buộc bắt buộc toàn dự án, không riêng theme).
* Cần hỗ trợ tỷ lệ ảnh 4:5 làm mặc định cho gallery sản phẩm.
* Không được là page builder bên thứ ba (đã cấm ở TECH_STACK.md).

## 15. Quyết định thiết kế — trạng thái cập nhật 2026-08-04

1. **Màu nâu chính — CÒN MỞ:** `#7A3B17` (`11_Brand_Guideline`) so với `#8A4A23` (`16_Brand_System_Detail`) — xem chi tiết ngay dưới. Chưa chốt trong lần cập nhật theme này (founder chủ động giữ mở, xem `docs/THEME-DECISION.md`).
2. **Cấu trúc danh mục điều hướng — CÓ HƯỚNG ĐI NHƯNG CHƯA PHẢI QUYẾT ĐỊNH CUỐI:** founder đã ghi nhận hướng dẫn đầu V1 là 5 danh mục theo loại sản phẩm (Móc khóa len, Gấu bông len, Hoa len, Hộp quà, Đặt mẫu theo yêu cầu), **không** dùng Size S/M/L làm điều hướng cấp cao — xem `docs/WEBSITE-REQUIREMENTS.md`. Đây là "hướng đi dự kiến trừ khi việc rà soát dữ liệu sản phẩm cho thấy bất nhất đáng kể", chưa phải chốt tuyệt đối 100%.
3. **Theme nền — ĐÃ CHỐT (ACCEPTED 2026-08-04):** Botiga Free, xem `docs/THEME-DECISION.md`. Storefront không còn là lựa chọn chính, chỉ còn là fallback cấp 2.
4. **Kiến trúc theme — ĐÃ CHỐT (ACCEPTED 2026-08-04):** classic/hybrid (không phải block theme/FSE làm kiến trúc chính V1) — xem `docs/THEME-DECISION.md`.
5. **Hiển thị sản phẩm thiếu ảnh đầy đủ — CÓ QUY TẮC TẠM THỜI, THIẾT KẾ PLACEHOLDER CUỐI CÙNG CÒN MỞ:** sản phẩm chưa có ảnh thật đạt yêu cầu giữ trạng thái draft, không publish mặc định; không dùng ảnh nhóm khi có thể gây hiểu nhầm về một sản phẩm cụ thể; placeholder thương hiệu chỉ dùng cho ngoại lệ được duyệt rõ ràng; layout theme phải ổn định khi sản phẩm chỉ có 1 ảnh. Thiết kế placeholder cụ thể (màu, hình minh họa) chưa chốt — xem `docs/WEBSITE-REQUIREMENTS.md`.

### Chi tiết mâu thuẫn #1 — màu nâu chính

* `11_Brand_Guideline` ghi `#7A3B17` là "Nâu thương hiệu", dùng cho logo/tiêu đề/CTA.
* `16_Brand_System_Detail` ghi `#8A4A23` là "Màu chính (Lyli Brown)", đánh dấu trạng thái riêng "Đã chốt".
* `18_NHẬT KÝ QUYẾT ĐỊNH TOÀN DỰ ÁN`, mục `DEC-006`, ghi: "11_Brand_Guideline là chuẩn hiện hành... Nếu tài liệu chi tiết cũ khác màu hoặc quy tắc, ưu tiên 11_Brand_Guideline" — điều này ngụ ý ưu tiên `#7A3B17`. Tuy nhiên `16_Brand_System_Detail` vẫn đang hiển thị `#8A4A23` với nhãn "Đã chốt" riêng, chưa được sửa lại để khớp `DEC-006`, và sheet `18_` này bản thân có dấu hiệu là nhật ký cũ hơn (không nằm trong danh sách sheet bắt buộc đọc của `00_AI_Project_Context`; sheet đó chỉ định `01_Dashboard` mới là nhật ký quyết định hiện hành). **Chưa đọc `01_Dashboard` trong nhiệm vụ này** — nếu có quyết định mới hơn ở đó, nó sẽ vượt qua mọi thứ ghi ở đây. Kết luận: có bằng chứng nghiêng về `#7A3B17`, nhưng không đủ để tự động chốt — cần founder xác nhận trực tiếp, hoặc yêu cầu đọc thêm `01_Dashboard` trước khi quyết định.

### Chi tiết mâu thuẫn #2 — cấu trúc danh mục điều hướng

`06_Website` (tracker trang) liệt kê các trang: "Danh mục Size S" (gấu, mèo, vịt, hổ…), "Danh mục Size M" (thỏ, gấu Teddy, cây thông…), "Danh mục Size L" (mẫu nhiều chi tiết) như các trang category riêng biệt theo size. Trong khi đó, `09_Product` xác nhận cấu trúc danh mục chính thức hiện hành (DEC-024/025) là 5 danh mục theo loại sản phẩm, hoàn toàn không dùng khái niệm size cho điều hướng, và một quyết định cũ hơn (`DEC-007`, đã bị thay thế) từng dùng cách chia khác nữa (7 nhóm). Tên trang trong `06_Website` chưa được cập nhật lại theo cấu trúc 5 danh mục — đây là khoảng trống đồng bộ giữa tracker và Product Database, không phải một quyết định mới.

**Cập nhật 2026-08-04:** Founder đã ghi nhận hướng đi dẫn đầu cho V1 là 5 danh mục theo loại sản phẩm (khớp Product Database), **không** dùng Size S/M/L làm điều hướng cấp cao — size có thể triển khai sau như product attribute/filter/variation/collection subdivision bên trong từng danh mục. Đây là "hướng đi dự kiến trừ khi rà soát dữ liệu sản phẩm cho thấy bất nhất đáng kể" — chưa phải phê duyệt tuyệt đối 100%, nhưng đủ để bắt đầu implementation plan theo hướng này.
