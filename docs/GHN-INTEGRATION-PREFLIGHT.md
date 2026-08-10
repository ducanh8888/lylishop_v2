# GHN integration preflight

Trạng thái: **BUILD LYLI GHN CONNECTOR — deployed active; TEST E2E blocked by rejected credential; no GHN shipment created**. Ngày kiểm tra/triển khai: 2026-08-10. Starting commit: `97a93fa76bec3cec4406c45e1b6d1b2aafa72f81`; implementation commit: `1d7bb7b93a241eaf4448e8f1e70f5ccc2d0853c6`.

## 1. Quyết định kiến trúc

Không cài plugin GHN bên thứ ba. V1 dùng plugin nội bộ, được theo dõi trong repo tại `web/app/plugins/lyli-ghn-connector/`, và đăng ký provider vào framework có sẵn của Vietnam Store Toolkit 1.1.2. Plugin không tạo checkout/address model/database shipment riêng, không sửa WooCommerce/Toolkit/theme, không gọi aggregator và không thêm cước GHN live.

Luồng V1:

`Woo order → owner review → Toolkit shipment metabox → Lyli GHN provider → official GHN API`

Vận đơn chỉ được tạo bởi thao tác rõ ràng của người có `manage_woocommerce`. Không auto-create theo trạng thái đơn, không tự đổi Woo order status, không webhook và không shipment thật trong preflight/deployment.

## 2. Hệ sinh thái đã kiểm tra

| Candidate | Bằng chứng hiện tại | Kết luận |
|---|---|---|
| ShipDepot | WordPress.org/SVN stable `1.2.19`, tested up to 6.7.1; GPL-2.0-or-later; gọi dịch vụ trung gian `admin.shipdepot.co`; source vẫn có public REST callbacks và `wp_ajax_nopriv_*` cho thao tác vận chuyển | **REJECT**. CVE-2025-31866/CWE-862 áp dụng `<=1.2.19`; chưa có patched release |
| Shipping Viet Nam WooCommerce | Stable `3.0.1` (2020), tested up to 5.5, MIT; source dùng GHN v2 nhưng checkout ba cấp, custom table và các mutation `create/cancel/status` còn đăng ký `wp_ajax_nopriv_*` | Reference only; không tương thích mô hình địa chỉ hai cấp và security baseline |
| VNShipping for WooCommerce | Stable `0.2.0` (2021), tested up to 5.8, GPL-2.0-or-later; GitHub URI `awethemes/vn-shipping` hiện không còn clone được, WordPress SVN vẫn có source; source có GHN client/response classes nhưng map ba cấp và endpoint/detail payload cũ | Reference only; project tự ghi incomplete-development |
| Vietnam Store Toolkit 1.1.2 | Đang active, current source đã audit | **Reuse provider framework** |

Ý tưởng chỉ tham khảo: WP HTTP API, response object, route/service discovery và manual order UI. Không copy address dataset ba cấp, custom shipment tables, public AJAX authorization hay hard-coded `service_id` cũ. Không có third-party code được chép vào connector nên không phát sinh attribution source-code bổ sung.

## 3. Toolkit provider contract đã xác minh từ source 1.1.2

Filter: `yoohw_vietnam_store_tools_shipping_providers`.

Provider normalized shape:

- `id`, `name`;
- `supports`: subset của `create`, `sync`, `print`, `cancel`;
- callbacks `render_create_fields`, `create_shipment`, `sync_shipment`, `print_shipment`, `cancel_shipment`;
- action callback nhận `($order, $context)`, với `context.action`, `context.provider_id`, `context.request` đã sanitize;
- callback trả array hoặc `WP_Error`;
- print callback phải trả binary `content`, `filename`, `content_type`.

Toolkit tự kiểm tra `manage_woocommerce`, nonce gắn với order và resolve WC order trước mutation. Connector thêm capability/order validation defense-in-depth. Toolkit dùng Woo CRUD/HPOS-compatible metadata và đã quản lý metabox, customer tracking/timeline, notice/error handling.

