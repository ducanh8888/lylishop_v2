# BRAND + MOBILE REMEDIATION PLAN

Trạng thái: **PLANNED / PREFLIGHT COMPLETE — CHƯA IMPLEMENT / CHƯA DEPLOY**

Quyết định binding: founder, 2026-08-10

Kế hoạch này thay thế quyết định palette provisional trước đây và ghép color migration với một responsive pass mobile-first. Không file PHP/CSS/JSON/theme mod hay production option nào được sửa trong pre-flight.

## 1. Before-state evidence

Nguồn canonical hiện tại là `web/app/themes/shop-child/theme.json`; `inc/design-tokens.php` chỉ bảo vệ palette child khỏi filter ghi đè của Botiga và không nhân đôi token.

Palette đang triển khai:

| Slug/role hiện tại | Hex | Trạng thái |
|---|---|---|
| Primary | `#7A3B17` | Giữ, đổi tên semantic rõ hơn |
| Secondary | `#8A4A23` | **Retire khỏi official palette và CSS** |
| White | `#FFFFFF` | Chỉ giữ nếu cần functional white, không phải brand swatch |
| Soft surface | `#F8F5F2` | Thay bằng cream |
| Canvas | `#FCFBFA` | Thay bằng warm white |
| Hero soft | `#F0E9E3` | Map theo component sang cream/blush/lavender |
| Text | `#2D2A26` | Functional/accessibility neutral |
| Muted | `#6B6560` | Functional/accessibility neutral |
| Border | `#E4DED8` | Functional/accessibility neutral |

Các lỗi responsive xác nhận từ source và runtime read-only:

- Hero bắt đầu từ tỷ lệ desktop `56/44`, cover min-height 520px và mobile vẫn ép visual 360px.
- Mobile H1 dùng khoảng `2.55rem` cùng preset/override lớn, làm first viewport quá cao.
- Category/product grid về một cột dưới 480px, làm nội dung kéo dài không cần thiết.
- Section margin vẫn khoảng 52px ở mobile; card padding/min-height còn theo nhịp desktop.
- Header mobile production đang có `search` + `mobile_woocommerce_icons`; offcanvas cũng lặp search/icons, chưa có composition logo + cart + hamburger có chủ ý.
- Nhiều desktop column ratio chỉ stack xuống mobile; fixed presets và `!important` chồng nhau.
- `html, body { overflow-x: hidden; }` che overflow thay vì loại nguồn gây tràn.
- `.lyli-custom-cta` dùng brown làm nền khối lớn; footer dùng dark functional surface khổ lớn, đều không khớp palette/ratio mới.

## 2. Sáu màu brand binding

| Semantic token | Tên Gutenberg tiếng Việt | Hex | Mục tiêu | Vai trò |
|---|---|---|---|---|
| `brand-primary` | Nâu ấm chủ đạo | `#7A3B17` | 5–15% | Heading, tag, border nhỏ, accent quan trọng, primary CTA |
| `surface-main` | Trắng ấm — nền chính | `#FFFCF7` | 35–55% | Body, whitespace, neutral surface chính |
| `surface-cream` | Kem — nền phụ | `#FBEFE5` | 20–40% | Section/card mềm, packaging/linen character |
| `accent-blush` | Hồng phấn — điểm nhấn | `#F6E4E3` | 5–15% | Floral/ribbon, secondary surface thỉnh thoảng |
| `accent-sage` | Xanh sage — điểm nhấn | `#E9F1EA` | 5–15% | Prop/card/section accent thưa |
| `accent-lavender` | Tím xám — điểm nhấn | `#C2C3D2` | 5–15% | Balancing detail/quiet surface |

Tỷ lệ là mục tiêu toàn trang, không phải cộng tối thiểu mọi màu trong mỗi viewport. Một viewport/section chỉ nên có một pastel accent nổi bật bên cạnh warm white/cream/brown; không tạo “cầu vồng pastel”. Brown không làm nền lớn, trừ title/accent zone cố ý nhỏ.

