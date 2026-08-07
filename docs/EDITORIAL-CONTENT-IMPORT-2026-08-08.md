# Editorial content import — 2026-08-08

## Phạm vi đã chốt

- Giữ nguyên cấu trúc Gutenberg storefront hiện có; không dựng lại homepage theo bố cục site nguồn.
- Bổ sung toàn bộ nội dung trang, blog và ảnh phù hợp; được crop bằng CSS `object-fit` và có thể thay ảnh sau.
- Không tạo WooCommerce Product; Shop để trống.
- Không nhập hoặc hiển thị chương trình khuyến mãi.
- CTA tạm dùng “Liên hệ đặt hàng”.
- Menu theo site nguồn: Trang chủ → Danh mục → Sản phẩm → Blog → Giới thiệu → Liên hệ.
- Publish Privacy, Terms, Shipping và Returns. Shipping/Returns chỉ dùng các đoạn đã có trong Terms/FAQ nguồn, không tự đặt cam kết mới.
- Xóa bài mẫu `Hello world!`.

## Nguồn và manifest

- Handoff: `C:\Users\ADMIN\Music\web_lyli\docs\CONTENT_AND_IMAGE_EXTRACTION_GUIDE.md`.
- Extractor: `scripts/extract-lyli-editorial.mjs`.
- Data: `web/app/mu-plugins/lyli-editorial-import/data/editorial-content.json`.
- Data SHA-256: `4bc68f7fc1622dd1070602a26582045f1582129e534167ba21ab294cb462847b`.
- Package: 5 blog posts, 25 checksummed assets, không có key `products` hoặc `promotion`.
- Command: `wp lyli editorial import`; mặc định dry-run, cần `--apply` để ghi.

## Production record

| Mục | Giá trị |
|---|---|
| Deployed source | `ea79f4ef688c285883637e738a4b0cd515afa7a4` |
| Active release | `apps/lylishop/releases/20260808001500` |
| Artifact SHA-256 | `d9ceafbb259cb8c6a4e4ddd48713e0de4f486286b108d9899382887c87898107` |
| Pre-change backup | `apps/lylishop/shared/backups/20260808001000` |
| Immediate code rollback | `apps/lylishop/releases/20260808001052` |
| Full pre-editorial rollback | release `20260807205828` + backup `20260808001000` |
| Runtime WP-CLI | `/opt/alt/php83/usr/bin/php /usr/bin/wp --path=apps/lylishop/current/web/wp` |
| Permalink | `/%postname%/` |

Backup DB qua `gzip -t` và uploads qua `tar -tzf` đều pass trước apply.

## Nội dung production

- Homepage giữ các class/section gốc: hero, featured categories, empty products, USP, custom CTA, story và final CTA.
- Hero placeholder và story placeholder được thay bằng Media attachments; thêm gallery, FAQ, latest posts và contact bằng Gutenberg blocks.
- 5 bài blog publish cùng featured image, rich blocks, FAQ và metadata nguồn.
- 9 trang editorial được quản lý: Home, About, Contact, Custom Order, Blog và 4 policy.
- 25/25 ảnh import tải công khai; alt text lấy từ nguồn.
- 0 sản phẩm publish, Shop không có product card, 0 payment gateway bật.

## Bề mặt chỉnh sửa trong WP Admin

- **Trang → Trang chủ/Giới thiệu/Liên hệ/Đặt mẫu/Policies:** sửa toàn bộ Gutenberg blocks, text, gallery, FAQ, CTA và ảnh.
- **Bài viết:** sửa 5 blog posts, featured image, excerpt và nội dung.
- **Media:** thay/crop ảnh; Gutenberg cho phép Replace trực tiếp trên block ảnh.
- **Giao diện → Menu:** sửa Primary Menu và thứ tự mục.
- **Giao diện → Tùy biến/Botiga Header Builder:** sửa cấu trúc header/menu.
- **Lyli Shop → Cài đặt giao diện:** sửa liên hệ, social, footer và CTA; announcement đang tắt.

## Gate đã kiểm tra

- Storefront/Bedrock validators pass; private dry-run và post-apply assertions pass.
- Home, Blog, Shop, Cart, Checkout, My Account, 4 policies và admin login HTTP 200, không PHP fatal.
- Checkout với giỏ trống chuyển về Cart theo WooCommerce.
- Mobile emulation 375px: document width 375px; hero nằm trong viewport.
- `.env` trả 403; SQL trả 404; uploads directory listing trả 403.

Ảnh và CTA hiện là lựa chọn tạm theo yêu cầu, chủ shop có thể thay trực tiếp trong WP Admin.