Các key chuẩn đã xác minh: provider/name, service code/name, label ID, tracking code/ID/URL, status ID/label, fee, insurance fee, COD, last sync và raw response. Connector không tạo bảng/meta song song.

Lưu ý runtime: nếu provider hỗ trợ `sync`, Toolkit gọi sync read-only khi người có `manage_woocommerce` mở metabox của order đã có shipment. Vì vậy sync dùng endpoint detail, timeout ngắn và không mutation.

## 4. Official GHN endpoint matrix

Base production: `https://online-gateway.ghn.vn/shiip/public-api/`
Base test: `https://dev-online-gateway.ghn.vn/shiip/public-api/`

| Chức năng | Path | Auth theo docs | V1 |
|---|---|---|---|
| Preview | `v2/shipping-order/preview` | `Token` + `ShopId` | Có, trước create |
| Create | `v2/shipping-order/create` | `Token` + `ShopId` | Có, không retry tự động |
| Detail | `v2/shipping-order/detail` | `Token` | Có, sync |
| Detail by client code | `v2/shipping-order/detail-by-client-code` | `Token` | Có, idempotency/recovery |
| Cancel | `v2/switch-status/cancel` | `Token` + `ShopId` | Có, explicit owner action |
| Print token | `v2/a5/gen-token` | `Token` | Có; server fetch PDF từ fixed GHN print host; token 30 phút không persist |
| Update | `v2/shipping-order/update` | `Token` + `ShopId` | Deferred |
| Fee | `v2/shipping-order/fee` | `Token` + `ShopId` | Deferred |
| Available services | `v2/shipping-order/available-services` | `Token`, route/shop fields | Deferred |
| Legacy province/district/ward | `master-data/*` | `Token` | Reference only, không thay Toolkit address UI |
| Status callback | GHN POST vào merchant URL | Public docs không nêu HMAC/signature | Không triển khai |

HTTP client chỉ cho hai gateway HTTPS cố định, JSON explicit, kiểm tra HTTP status lẫn GHN `code`, timeout 8–15 giây, không log, redact Token trong lỗi, không retry create/cancel và không nhận arbitrary URL.

## 5. Địa chỉ hai cấp và chiến lược cước

Vietnam Store Toolkit là nguồn duy nhất cho checkout address: Woo `state = province code`, Woo `city = ward code`. Dataset bundled là 34 tỉnh/3.321 phường-xã, format hai cấp 2026 và có public helpers để đổi code sang tên.

Tài liệu hai cấp được link trực tiếp từ GHN Create Order xác nhận:

- đặt `is_new_to_address=true`;
- create/preview chấp nhận `to_ward_name + to_province_name` cùng `to_address`;
- trong mode mới không cần district;
- địa chỉ cũ và mới được GHN xử lý theo flag riêng.

Connector dùng đúng name mode này và không dựng district giả.

Live fee **chưa bật**. Tài liệu hai cấp GHN yêu cầu `to_ward_id_v2 + to_address_v2 + is_new_to_address=true`; ID này đến từ bảng mapping riêng của GHN, không phải mã phường quốc gia đang lưu bởi Toolkit. GHN có official Google Sheet mapping old/new GHN WardID, nhưng chưa có authenticated runtime API/version contract để connector map 3.321 ward một cách bền vững. V1 tiếp tục dùng shipping rules của Toolkit tại checkout. Không có `WC_Shipping_Method`, remote checkout call, fake rate hay hard-coded service ID.

## 6. Shipment policy

- `client_order_code = LYLI-WC-{order_id}` (ổn định, dưới giới hạn 50 ký tự).
- Trước create: kiểm tra Toolkit local metadata, sau đó `detail-by-client-code`. Existing shipment được nhận lại; not-found mới preview/create.
- Timeout create không retry. Lần bấm lại sẽ recover bằng client code, ngăn duplicate.
- GHN `order_code` là tracking/label ID canonical.
- Status GHN được map vào Toolkit timeline, không tự đổi Woo status.
- Webhook không có strong verification trong public docs: V1 chỉ manual/pageload sync có auth outbound.
- Print token lấy server-side, chỉ fixed GHN URL, chỉ chấp nhận PDF và không persist token.

