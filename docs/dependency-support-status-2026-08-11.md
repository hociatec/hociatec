Dependency support status as of August 11, 2026

- Backend runtime currently observed locally: PHP `8.3.32`.
- Official PHP support timeline confirms PHP `8.3` remains security-supported until `December 31, 2027`.
- Composer constraint in `backend/composer.json` is `php >=8.2`.
- Locked Symfony full-stack version is Symfony `7.4.16`.
- Official Symfony release timeline lists Symfony `7.4` as the current LTS, with bug fixes until `November 2028` and security fixes until `November 2029`.
- `composer audit --locked` returns no known security advisory on the locked dependency graph on August 11, 2026.

Operational conclusion on August 11, 2026

- The current locked Symfony line is supported.
- The current local PHP runtime is still supported, but it is one patch behind the latest published PHP `8.3.33` security release from `July 30, 2026`.
- A short-term runtime maintenance action remains recommended: update production and CI runtimes from PHP `8.3.32` to the latest supported `8.3.x` patch.
- A medium-term platform decision remains recommended: plan the move to PHP `8.4` before adopting Symfony `8.1+`, because Symfony `8.1` requires PHP `8.4.0` or higher.

Verification commands

```bash
cd backend
composer audit --locked
composer show symfony/framework-bundle --locked
composer show --platform php
composer outdated --direct --locked
```

Direct dependencies with newer major lines available on August 11, 2026

- `doctrine/dbal`: locked `3.10.6`, latest major `4.4.4`
- `phpdocumentor/reflection-docblock`: locked `5.6.7`, latest major `6.0.3`
- `phpunit/phpunit`: locked `11.5.56`, latest major `12.5.33`
- `symfony/monolog-bundle`: locked `3.11.2`, latest major `4.0.2`

These do not block closure of the support/CVE checkpoint because:

- no Composer advisory is currently reported on the locked set;
- the locked Symfony line is maintained;
- the locked PHP compatibility floor remains aligned with Symfony `7.4`;
- the remaining updates are upgrade-planning items, not emergency support breaks as of August 11, 2026.
