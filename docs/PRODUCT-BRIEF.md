# PRODUCT BRIEF — Lyli Shop

Nguồn: `Lyli-Shop-90-Day-Project-Management-Workbook.xlsx`, sheets `00_AI_Project_Context`, `09_Product`, `11_Brand_Guideline`, `16_Brand_System_Detail` (đọc 2026-08-03). Tài liệu này chỉ trích xuất phần không nhạy cảm, phục vụ triển khai website. Workbook gốc **không** được commit vào repository công khai; xem `.gitignore`.

Không có link Google Drive, không có dữ liệu khách hàng, không có thông tin chiến dịch nội bộ nào được sao chép vào tài liệu này.

## Thương hiệu

* **Tên:** Lyli Shop — phụ kiện và quà tặng len handmade nhỏ xinh.
* **Mission:** Tạo ra những món quà handmade nhỏ xinh, được làm bằng sự tỉ mỉ và chân thành, giúp mỗi món quà trở nên ý nghĩa và mang dấu ấn riêng của người nhận.
* **Brand Story (rút gọn):** Bắt đầu từ mong muốn mang niềm vui đến mọi người qua quà tặng handmade nhỏ xinh; mỗi sản phẩm làm cẩn thận để người nhận cảm thấy ấm áp, vui vẻ.
* **Core Values (Brand Foundation):** Chuyên nghiệp; gần gũi; cá nhân hóa; sáng tạo.
* **Giá trị vận hành (00_AI_Project_Context):** Chân thành – Cá nhân hóa – Chỉn chu.
* **Brand Promise:** Mỗi sản phẩm luôn được hoàn thiện bằng sự tỉ mỉ và chân thành.
* **Brand Personality:** Một cô gái nhẹ nhàng, khéo tay, vui vẻ, chú ý chi tiết, luôn làm mọi thứ chỉn chu.
* **USP:** Sự chỉn chu.
* **Không bao giờ làm:** Không bán hàng kém chất lượng; không quảng cáo sai sự thật; không dùng ảnh giả sản phẩm.

## Đối tượng khách hàng

* **Định vị:** Người trẻ 16–24 tuổi; có mẫu sẵn vừa túi tiền, nhận làm cá nhân hóa, hoàn thiện chỉn chu.
* **Khách hàng ưu tiên:** 16–24 tuổi; chủ yếu học sinh, sinh viên và người trẻ thu nhập dưới 15 triệu/tháng; tập trung khu vực Hà Nội; mua cho bản thân, bạn bè, người yêu, gia đình.
* **Lý do khách chọn Lyli:** Chỉn chu; đầu tư; chuyên nghiệp; đồng bộ; nhận diện rõ; mẫu hợp tuổi teen; đẹp; giá dễ tiếp cận.
* **Phản ứng mong muốn khi khách xem sản phẩm/nội dung:** "Đáng yêu quá!"

## Tone giọng

Ấm áp, gần gũi, nhẹ nhàng, chân thật; nói như một người bạn khéo tay đang chia sẻ món đồ mình vừa làm; xưng "Lyli", gọi khách là "bạn"; câu ngắn, mỗi bài một thông điệp; không phô trương, không ép mua, không dùng ngôn ngữ giật gân kiểu "giá sốc/siêu rẻ/chốt đơn ngay" khi không có giới hạn thật.

## Cấu trúc danh mục sản phẩm (hiện hành — DEC-024/025)

5 danh mục chính, mỗi mẫu sản phẩm = một dòng dữ liệu (không tách riêng theo màu — màu ghi chung một ô, cách nhau dấu phẩy):

| Danh mục | Nhóm con | Mô hình giá |
|---|---|---|
| Móc khóa len | Lyli Tiny · Lyli Charm · Lyli Signature | 45.000đ · 55.000–65.000đ · 70.000–80.000đ (không dùng nhãn S/M/L, không dùng "Đồng giá") |
| Gấu bông len | Nhóm 250K · Nhóm 300K | 250.000đ · 300.000đ |
| Hoa len | Bó hoa · Hoa lẻ | Theo từng mẫu |
| Hộp quà | Quà bất ngờ · Quà yêu thương · Quà đặc biệt | Báo giá theo cấu hình hộp |
| Đặt mẫu theo yêu cầu | Kênh nhận yêu cầu: Facebook · Instagram · Zalo | Báo giá riêng |

