# Vietnam Store Toolkit decoupling

Updated: 2026-08-11

## Current truth

- **CURRENT RUNTIME:** production remains on `lyli-ghn-connector` 0.1.1 plus Vietnam Store Toolkit 1.1.2. This source cleanup does not deploy, activate, deactivate, migrate data, or call GHN.
- **CURRENT SOURCE:** GHN connector 0.2.0 has one first-party application workflow for Create/Sync/Cancel/Print. WooCommerce admin and the optional Toolkit adapter dispatch that same workflow.
- **TRANSITIONAL DEPENDENCY:** keep `wpackagist-plugin/yoohw-vietnam-store-tools:1.1.2` because production still uses its Vietnamese two-level address UI/data and checkout shipping rules.
- **ADDRESS:** **REUSE-FIRST / SOURCE NOT YET SELECTED.** No administrative dataset is copied into owned code. The GHN canonical address boundary accepts recipient, phone, optional province/ward codes, names, and street; unresolved numeric values fail instead of fabricating a district.
- **VIETQR:** **REUSE-FIRST / CANDIDATE NOT YET SELECTED.** The undeployed custom `vietqr-bacs-for-woocommerce` prototype was removed. No replacement is selected or added by this cleanup.

## Source boundaries

```text
Woo order panel ─┐
                 ├─ Shipment_Application ─ GHN Client
Toolkit adapter ─┘          │
                            ├─ Order Mapper / canonical Address
                            ├─ Settings Repository
                            └─ Shipment Repository
```

`Shipment_Application` is the only implementation of the lifecycle. Admin surfaces own capability/nonce checks and presentation; application also performs defense-in-depth order/capability validation. API endpoint selection, transport parsing, print allowlists and redaction remain in the GHN client. COD and package validation are domain policies.

Toolkit-specific provider hooks, address lookup and `_vck_*` compatibility live only under `includes/integrations/vietnam-store-toolkit/`, apart from the composition-root detection/registration needed to load the optional adapter. GHN core continues when Toolkit support is absent and falls back to the standalone Woo panel/customer tracking.

## Persistence and naming

New writes use one centralized canonical schema in `Shipment_Meta_Keys`:

- `_openship_ghn_provider`
- `_openship_ghn_order_code`
- `_openship_ghn_client_order_code`
- `_openship_ghn_service_code`, `_openship_ghn_service_name`
- `_openship_ghn_status`, `_openship_ghn_status_label`
- `_openship_ghn_tracking_url`
- `_openship_ghn_fee`, `_openship_ghn_insurance_fee`, `_openship_ghn_cod_amount`
- `_openship_ghn_last_synced_at`

Read priority is canonical, legacy `_lyli_ghn_*`, then legacy Toolkit `_vck_shipping_*`. Each legacy schema is isolated in a reader; application, admin and GHN client know none of those keys. There is no multi-write and no credential/print token in order metadata.

The physical plugin slug, PHP namespace, text domain, action/nonces, settings option `lyli_ghn_settings`, private non-autoload Token option `lyli_ghn_token`, and GHN `LYLI-WC-{order_id}` client code remain stable deliberately. They were already part of deployed 0.1.1 settings/idempotency behavior; renaming them would create credential loss, broken admin requests, or duplicate-shipment risk without improving the carrier boundary. New shipment storage is neutral because 0.2.0 has not been deployed.

## Runtime cutover gate

1. Migrate checkout shipping rules to WooCommerce Shipping Zones/native methods and verify totals at 375/768/1440.
2. Select and audit a reusable two-level Vietnam address component; preserve order/account address compatibility.
3. Select and audit a reusable VietQR component only if owner requirements still call for it; do not run two QR renderers.
4. Deploy GHN 0.2.x through immutable release workflow and verify canonical writes plus both legacy read paths.
5. Confirm Toolkit VAT/e-invoice/migrations/tracking surfaces are no longer required.
6. Back up DB, deactivate Toolkit in a controlled cutover, smoke Home/Shop/Cart/Checkout/Account/order admin, then keep or rollback.
7. Remove the Toolkit Composer package only in a later source task after the runtime gate passes.

Until every gate passes, Toolkit remains a transitional runtime dependency. This document does not authorize production cutover.
