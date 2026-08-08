# Typography implementation — 2026-08-08

## Nguồn quy chuẩn

Nguồn chuẩn là workbook `Lyli-Shop-90-Day-Project-Management-Workbook.xlsx`, sheet `11_Brand_Guideline`:

- `C12:D12`: Fraunces SemiBold cho tiêu đề post, banner, website và tên Lyli.
- `C13:D13`: Be Vietnam Pro Regular / Medium cho nội dung dài, mô tả sản phẩm và CTA.
- Aristotelica Pro chỉ dành cho tagline trong asset logo chính thức; không dùng làm font nội dung website.

## Phạm vi đã triển khai

- Source commit production: `8b7dc92388a186d58a34484c3c9971f8aa935c6a`.
- Release: `apps/lylishop/releases/20260808132816`.
- Rollback code release: `apps/lylishop/releases/20260808001500`.
- `shop-child/theme.json` tiếp tục là nguồn chuẩn cho typography frontend và Gutenberg.
- Hai preset trong editor được đặt tên rõ ràng: **Fraunces — Tiêu đề** (`lyli-heading`) và **Be Vietnam Pro — Nội dung & CTA** (`lyli-body`). Slug cũ được giữ nguyên để không làm hỏng block/CSS đã lưu.
- Weight mặc định: nội dung `400`, heading `600`, button/CTA `500`; editor stylesheet và storefront CSS được đồng bộ theo cùng quy tắc.
- Google Fonts runtime được giữ nguyên trong lần này: Fraunces `400/600/700`, Be Vietnam Pro `400/500/600/700`. Các weight phụ chưa bị bỏ vì Botiga/WooCommerce có thể còn yêu cầu; không có font binary được commit.
- Validator storefront có assertion riêng cho tên preset, family, quyền chọn weight, ba weight semantic và URL font runtime.

## Bằng chứng

- `php scripts/validate-storefront.php`: PASS, gồm các assertion typography mới.
- Artifact `release-20260808132816.tar.gz`: SHA-256 `3b841d85a72f826fb20dd134df03972712dc6be26b1fb423098b7f1e701918a3`.
- Private release bootstrap bằng WP-CLI với `--path=apps/lylishop/releases/20260808132816/web/wp`: WordPress installed, active theme `shop-child`, không có PHP fatal.
- WordPress runtime `wp_get_global_settings()` trả đúng hai preset editor và tên tiếng Việt nêu trên.
- Sau cutover, `apps/lylishop/current` trỏ tới `releases/20260808132816`; homepage HTTPS trả HTTP 200.
- CSS public chứa preset `--wp--preset--font-family--lyli-heading` / `lyli-body` và áp dụng body `400`, heading `600`, button `500`.
- MCP Lyli kết nối thành công sau cutover; active stylesheet `shop-child`, parent template `botiga`.
- In-app browser không có phiên đăng nhập wp-admin nên không nhập credential hoặc lưu thử nội dung. Runtime Gutenberg/WordPress và public output là bằng chứng chính; việc thiếu phiên browser không chặn deploy.

## Chủ shop sử dụng

1. Vào **Trang** hoặc **Bài viết**, mở nội dung bằng trình sửa khối.
2. Chọn block cần sửa, mở **Typography → Font family**.
3. Chọn **Fraunces — Tiêu đề** cho heading; dùng weight `600`.
4. Chọn **Be Vietnam Pro — Nội dung & CTA** cho đoạn văn; dùng `400`.
5. Với nút/CTA, dùng Be Vietnam Pro và weight `500`.
6. Bấm **Cập nhật**. Không cần Custom CSS, plugin font hay tài khoản Administrator.

Thay đổi font mặc định toàn website hoặc cơ chế tải font vẫn là thay đổi source-controlled và phải đi qua validate → build → deploy; không ghi đè Global Styles trực tiếp bằng MCP.
