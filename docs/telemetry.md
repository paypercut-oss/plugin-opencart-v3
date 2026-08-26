# Debug sessions (client telemetry)

A merchant-started, time-boxed diagnostic feed. Off until someone presses
**Start debug session** in Extensions → Payments → Paypercut → **Debug Session**;
ends by itself after about an hour.

Nothing is sent when no session is running. `EventRecorder::record()` reads one
setting OpenCart has already loaded for the request and returns, so call sites
on the checkout path report unconditionally.

## Shape

```
Start  →  POST {api}/v1/telemetry/tokens   (the store's API key, once)
          →  short-lived RS256 token
Events →  POST {edge}/v1/telemetry         (the token; never the API key)
```

The edge verifies the token offline and never calls back into the platform, so
telemetry cannot block a payment.

Both hosts are resolved from the single **Environment** setting
(`payment_paypercut_environment`) in the same call sequence. A token minted for
one environment is refused by every other environment's edge with a 401 that is
indistinguishable from a forged token, so an unknown or unset environment yields
**no session** rather than a confusing one. The API base falls back to
production for an unknown value; the edge base does not.

| Environment | API base | Telemetry edge |
|---|---|---|
| `production` | `https://api.paypercut.io/` | `https://telemetry.paypercut.io/` |
| `stage` | `https://api.stage.paypercut.net/` | `https://telemetry.stage.paypercut.net/` |
| `dev` | `https://api.dev.paypercut.net/` | `https://telemetry.dev.paypercut.net/` |

Both bases are accepted only on an https `paypercut.net` / `paypercut.io` host
(`Environment::allowedPaypercutBase()`), because the store's API key travels on
the mint request.

| Piece | Role |
|---|---|
| `Event` | Named constructors, the deny assertion, the wire envelope |
| `EventRecorder` | Buffers events; one queue write per request, at shutdown |
| `EventQueue` | Capped store; splits batches to the edge's bounds |
| `Store` | The `oc_paypercut_telemetry` table plus the session record in `oc_setting` |
| `Flusher` | Delivers from authenticated admin requests only; handles 413 by splitting |
| `TokenMinter` | Exchanges the API key for a token, on the mint host |
| `EdgeClient` | POSTs a batch; reads `accepted`/`dropped` off a 202 |
| `SentLog` | The delivered envelopes, for the merchant to read back |
| `FatalErrorWatch` | Shutdown handler for fatals that reach no catch block |

Storage keys, all rows of `oc_paypercut_telemetry` except the record:
`paypercut_telemetry_token`, `paypercut_telemetry_queue`,
`paypercut_telemetry_inflight`, `paypercut_telemetry_runtime`,
`paypercut_telemetry_start_lock`, `paypercut_telemetry_flush_lock`,
`paypercut_telemetry_sent_log`. The session record lives in `oc_setting` under
the code `paypercut_telemetry`, which the payment settings form never writes —
so a settings save cannot lose it, and the storefront reads it for free.

## Events

| Event | When |
|---|---|
| `session.started` / `session.stopped` | Lifecycle |
| `environment.snapshot` | Module, OpenCart, PHP and theme versions; multi-store and TLS flags |
| `environment.configuration` | How the module is configured; re-sent when settings are saved mid-session |
| `environment.plugins` | Installed extensions, chunked 14 per event |
| `checkout.hosted.redirected` | Shopper sent to the hosted page |
| `checkout.hosted.create_failed` | Hosted session could not be created |
| `checkout.hosted.redirect_missing` | 200 from the API with no redirect URL |
| `checkout.embedded.session_created` | Embedded session created |
| `checkout.embedded.create_failed` | Embedded session could not be created (`no_session_id`, `session_create`) |
| `checkout.embedded.order_created` | Embedded checkout confirmed and the order moved |
| `checkout.session_create_failed` | The create call threw; only the exception's type travels |
| `checkout.session_unverifiable` | A checkout or payment lookup failed |
| `checkout.return.pending` | Shopper returned with the payment still pending |
| `checkout.return.unverifiable` | Return could not be verified (`no_payment_status`, `lookup_failed`) |
| `checkout.order_missing` | The session's order id no longer resolves |
| `payment.succeeded` | Payment confirmed on the return path |
| `payment.failed` | Session expired, or the platform reported a decline |
| `payment.closed_unpaid` | Session closed without payment — deliberately *not* a decline |
| `payment.captured` / `payment.capture_failed` | Admin capture of an authorised payment (OpenCart-specific) |
| `payment.canceled` / `payment.cancel_failed` | Admin void of an uncaptured payment (OpenCart-specific) |
| `order.marked_paid` / `order.marked_failed` | Order history written |
| `order.status_unhandled` | A payment status this module has no mapping for |
| `webhook.received` | Delivery accepted, with `duplicate` |
| `webhook.rejected` | `empty_body`, `missing_signature`, `invalid_signature` |
| `webhook.payload_invalid` | Body present but not parsable |
| `webhook.order_updated` | Order moved from a webhook |
| `webhook.unresolved` | Delivery could not be matched to an order |
| `webhook.skipped` | Unhandled type, already processed, or signature checking off |
| `webhook.registered` / `webhook.registration_failed` | Webhook management |
| `webhook.deleted` / `webhook.delete_failed` | Webhook management |
| `refund.succeeded` | Refund accepted (`is_partial`, `has_reason`, `has_refund_id`) |
| `refund.rejected` | Refused before any API call |
| `refund.failed` | The API refused it, or the request never completed |
| `connection.validated` | Settings saved |
| `connection.validation_failed` | Test Connection threw; only the exception's type travels |
| `connection.tested` | Test Connection result, with `ok` both ways |
| `connection.webhook_registered` / `connection.webhook_registration_failed` | From the settings screen |
| `connection.payment_domain_registered` / `connection.payment_domain_registration_failed` | From the settings screen |
| `payment_domain.registered` / `payment_domain.registration_failed` | Wallet domain registration |
| `settings.webhooks_unreadable` | The webhook list could not be read |
| `settings.payment_domains_unreadable` | The payment-domain list could not be read |
| `api.request_failed` | An API call answered 4xx/5xx, or never completed (`transport`) |
| `api.request_slow` | An API call took 3s or more |
| `api.response_unparsable` | A 200 whose body is not JSON; only its byte count travels |
| `php.fatal` | A fatal that ended the request, from the shutdown handler |

