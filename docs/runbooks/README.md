# Runbooks

Operational procedures for the PayPerCut OpenCart v3 payment module. Each runbook
is a short, skimmable, command-first document for a single scenario.

> These docs are for maintainers and on-call. They are **not** shipped to
> merchants — the release workflow excludes `docs/` from the `.ocmod.zip`.

## Index

| Runbook | Trigger | Severity | Last verified |
|---|---|---|---|
| [release-new-version.md](release-new-version.md) | Cutting a new plugin version | routine | 2026-04-22 |
| [install-upgrade-module.md](install-upgrade-module.md) | Merchant installs or upgrades | routine | 2026-04-22 |
| [webhook-not-received.md](webhook-not-received.md) | Orders not completing after payment | SEV-2 | 2026-04-22 |
| [rollback-release.md](rollback-release.md) | Regression in a published release | SEV-2 | _pending_ |
| [payment-stuck-pending.md](payment-stuck-pending.md) | Order stays in pending state | SEV-3 | _pending_ |
| [rotate-api-credentials.md](rotate-api-credentials.md) | Credential rotation / leak | SEV-2 | _pending_ |
| [incident-response.md](incident-response.md) | Any SEV-1/SEV-2 declared | SEV-1 | _pending_ |

## Conventions

- **One scenario per file.** If a procedure branches into two distinct workflows,
  split it.
- **Commands over prose.** Prefer exact shell commands, UI paths, and expected
  outputs over explanations.
- **Every runbook has a Verification and a Rollback section.** If rollback is
  not possible, say so explicitly.
- **Date-stamp verification.** Update `Last verified` whenever the procedure is
  executed or reviewed. Runbooks older than 90 days should be re-verified.

## Adding a runbook

1. Copy [`_template.md`](_template.md) to a new file with a kebab-case name
   describing the scenario (`fix-<thing>.md`, `rotate-<thing>.md`, etc.).
2. Fill in every section. Remove sections that genuinely do not apply and
   replace with `_Not applicable_`.
3. Add a row to the index table above.
4. Open a PR labeled `runbook`. Request review from a maintainer who has
   executed the procedure at least once.

## Severity guide

| Level | Meaning | Response |
|---|---|---|
| SEV-1 | Payments broken for all merchants | Page immediately, start incident channel |
| SEV-2 | Payments broken for a subset, or admin tooling broken | Respond within business hours same day |
| SEV-3 | Degraded experience, workaround exists | Next business day |
| routine | Planned/operational work | Scheduled |
