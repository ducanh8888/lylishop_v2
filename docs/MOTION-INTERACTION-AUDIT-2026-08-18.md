# Motion/Interaction audit (second pass) — 2026-08-18

Audit trực quan trên production thật (`https://lylishop.online`), không phải source local. Đây là ghi chép kết quả — **không sửa source, không deploy trong phạm vi audit này** (xem `SOURCE CHANGES: NONE` cuối file).

## Nguồn xác nhận

- Source HEAD lúc audit: `aa879bc5ade35e622239589c52bd0acf6a30014e`.
- Release production lúc audit: `apps/lylishop/releases/20260818003724` (symlink `current` xác nhận khớp qua SSH).
- Phương pháp: fetch HTML/CSS thật qua `curl` (đối chiếu cascade/specificity) **rồi verify lại toàn bộ bằng Chrome thật qua Playwright MCP** — screenshot, computed style, DOM measurement, click-interaction, PerformanceObserver. Một kết luận từ pha đọc-CSS-thuần-túy đã bị chứng minh sai và sửa lại sau khi có browser thật (xem mục "Sai sót đã tự sửa").
- Viewport đã test: Desktop 1440×900, Tablet 820×1180, Mobile 390×844.
- Trang đã test bằng browser thật: Home, Shop (`/cua-hang/`), Product category (`/product-category/moc-khoa-len/`), Product detail, Blog article, Cart (`/gio-hang/`), Account (`/tai-khoan/`). Blog archive (`/blog/`) và Checkout chỉ verify qua HTML fetch (không screenshot).

## VISUAL STATUS

| Trang | Trạng thái | Bằng chứng |
|---|---|---|
| Homepage | **NEEDS WORK** | Browser-verified: 3 bug nghiêm trọng (xem P0-0, P0-1, P0-2) |
| Homepage — mobile 390px | **NEEDS WORK (nặng nhất)** | Screenshot: ring trang trí đè qua giữa headline chính |
| Shop archive | **NEEDS WORK** | Screenshot: banner trắng lạc tông + dead space |
| Product category archive | **NEEDS WORK** | Screenshot: cùng vấn đề với Shop (dùng chung `.woocommerce-page-header`) |
| Product detail | **PASS** | Screenshot: layout sạch, gallery/price/CTA rõ ràng, nav không lỗi |
| Blog archive (`/blog/`) | **PASS** | HTML/CSS: grid 3 cột thật, container 1140px nhất quán, card đã có style — khác giả định ban đầu |
| Blog article | **PASS** | Screenshot: typography sạch, không banner trắng |
| Cart | **PASS** | Screenshot: dùng `.page-header` (không phải `.woocommerce-page-header`), không dead-space |
| Checkout | **PASS** | Redirect 302 về giỏ hàng khi trống — hành vi WooCommerce chuẩn |
| Account | **PASS** | Screenshot: login form sạch, input/button đúng brand |
| FAQ (accordion) | **PASS** | Click-test thật: mở/đóng đúng, chevron xoay đúng hướng |
| Console/Network | **PASS** | 0 error, 0 warning toàn session; 0 request 404 cho CSS/JS |
| CLS (Homepage) | **PASS** | `PerformanceObserver` đo được `CLS = 0` |

## Phát hiện theo mức độ ưu tiên

### P0-0 — Homepage: block `wp:latest-posts` vỡ layout hoàn toàn (nghiêm trọng nhất toàn site)

**Vị trí:** section "Cẩm nang quà handmade nhỏ xinh" (`id="news"`, class `lyli-editorial-news`), gần cuối trang chủ.

**Xác nhận bằng DOM + screenshot:** đây là Gutenberg core block `wp:latest-posts` (`<ul class="wp-block-latest-posts__list has-dates ...">`), **không thuộc 8 pattern kiểm soát** trong `inc/block-patterns.php` — do chủ shop tự thêm qua block editor. `style.css` của theme **không có bất kỳ rule nào** cho `.wp-block-latest-posts`.