Every failure carries `origin` (`paypercut` / `plugin` / `theme` / `core`) and,
for an extension, `origin_plugin` — taken from the first stack frame outside our
own files. That is the answer to "which extension broke us". The wire values are
the same words on every Paypercut plugin so support can compare stores, even
though OpenCart calls them extensions.

### Structural blind spots

- A store that has never saved an API key and an environment cannot start a
  session, so first-time setup failures are invisible by construction.
- A card refused inside the embedded iframe is not visible here; closing that
  needs browser-side telemetry.
- `payment.closed_unpaid` is not a decline. A checkout that is still open, or
  complete but unpaid, is reported under its own name — reporting it as a
  failure would put a false decline in front of a merchant whose payment worked.
- OpenCart records no version for most extensions, so `environment.plugins`
  carries a version only where an OCMod modification of the same code has one.
- The module has no reference-sync path, so there is no
  `payment.reference_sync_failed` here.

## What is shared

The two places below must stay word-identical — the consent panel (the
`text_telemetry_*` strings in
`upload/admin/language/en-gb/extension/payment/paypercut_telemetry.php`) and this
section. `tests/run.php` fails the build if they drift.

Module, OpenCart, PHP and theme versions; the extensions installed on this store
and their versions; how this store has the Paypercut module configured (which
checkout mode is selected and which options are switched on — never the values
of your credentials); a record of each checkout, refund and payment notification
the module handled and whether it succeeded, identified by OpenCart order id and
Paypercut payment reference; when something fails, the error message, the file
and line it came from, and which extension or theme raised it; and when the
session started and stopped.

**Not shared:** customer names, email addresses, billing or shipping addresses,
order totals, line items, payment card data, the reason text you type when
issuing a refund, or any API key, webhook secret or password.

Your API key is never sent to the telemetry service. It is used once, over
HTTPS, to obtain a short-lived diagnostic token from api.paypercut.io.

Paypercut keeps this diagnostic data for 30 days.

## What never goes on the wire

Card data (screened with a Luhn check on every value), credentials, refund
reason text, customer names and addresses, order totals and line items, absolute
filesystem paths, and the upstream API's own prose — the platform quotes
submitted input back, so a rejected key arrives inside it and `api_code` /
`trace_id` carry the diagnosis instead.

The deny assertion in `EventQueue::append()` is the last gate. It drops the
**whole event**, not the offending field: an event that trips it was assembled
wrongly, so the rest of it cannot be trusted either. It screens `attrs` and
`error` (including `error.stack`, two levels down) against denied key names,
denied value shapes, a Luhn PAN check and the store's actual credentials.

The admin user who started a session is kept in the local record for the banner
but is deliberately absent from `session.started`: a store-user identifier is not
covered by the disclosure above.

## Delivery

Events are delivered only from authenticated admin requests: the panel's status
poll (every ~60s while the screen is open), the Stop button, and the settings
page render as a backstop. A storefront request does exactly two things — read
the "is a session live" setting, and, if it is, make one buffered queue write at
shutdown.

Budgets: 200 events / 64 KiB queued, 50 events / 16 KiB per batch, 16 attributes
and 256 **bytes** per string per event, 8 stack frames. A 413 halves the batch
and never counts as a failure; a 401 ends the session and never re-mints, because
nothing can revoke a token and a fresh one would outlive the merchant's consent.

## Running the tests

```bash
php tests/run.php
```

No dependencies: it exercises the deny assertion, the environment pairing, the
flusher's decision table, the queue bounds and the disclosure wording.
