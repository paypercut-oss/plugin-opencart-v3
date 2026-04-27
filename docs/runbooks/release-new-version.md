# Release a new plugin version

**Audience:** release engineer
**Severity:** routine
**Est. time:** ~10 minutes
**Last verified:** 2026-04-22

## When to use

- Shipping a new version of the PayPerCut OpenCart v3 module to merchants.
- Publishing a hotfix after a rollback.

## Preconditions

- Write access to `paypercut-oss/plugin-opencart-v3`.
- `main` is green and contains every change intended for the release.
- A version number chosen per [semver](https://semver.org). The current version
  is tracked in [install.xml](../../install.xml) and is rewritten by the release
  workflow from the tag.

## Procedure

1. Ensure you are on an up-to-date `main`.
   ```bash
   git checkout main
   git pull --ff-only
   ```
2. Confirm the latest commit is what you intend to release.
   ```bash
   git log --oneline -n 5
   ```
3. Create and push an annotated tag. Replace `X.Y.Z` with the new version.
   ```bash
   VERSION=X.Y.Z
   git tag -a "v${VERSION}" -m "Release v${VERSION}"
   git push origin "v${VERSION}"
   ```
4. The [`Release OpenCart Module ZIP`](../../.github/workflows/release-zip.yml)
   workflow runs automatically on `v*` tags. Watch it complete:
   ```bash
   gh run watch --exit-status
   ```

## Verification

- GitHub Actions run `Release OpenCart Module ZIP` is green.
- A new GitHub Release named `v${VERSION}` exists with the `.ocmod.zip` asset
  attached (asset name pattern is defined in the release workflow).
- Download the asset and confirm:
  ```bash
  unzip -l paypercut-opencartv2-${VERSION}.ocmod.zip | head
  # Expect install.xml and upload/ at the zip root, no docs/ or .github/.
  ```
- `install.xml` inside the zip has `<version>${VERSION}</version>`.

## Rollback

If the workflow failed or produced a bad asset:

1. Delete the GitHub Release (keeps the tag).
   ```bash
   gh release delete "v${VERSION}" --yes
   ```
2. Delete the tag locally and remotely.
   ```bash
   git tag -d "v${VERSION}"
   git push origin ":refs/tags/v${VERSION}"
   ```
3. Fix the underlying issue on `main`, then re-run this runbook with the same
   or a bumped version.

If merchants have already downloaded the bad asset, follow
[rollback-release.md](rollback-release.md) instead.

## Troubleshooting

| Symptom | Likely cause | Action |
|---|---|---|
| Workflow fails at `Verify ZIP contents` | `install.xml` or `upload/` missing | Restore files on `main`, re-tag |
| Release created without asset | `softprops/action-gh-release` step failed | Check workflow logs, re-run failed jobs |
| Tag push rejected | Tag already exists | Pick next patch version; never reuse a published tag |
| Asset name still says `opencartv2` | Historical artifact-name bug carried over from v2 | Tracked separately; does not block releases |

## Escalation

- Notify the maintainers channel if the release is blocking a merchant
  incident. Otherwise the release can wait for normal review.

## References

- Workflow: [.github/workflows/release-zip.yml](../../.github/workflows/release-zip.yml)
- Manifest: [install.xml](../../install.xml)
- Related: [rollback-release.md](rollback-release.md),
  [install-upgrade-module.md](install-upgrade-module.md)
