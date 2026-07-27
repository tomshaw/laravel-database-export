# Description

<!-- What does this change do, and why? Link any related issue with "Closes #123". -->

## Type of Change

<!-- Check all that apply. -->

- [ ] 🐛 Bug fix (non-breaking change that fixes an issue)
- [ ] ✨ New feature (non-breaking change that adds functionality)
- [ ] 💥 Breaking change (fix or feature that changes existing behavior)
- [ ] 📖 Documentation
- [ ] 🧹 Refactor / maintenance

## How Has This Been Tested?

<!--
Which drivers and platforms did you verify against? The export command builds a
different shell command per driver and per OS, so please say which you exercised.
-->

- Drivers: <!-- mysql / pgsql / sqlsrv / sqlite -->
- OS: <!-- Linux / macOS / Windows -->

## Checklist

- [ ] My PR title follows [Conventional Commits](https://www.conventionalcommits.org/) (e.g. `feat:`, `fix:`, `docs:`) — release-please uses it to version the package and build the changelog.
- [ ] I have added or updated tests covering my change.
- [ ] `composer test` passes.
- [ ] `composer analyse` passes (PHPStan level max).
- [ ] `composer format` has been run.
- [ ] I have updated the `README.md` if this changes behavior or configuration.
- [ ] This PR does one thing — separate concerns are in separate PRs.
- [ ] No credentials, dumps, or backup files are included in the diff.
