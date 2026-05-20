# plugin-opencart-v3

Paypercut's **OpenCart 3.x payment module** (PHP). Distributed from the `paypercut-oss` GitHub org. Lets OpenCart 3.x merchants accept card / Google Pay / Apple Pay via Paypercut, using either a **hosted checkout** redirect or an **embedded** inline form. Sibling repos cover OpenCart v2 + v4.

**Audience**: OpenCart 3.x merchants and store integrators wiring Paypercut as a payment gateway.

## Layout

**Single OCMod extension** (no Composer / npm, no build step). Mirrors the OpenCart filesystem under `upload/`; an `install.xml` at the root declares the OCMod manifest. CI/CD is one GitHub Actions workflow that packages a release zip.

## Architecture

- **`install.xml`** — OCMod manifest. Extension name "Paypercut Payments", code `paypercut`; version rewritten by the release workflow.
- **`upload/catalog/...`** — storefront side:
  - `controller/extension/payment/paypercut.php` — `index`, `send` (AJAX), `confirm` (AJAX), `callback`, `webhook`, status pages
  - `model/extension/payment/paypercut.php` — payment-method availability + currency validation
  - `view/theme/default/template/extension/payment/paypercut.twig` — checkout templates (OpenCart 3 uses `.twig`)
  - `language/{locale}/extension/payment/paypercut.php` — storefront translations (13 locales)
- **`upload/admin/...`** — admin side (~800 lines):
  - `controller/extension/payment/paypercut.php` — settings form, `testConnection`, `createWebhook`/`deleteWebhook`, install/uninstall, Apple Pay domain file deployment
  - `view/extension/payment/paypercut.twig` — settings form
  - `language/{locale}/...` — 13 admin locales
- **`upload/system/library/paypercut/applepay/`** — Apple Pay domain-association file, deployed to `/.well-known/` on install + settings save.
- **`docs/runbooks/`** — install, webhook troubleshooting, Apple Pay domain, release procedures.

## Payment flow

- **Hosted (default)**: `createCheckoutSession()` → `POST /v1/checkouts` → redirect to Paypercut. Return via `callback()` (verifies via `GET /v1/checkouts/{id}`).
- **Embedded**: checkout session created in `index()` or `send()`; `PaypercutCheckout` JS SDK mounts inline in `paypercut.twig`; success callback verifies the same way.
- **Webhook fallback** finalizes the order when buyers don't return.

## API integration

- Base: `https://api.paypercut.io/v1` (hardcoded).
- Endpoints used: `POST /checkouts`, `GET /checkouts/{id}`, `GET /payments/{id}`, `POST /customers`, `GET|PATCH /customers/{id}`, `GET /payment-configs`, `GET /payment_method_domains`, `GET|POST|DELETE /webhooks`.
- Auth: `Authorization: Bearer <api_key>` (key stored in `oc_setting` as `payment_paypercut_api_key`). Key prefix `sk_test_` / `sk_live_` auto-detects mode.

## Database (4 custom tables)

Created in the admin `install()` method with `CREATE TABLE IF NOT EXISTS`. Charset `utf8`, prefix is OpenCart's `DB_PREFIX`. **Tables preserved on uninstall** (intentional).

| Table | Purpose |
|---|---|
| `paypercut_customer` | OpenCart ↔ Paypercut customer mapping (unique on both IDs) |
| `paypercut_transaction` | checkout_id, payment_id, amount, currency, status — composite indexes for order_id / customer_id / checkout_id |
| `paypercut_refund` | refund records (structure present; not yet exercised in UI) |
| `paypercut_webhook_log` | event_id-indexed webhook audit / idempotency |

## Webhook

- Endpoint: `/index.php?route=extension/payment/paypercut/webhook` (catalog side).
- Signature: HMAC-SHA256 against `X_PAYPERCUT_SIGNATURE` header using the configured webhook secret.
- Events: `payment_intent.captured`, `checkout_session.completed`.
- Idempotency: event ID stored in `paypercut_webhook_log` — duplicates skipped.
- Admin AJAX (`createWebhook`/`deleteWebhook`) manages the Paypercut-side webhook and stores the returned secret in `oc_setting`.

## Common commands

This module has **no build step and no test suite**.

```bash
# Package a release zip (mirrors .github/workflows/release-zip.yml on a v* tag)
git tag v1.0.2 && git push --tags  # → paypercut-opencartv3-<version>.ocmod.zip

# Install: Admin → Extensions → Installer → upload the .ocmod.zip,
# then Extensions → Modifications → Refresh, then Extensions → Payments → Paypercut → Enable.
```

## Conventions

- **OCMod, not vQmod** — extension files live under `upload/`; the OpenCart loader picks them up.
- **Configuration keys** are namespaced `payment_paypercut_*` (OpenCart 3's extension-config convention) and stored via `Setting` model.
- **Supported currencies**: BGN, DKK, SEK, NOK, GBP, EUR, USD, CHF, CZK, HUF, PLN, RON. Store currencies outside this list auto-disable the payment method (admin shows a warning at save time).
- **Templates are Twig** (`.twig`), not the older `.tpl` from OpenCart 2.x.
- **Customer mapping** is verified on reuse: if the remote Paypercut customer no longer exists, the local mapping is deleted before re-creating.

## Gotchas

- **PCI scope**: card data never touches the OpenCart server. The plugin either redirects to Paypercut Hosted Checkout or embeds a Paypercut-hosted iframe. Never add a native card form.
- **Apple Pay**: the domain-association file is deployed to `/.well-known/` on install + every settings save. Deployment is silent-fail if the webroot isn't writable — verify the file is reachable over HTTPS, and that the domain is registered via `POST /v1/payment_method_domains`, before debugging "Apple Pay button missing" reports.
- **Currency lock-in**: 12 supported currencies; the payment method **disables itself** for anything outside that set.
- **Amount rounding**: `round()` before integer cast (minor units) — don't introduce float math upstream.
- **Order status mapping**: payment success uses `payment_paypercut_order_status_id` (default 2 = Processing). Webhook + redirect callbacks both finalize, both check idempotency.
- **Uninstall preserves data**: the four `paypercut_*` tables are NOT dropped (intentional, for transaction history).
- **Event hook into order detail page** uses OpenCart 3's event system — confirm the merchant's OpenCart version supports `admin/view/sale/order_info/before` (standard from 3.0).
- **Payment handoff with paycore**: checkout, status, refunds all flow through `api.paypercut.io`. Non-2xx responses are user-visible — log them.
