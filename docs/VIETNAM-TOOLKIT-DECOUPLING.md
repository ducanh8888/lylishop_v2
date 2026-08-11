# Vietnam Store Toolkit decoupling

Updated: 2026-08-11

## Current truth

- **CURRENT RUNTIME:** production remains on `lyli-ghn-connector` 0.1.1 plus Vietnam Store Toolkit 1.1.2. This source cleanup does not deploy, activate, deactivate, migrate data, or call GHN.
- **CURRENT SOURCE:** GHN connector 0.2.1 has one first-party application workflow for Create/Sync/Cancel/Print. WooCommerce admin and optional address/Toolkit adapters dispatch that same workflow. Commit `7c36070db7fc9cc95091f1119e83b220785a28da` also prevents a late ward AJAX response from replacing the current province selection.
- **TRANSITIONAL DEPENDENCY:** keep `wpackagist-plugin/yoohw-vietnam-store-tools:1.1.2` because production still uses its Vietnamese two-level address UI/data and checkout shipping rules.
- **ADDRESS:** **SELECTED / SOURCE-INTEGRATED / NOT ACTIVE IN PRODUCTION.** `lyli-vietnam-address` uses the pinned MIT-licensed `thanglequoc/vietnamese-provinces-database` `v4.0.0` asset (34 provinces, 3,321 wards) behind a thin Woo adapter and canonical DTO. The storage contract remains compatible with current Toolkit two-level codes.
- **VIETQR:** **SELECTED / SOURCE-INTEGRATED / NOT ACTIVE IN PRODUCTION.** `lyli-vietqr-bacs` uses `liopay/vietqr` `1.0.0` plus `chillerlan/php-qrcode` `6.0.1`. It presents a locally generated QR for native BACS only; it adds no gateway, SaaS, webhook or paid-status mutation.
- **DECISION EVIDENCE:** see [`REUSE-DEPENDENCY-DECISIONS.md`](REUSE-DEPENDENCY-DECISIONS.md).

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

The source composition root now prefers `includes/integrations/vietnam-address/` when the selected address plugin is active, then uses the Toolkit address resolver only as a transitional compatibility fallback. It still emits the same canonical GHN `Address`; carrier code has no Woo field or dataset dependency.

Payment is no longer a reason to retain Toolkit. Native Woo BACS remains the gateway/order-status owner; the reusable VietQR adapter is presentation only.

## Responsibility recomputation

| Responsibility | Current production | Prepared replacement | Toolkit still required now? |
|---|---|---|---|
| Two-level Vietnam address UI/data | Toolkit 1.1.2 | `lyli-vietnam-address` + pinned upstream `v4.0.0` | Yes, until controlled activation/cutover |
| Checkout shipping rules | Toolkit 1.1.2 | Native Woo Shipping Zones / Flat Rate / Free Shipping | Yes, until rules are migrated and totals verified |
| VietQR/BACS presentation | Toolkit may provide it but BACS/VietQR is currently not the runtime cutover target | `lyli-vietqr-bacs` + reusable payload/renderer libraries | No source-level reason to retain Toolkit |
| GHN shipment lifecycle | First-party GHN connector | Already first-party and standalone | No |
| Legacy Toolkit shipment reads | `_vck_*` compatibility reader | Read-only compatibility path | Keep through migration window |

`TOOLKIT SOURCE RESPONSIBILITY ZERO` is **NO** in this commit: the optional Toolkit provider/address adapters and legacy reader remain, and production still owns active shipping/address behavior through Toolkit. The selected replacements make zero responsibility achievable after the runtime cutover; this task intentionally does not remove the package or compatibility code.

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

The physical plugin slug, PHP namespace, text domain, action/nonces, settings option `lyli_ghn_settings`, private non-autoload Token option `lyli_ghn_token`, and GHN `LYLI-WC-{order_id}` client code remain stable deliberately. They were already part of deployed 0.1.1 settings/idempotency behavior; renaming them would create credential loss, broken admin requests, or duplicate-shipment risk without improving the carrier boundary. New shipment storage is neutral because 0.2.1 has not been deployed.

## Runtime cutover gate

