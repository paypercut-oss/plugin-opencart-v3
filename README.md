# plugin-opencart-v3
Paypercut OpenCart V3 Payment Module

## Layout

- `upload/` — everything OCMod installs into the store (admin + catalog MVC-L,
  plus `upload/system/library/paypercut/`, the module's own PHP library).
- `install.xml` — the OCMod manifest. The version there and in
  `upload/system/library/paypercut/version.php` must match the release tag; the
  release workflow checks both.
- `docs/` — runbooks and feature docs. Not shipped in the release ZIP.
- `tests/` — dependency-free test runner: `php tests/run.php`.

## Environment

The **Environment** setting (Extensions → Payments → Paypercut → API
Configuration) picks which Paypercut environment the store talks to:
`production` (default), `stage` or `dev`. It resolves both the payment API host
and the telemetry edge host, so the two can never disagree. Existing stores that
have never saved it keep talking to production.

## Debug sessions

A merchant can start a time-boxed diagnostic feed from the **Debug Session** tab.
It is off by default and ends by itself after about an hour. What it sends, what
it never sends, and the full event catalogue: [`docs/telemetry.md`](docs/telemetry.md).

## Maintainers

Operational procedures (release, install/upgrade, incident response) live in
[`docs/runbooks/`](docs/runbooks/README.md). Start there when paged or when
cutting a release.