- HTML khai báo ảnh `width="150" height="150"` (`class="attachment-thumbnail size-thumbnail"`, `alignleft`), nhưng khi render thực tế trong browser, ảnh chiếm **gần full content-width (~700px+)**, không crop, không tỉ lệ khống chế.
- Ảnh hiển thị là ảnh chụp góc làm việc chưa qua chọn lọc (bàn làm việc, màn hình máy tính, không phải ảnh sản phẩm biên tập).
- Toàn bộ section đo được **chiều cao 4068px** (`getBoundingClientRect()`) chỉ để hiển thị 5 link bài viết dạng danh sách.

**Đề xuất (chưa làm):**
(a) style `.wp-block-latest-posts` theo ngôn ngữ card đã dùng ở blog archive (border-radius, `aspect-ratio` cố định, `object-fit:cover`), hoặc
(b) xóa block này khỏi trang chủ, trỏ nút "Xem thêm" về `/blog/` (đã xác nhận hoạt động tốt).

**Layer:** CSS only (a) hoặc content-edit (b). **Risk:** thấp. **Impact:** rất cao.

### P0-1 — Homepage Hero: ảnh không lấp khung + ring trang trí đè lên ảnh/chữ

**Xác nhận bằng screenshot desktop + tablet + mobile:**

1. **Ảnh không lấp đầy khung `.lyli-hero-visual`** — lộ khối nền `--lyli-color-lavender` trống, chiếm ~40% chiều cao khung ở desktop. Nguyên nhân chính xác (đã trace CSS cascade): `.lyli-hero-visual img { height: 100% }` không thể resolve vì `.lyli-hero-visual` chỉ có `min-height` (không phải `height` tường minh) — quy tắc CSS: percentage height không resolve trên parent chỉ có `min-height`. Ảnh do đó co theo `aspect-ratio: 5/4` tự nhiên (ngắn hơn `min-height` của container), phần còn lại lộ màu nền lavender thô.
2. **Ring trang trí `.lyli-hero-visual::before/::after`** (2 vòng tròn viền dày, thiết kế gốc cho ảnh placeholder trừu tượng của `wp:cover` block) vẫn render trên `<figure class="wp-block-image">` chứa ảnh sản phẩm thật mà chủ shop đã thay vào — đè lên góc ảnh ở mọi viewport.
3. **Nghiêm trọng nhất ở mobile (390px):** ring `.lyli-hero::before` (kích thước cố định 320px, không có `clamp()`/`vw`) đè **trực tiếp qua giữa chữ headline chính** ("Móc khóa len handmade cute..."), làm chữ đầu dòng mờ/khó đọc — ảnh hưởng đọc-hiểu above-the-fold thật sự, không chỉ thẩm mỹ.

**Đề xuất (chưa làm):**
- Cho `.lyli-hero-visual` một `height` tường minh khớp `min-height`, hoặc bỏ `height:100%` trên `img` và để `aspect-ratio` tự tính (loại bỏ khối lavender).
- Scale ring theo `clamp()`/`vw` thay vì px cố định, hoặc loại trừ ring khi `.lyli-hero-visual` chứa `.wp-block-image` (ảnh thật) thay vì `.wp-block-cover` (placeholder gốc).

**Layer:** CSS only. **Risk:** thấp. **Impact:** rất cao (above-the-fold, ảnh hưởng đọc-hiểu mobile).

### P0-2 — Header/Nav: 4/6 mục cùng hiện trạng thái "đang chọn" trên Homepage

**Xác nhận bằng `getComputedStyle` thật** (không chỉ đọc markup): trên Homepage, 4 mục nav ("Trang chủ", "Danh mục", "Giới thiệu", "Liên hệ") đều render `color: rgb(122, 59, 23)` (= `--lyli-color-primary`, màu "đang chọn") **đồng thời**, vì WordPress core gán `current-menu-item` + `aria-current="page"` cho mọi custom-link menu item có URL trỏ về `/` hoặc `/#anchor` (menu này dùng anchor nội bộ trong trang chủ, không phải trang riêng). Verify trên Shop archive: đúng, chỉ 1 mục ("Sản phẩm") sáng — bug chỉ xảy ra ở Homepage.

