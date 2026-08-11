# Vietnam Store Toolkit decoupling

Updated: 2026-08-11

## Current truth

- **CURRENT RUNTIME:** Release B `20260811151035`, source `2b8d14e470fa3a74be3933c7e4ae822866591b2e`. Vietnam Store Toolkit is inactive and absent from Composer/artifact.
- **GHN:** `lyli-ghn-connector` 0.2.1 is active code but disabled/Test after a complete standalone GHN Test lifecycle. New writes use only canonical `_openship_ghn_*`; legacy Toolkit `_vck_shipping_*` support is read-only.
- **ADDRESS:** `lyli-vietnam-address` is active and owns Classic Checkout/Account address behavior. The pinned MIT `thanglequoc` v4.0.0 dataset has 34 provinces and 3,321 wards; checksum and existing-address compatibility pass.
- **SHIPPING:** Woo zone/methods own the rate. Flat Rate instance `3` (`Vận chuyển`, cost `0`) is active; `lyli-shipping-policy` only preserves the former inclusive `100,000,000` VND / `1 kg` eligibility ceilings.
- **PAYMENT:** Native BACS remains enabled with one owner-configured account. `lyli-vietqr-bacs` code is active but its integration is disabled; it adds no gateway, SaaS, webhook or paid-status mutation.
- **ROLLBACK:** corrected Release A `20260811145842` and backup `shared/backups/20260811144446` are retained.
- **DECISION EVIDENCE:** see [`REUSE-DEPENDENCY-DECISIONS.md`](REUSE-DEPENDENCY-DECISIONS.md).

## Source boundaries

```text
Woo order panel ─┐
                 ├─ Shipment_Application ─ GHN Client
Woo standalone admin ───────┘
                            ├─ Order Mapper / canonical Address
                            ├─ Settings Repository
                            └─ Shipment Repository
```

`Shipment_Application` is the only implementation of the lifecycle. Admin surfaces own capability/nonce checks and presentation; application also performs defense-in-depth order/capability validation. API endpoint selection, transport parsing, print allowlists and redaction remain in the GHN client. COD and package validation are domain policies.

Toolkit provider/address hooks and active integration files have been removed. The source composition root uses `includes/integrations/vietnam-address/` and falls back only to ordinary Woo address values. The isolated Toolkit legacy shipment reader can read historical `_vck_shipping_*` metadata but cannot write it or call Toolkit code.

Payment is no longer a reason to retain Toolkit. Native Woo BACS remains the gateway/order-status owner; the reusable VietQR adapter is presentation only.

## Responsibility recomputation

| Responsibility | Current production | Prepared replacement | Toolkit still required now? |
|---|---|---|---|
| Two-level Vietnam address UI/data | `lyli-vietnam-address` + pinned upstream `v4.0.0` | Active | No |
| Checkout shipping rules | Native Woo Shipping Zone + Flat Rate + minimal policy guard | Active; equivalence PASS | No |
| VietQR/BACS presentation | Native BACS + disabled `lyli-vietqr-bacs` integration | Owner enables later | No |
| GHN shipment lifecycle | First-party GHN connector 0.2.1 | Active code, disabled/Test | No |
| Legacy Toolkit shipment reads | Isolated `_vck_*` read-only compatibility reader | Historical orders only | No runtime package required |

`TOOLKIT SOURCE RESPONSIBILITY ZERO` is **YES**. No active hook, provider, address resolver or Composer package requires Toolkit; the read-only historical metadata reader is data compatibility, not a runtime dependency.

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

The physical plugin slug, PHP namespace, text domain, action/nonces, settings option `lyli_ghn_settings`, private non-autoload Token option `lyli_ghn_token`, and GHN `LYLI-WC-{order_id}` client code remain stable deliberately. They preserve credentials, admin requests and idempotency across the 0.1.1 → 0.2.1 cutover. New 0.2.1 shipments write only neutral canonical metadata.

## Runtime cutover gate

1. **Classic Cart + Classic Checkout are the binding V1 contract.** Checkout Blocks are deferred and are not a cutover gate.
2. Migrate checkout shipping rules to WooCommerce Shipping Zones/native methods and verify equivalent totals at 375/768/1440.
3. Back up DB, activate `lyli-vietnam-address`, and verify existing customer/order codes plus new checkout/account save and display flows.
4. Configure and validate `lyli-vietqr-bacs` with owner merchant data only; ensure Toolkit VietQR is off so two QR renderers never run together.
5. Deploy GHN 0.2.x through immutable release workflow and verify selected address resolution, canonical writes and both legacy read paths.
6. Confirm Toolkit VAT/e-invoice/migrations/tracking surfaces are unused, then deactivate Toolkit in a controlled cutover and smoke Home/Shop/Cart/Checkout/Account/order admin.
7. Remove optional Toolkit provider/address wiring and the Composer package only in a later source task after the runtime gate remains healthy; retain read-only legacy metadata compatibility only as long as needed.

All listed Classic Checkout runtime gates passed on 2026-08-11. The numbered list above is retained as the executed cutover record, not a pending authorization.

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

Additional pre-cutover truth captured without merchant data disclosure: BACS was enabled with one configured account and Toolkit VietQR was disabled. That snapshot is historical and is superseded by the final result below.

## Final controlled cutover result (2026-08-11)

| Gate | Evidence | Result |
|---|---|---|
| Shipping source equivalence | 23 deterministic assertions against Toolkit amount/weight/boundary semantics | PASS |
| Shipping runtime equivalence | 8 representative packages, including exact and over-limit boundaries | PASS |
| Classic Checkout | Product session HTTP 200; Province required/priority 70 before Ward required/priority 80; address JS loaded | PASS |
| Address compatibility | Dataset 34/3,321/checksum; 6 order and 2 customer addresses resolve; AJAX late-response guard retained | PASS |
| Payment preservation | BACS enabled, one pre-existing account; Lyli VietQR disabled; no merchant fields documented or changed | PASS |
| GHN 0.2.1 standalone | Preview/Create/idempotency/detail/sync/print/cancel/post-cancel sync on Test; canonical writes only; cleanup complete | PASS |
| Toolkit removal | Inactive, Composer package absent, artifact directory absent, active adapters removed | PASS |
| Public/security | Public routes and session checkout healthy; SSL valid; sensitive paths denied; no exposed PHP errors | PASS |

Release sequence:

1. Shipping policy source: `0d03e8e16b97627ee3793547b599272ffc106d15`.
2. Corrected Release A source: `b68a874db59abb76c7d7a4ee23a976fa9cabda8d`; release `20260811145842`; retained rollback.
3. Pre-cutover backup: `shared/backups/20260811144446` (`database.sql.gz` and `uploads.tar.gz`, integrity PASS).
4. Toolkit removal source: `2b8d14e470fa3a74be3933c7e4ae822866591b2e`.
5. Final Release B: `20260811151035`, SHA-256 `96b4514d11e1c97510540047a2a5150327ba67a4251bb8cf6b88b0355d476d11`.

No real `shop_owner` user exists yet. A disposable role test proved `manage_woocommerce` while denying `manage_options`, plugin activation and user administration; it was deleted. Provisioning the named owner account remains an owner/developer handoff action.