## 7. COD, bảo hiểm và kích thước

Mặc định COD và khai giá đều tắt. Nếu owner chọn `cod_gateway_only`, connector chỉ thu số tiền còn lại khi Woo payment method là `cod` và order chưa paid; BACS/prepaid/paid/free không bị suy ra thành COD. Refund đã trừ khỏi amount. Không có auto order-status mutation.

Hai product publish hiện tại không có `_weight/_length/_width/_height`. Connector không gửi fallback 1g/1cm. Owner phải nhập package weight/length/width/height trước khi bật. Hàng nhẹ dùng package defaults. Hàng nặng còn yêu cầu đủ dimensions/weight cho từng line product; thiếu thì create bị block với lỗi hành động được.

Không hard-code service ID lịch sử. Owner chọn `service_type_id` 2 (hàng nhẹ) hoặc 5 (hàng nặng); V1 không gọi available-services vì chưa có WardID v2 route mapping.

## 8. Permission, secret và dữ liệu

- `shop_owner`: đã có `manage_woocommerce`; thấy settings và dùng Toolkit create/sync/cancel/print khi account được provision sau này.
- `shop_staff`: hiện không có `manage_woocommerce`; không được cấu hình hoặc thao tác GHN. Không mở thêm quyền trong V1.
- administrator: emergency/developer access.
- settings save có capability + nonce; Toolkit mutations có capability + per-order nonce; connector kiểm tra capability/order lần nữa.
- Token nằm trong option riêng `lyli_ghn_token`, `autoload=false`, input password luôn rỗng khi render, để trống giữ nguyên, có clear explicit. Token không vào Git/docs/HTML/logs/diagnostics.
- Không “mã hóa” option bằng key cùng database vì không tạo thêm security boundary thực.
- Plugin không có REST/webhook/AJAX public, direct SQL, schema activation hay background job.

## 9. Risk matrix

| Mức | Rủi ro | Mitigation/gate |
|---|---|---|
| HIGH | Tạo shipment thật/COD sai | Connector deploy disabled/unconfigured; manual action only; conservative COD; no credentials/test shipment in deployment |
| HIGH | Webhook spoofing | Không expose webhook; sync bằng outbound authenticated detail |
| MEDIUM | GHN name matching đổi theo dataset | Validate code bằng Toolkit và gửi official two-level names/flag; test gateway E2E bắt buộc trước production enable |
| MEDIUM | Duplicate do timeout/double-click | local metadata + deterministic client code + detail recovery; no automatic retry |
| MEDIUM | Thiếu package/product dimensions | owner package defaults required; heavy item dimensions block create |
| MEDIUM | GHN outage làm chậm admin | chỉ gọi trong shipment UI, timeout ngắn; không gọi checkout/public pages |
| MEDIUM | Print response active content | chỉ accept PDF từ fixed GHN host; reject HTML/other content |
| LOW | Token disclosure | masked non-autoload option, redacted errors, no logging/export |
| LOW | Toolkit auto-sync on order view | detail-only sync, no Woo status mutation |

## 10. Validation and implementation gate

Network-free validator `scripts/validate-ghn-connector.php` covers serialization, response/application errors, timeout, token redaction, two-level mapping, missing dimensions, COD/refund logic, status mapping, existing client-code idempotency, capability denial, nonce rejection and absence of public webhook/live-rate code.

PHP lint, GHN validator, storefront validator, Bedrock bootstrap validator, Composer validation/audit, secret scan and artifact inspection đều PASS. Production release `20260810210244` dùng artifact `release-20260810210129.tar.gz` (SHA-256 `e1cdfc5bc9fa56605799f2c48fa0d9f202a9ce590128d848112d71f6ab08db05`). Backup trước switch là `shared/backups/20260810210321`; rollback target `20260810190111` vẫn còn và không ở maintenance.

