# <Runbook title>

**Audience:** <on-call | release engineer | support>
**Severity:** <SEV-1 | SEV-2 | SEV-3 | routine>
**Est. time:** ~N minutes
**Last verified:** YYYY-MM-DD by @handle

## When to use

- Bullet describing the trigger (alert, merchant report, scheduled task).
- Add symptoms that distinguish this runbook from adjacent ones.

## Preconditions

- Access required (GitHub write, OpenCart admin, PayPerCut dashboard, etc.)
- Tools (`git`, `gh`, browser, access to merchant store)
- Relevant links (dashboards, logs, status page)

## Procedure

1. Step with the exact command or UI path.
   ```bash
   # example
   git status
   ```
2. State the expected observation after the step.
3. Next step…

## Verification

- Concrete checks that prove success (HTTP 200, order status = `processing`,
  release asset present on GitHub, etc.).

## Rollback

Explicit reverse steps if the procedure fails mid-way. If rollback is not
possible, state that and describe the mitigation instead.

## Troubleshooting

| Symptom | Likely cause | Action |
|---|---|---|
| … | … | … |

## Escalation

- Who to page / Slack channel / on-call rotation link.
- When to escalate (time bound, failed verification, scope expansion).

## References

- Related runbooks: …
- Code paths: …
- External docs: …