Đây là hệ quả trực tiếp của patch nav-current-state trong lần triển khai motion/hover trước (`style: improve navigation interaction states`, commit `93ddbb6`) — CSS đúng như thiết kế, nhưng markup thực tế của menu (toàn bộ item trỏ về trang chủ dưới dạng anchor) khiến điều kiện "current page" không phân biệt được item nào thực sự tương ứng section đang xem.

**Đề xuất (chưa làm):** không áp dụng `.current-menu-item` cho menu item có URL là anchor-fragment của chính trang chủ; chỉ áp dụng cho menu item trỏ tới URL/trang thực sự khác (Shop, Blog).

**Layer:** CSS (loại trừ selector, hoặc thêm class phân biệt qua PHP nếu cần chính xác tuyệt đối). **Risk:** thấp. **Impact:** cao — trang chủ là trang mọi khách đều thấy đầu tiên.

### P0-3 — Shop/Category archive: banner chưa brand hóa + dead space

**Xác nhận bằng screenshot Shop VÀ Product-category** (cùng bug, vì cả hai dùng chung `.woocommerce-page-header`):

- Banner "Cửa hàng"/"Móc khóa len" nền **trắng tinh** (`background:#FFF`, `h1{color:#212121}` — giá trị mặc định Botiga generate ra, **chưa từng được ghi đè sang token brand Lyli**), tương phản mạnh với nền cream phía trên (header) và phía dưới (sorting bar) — trông như lỗi render, phá vỡ tông màu ấm xuyên suốt site.
- Khoảng trắng lớn giữa H1 và product grid — định lượng qua CSS cascade: `.woocommerce-page-header{padding-top/bottom:80px}` (Botiga) + margin cộng dồn từ `.woocommerce .page-title{margin-bottom:≤54px}` và `.woocommerce-products-header{margin-bottom:≤54px}` (2 rule trong `shop-child/style.css`), một phần được Botiga tự bù bằng `margin-bottom:-60px` + `.woocommerce-page-header + .content-wrapper{margin-top:100px}` (net ~40px, không phải 100px thô).

**Đề xuất (chưa làm):** brand hóa `background`/`color` của `.woocommerce-page-header` sang token Lyli; giảm `padding-top/bottom` xuống ~40-48px.

**Layer:** CSS only (`shop-child/style.css`, không sửa Botiga source). **Risk:** thấp. **Impact:** cao.

### P1-1 — Homepage Category cards: chiều cao không đều

**Xác nhận bằng đo DOM thật** (`getBoundingClientRect()`): 5 card ở desktop cao không đều — card 1 ("Móc khóa len") = 385.475px, card 2-5 = 361.475px (chênh 24px). Title wrap 2 dòng (chiều cao đo được 82.8px) với text ngắn ("Móc khóa len", "Hoa len đặt riêng", "Hộp quà đặt riêng") vs 3 dòng (110.4px) với text dài hơn ("Gấu bông len đặt riêng", "Đặt mẫu theo yêu cầu") — do chủ shop tự sửa nội dung dài hơn bản pattern gốc.

Ghi nhận thêm (không phải motion/CSS bug, là quyết định nội dung của chủ shop): 4/5 card hiện trỏ cùng một URL `/dat-mau-theo-yeu-cau/` (chỉ card 1 trỏ tới category thật `?product_cat=moc-khoa-len`).

**Đề xuất (chưa làm):** `-webkit-line-clamp:2` cho title, hoặc giảm 1 bậc font-size khi title dài, để đảm bảo card đều hàng.

**Layer:** CSS only. **Risk:** thấp. **Impact:** trung bình.

### P1-2 — Product loop: add-to-cart button lồng trong product-link anchor

Xác nhận qua HTML thật của Shop/Category archive: `<a class="woocommerce-LoopProduct-link">...<a class="button ... add_to_cart_button">Thêm vào giỏ hàng</a>...</a>` — anchor lồng trong anchor (HTML không hợp lệ), do cách Botiga sắp xếp hook (`shop_product_add_to_cart_layout=layout3` mặc định), không phải do `shop-child`. Trình duyệt tự "sửa" bằng cách tách anchor ngoài quanh anchor trong nên hành vi click vẫn hoạt động độc lập đúng (đã verify: nav/button hoạt động bình thường trên mọi trang test).