Runtime giữ đúng safe state: WordPress plugin 0.1.0 active; connector chưa enabled; không có option `lyli_ghn*`, Token, ShopId, provider runtime hoặc shipment meta GHN. Menu **WooCommerce → Kết nối GHN** đăng ký với `manage_woocommerce`; form Token luôn masked/blank khi render. Production chưa có account role `shop_owner`, nên developer phải provision account đúng người trước handoff. Public Home/Shop/Cart/Checkout/Account/login đều HTTP 200, checkout vẫn có Province/Ward của Toolkit, và không lộ PHP warning/fatal.

`CODE VALIDATED` does not mean `GHN API END-TO-END VALIDATED`. E2E requires owner-provided test Token + test ShopId and explicit later authorization. See `docs/GHN-OWNER-SETUP.md`.

### TEST gateway validation update — 2026-08-10

Owner đã lưu Token/ShopId và bật connector, nhưng environment ban đầu là Production. Validation dừng trước network, chuyển riêng environment sang **Test** bằng settings handler có capability/nonce, rồi xác nhận resolved base URL là `https://dev-online-gateway.ghn.vn/shiip/public-api/`. Non-mutating `detail-by-client-code` probe trả `HTTP 401`, GHN code `401`, sanitized message `Token is not valid`, latency 172 ms. Đây khớp error contract chính thức và không phải parser/endpoint defect.

Không tiếp tục Preview/Create/Sync/Print/Cancel vì credential gate chưa PASS; Woo test order và GHN shipment không được tạo. Connector vẫn ở Test, COD disabled, live fee/webhook disabled. Secret audit PASS: Token không xuất hiện trong admin HTML, frontend, REST index, log, order meta, source hoặc option khác; option Token không autoload. Permission runtime cho Settings/Create/Sync/Cancel/Print và invalid-nonce denial PASS. 18 network-free checks cùng 7 runtime error/parser checks đều PASS.

### Direct Token diagnosis update — 2026-08-11

Owner xác nhận saved Token được lấy từ `5sao.ghn.dev`, không phải production. Để tách connector khỏi phép thử, server gọi trực tiếp `GET https://dev-online-gateway.ghn.vn/shiip/public-api/master-data/province` bằng WordPress HTTP API với đúng saved Token và không có ShopId/header connector khác. Kết quả: TLS/transport thành công, HTTP `401`, GHN code `401`, sanitized message `Token is not valid`, latency 259 ms; Token không được in/log.

Vì direct request và connector request cùng bị GHN Test từ chối, root cause được phân loại là **GHN staging credential/account provisioning problem**. Không có bằng chứng lỗi settings save, option read, masking hoặc HTTP-header construction; không sửa source. ShopId và shipment lifecycle chỉ được kiểm tra sau khi direct province request trả 200. Owner cần kiểm tra account/shop đã được kích hoạt trong 5Sao staging hoặc cấp lại Token staging, nhập qua wp-admin và không gửi credential qua chat/Git.

## 11. Primary evidence

- WordPress.org/SVN: `https://wordpress.org/plugins/ship-depot/`, `https://plugins.svn.wordpress.org/ship-depot/trunk/`, `https://wordpress.org/plugins/shipping-viet-nam-woocommerce/`, `https://plugins.svn.wordpress.org/vnshipping-for-woocommerce/`.
- NVD: `https://nvd.nist.gov/vuln/detail/CVE-2025-31866`.
- GHN docs root: `https://api.ghn.vn/home/docs`; Create `id=122/123`, Preview `id=81`, Fee `id=76`, Services `id=77`, Detail `id=66`, Detail by client code `id=118/119`, Cancel `id=73`, Update `id=75`, Print `id=67`, Callback `id=47`, Status `id=48`, Province/District/Ward `id=60/78/61`.
- GHN two-level administrative-unit document and GHN WardID mapping are the Google document/sheet linked directly from Create Order `id=122`; checked on 2026-08-10, not copied into the repo.
- Exact Toolkit contract: installed Composer package source `web/app/plugins/yoohw-vietnam-store-tools/includes/class-vietnam-commerce-kit-shipping.php` and two-level data helper/source in the same pinned 1.1.2 package.
