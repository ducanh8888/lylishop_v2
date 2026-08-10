# Hướng dẫn quản trị Lyli Shop

Tài khoản dùng hằng ngày có vai trò **Shop Owner (`shop_owner`)**. Tài khoản Administrator chỉ dành cho nhà phát triển hoặc tình huống khẩn cấp.

> Handoff 2026-08-10: policy `shop_owner` đã được kiểm chứng nhưng production hiện chỉ có một user `administrator`; chưa có account vận hành mang role `shop_owner`. Developer phải provision hoặc chuyển đúng tài khoản sau khi xác nhận danh tính/credential với owner. Không dùng tài khoản Administrator hiện tại như một cách bỏ qua boundary này.

## Bắt đầu

Sau khi đăng nhập, WordPress mở **Lyli Shop — Khu vực chủ cửa hàng**. Chọn nút đúng với việc cần làm; không cần vào phần kỹ thuật.

Giao diện WordPress và WooCommerce của tài khoản vận hành được đặt thành **Tiếng Việt**. Nếu tab wp-admin đã mở từ trước, hãy đăng xuất/đăng nhập lại hoặc tải lại trang. Có thể kiểm tra tại **Thành viên → Hồ sơ → Ngôn ngữ: Tiếng Việt**.

- **Sửa trang chủ:** mở trang chủ trong trình sửa khối. Bấm trực tiếp vào chữ để sửa; kéo khối để đổi thứ tự; mở menu ba chấm của khối để ẩn/xóa một mục.
- **Đổi ảnh hero hoặc ảnh trong trang:** chọn vùng ảnh, bấm **Thay thế**, rồi chọn ảnh trong Media hoặc tải ảnh mới lên. Nhập văn bản thay thế ngắn gọn cho ảnh.
- **Sản phẩm:** production hiện có 2 sản phẩm thật đã được nhập trước rollout toolkit; rollout 2026-08-10 không tạo sản phẩm test. Chỉ vào **Sản phẩm → Thêm sản phẩm** khi shop sẵn sàng mở rộng catalogue; không cần tạo sản phẩm để sửa nội dung trang hoặc gallery.
- **Danh mục:** vào **Danh mục** dưới Sản phẩm. Năm danh mục chính là Móc khóa len, Gấu bông len, Hoa len, Hộp quà và Đặt mẫu theo yêu cầu.
- **Menu:** vào **Giao diện → Menu điều hướng**. Thêm/bớt trang, kéo để đổi thứ tự, rồi bấm lưu menu.
- **Logo và giao diện:** vào **Giao diện → Logo & giao diện**. Dùng Customizer để đổi logo, nhận diện website và các tùy chọn Botiga được phép; bấm **Đăng** để lưu.
- **Font trong nội dung:** trong trình sửa Trang/Bài viết, chọn block rồi mở **Typography → Font family**. Chọn **Fraunces — Tiêu đề** (`600`) cho heading; chọn **Be Vietnam Pro — Nội dung & CTA** cho đoạn văn (`400`) hoặc nút/CTA (`500`). Không cần Custom CSS hay plugin font.
- **Footer, liên hệ, mạng xã hội, thông báo:** vào **Lyli Shop → Cài đặt giao diện**. Để trống trường nào thì phần tương ứng sẽ tự ẩn.
- **Trang và bài viết:** dùng **Trang** để sửa toàn bộ section Gutenberg đã publish; dùng **Bài viết** để sửa 5 bài blog và featured image. Privacy, Terms, Shipping và Returns đều đang public từ nội dung nguồn đã duyệt cho lần import này.
- **Media:** quản lý ảnh đã tải lên. Nên dùng ảnh rõ, cùng tỷ lệ và dung lượng vừa phải.
- **Đơn hàng:** mở **Đơn hàng** hoặc màn hình WooCommerce tương ứng để xử lý vận hành.

## Vietnam store và chuyển khoản — đã active, chờ owner cấu hình

Sau khi account vận hành được gán role `shop_owner`, Shop Owner có các lối vào sau mà không cần quyền Administrator:

- **Vietnam store:** `/wp/wp-admin/admin.php?page=yoohw-vietnam-store` — bật/tắt tính năng địa chỉ, điện thoại và công cụ vận hành.
- **Chuyển khoản/VietQR:** **WooCommerce → Cài đặt → Thanh toán → Chuyển khoản ngân hàng** — cấu hình BACS và VietQR.
- **Vận chuyển:** **WooCommerce → Cài đặt → Giao hàng** — tự thêm phương thức vào đúng Shipping Zone và nhập giá/rule.
- **Tracking/email:** các section tương ứng trong WooCommerce Settings và từng đơn hàng.

Trạng thái handoff: BACS **tắt**, VietQR **tắt**, danh sách tài khoản ngân hàng **trống**. Trước khi bật VietQR, owner phải duyệt/bổ sung privacy disclosure cho request ảnh tới `img.vietqr.io`; URL ảnh có thể chứa BIN, số tài khoản, tên chủ tài khoản, số tiền và nội dung chuyển khoản.

Developer chỉ cài/activate code. Shop Owner tự nhập số tài khoản, chủ tài khoản, VietQR merchant information, giá/rule vận chuyển, COD policy, VAT/invoice/tracking credentials và payment text. Không gửi các giá trị này để commit vào repository.

VietQR của plugin chỉ hiển thị thông tin/QR trên WooCommerce Direct Bank Transfer (`bacs`); không tự xác nhận tiền về và không tự đánh dấu đơn đã thanh toán. BACS/VietQR phải giữ tắt/chưa cấu hình cho tới khi owner hoàn tất dữ liệu merchant và privacy disclosure. SePay là **DEFERRED / OPTIONAL**, chỉ xem lại nếu sau này cần đối soát tự động.

## GHN — plugin đã active, connector đang tắt

Sau khi developer provision account `shop_owner`, vào **WooCommerce → Kết nối GHN**. Chỉ nhập Token/ShopId do chính shop nhận từ GHN, bắt đầu bằng môi trường **Test**, nhập khối lượng/kích thước kiện thật và chốt COD/payer/inspection/insurance trước khi bật. Token đã lưu không được hiển thị lại.

V1 không tính cước GHN live ở checkout; phí checkout vẫn do **Vietnam Store → Shipping rules** quản lý. Connector không tự tạo vận đơn, không tự đổi trạng thái Woo order, không nhận webhook và không tự thu COD. Owner phải mở từng order, review dữ liệu rồi chủ động Create/Sync/Cancel/Print qua shipment panel của Vietnam Store Toolkit. Xem checklist tại `docs/GHN-OWNER-SETUP.md`.

## Việc cần nhà phát triển

Shop Owner không thể cài/cập nhật plugin hoặc giao diện, đổi mã PHP, thay cấu hình hệ thống hay quản lý người dùng. Sau khi Vietnam Store Toolkit được developer triển khai, owner được cấu hình/bật BACS/VietQR và shipping trong các màn hình WooCommerce đã whitelist; việc thêm gateway/plugin mới vẫn thuộc developer. Đổi font mặc định toàn website hoặc thêm font family mới là thay đổi source-controlled; gửi các việc này cho nhà phát triển. Không dùng tài khoản Administrator cho công việc hằng ngày.
