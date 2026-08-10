# GHN owner setup

Trạng thái production cập nhật 2026-08-11: WordPress plugin `lyli-ghn-connector` 0.1.0 active trong release `20260810210244`; connector đang **bật ở môi trường Test**, Token và ShopId có mặt nhưng không được ghi vào docs. Owner xác nhận Token lấy từ `5sao.ghn.dev`, tuy nhiên direct test không qua connector tới GHN staging Province API vẫn trả `Token is not valid`. Đây là vấn đề credential/account provisioning phía GHN staging; chưa có Woo test order, rate hoặc vận đơn GHN. Owner cần kiểm tra 5Sao account/shop activation hoặc cấp lại Token staging rồi yêu cầu chạy lại E2E. Không chuyển environment sang Production và không gửi Token qua chat.

## Khi shop đã có tài khoản GHN test

1. Đăng nhập bằng tài khoản `shop_owner` đã được developer provision.
2. Vào **WooCommerce → Kết nối GHN**.
3. Chọn **Test**.
4. Nhập Token và ShopId test của chính shop. Token đã lưu sẽ không hiện lại.
5. Chốt chính sách kinh doanh:
   - loại dịch vụ GHN: hàng nhẹ hoặc hàng nặng;
   - shop hay người nhận trả phí GHN;
   - cho xem/thử hàng hay không;
   - COD chỉ cho đơn WooCommerce COD hay tắt;
   - khai giá hay tắt.
6. Nhập khối lượng và kích thước kiện đóng gói thực tế. Không dùng số giả. Với hàng nặng, từng sản phẩm cũng phải có đủ weight/dimensions.
7. Chỉ bật connector sau khi mọi field bắt buộc đã đúng, rồi lưu.

## Test có kiểm soát

1. Dùng một Woo order test/draft không tạo giao dịch thật, có địa chỉ Việt Nam hai cấp hợp lệ.
2. Mở order, xem khung shipment của Vietnam Store Toolkit và chọn **GHN (Lyli)**.
3. Kiểm tra preview: địa chỉ, kiện, payer, COD và khai giá.
4. Chỉ với GHN test gateway, bấm Create một lần; kiểm tra sync/cancel/print.
5. Xóa/đóng test data theo quy trình đã duyệt. Không dùng Token ví dụ từ tài liệu GHN.

Chỉ chuyển sang **Production** sau khi test gateway pass và owner xác nhận merchant policy. V1 không có live GHN fee ở checkout; phí khách thấy vẫn do **Vietnam Store → Shipping rules** quản lý.

## Hành vi cần nhớ

- Woo order không tự tạo GHN shipment.
- GHN delivered không tự đổi Woo order thành completed.
- COD không tự bật cho BACS/prepaid.
- Connector không nhận webhook; dùng Sync trong order admin.
- `shop_staff` chưa có quyền GHN. Chỉ `shop_owner`/administrator có `manage_woocommerce` được thao tác.
- Nếu mất Token, nhập Token mới; hệ thống không hiển thị lại Token cũ.

Nếu không còn dùng connector: tắt ở **WooCommerce → Kết nối GHN** trước. Deactivate plugin không xóa shipment metadata chuẩn do Toolkit giữ; Token/config chỉ được xóa bằng thao tác owner/developer có chủ đích.