Phụ phí cá nhân hóa (thêm tên/chữ) ghi tách riêng khỏi giá cơ bản, dao động khoảng 15.000–20.000đ tùy độ dài/độ khó (ví dụ ghi trong dữ liệu sản phẩm, không phải mức giá cố định cho mọi trường hợp).

**Lưu ý lịch sử:** một cấu trúc phân loại cũ hơn (7 nhóm, bao gồm phân biệt theo size) đã được ghi nhận là thay thế bởi cấu trúc 5 danh mục hiện hành. Trang tracker website (`06_Website`) hiện vẫn còn đặt tên một số trang danh mục theo size (S/M/L) — đây là điểm chưa đồng bộ giữa tracker cũ và cấu trúc danh mục hiện hành, được flag riêng ở `docs/THEME-DECISION-BRIEF.md`.

## Quy tắc đặt tên và dữ liệu sản phẩm

* **Tên quản lý (nội bộ):** `Lyli` + loại sản phẩm + mã số 3 chữ số + `_` + tên mẫu. Ví dụ dạng: `Lyli Móc khóa 001_Gấu`.
* **Tên hiển thị (khách thấy):** tự nhiên, dễ đọc, không mã số, không dấu gạch dưới.
* Chuẩn dữ liệu sản phẩm hiện hành gồm 24 trường (SKU, tên quản lý, tên hiển thị, mẫu, danh mục, mô tả ngắn, loại giá, giá cơ bản, kích thước, màu sắc, cá nhân hóa, phụ phí cá nhân hóa, thời gian hoàn thành, hình thức bán, trạng thái sản phẩm, tình trạng ảnh, ảnh chính, ảnh phụ, video, đối tượng tặng, dịp sử dụng, chất liệu, ghi chú, ngày cập nhật) — dùng làm tham chiếu khi thiết kế trường sản phẩm WooCommerce, không cần chép nguyên trạng thái nội bộ.
* Kích thước handmade dùng mốc tham chiếu và cho phép dao động (ví dụ "cao khoảng 12–15 cm"), không cam kết số đo tuyệt đối từng chiếc — cần phản ánh đúng cách này trong mô tả sản phẩm, tránh cam kết số đo cứng.

## Tình trạng ảnh sản phẩm (ảnh hưởng thiết kế trang sản phẩm/danh mục)

Thang trạng thái ảnh hiện dùng cho toàn bộ sản phẩm: *Chưa đánh giá → Đạt - ảnh gốc → Đạt - AI hỗ trợ có ảnh gốc → Đạt tạm thời - ảnh nhóm → Cần đối chiếu ảnh gốc → Cần chụp lại → Chưa có ảnh.*

Tại thời điểm đọc workbook, nhiều dòng sản phẩm đang ở các trạng thái trung gian (ảnh nhóm tạm thời, cần chụp lại, hoặc chưa có ảnh) — đây là dữ liệu vận hành thật, không phải trường hợp hiếm. Website cần một cách hiển thị nhất quán cho các trạng thái này (xem flag #5 trong `docs/THEME-DECISION-BRIEF.md` — chưa có quyết định hiển thị, không tự suy đoán).

Quy tắc ảnh đã chốt, áp dụng bất kể theme nào được chọn:
* Không dùng ảnh tham khảo ngoài (có watermark hoặc chưa rõ quyền sử dụng) làm ảnh sản phẩm chính công khai.
* Ảnh AI chỉ được hỗ trợ nền/ánh sáng/bố cục; không đổi màu, hình dáng, tỷ lệ hoặc chi tiết sản phẩm thật; luôn phải có ảnh gốc để đối chiếu.
* Ảnh nhóm nhiều mẫu được chấp nhận tạm thời nếu ghi rõ SKU/mẫu xuất hiện trong ảnh; chưa thay thế hoàn toàn ảnh riêng từng mẫu.