## 3. Old → new token mapping

| Cũ | Mới | Quy tắc migration |
|---|---|---|
| `lyli-primary` / `#7A3B17` | `brand-primary` | Giữ giá trị, cập nhật semantic references |
| `lyli-secondary` / `#8A4A23` | Không có mapping 1:1 | Xóa swatch và hardcode; remap theo ngữ cảnh sang primary hoặc một accent chính thức |
| `white` / `#FFFFFF` | `surface-main` / `#FFFCF7` | Nền brand; pure white chỉ là functional internal surface nếu contrast/UI thật sự cần |
| `surface-soft` / `#F8F5F2` | `surface-cream` / `#FBEFE5` | Card/section phụ |
| `canvas` / `#FCFBFA` | `surface-main` / `#FFFCF7` | Body/canvas |
| `hero-soft` / `#F0E9E3` | Cream hoặc một accent | Chọn theo component, không tạo token thứ bảy |
| `text` / `#2D2A26` | `functional-text` | Functional/accessibility neutral, không phải brand color |
| `muted` / `#6B6560` | `functional-muted` | Functional/accessibility neutral |
| `border` / `#E4DED8` | `functional-border` | Functional/accessibility neutral |

Error, success, warning, disabled và WooCommerce notices được phép có màu chức năng ngoài sáu màu, phải đặt tên `functional-*`/`system-*`, không expose như brand swatch và không ép pastel vào text role.

Độ tương phản tính theo WCAG: brown trên warm white khoảng 8.32:1; brown trên cream 7.54:1; functional text trên warm white 13.95:1; muted trên warm white 5.61:1; brown trên lavender 4.88:1. Vẫn phải chạy automated contrast và kiểm tra focus/hover/disabled thực tế.

## 4. Một nguồn token duy nhất và editor parity

`theme.json` tiếp tục là canonical source cho brand palette, typography và preset. Thực thi kế tiếp phải:

1. Thay palette hiện tại bằng sáu brand token và các functional neutral đặt tên rõ.
2. Xóa `#8A4A23` khỏi `theme.json`, CSS, editor CSS, patterns, gradients, rgba/derived values và docs active.
3. CSS frontend/editor chỉ alias `var(--wp--preset--color--...)`; không định nghĩa lại hex brand trong PHP hoặc file CSS thứ hai.
4. Giữ filter hẹp ngăn Botiga 2.4.7 ghi đè child palette.
5. Kiểm tra editor hiển thị đúng sáu tên tiếng Việt và preview block gần khớp frontend.
6. Không yêu cầu owner sửa raw CSS/Additional CSS.

Botiga Customizer/theme mods production hiện không chứa override màu cần migrate. Nếu implementation phát hiện màu được lưu mới trong theme mods, phải lập danh sách before/after và chỉ cập nhật option bằng migration idempotent có backup—không tạo palette song song.

## 5. Component-by-component color plan

| Component | Planned remap |
|---|---|
| Body/main | `surface-main`; functional text/muted; brown cho heading/link active nhỏ |
| Header | Warm white, border nhẹ; brown logo/navigation active; tránh brown full-width |
| Hero | Warm white + cream; tối đa một blush/lavender detail; CTA brown; không phủ brown khối lớn |
| Category cards | Cream/warm white; một accent thưa theo nhóm; brown label/border nhỏ |
| Product cards | Warm white/cream, functional text/border; brown CTA/focus; ảnh là trọng tâm |
| Buttons | Primary brown trên warm surfaces; secondary transparent/cream; focus ring functional dễ thấy |
| USP | Cream section, cards warm white; tối đa một accent màu trong viewport |
| Custom-order CTA | Thay nền brown lớn bằng cream hoặc warm white; brown chuyển thành heading/border/button/chip |
| Story | Cream hoặc sage rất nhạt; body text functional; không ghép nhiều pastel |
| Gallery | Warm-white canvas, functional borders; accent chỉ ở caption/detail |
| FAQ | Alternating warm white/cream; brown title/focus; functional divider |
| Contact | Cream/warm white, primary CTA brown, một blush accent nếu cần |
| Cart/Checkout/Account | Warm white; functional form/notice colors; cream summary card; brown CTA |
| Footer | Bỏ dark full-width surface; dùng cream/warm white, brown heading/link accent và dải/border brown nhỏ |
| Gutenberg editor | Cùng token, typography, spacing và component states như frontend |