1. **Classic Cart + Classic Checkout are the binding V1 contract.** Checkout Blocks are deferred and are not a cutover gate.
2. Migrate checkout shipping rules to WooCommerce Shipping Zones/native methods and verify equivalent totals at 375/768/1440.
3. Back up DB, activate `lyli-vietnam-address`, and verify existing customer/order codes plus new checkout/account save and display flows.
4. Configure and validate `lyli-vietqr-bacs` with owner merchant data only; ensure Toolkit VietQR is off so two QR renderers never run together.
5. Deploy GHN 0.2.x through immutable release workflow and verify selected address resolution, canonical writes and both legacy read paths.
6. Confirm Toolkit VAT/e-invoice/migrations/tracking surfaces are unused, then deactivate Toolkit in a controlled cutover and smoke Home/Shop/Cart/Checkout/Account/order admin.
7. Remove optional Toolkit provider/address wiring and the Composer package only in a later source task after the runtime gate remains healthy; retain read-only legacy metadata compatibility only as long as needed.

Until every gate passes, Toolkit remains a transitional runtime dependency. This document does not authorize production cutover.

## Controlled cutover attempt — blocked before deployment (2026-08-11)

The production inventory found that Toolkit is not merely carrying stale configuration. Three existing orders contain shipping items from method `yoohw_vietnam_store_tools_shipping_rules`, instance `2`. The active `Vietnam` country zone contains one enabled rule with these effective constraints:

- geography: all Vietnamese provinces and wards;
- cart total: `0` through `100,000,000` VND;
- package weight: `0` through `1` kg;
- shipping fee: `0` VND;
- free-shipping threshold: `500,000` VND;
- customer title: `Vận chuyển`;
- COD allowed by the Toolkit rule.

Native WooCommerce Flat Rate and Free Shipping can reproduce a zero fee and a minimum-order free-shipping threshold, but neither native method can make the rate unavailable above both a package-weight ceiling and a cart-total ceiling. Consequently the proposed native-only replacement fails exact equivalence at the exclusion boundaries:

| Representative package | Toolkit result | Native Flat Rate / Free Shipping result | Gate |
|---|---|---|---|
| `100,000` VND, `0.50` kg | `Vận chuyển`, `0` VND | zero-cost rate | PASS |
| `500,000` VND, `1.00` kg | `Vận chuyển`, `0` VND | zero-cost/free-shipping rate | PASS |
| `100,000` VND, `1.01` kg | no matching rate | zero-cost rate remains available | **FAIL** |
| `100,000,001` VND, `0.50` kg | no matching rate | zero-cost rate remains available | **FAIL** |

The task explicitly prohibited silent approximation and a new custom shipping engine. The cutover therefore stopped before Release A, maintenance mode, database mutation, plugin activation/deactivation, or symlink changes. No rollback execution or new production backup was necessary because production state did not change.

Founder resolved this gate by authorizing option 3: the narrowly scoped `lyli-shipping-policy` plugin. It filters already-calculated Woo rates and removes only a native `flat_rate` labelled `Vận chuyển` when the legacy ceiling is exceeded. It does not register a shipping method, calculate a fee, own a zone, or affect Local Pickup/GHN/unrelated rates.

Source inspection of Toolkit 1.1.2 establishes the exact compatibility contract:

- amount is `package['contents_cost']`: Woo cart line totals after discounts, excluding tax and shipping;
- weight is the sum of each shipping product's stored weight multiplied by quantity, in the configured Woo store weight unit; missing weight contributes zero;
- `1.000` kg and `100,000,000` VND are inclusive; only values above either ceiling hide the rate;
- when no rule matches and default fee is blank, Toolkit adds no rate;
- the `500,000` VND threshold sets the matched rule cost to zero, but the configured fee is already zero below the threshold. A second native Free Shipping rate would therefore change the visible method set without changing cost and is not equivalent.

Production uses `kg`, so the guard's centralized `MAX_WEIGHT = 1.0` exactly matches the active Toolkit rule. `MAX_AMOUNT = 100000000.0` uses the same `contents_cost` basis. Native Woo owns the zone and zero-cost Flat Rate; the guard owns only these two missing maximum-eligibility predicates.

Additional runtime truth captured without merchant data disclosure: BACS is currently enabled and has one configured account; Toolkit VietQR remains disabled. Production remains on release `20260811011830`, GHN connector 0.1.1 disabled/Test, and Toolkit 1.1.2 active.
