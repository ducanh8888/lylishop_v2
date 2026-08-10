# Vietnam Store Toolkit decoupling

## Trạng thái

- **CURRENT RUNTIME:** production vẫn chạy `lyli-ghn-connector` 0.1.1 và Vietnam Store Toolkit 1.1.2. Không có thay đổi activation, option, payment, shipping hay merchant data trong refactor này.
- **CURRENT SOURCE:** GHN connector 0.2.0 chỉ hard-require WooCommerce; Toolkit là adapter tùy chọn. Plugin generic `vietqr-bacs-for-woocommerce` 0.1.0 đã có trong repo nhưng chưa deploy/activate.
- **COMPOSER:** giữ `wpackagist-plugin/yoohw-vietnam-store-tools:1.1.2`. Dependency chưa đạt zero vì production address fields/dataset hai cấp và checkout shipping rules chưa có cutover thay thế.

## Dependency inventory

| Nhóm | Trước refactor | Phân loại và kết quả |
|---|---|---|
| A. GHN | Core mapper gọi Toolkit address helper; Provider đọc Toolkit shipment meta; chỉ có Toolkit order panel/actions | **REPLACEABLE BY SMALL FIRST-PARTY MODULE — DONE IN SOURCE.** Domain/API/COD/idempotency/print/status không import Toolkit. Connector sở hữu shipment repository và Woo order admin fallback; thin adapter giữ Toolkit UI khi contract tương thích |
| B. VietQR/payment | Toolkit mở rộng native `bacs` | **REPLACEABLE BY SMALL FIRST-PARTY MODULE — IMPLEMENTED, NOT ACTIVE.** `vietqr-bacs-for-woocommerce` dùng WC Integration settings và native BACS semantics; không gateway mới, autobank, webhook hoặc auto-paid |
| C. Address | Toolkit sở hữu checkout Province/Ward fields và national two-level dataset | **REQUIRED AT CURRENT RUNTIME.** GHN có address contract, Toolkit resolver tùy chọn và Woo fallback đọc state/city dạng tên. Fallback từ chối numeric/unresolved codes; không dựng district mapping |
| D. Shipping rules | Toolkit shipping rules hiện là nguồn phí checkout; GHN live fee deferred | **REQUIRED UNTIL NATIVE CUTOVER.** GHN không còn tham chiếu shipping rules. Tương lai migrate có kiểm soát sang Woo Shipping Zones + Flat Rate/Free Shipping; task này không đổi production |
| E. Tracking | Toolkit metadata/panel/customer display | **OPTIONAL INTEGRATION.** GHN ghi canonical order meta bằng Woo CRUD và có standalone admin/customer display. Legacy `_vck_shipping_*` vẫn đọc được; Toolkit adapter mirror dữ liệu UI cần qua framework hiện hữu |
| F. Invoice/e-invoice | Toolkit có VAT, electronic invoice và credential surfaces | **OBSOLETE FOR CURRENT LYLI REQUIREMENTS.** Chưa cấu hình/dùng; không tái tạo. Chỉ thêm giải pháp riêng nếu có requirement kinh doanh sau này |
| G. Owner/admin UI | Toolkit main page, shipping/provider panel và BACS extension | **PARTLY REPLACED.** GHN có settings + order controls với `manage_woocommerce`/nonce. VietQR dùng WooCommerce Integrations. Toolkit UI còn cần cho address/shipping trong runtime hiện tại |
| H. Phone/migration/other | Phone normalization, DevVN migration tools, tax/order utilities | Phone normalization là **OPTIONAL** vì GHN tự normalize payload; DevVN tools là **OBSOLETE after Toolkit removal** nhưng site-policy guard vẫn cần khi Toolkit còn active; tax/order utilities chưa có requirement phải tái tạo |

## GHN source architecture

```text
Domain Address + GHN API/Print/Status/COD
        ↓
Woo OrderAdapter / Shipment_Repository / Standalone_Admin
        ↓
WooCommerce CRUD and capabilities

Optional composition adapters:
Integrations/VietnamStoreToolkit/Toolkit_Address_Resolver
Integrations/VietnamStoreToolkit/Toolkit_Adapter
Integrations/VietnamStoreToolkit/Toolkit_Legacy_Shipment_Reader
```

Canonical state dùng `_lyli_ghn_*` order meta qua `WC_Order::update_meta_data()`/`save_meta_data()`: provider `ghn`, GHN order code, client order code, service/status, last sync, fee, insurance và COD. Không lưu GHN Token hoặc print token. Read order: canonical trước, legacy Toolkit metadata sau. New create/sync/cancel writes canonical; Toolkit framework chỉ là optional UI adapter.

Khi Toolkit contract hợp lệ, chỉ Toolkit panel được đăng ký để tránh hai panel. Khi Toolkit vắng/không hỗ trợ, Woo-native meta box cung cấp Create/Sync/Print/Cancel và customer tracking. Mọi mutation tiếp tục yêu cầu `manage_woocommerce`, nonce và server-side order validation.

## VietQR BACS replacement

Plugin `vietqr-bacs-for-woocommerce`:

- owner cấu hình trong **WooCommerce → Settings → Integrations → VietQR BACS**;
- mặc định disabled; không chứa bank/account data trong source;
- render QR chỉ cho order BACS chưa paid tại Thank You, Order Pay và My Account order view;
- amount = total trừ refund; transfer description deterministic từ order number/id;
- URL chỉ dùng `https://img.vietqr.io/image/`; không callback, settlement hay tự đổi order status;
- owner phải duyệt privacy disclosure trước khi enable vì URL ảnh chứa merchant/order fields.

Không chạy đồng thời QR của Toolkit và plugin mới. Runtime cutover phải tắt Toolkit VietQR trước, activate/configure replacement, smoke rồi mới cân nhắc deactivate Toolkit.

## Điều kiện gỡ Toolkit hoàn toàn

Chỉ remove Composer package sau khi tất cả gate sau PASS trong task cutover riêng:

1. Cấu hình Woo Shipping Zones/Flat Rate/Free Shipping tương đương rule production và checkout regression PASS.
2. Có owner-approved source cho Province/Ward fields hai cấp, hoặc xác nhận standard Woo state/city dạng tên đủ cho checkout và GHN; không để order mới lưu numeric codes không resolve được.
3. VietQR replacement đã deploy nhưng giữ disabled cho tới khi owner nhập merchant data; Toolkit VietQR không còn active.
4. Historical GHN orders đọc được qua canonical/legacy fallback; standalone order controls và customer tracking PASS khi Toolkit inactive.
5. Xác nhận VAT/e-invoice/phone/tracking Toolkit không còn production requirement.
6. Backup DB, deactivate Toolkit có kiểm soát, smoke Home/Shop/Cart/Checkout/Account/order admin; keep hoặc rollback.

## Runtime cutover sau này

1. Local validate source; build immutable release.
2. Backup DB/uploads và ghi rollback release.
3. Deploy code nhưng chưa activate VietQR replacement và chưa deactivate Toolkit.
4. Verify GHN 0.2 standalone persistence/adapter compatibility; migrate/sync một disposable Test order nếu được duyệt.
5. Cấu hình native shipping zones và regression checkout.
6. Activate VietQR replacement disabled; owner tự nhập merchant values và privacy disclosure.
7. Tắt Toolkit VietQR, verify không có hai QR implementation.
8. Chỉ khi address/shipping/invoice gates đều đạt: deactivate Toolkit, smoke, rồi remove Composer package trong source task tiếp theo.
9. Không đạt gate nào thì giữ Toolkit active hoặc rollback; không xóa options/order metadata.