Acceptance bằng ảnh chụp/overlay phải xác nhận warm white + cream chiếm phần lớn, brown không vượt vai trò accent/CTA và không có section dùng đồng thời blush + sage + lavender gây nhiễu.

## 6. Mobile-first architecture

Không chỉ thêm media query vào CSS desktop. Component base được thiết kế cho narrow viewport, rồi mở rộng dần. Inline block style cố định trong patterns phải chuyển sang class/preset/layout controls owner vẫn sửa được.

Nguyên tắc:

- Dùng một scale `clamp()` nhất quán cho heading/body/spacing.
- Giảm `!important`; chỉ giữ nơi thật sự cần override third-party specificity hoặc reduced-motion.
- Tìm và sửa width/min-width/gap/transform gây overflow; sau đó bỏ blanket `overflow-x:hidden`.
- Grid dùng `minmax(0, 1fr)` và children có `min-width:0` khi cần.
- Ảnh có intrinsic dimensions/aspect-ratio phù hợp, không ép source 520/360px cố định.
- Recompose layout theo viewport, không chỉ stack tỷ lệ desktop.
- Giữ mọi section là Gutenberg blocks/patterns hoặc setting owner-editable; CSS chỉ trình bày.

## 7. Viewport targets

### 375px

- H1 30–34px bằng fluid clamp; line-height/measure ngắn, không dùng preset gigantic cố định.
- Hero visual khoảng 240–280px khi có ảnh; giảm padding/gap để first viewport ngắn đáng kể.
- Header visible: logo + cart + hamburger. Account/menu/search nằm trong drawer; search có một vị trí chủ ý, không lặp ở cả row và drawer.
- Category grid 2 cột cho compact category cards, trừ khi visual evidence của đúng nội dung chứng minh 1 cột tốt hơn.
- Product grid 2 cột cho normal product cards; compact padding/min-height, title không làm lệch hàng quá mức.
- Section vertical rhythm khoảng 32–40px; touch target tối thiểu 44px.
- Form checkout một cột, labels/errors rõ, order review không overflow.

### 768px

- Category 2–3 cột; product 2–3 cột theo available width.
- Hero là controlled stack hoặc compact two-column sau visual test; không kế thừa tỷ lệ 56/44 một cách máy móc.
- Header có thêm không gian nhưng vẫn ưu tiên navigation rõ; search chỉ xuất hiện nếu không làm chật.
- Footer được tái bố cục 2–3 vùng, không chỉ xếp tuần tự mọi cột desktop.
- Section rhythm khoảng 40–56px theo nội dung.

### 1440px (và 1024px+)

- Giữ desktop composition đã hoạt động: max-width, whitespace và hierarchy hiện tại.
- Hero có thể trở lại two-column nhưng visual/text không khóa ở một chiều cao cứng.
- Category/product desktop grid giữ density hợp lý, không kéo card quá rộng.
- Header desktop hai hàng hiện tại chỉ thay khi visual regression chứng minh cần thiết.

## 8. Component responsive strategies

### Header

Đặt Botiga mobile layout thành logo + cart + hamburger; tắt account icon khỏi narrow row và chuyển account/search/menu vào offcanvas. Tránh cấu hình runtime hiện tại `search + mobile_woocommerce_icons` ở cả row/offcanvas. Thay đổi theme mods phải qua bootstrap/migration idempotent, có snapshot options và owner vẫn chỉnh được trong Customizer.

### Hero

Loại inline `56/44`/520px làm nguồn chân lý. Base stack có text trước, visual sau; content width/line length và image aspect-ratio kiểm soát bằng class. 375px visual khoảng 240–280px; 768px chọn stack/two-column theo screenshot; 1024px+ dùng two-column linh hoạt.

