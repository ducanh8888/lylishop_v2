# WEBSITE REQUIREMENTS — Lyli Shop

Nguồn: `06_Website`, `16_Brand_System_Detail` (mục Website Blueprint, trạng thái **Bản nháp**), đối chiếu với `PLAN.md`/`TECH_STACK.md` đã chốt. Đọc cùng `docs/PRODUCT-BRIEF.md`. Workbook gốc không commit; xem `docs/HOSTING-AUDIT.md` và `.gitignore`.

Đây là yêu cầu nội dung/chức năng trang, **không phải** quyết định theme/màu sắc/layout thị giác — xem `docs/THEME-DECISION.md` (theme nền đã chốt: Botiga Free) và `docs/THEME-DECISION-BRIEF.md` (màu sắc và một số điểm khác vẫn đang mở).

## Phạm vi trang MVP (theo `06_Website`, đã bỏ cột deadline/người thực hiện — thông tin vận hành nội bộ)

| Trang | Nội dung yêu cầu |
|---|---|
| Trang chủ | Hero, danh mục, USP, CTA |
| Cửa hàng (Shop) | Trang tổng sản phẩm |
| Danh mục sản phẩm | Hướng đi dẫn đầu V1 (2026-08-04): 5 danh mục theo loại sản phẩm — Móc khóa len, Gấu bông len, Hoa len, Hộp quà, Đặt mẫu theo yêu cầu. Xem ghi chú quan trọng bên dưới |
| Danh mục Hoa len | Bó hoa/hoa lẻ nếu có |
| Danh mục Set quà | Set quà tặng |
| Template chi tiết sản phẩm | Gallery, giá, size, CTA |
| Trang cá nhân hóa | Quy trình và phạm vi tùy chỉnh — **nội dung cụ thể chưa được đặc tả trong workbook**, chỉ có tên trang |
| Giới thiệu | Câu chuyện thương hiệu ngắn |
| Vận chuyển & đổi trả | Quy định rõ, dễ đọc |
| Liên hệ | Fanpage, form, email/điện thoại |
| Blog (danh sách) | Trang danh sách bài viết |
| Blog bài SEO | Tối thiểu 2 bài: gợi ý quà handmade; bảo quản đồ len |

**Ghi chú quan trọng — chưa đồng bộ:** `06_Website` liệt kê các trang danh mục theo size ("Danh mục Size S/M/L") với ví dụ sản phẩm (gấu, mèo, vịt, hổ / thỏ, gấu Teddy, cây thông...). Cấu trúc danh mục sản phẩm hiện hành trong `09_Product` (DEC-024/025) lại dùng 5 danh mục theo loại sản phẩm (Móc khóa len, Gấu bông len, Hoa len, Hộp quà, Đặt mẫu theo yêu cầu), không theo size.

**Cập nhật 2026-08-04 — hướng đi dẫn đầu:** Founder ghi nhận điều hướng site theo **5 danh mục sản phẩm** là hướng đi cho V1, **không** dùng Size S/M/L làm navigation cấp cao. Size S/M/L có thể triển khai sau như product attribute, filter, variation hoặc collection subdivision bên trong một danh mục — không phải danh mục cấp cao độc lập. Đây là hướng đi dự kiến trừ khi việc rà soát dữ liệu sản phẩm cho thấy bất nhất đáng kể — chi tiết trạng thái ở `docs/THEME-DECISION-BRIEF.md` mục 15.

## Sitemap tham khảo (Website Blueprint, `16_Brand_System_Detail` — trạng thái Bản nháp)

Home; Shop; Collection; Product; Giới thiệu; FAQ; Liên hệ; Chính sách; Giỏ hàng; Thanh toán.

Đây là bản nháp riêng, không hoàn toàn khớp danh sách trang ở `06_Website` (ví dụ không nhắc Blog, không nhắc trang cá nhân hóa). Cả hai đều là input thô, chưa có bản hợp nhất chính thức — không tự hợp nhất hộ ở đây.

## Header & Footer (Website Blueprint — Bản nháp)

* **Header:** Logo; Shop; Bộ sưu tập; Giới thiệu; tìm kiếm; tài khoản; giỏ hàng. Sticky, nền trắng, gọn.
* **Footer:** Logo; giới thiệu ngắn; chính sách; liên hệ; liên kết Facebook/Instagram/TikTok; email.

