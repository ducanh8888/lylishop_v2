# Reuse dependency decisions

Decision date: 2026-08-11
Scope: source integration only; production remains unchanged.

## Vietnam address

### Candidates and evidence

| Candidate | Evidence | Assessment |
|---|---|---|
| [`thanglequoc/vietnamese-provinces-database`](https://github.com/thanglequoc/vietnamese-provinces-database) | Release [`v4.0.0`](https://github.com/thanglequoc/vietnamese-provinces-database/releases/tag/v4.0.0), 2026-06-21; 34 province-level and 3,321 ward-level units; districts removed from the current model; MIT license | **Selected.** Current, versioned, offline, deterministic and redistribution-compatible. Official administrative codes are stable across the current Toolkit storage contract. |
| [`vietnam-address-database`](https://www.npmjs.com/package/vietnam-address-database) | npm `1.0.0`, MIT; includes 34/3,321 current units and historical mappings based on the 2025 reorganization | Useful historical mapping lead, but its administrative snapshot predates selected `v4.0.0`; not selected as canonical current data. |
| [`vietnam-provinces`](https://pypi.org/project/vietnam-provinces/2026.3.0/) | Python `2026.3.0`, GPL-3.0-or-later, legacy/current conversion data | Toolkit's legacy alias data cites it, but it predates the selected June 2026 release and introduces a Python/GPL distribution boundary unsuitable for the PHP runtime. |
| [Province Open API v2](https://provinces.open-api.vn/api/v2/redoc) | Two-level API and mapping endpoints | Not selected because checkout must not acquire a runtime SaaS/network dependency when a pinned static dataset is practical. |
| [Vietnam Store Toolkit](https://wordpress.org/plugins/yoohw-vietnam-store-tools/) | `1.1.2`; current two-level fields and codes; already deployed | Transitional runtime source only. Its broad commerce feature set is not the desired long-term address dependency. |
| [Woo Vietnam Checkout](https://wordpress.org/plugins/woo-vietnam-checkout/) | `2.1.6`; broad checkout plugin; published compatibility and data notes lag the selected June 2026 dataset | Not selected: too broad to adopt solely for address data, and its address/UI lifecycle would overlap the transitional Toolkit. |

### Selected integration

`lyli-vietnam-address` is a thin, repo-controlled WooCommerce adapter over one unmodified upstream data asset:

```text
thanglequoc v4.0.0 JSON
        ↓ pinned URL + SHA-256 + count/code validation
Lyli Repository → canonical Address DTO
        ├─ Woo state/city selects + validation/formatting
        └─ optional GHN Address_Resolver adapter
```

- Upstream asset: `json/vn_only_simplified_json_generated_data_vn_units_minified.json`
- SHA-256: `f36c1b4fd6f0c61065936c365395d66cc4a1d12b4e0f313819f2930fd27293e2`
- License: MIT, copyright Thang Le Quoc; notice is retained beside the data.
- Update mechanism: `php scripts/update-vietnam-address-data.php`; it uses an exact tag URL, rejects redirects/checksum/count/code/relation changes, and atomically replaces the asset.
- Runtime behavior: no remote administrative API. The public ward lookup reads local data, requires a nonce, accepts only a province code and returns no private data.
- Storage contract: Woo `state = province_code` and `city = ward_code`, matching current Toolkit-coded customers/orders. Names resolve through the canonical DTO; no district is stored or fabricated.
- Historical compatibility: already-current Toolkit two-level codes need no migration. Pre-current/ambiguous addresses are not silently guessed; an upstream, reviewed migration dataset or manual correction is required during a later runtime cutover.
- Checkout support in this source increment: Classic Checkout and account billing/shipping forms. Checkout Blocks support is a cutover prerequisite and must use the supported Woo Blocks field/data APIs rather than a second address store.

The address dataset is reused, not handwritten. The adapter owns only Woo field presentation, code/name resolution and conversion to the existing GHN DTO.

## VietQR

### Candidates and evidence

| Candidate | Evidence | Assessment |
|---|---|---|
| [`liopay/vietqr`](https://github.com/liopayvn/vietqr-php) | Packagist `1.0.0`, commit `39cc860380f56652f4d931ce93274610156d73eb`, MIT, PHP >=7.4, zero runtime dependencies, builder/parser/CRC tests | **Selected payload library.** Local deterministic NAPAS/EMVCo payload construction; no network, telemetry, database or credentials. |
| [`chillerlan/php-qrcode`](https://github.com/chillerlan/php-qrcode) | `6.0.1`, commit `49006e34bd5328f163e80329e7312f34dceea59b`, PHP ^8.2, MIT/Apache-2.0 | **Selected renderer.** Generates a local base64 SVG data URI without GD, files or a QR SaaS. Its only locked runtime dependency is `chillerlan/php-settings-container` `3.3.0` (MIT). |
| [VietQR WordPress plugin](https://wordpress.org/plugins/vietqr/) | Older standalone plugin; published compatibility does not cover the current Lyli stack | Rejected as a whole plugin due to stale compatibility evidence. |
| [BigVNN VietQR gateway](https://wordpress.org/plugins/bigvnn-vietqr-payment-gateway/) | `1.0.2`; custom gateway with optional automated confirmation product path | Rejected: native BACS already models the required manual transfer, and a second gateway/automation surface is unnecessary. |
| [SePay](https://wordpress.org/plugins/sepay-gateway/) | Account-linked service designed for automated reconciliation | Deferred/optional only if automatic reconciliation is later required; it adds SaaS/webhook behavior outside the current decision. |
| [payOS](https://wordpress.org/plugins/payos/) | Hosted payment product/gateway | Rejected for this manual BACS use case because it adds an external payment-service dependency. |
| Vietnam Store Toolkit `1.1.2` | Adds VietQR presentation to BACS | Transitional only; payment must not be a reason to retain the broader Toolkit. |

### Selected integration

`lyli-vietqr-bacs` is a thin presentation adapter:

```text
native WooCommerce BACS order
  + owner-managed bank BIN/account settings
  + remaining VND order amount
  + deterministic LYLI{order-number} reference
        ↓
liopay/vietqr 1.0.0 payload
        ↓
chillerlan/php-qrcode 6.0.1 local SVG data URI
        ↓
Thank You / Order Pay / My Account order details
```

- It is not a payment gateway and does not enable BACS.
- It makes no bank/API/SaaS request, registers no webhook and cannot mark an order paid.
- It displays only for native `bacs` orders after the owner explicitly enables and configures the integration.
- Amount is the Woo order total less recorded refunds, rounded to whole VND; zero/non-positive values are rejected.
- Merchant values remain Woo settings and are absent from Git, fixtures and docs.
- Dynamic values are escaped; the SVG is generated as base64 by the pinned renderer rather than rendering remote HTML.
- No custom VietQR TLV or CRC implementation exists in owned code.

Composer audit reported no disclosed advisories for the selected locked dependency set on 2026-08-11. The payload library has low adoption, so exact pinning, source audit and deterministic parser tests are deliberate mitigations.

## Security and dependency boundary

- Selected address input is inert JSON with a retained MIT notice and build-time checksum validation.
- The address frontend performs no external request; the local Woo AJAX read is nonce-protected and returns only public administrative names/codes.
- Selected VietQR libraries contain no HTTP client, webhook, direct SQL or telemetry path used by the adapter.
- The adapter exposes no new payment mutation endpoint. WooCommerce owns settings capability and nonce enforcement.
- No production secret, bank value, customer address or order is included in source/tests.
- No Toolkit, WooCommerce, theme or vendor source is modified.

## Runtime status and cutover gates

This commit prepares source only. Neither new plugin is active in production.

Before runtime cutover:

1. add supported Checkout Blocks integration or explicitly keep Classic Checkout as the verified production contract;
2. create a DB backup and record the rollback release;
3. migrate Toolkit shipping rules to native Woo Shipping Zones, Flat Rate and Free Shipping without changing totals;
4. activate and smoke `lyli-vietnam-address`, verify existing customer/order code display and new checkout/account save flows;
5. configure merchant bank values through Woo admin, keep native BACS disabled until owner approval, then validate QR with bank-app test data;
6. deactivate Toolkit only after address, shipping, checkout, account, GHN order mapping and BACS presentation all pass;
7. remove the Toolkit Composer dependency in a later source commit only after the controlled runtime cutover remains healthy.
