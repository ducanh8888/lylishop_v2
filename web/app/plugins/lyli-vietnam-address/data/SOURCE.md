# Upstream data provenance

- Project: `thanglequoc/vietnamese-provinces-database`
- Release: `v4.0.0` (2026-06-21)
- File: `json/vn_only_simplified_json_generated_data_vn_units_minified.json`
- Source URL: <https://raw.githubusercontent.com/thanglequoc/vietnamese-provinces-database/v4.0.0/json/vn_only_simplified_json_generated_data_vn_units_minified.json>
- SHA-256: `f36c1b4fd6f0c61065936c365395d66cc4a1d12b4e0f313819f2930fd27293e2`
- Expected shape: 34 province-level units and 3,321 ward-level units; no district level.
- License: MIT; see `LICENSE.upstream`.

Run `php scripts/update-vietnam-address-data.php` to fetch, verify and atomically replace the vendored data. The updater never follows a `latest` URL.