### Category và product grids

Base 2 cột ở 375px với gap/padding compact; product image giữ tỷ lệ nhất quán. 768px tăng 2–3 cột, desktop theo grid hiện tại. Chỉ fallback 1 cột cho viewport cực hẹp hoặc card nội dung đặc biệt, không dùng rule chung dưới 480px.

### Footer

Chuyển nền dark full-width sang cream/warm white; mobile gom nhóm brand/contact, policy và social theo accordion/stack rõ; 768px thành 2–3 vùng; desktop columns cân bằng. Links/touch targets/focus vẫn đủ 44px khi là control chính.

### Typography và spacing

Giữ Fraunces 600 cho heading, Be Vietnam Pro 400/500 cho body/CTA. Dùng một fluid scale thay vì preset cố định cộng nhiều breakpoint override. Giới hạn line length, tránh orphan heading và giảm section/card padding trên mobile.

## 9. Patterns và owner editability

Audit/update tất cả block patterns có inline color, width, font size, min-height, column ratio hoặc spacer cố định. Nội dung, ảnh, button text/link, section order và block colors vẫn chỉnh từ Gutenberg. Không hardcode nội dung production vào template PHP; không biến section thành ảnh phẳng; không yêu cầu Additional CSS.

Patterns mới/chỉnh sửa phải:

- dùng semantic preset, không hex;
- dùng class/layout structure ổn định, không fixed desktop width;
- render tương đương trong editor và frontend;
- có block lock chỉ nơi cần bảo vệ cấu trúc, không khóa content/ảnh của owner;
- không tạo sản phẩm mẫu hoặc fake content.

## 10. Visual acceptance criteria

Chụp/kiểm tra tại 375, 768 và 1440 cho: Homepage, Shop, product, Cart, Checkout, My Account.

PASS khi:

- không horizontal overflow với `overflow-x:hidden` đã bỏ;
- header 375 có logo + cart + hamburger, không chật/lặp search/account;
- H1/hero/first viewport đạt mục tiêu mobile và CTA thấy sớm hơn;
- category/product grids đạt density mục tiêu, text/price/button không va chạm;
- controls chính có target >=44px, focus rõ, contrast WCAG-readable;
- Cart/Checkout tables/forms/notices không tràn; plugin address fields không phá layout;
- warm white/cream là diện tích chính; brown chỉ 5–15% và không có CTA/footer brown khổ lớn;
- mỗi section chỉ dùng pastel accent có chủ ý;
- sáu swatch tiếng Việt hiện trong Gutenberg, `#8A4A23` không còn là brand choice;
- editor preview và frontend không lệch cấu trúc/màu rõ rệt;
- không PHP/JS error mới.

Cosmetic nhỏ không buộc rollback. Functional checkout, overflow thật, inaccessible controls, PHP fatal hoặc palette canonical sai là gate fail.

## 11. Exact next implementation sequence

Thực hiện chung với `VIETNAM-STORE-TOOLKIT-PREFLIGHT.md`:

1. Pin Composer toolkit exact 1.1.2 và update lock.
2. Migrate official six-color tokens trong `theme.json`.
3. Remap toàn bộ frontend/editor/pattern/Botiga integration; retire `#8A4A23`.
4. Rebuild mobile-first header, hero, grids, spacing, footer và bỏ nguồn overflow thật.
5. Chỉnh site-policy nhỏ nhất cho owner/toolkit, không cấp quyền kỹ thuật.
6. WSL focused validation và source scans không hardcode/obsolete token.
7. Build một immutable release; pre-deploy backup; deploy/activate plugin.
8. Không nhập merchant values; giữ BACS/VietQR disabled/unconfigured.
9. Permission/checkout regression + visual 375/768/1440.
10. Smoke rồi KEEP hoặc ROLLBACK.

Exact authorization string cho task kế tiếp: **IMPLEMENT VIETNAM TOOLKIT + BRAND/MOBILE REMEDIATION**.
