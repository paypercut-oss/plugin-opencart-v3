# Webhook not received / orders not completing after payment

**Audience:** on-call, support
**Severity:** SEV-2
**Est. time:** ~20 minutes
**Last verified:** 2026-04-22

## When to use

- A merchant reports that customers pay successfully on PayPerCut but the
  OpenCart order stays in the pre-payment status (e.g. `Pending`).
- PayPerCut dashboard shows a completed `checkout_session.completed` event but
  the corresponding OpenCart order is not updated.

## Preconditions

- Access to the merchant's OpenCart admin (`extension/payment/paypercut_logs`
  view — the **View Logs** button on the Paypercut extension edit page).
- Access to the merchant's PayPerCut dashboard (Webhooks + Events).
- Ability to curl the store's webhook endpoint from an external host.

## Procedure

1. **Reproduce and capture the order id.** Ask the merchant for the affected
   OpenCart order number and the PayPerCut session/payment id.
2. **Check PayPerCut side.** In the PayPerCut dashboard → Webhooks, confirm:
   - An endpoint is registered pointing at the merchant's store
     (`https://<merchant-domain>/index.php?route=extension/payment/paypercut/webhook`).
   - The subscribed events include `checkout_session.completed` (this is the
     only event the plugin consumes — see branch `fix/webhook-enabled-events`).
   - Recent delivery attempts to that endpoint. Note the HTTP status returned
     by the store.
3. **Check store side.** In OpenCart admin, open the Paypercut **Logs** view
   (**Webhook Logs** tab) and search for the session id. Determine which of
   these you have:
   - No entry at all → the webhook never reached the store.
   - Entry with error → the store received it but failed to process.
   - Entry with success → the webhook was processed; the order-status mapping
     or cache is the suspect.
4. **If the webhook never reached the store**, test the endpoint from outside:
   ```bash
   curl -i -X POST \
     "https://<merchant-domain>/index.php?route=extension/payment/paypercut/webhook" \
     -H "Content-Type: application/json" \
     -d '{"type":"ping"}'
   ```
   Expect a non-5xx response. 404 means URL rewriting or route mismatch; 5xx
   means a server-side error — check the store's PHP error log and the
   `paypercut_error.log` file under `system/storage/logs/`.
5. **If the webhook arrived but failed**, open the log entry and capture:
   - Exception / error message
   - Request body
   - OpenCart order id referenced in the payload

   Cross-check the `webhook()` action in
   [upload/catalog/controller/extension/payment/paypercut.php](../../upload/catalog/controller/extension/payment/paypercut.php)
   and the model in
   [upload/catalog/model/extension/payment/paypercut.php](../../upload/catalog/model/extension/payment/paypercut.php).
6. **Manually reconcile the order** so the merchant is unblocked while the root
   cause is fixed. In the PayPerCut dashboard, find the event and click
   **Resend**. Verify the order updates. If resend is unavailable, update the
   OpenCart order status manually to the configured "Completed" status and
   attach a note referencing the PayPerCut session id.

## Verification

- OpenCart order moves to the merchant's configured success status (commonly
  `Processing` or `Complete`).
- A new success entry appears in the Paypercut **Webhook Logs** for the
  session.
- PayPerCut dashboard shows the delivery as HTTP 200.

## Rollback

_Not applicable_ — this runbook is recovery, not a change. If you manually
edited an order status in step 6 and the automated delivery later succeeds,
confirm the final status is the intended one.

## Troubleshooting

| Symptom | Likely cause | Action |
|---|---|---|
| All deliveries return 404 | Webhook URL typo, or SEO URLs hiding `index.php` route | Reconfigure URL in PayPerCut dashboard |
| Deliveries return 401/403 | Signing secret mismatch after key rotation | Run [rotate-api-credentials.md](rotate-api-credentials.md) end-to-end |
| Deliveries return 500 | PHP error in handler | Capture stack trace from `paypercut_error.log` / PHP error log; file an issue with reproduction |
| Logs show signature verification failure | Clock skew or wrong secret | Check server time (`date -u`) and re-copy the webhook secret |
| Event type not `checkout_session.completed` | Extra events subscribed on dashboard | Narrow subscription to `checkout_session.completed` only |

## Escalation

- If manual resend does not reconcile the order after two attempts, escalate
  to a plugin maintainer with: merchant domain, order id, session id, log
  entry, and PayPerCut event id.
- If multiple merchants are affected simultaneously, declare SEV-1 and follow
  [incident-response.md](incident-response.md).

## References

- Catalog controller (webhook entry point):
  [upload/catalog/controller/extension/payment/paypercut.php](../../upload/catalog/controller/extension/payment/paypercut.php)
- Admin logs controller:
  [upload/admin/controller/extension/payment/paypercut_logs.php](../../upload/admin/controller/extension/payment/paypercut_logs.php)
- Admin logs view:
  [upload/admin/view/template/extension/payment/paypercut_logs.twig](../../upload/admin/view/template/extension/payment/paypercut_logs.twig)
- Related: [payment-stuck-pending.md](payment-stuck-pending.md),
  [rotate-api-credentials.md](rotate-api-credentials.md)