## Trang sản phẩm (Product Page)

Theo `16_Brand_System_Detail`: Ảnh/video; tên; giá; mô tả; biến thể; số lượng; nút thêm vào giỏ; nút mua ngay; sản phẩm liên quan.

Theo `06_Website` (WEB-08): Gallery, giá, size, CTA.

Ràng buộc kỹ thuật đã chốt (không thuộc phạm vi quyết định theme): Cart/Checkout dùng Classic (không dùng Cart/Checkout Blocks) theo TECH_STACK.md mục 4.1 — trang sản phẩm vẫn có thể dùng Gutenberg/blocks cho nội dung, chỉ luồng giỏ hàng/thanh toán là classic.

## Trang danh mục (Collection Page)

Theo `16_Brand_System_Detail`: Banner; bộ lọc; sắp xếp; danh sách sản phẩm; phân trang.

## Cá nhân hóa

* Cá nhân hóa là một core value và là một trường dữ liệu sản phẩm bắt buộc kiểm tra (`09_Product`: trường "Cá nhân hóa" Có/Không, "Phụ phí cá nhân hóa" ghi riêng bằng số VND).
* Kênh nhận yêu cầu "Đặt mẫu theo yêu cầu" hiện là Facebook/Instagram/Zalo (ngoài website) — workbook chưa mô tả một luồng đặt hàng cá nhân hóa on-site cụ thể (form, quy trình duyệt mẫu, thời gian phản hồi) ngoài việc có một trang tên "Trang cá nhân hóa" trong tracker. Đây là khoảng trống dữ liệu thật, không suy đoán thêm.

## Chính sách sản phẩm thiếu ảnh (provisional, 2026-08-04)

Ghi nhận theo yêu cầu founder, áp dụng bất kể theme nào:

* Sản phẩm chưa có ảnh thật đạt yêu cầu **giữ trạng thái draft, không publish mặc định**.
* **Không dùng ảnh nhóm** khi ảnh đó có thể gây hiểu nhầm về một sản phẩm cụ thể (ví dụ ảnh nhóm nhiều mẫu khác biệt rõ rệt).
* Placeholder thương hiệu (branded placeholder) chỉ dùng cho **ngoại lệ được duyệt rõ ràng**, không dùng mặc định cho mọi sản phẩm thiếu ảnh.
* Layout theme (product card, gallery, archive) phải **ổn định khi sản phẩm chỉ có đúng 1 ảnh** — không được giả định luôn có ảnh phụ/nhiều ảnh.
* **Thiết kế placeholder cuối cùng vẫn còn mở** — chưa chốt màu, hình minh họa, hay văn bản hiển thị. Không tự thiết kế hộ ở đây.

Đây là quy tắc tạm thời (provisional) — có thể điều chỉnh khi có thêm dữ liệu ảnh thật.

## Yêu cầu mobile & SEO/UX (Website Blueprint)

* Mobile-first.
* Tối đa 3 click để tới trang sản phẩm.
* CTA rõ ràng.
* Không hiển thị popup gây phiền ngay khi khách mới vào site.
* SEO kỹ thuật cơ bản: title, meta description, URL ngắn, alt ảnh, schema product, breadcrumb.

## Ràng buộc kiến trúc đã chốt (không đổi ở bước này)

Nhắc lại từ PLAN.md/TECH_STACK.md để tránh nhầm với phần đang chờ quyết định:

* WooCommerce core cho toàn bộ sản phẩm/giỏ/đơn hàng — không xây lại.
* Cart/Checkout Classic (chưa dùng Blocks) trong V1.
* Theme nền: Botiga Free + `shop-child`, classic/hybrid — đã chốt 2026-08-04 (`docs/THEME-DECISION.md`).
* Không dùng page builder bên thứ ba, không dùng Full Site Editing làm kiến trúc chính V1.
* Một website, một database, một môi trường production duy nhất — không có staging (xem `docs/DEPLOYMENT.md`).
* Màu sắc cuối cùng, chốt tuyệt đối cấu trúc navigation, thiết kế placeholder ảnh: **vẫn chưa được phép quyết định trong tài liệu này** — xem `docs/THEME-DECISION-BRIEF.md`.