**Không xử lý trong phạm vi CSS-only** — cần đổi PHP hook/theme_mod, ngoài phạm vi motion refinement. Ghi nhận để cân nhắc riêng.

## Đã verify KHÔNG có vấn đề (khác giả định ban đầu trong yêu cầu)

- **Blog archive (`/blog/`)**: **không phải** layout dồn-trái/ảnh-quá-to như mô tả ban đầu trong yêu cầu. Grid 3 cột Bootstrap thật (`col-lg-4 col-md-4`, `posts-archive.layout3`), container 1140px nhất quán với toàn site, card đã có border/radius/shadow/aspect-ratio cố định qua `shop-child/style.css`. Vấn đề "blog" thật sự nằm ở block `wp:latest-posts` trên **Homepage** (P0-0), không phải trang blog archive.
- **FAQ chevron**: kiểm tra lần đầu (đọc `getComputedStyle` ngay sau khi gọi `.click()`) cho kết quả sai — tưởng chevron không xoay. Nguyên nhân: đọc computed style giữa lúc CSS transition 320ms đang chạy, chưa tới trạng thái cuối. Test lại với `await` 500ms sau click: chevron xoay đúng (`rotate(-135deg)` xác nhận qua transform matrix), và screenshot với 3 mục mở đồng thời cho thấy style/behavior hoàn toàn đúng. **Đây là bug trong phương pháp test, không phải bug trong code.**
- **Button hover/press feedback**: subtle đúng chuẩn đã brief, xác nhận bằng hover thật.
- **Console/Network/CLS**: sạch hoàn toàn (0 error, 0 warning, 0 404, CLS=0) — nền tảng tốt cho việc thêm motion mới ở lần sau.

## TOP 5 thay đổi ưu tiên tiếp theo

1. Xử lý block `wp:latest-posts` vỡ layout trên Homepage (P0-0).
2. Sửa ảnh hero không lấp khung + scale ring theo viewport, đặc biệt ưu tiên mobile (P0-1).
3. Sửa nav current-state hiện sai đồng thời 4 mục trên Homepage (P0-2).
4. Brand hóa banner Shop/Category archive + giảm dead space (P0-3).
5. Category card height/text-wrap consistency (P1-1).

*(Hero entrance choreography và section-reveal system dùng chung — đề xuất từ pha audit đầu — vẫn còn giá trị nhưng xếp sau 5 mục sửa-lỗi này vì đây là bug thật đang hiển thị sai cho khách, ưu tiên hơn thêm motion mới.)*

## DO NOT CHANGE

- FAQ accordion (chevron, open/close, style) — xác nhận hoạt động hoàn hảo qua browser thật.
- Button hover/`:active` press feedback, shadow-alpha-0 interpolation.
- Product card image scale + fix xung đột opacity với Botiga (từ lần triển khai trước).
- Category card lift `-2px` + shadow 2-layer interpolation (biên độ đúng, chỉ chiều cao cần sửa — xem P1-1).
- Hover-capability media query architecture, reduced-motion 2-lớp, sticky header backdrop-filter scoping.
- Blog archive (`/blog/`) grid/width/card styling — không cần đại tu, chỉ polish nhỏ nếu muốn (hover card, style `.entry-meta`).

## Giới hạn của audit này

Chưa test bằng browser thật: tablet cho Shop/Product/Blog, mobile cho Shop/Product/Blog/Cart/Checkout/Account, Lighthouse/DevTools Performance panel đầy đủ, hành vi hover-tap thật trên thiết bị iOS/Android vật lý (Playwright mô phỏng viewport, không mô phỏng hoàn toàn touch-hardware). Nếu cần, có thể tiếp tục pass browser cho phần còn lại trước khi lên plan implementation.

---

**SOURCE HEAD:** `aa879bc5ade35e622239589c52bd0acf6a30014e`
**DEPLOYED RELEASE:** `releases/20260818003724`
**SOURCE CHANGES:** NONE
**DEPLOY:** NONE
