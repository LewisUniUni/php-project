# Engineering playbook adoption

This repository does not fully comply with the 18-page playbook. The playbook is a slide deck. Some boxes are recommendations, some are examples, and two numeric gates are blank. This file maps every slide item onto the Quay codebase as of 10 November 2026.

Statuses:

| Status | Meaning |
| --- | --- |
| Existing | Already true before this change |
| Implemented | Added here and checked with a command |
| Deferred | Not done, with a reason |
| Conflict | The slide and this repository disagree. Nothing was chosen silently |

## What was verified

Host PHP for coverage and Infection was Ubuntu's PHP 8.5.12 with PCOV 1.0.12, redis 6.3.0, and mongodb 2.5.5. The Herd lite binary cannot load shared extensions, so it cannot collect coverage. CI uses `shivammathur/setup-php` with `coverage: pcov`.

| Command | Result |
| --- | --- |
| `php artisan test` | Passed. 76 tests, 707 assertions. MySQL 8.4.13, Redis 8.10.3 cache and queue, MongoDB 8.0.34 |
| `composer ci:static` | Passed. Parallel-lint checked 181 files. Pint, PHPStan max, PHPMD, and composer-normalize reported no violations. `composer audit --locked --abandoned=report` found no advisories and still reports abandoned `symplify/rule-doc-generator-contracts` |
| `php vendor/bin/phpunit --filter=GuardrailTest` | Passed. 11 tests, 37 assertions |
| `php tools/Guardrails/suppressions.php origin/main` | Passed. Exit 0 |
| `php tools/Guardrails/test-changes.php origin/main` | Exit 0. Printed suspected assertion-line removals in `RuntimeTest` and `RedactSecretsProcessorTest`. No production path is approved, so the command does not fail |
| `php tools/Guardrails/packages.php origin/main` | Exit 0. Eleven new direct packages returned HTTP 200 from Packagist. The output says a found package is not a trust decision |
| `php tools/Guardrails/pr-size.php origin/main` | Exit 0. At `0c533ef`, 16370 changed lines (14452 insertions, 1918 deletions, 192 files). No threshold. The count includes the generated Boost files and lockfile growth already on this branch |
| `php tools/Guardrails/changed-files.php pint origin/main` | Passed |
| `php tools/Guardrails/changed-files.php phpstan origin/main` | Passed after the changed PHP files were made valid under the project rules. The command prints that this is not the full gate |
| `semgrep --config .semgrep/ai-smells.yml --error app` | 0 findings on 96 tracked files. The same rules find 4 findings in the fail fixtures and 0 in the pass fixture |
| `composer update --prefer-lowest --prefer-stable` | Not re-run for this change. It rewrites `composer.lock`. The CI job still runs it |
| Unit coverage | 180 / 1403 statements (12.83%). 96 files in the Clover report. 0 production files missing. 77 files had statements and no covered statements |
| Combined coverage | 1070 / 1403 statements (76.27%). Same 1403-statement denominator. 14 files still had no covered statements |
| Diff coverage vs `origin/main` | 246 / 279 changed executable statements (88.17%). No threshold |
| Infection 0.35.10 | 70 mutants, 70 killed. MSI 100%. Covered-code MSI 100%. Scope listed below. No minimum was configured |
| gitleaks 8.30.1 | No leaks in 9 commits |
| SQLFluff 4.5.0 | `docker` passed |
| hadolint 2.15.1 | `Dockerfile` passed |
| actionlint 1.7.12 | `.github/workflows/ci.yml` passed |
| Semgrep 1.185.0 | `.semgrep/sql-policy.yml` passed |
| Rector 2.8.4 dry-run | Not re-run. The earlier dry-run wanted edits in 28 files, including an unsafe `AssertSeeToAssertSeeHtml` change. It is not a required CI check |
| Snyk | Not re-run locally. The workflow still fails closed without `SNYK_TOKEN`, and Open Source, Code, Container, and IaC still use `--severity-threshold=high` |

The previous quality job on `main` was 17 tests and 104 assertions. The added assertions are mostly Eris samples on the delivery state machine, plus the new policy tests. Feature behaviour was re-run against the real stores.

Snyk was not re-run here. The workflow still fails closed without `SNYK_TOKEN`, and Open Source, Code, Container, and IaC still use `--severity-threshold=high`. Container and IaC findings are not filtered.

## Coverage denominator

`phpunit.xml` includes `app/` and sets `includeUncoveredFiles="true"`. `tools/Coverage/summarize.php` counts every Clover `<line type="stmt">`, including files whose covered-statement count is zero, and exits 1 if any `app/**/*.php` file is absent from the report. The unit and combined reports both contain 96 files and the same 1403 statements, with 0 production files missing, so the 12.83% unit figure is not a covered-files-only rate.

`routes/`, `config/`, `database/`, and `bootstrap/` are outside that denominator. They are part of the running application and are included in PHPStan.

## Infection scope

The mutation run covered only:

- `app/Modules/Delivery/Enums`
- `app/Support/Logging`
- `app/Support/Values`

The tests were `StopStatus`, `RedactSecrets`, `ConfiguredInt`, `ConfiguredString`, and `Identifier`. Inventory ledger code was not mutated. Its tests boot the HTTP stack, and Infection's generated PHPUnit config breaks Gesso, which looks for `gesso.php` beside that generated file. `tools/infection/phpunit.xml` is a Gesso-free suite for the scope above. MSI 100% on 70 mutants is the baseline for that scope only.

## Rector dry-run

`vendor/bin/rector process --dry-run` with PHP 8.5 sets and Laravel composer-based sets wanted changes in 28 files. The set includes `AssertSeeToAssertSeeHtmlRector`, which would rewrite the plain-text `/metrics` assertions. The other changes are mostly `readonly` classes, `#[Fillable]`, queue and command attributes, and `#[\Override]`. They were not applied. Rector is installed so the dry-run can be repeated. CI does not require a clean dry-run.

## Slide map

### Pipeline (slide 2)

| Item | Status | Reason |
| --- | --- | --- |
| Five gates, cheap ones first | Implemented | `hygiene`, `tests`, `prefer-lowest`, `gitleaks`, and `snyk` are separate jobs |
| Tests start only after gates 1–4 are green | Conflict | Tests run in parallel with Snyk and gitleaks. A container CVE must not hide the test result. Any failed job still fails the workflow. There is no `continue-on-error` |
| Every job is a required status check | Deferred | Branch protection was not changed |

### Composer and strict types (slides 3–4)

| Item | Status | Reason |
| --- | --- | --- |
| Commit `composer.lock`; CI uses `composer install` | Existing | The prefer-lowest job runs `composer update` only inside that job |
| `"php": "~8.5.0"` | Implemented | Replaces `8.5.*`. Both mean `>=8.5.0 <8.6.0` |
| `config.platform.php` | Implemented | `8.5.12`, the FrankenPHP image version |
| `declare(strict_types=1)` everywhere Pint formats | Implemented | `pint.json` adds `declare_strict_types` on the Laravel preset |
| One Composer script per local gate | Implemented | `composer ci:static` runs syntax, Pint, PHPStan, PHPMD, normalize, and audit |
| `composer validate --strict` | Existing | Kept |
| `composer-normalize` | Implemented | `ergebnis/composer-normalize` 2.56.0. `composer normalize:check` is the dry run |
| `composer-require-checker` | Deferred | Packagist 4.24.0 supports PHP 8.5. It was not installed. Laravel class strings and facades usually need a long allowlist, and that allowlist was not measured |
| `composer-unused` | Deferred | Packagist 0.9.8 supports PHP 8.5. Horizon, Octane, and Passport are used through providers. A false-positive allowlist was not invented |
| `parallel-lint` | Implemented | `php-parallel-lint` 1.4.0. 153 files, no syntax errors |
| Enums, readonly value objects, typed signatures | Existing | `StopStatus`, `Actor`, and the action classes already follow this. It is not enforced on every class |
| `final` by default | Implemented for controllers | `ArchitectureHygieneTest` requires concrete controllers to be `final`. `User`, `AppServiceProvider`, and the abstract base controller stay open |

### Pint, Rector, CaptainHook (slide 5)

| Item | Status | Reason |
| --- | --- | --- |
| Pint, Laravel preset, `pint --test` | Existing | Now also enforces strict types |
| No PHPCS or ECS beside Pint | Existing | Not added |
| Rector `process --dry-run` as a failing gate | Deferred | 28-file dry-run, including an unsafe metrics assertion change. See above |
| CaptainHook runs Pint before commit | Implemented | `captainhook.json`. Install with `vendor/bin/captainhook install`. Composer install does not rewrite git hooks |

### PHPStan, Psalm, PHPMD (slide 6)

| Item | Status | Reason |
| --- | --- | --- |
| PHPStan level max | Implemented | Level was 8. Max reported 13 `mixed` casts in `app/`, then 31 more once `bootstrap`, `config`, `database`, `routes`, and `tests` were included. Those were fixed without a baseline or an ignore comment. `vendor/bin/phpstan analyse` now reports 0 errors |
| Larastan, strict-rules, deprecation-rules | Existing | Still enabled |
| `phpstan-phpunit` | Implemented | 2.1.1 |
| Baseline that may only shrink | Not used | There is no `phpstan-baseline.neon`. The existing `staticMethod.dynamicCall` ignore is the Eloquent `__call` exclusion already documented in `phpstan.neon`. It was not added to hide max-level errors |
| Psalm `--taint-analysis` only | Deferred | Psalm 7.2.1 runs on PHP 8.5, but taint analysis runs together with type checking. `--taint-analysis` does not hide type errors. Psalm issues 11757 and 11796 ask for a taint-only filter and do not provide one. A Psalm gate would either duplicate PHPStan or need a baseline. Psalm was not installed |
| PHPMD | Implemented | `phpmd/phpmd` 3.0.0. Unused code is on. Cyclomatic report level is 16 and NPath minimum is 8192, which are the measured ceilings (`ProblemResponse::describe` is a flat exception map with NPath 8192). A higher value fails. This is not a file baseline. Facade static-access rules are omitted because Laravel facades are normal here |

The max-level fixes included a real logging bug: `config/logging.php` defined `stderr` twice, and the later Laravel default overwrote the channel that attaches `RedactSecretsProcessor` and the JSON formatter. The duplicate default channel was removed. `ArchitectureHygieneTest` locks that shape.

### Security (slide 7)

| Item | Status | Reason |
| --- | --- | --- |
| Snyk Open Source, high severity | Existing | `snyk test --file=composer.lock --severity-threshold=high`. The CLI is npm `snyk@1.1308.4`, not `snyk/actions/php@master`. The slide's sample also says "with a fix". This job fails on high findings whether or not a fix exists. That is stricter and was not loosened |
| Snyk Code, high | Existing | Unchanged |
| Snyk Container, high | Existing | Still builds `quay-platform:ci` and scans it. Known high base-image, OS, and FrankenPHP findings stay visible |
| Snyk IaC | Existing | Still runs on `Dockerfile` and `compose.yaml`. A skip or a lower threshold was not added |
| Missing `SNYK_TOKEN` fails closed | Existing | Unchanged |
| Psalm taint | Deferred | Same reason as slide 6 |
| Semgrep | Implemented | Version 1.185.0, pinned in `tools/ci/requirements.txt`. The release has no attached Linux binary, so the pin is the pip version. Rules ban raw SQL call shapes in `app/` and `SELECT *` in `*.sql`. They do not prove an Eloquent query is safe |
| `composer audit` | Existing | Still required. `--abandoned=report` prints the Rector transitive abandoned package and does not fail the build for it. An advisory still fails the build |
| `roave/security-advisories` | Implemented | `dev-latest`. `composer update` did not conflict |
| gitleaks | Implemented | 8.30.1, checksum-pinned by `tools/ci/install-pinned-tools.sh`. Default rules. No allowlist was added. `.env.example` and the test `APP_KEY` did not match |

### SQL (slide 8)

| Item | Status | Reason |
| --- | --- | --- |
| SQLFluff, MySQL dialect | Implemented | 4.5.0 lints `docker/mysql`. Migrations are PHP schema builders, so SQLFluff does not see them |
| No `SELECT *` | Implemented for `.sql` files | `RawSqlPolicyTest` and Semgrep. Eloquent `get()` still generates `SELECT *`. That is not claimed to be covered |
| No un-batched `UPDATE` or `DELETE` | Implemented for `.sql` statements | The check matches statements that start with `UPDATE` or `DELETE`. `GRANT ... UPDATE, DELETE` in `docker/mysql/init.sql` is a privilege, not a write statement. `RF05` is excluded because MySQL account names use `'user'@'host'` |
| `phpstan-dba` | Deferred | Not installed. `staabm/phpstan-dba` 0.5.4 documents built-in support for Doctrine DBAL, mysqli, and PDO. Laravel raw-query support is still the open issue 392. A green run over this Eloquent codebase would not analyze the query builder |
| CI MySQL plus schema-aware `phpstan-dba` | Deferred | Same coverage gap. MySQL is already started for tests |
| Ban concatenated SQL and raw calls outside a repository | Implemented for the raw APIs | `app/` has no `DB::raw`, `DB::select`, `whereRaw`, `selectRaw`, or the other listed raw methods. This project has no repository layer. Actions use the query builder and Eloquent. The test is a textual ban. It does not prove dynamic SQL safe |
| Queries return at most 100 rows | Implemented for application-origin reads | The scope decision is every application database read. `ReadCap` fails the query. It does not add a LIMIT. Ordinary callers name columns and fetch at most 100 rows. See the read-cap section below. Unapproved package reads are listed and are not an allowlist |

### Tests (slides 9–10)

| Item | Status | Reason |
| --- | --- | --- |
| Pest | Conflict | Tests stay on PHPUnit 12.5.43. Laravel's skeleton constraint is `phpunit/phpunit: ^12.5.12`. Conversion was not approved |
| `failOnRisky`, `failOnWarning`, `failOnDeprecation` | Implemented | Also `failOnNotice` and `failOnPhpunitDeprecation`. The suite passed with those flags |
| Changed-code coverage floor | Deferred | The slide's percentage is blank. This diff's changed-statement rate is 84.21% (16/19). No failing threshold was invented |
| PCOV in CI | Implemented | The test job sets `coverage: pcov`, writes unit and combined Clover files, writes the summaries, and uploads them even when a later step fails |
| Infection `--min-msi` | Deferred | The slide's number is blank. Measured MSI on the scope above is 100. Inventory is not in that number. No minimum was set |
| `prefer-lowest` | Implemented | A separate CI job. The local run passed 33 tests and 600 assertions, then the lock was restored. One later test reads `config/logging.php` and does not depend on package versions |
| `dd()` / `var_dump()` banned in `app/` | Implemented | PHPUnit scan, not Pest `arch()` |
| Final controllers | Implemented | Same test |
| Property tests | Implemented | Eris 1.3.0 on `StopStatus`: transition membership, terminal states, and no self-loops |
| Real MySQL and Redis in CI | Existing | MongoDB is there as well |
| OpenAPI contract tests | Existing | Gesso remains. `league/openapi-psr7-validator` stays unused because it targets OpenAPI 3.0.2 |
| Spectral | Deferred | Redocly CLI 2.76.0 already lints the 3.1 spec. A second spec linter was not added |
| PHPat module boundaries | Existing | Kept. Deptrac was not added beside it |

### Workflow, image, and metrics (slide 11)

| Item | Status | Reason |
| --- | --- | --- |
| Deptrac | Deferred | PHPat already guards module and HTTP boundaries. A second layer tool was not added |
| actionlint and SHA-pinned actions | Implemented | New `actions/upload-artifact` is `cf430e030ddbb5b0abf93d22962f4752f3646cd9` (`v7.0.2`). Existing actions stay on their SHAs |
| hadolint | Implemented | `SHELL` is bash with `pipefail` because the Composer checksum is a pipe. `DL3008` is ignored on the `apt-get` line: `ca-certificates` and `curl` are not version-pinned inside an image that is already digest-pinned. Snyk Container remains the CVE gate |
| Snyk Container on the PHP-FPM image | Conflict | The slide's image is an example. This application runs FrankenPHP. The runtime was not changed |
| PhpMetrics | Deferred | Not installed. PHPMD already fails the build on complexity above the measured ceiling. A second report was not added only to match the slide |

### Runtime and performance (slide 12)

| Item | Status | Reason |
| --- | --- | --- |
| N+1 and unbounded-query watches | Existing, partial | `Model::shouldBeStrict()` outside production, per-query slow log, and the stock-list query budget of 15. No automated `EXPLAIN` |
| PMM Query Analytics | Deferred | Not part of this draft |
| Blackfire | Deferred | Paid, and not an established service for this repository |
| k6 | Deferred | No load target was defined. A script that never runs would not be a test |
| OpenTelemetry, visualized in Grafana | Partial | The confirmed stack is OpenTelemetry plus Grafana. SigNoz is not the target. Implemented today: JSON stderr logs, secret redaction, and `trace_id` / `span_id` correlation on web, the outbox consumer, and the scheduler heartbeat. OTLP export is off unless `TELEMETRY_EXPORT=true` and `OTEL_EXPORTER_OTLP_ENDPOINT` is set. Tests force export off. The PHP SDK and auto-instrumentation are not installed. Grafana is not an OTLP receiver; no Loki, Tempo, Mimir, Alloy, or Grafana Cloud address is configured |
| Feature flags and canary releases | Deferred | No flag service or production orchestrator is chosen |

### AI workflow (slide 13)

| Item | Status | Reason |
| --- | --- | --- |
| `AGENTS.md`, with `CLAUDE.md` importing it | Implemented | Boost wrote the Laravel guidelines into `AGENTS.md`. The Quay gate section is after that block. `CLAUDE.md` is `@AGENTS.md` |
| Agent hooks run PHPStan, tests, and Pint on every edit | Deferred | Pint runs from CaptainHook on commit. The test suite needs MySQL, two Redis processes, and MongoDB. An every-edit hook that runs that suite would fail closed for the wrong reason |
| MCP for current library docs | Implemented | `laravel/boost` and `.mcp.json`. Boost does not register its Artisan commands unless `APP_ENV=local` or debug is on |
| Plan, then tests; small tasks; full finish gates | Implemented | `AGENTS.md` records the approved sequence: plan approval, test approval, tests stay as approved, small diffs, then the full Pint and PHPStan gates. The checks under `tools/Guardrails/` enforce suppressions, missing packages, and smell rules. Deleted tests and suspected assertion edits are printed and do not fail. `tools/Guardrails/approved-tests.txt` enrolls no production test |
| A second review model | Deferred | No independent second-model review has been run |

`php artisan boost:install` rewrites `AGENTS.md` and `CLAUDE.md`. A later install can replace the import and the Quay gate section. Re-apply them if that happens.

### Sample workflow (slide 14)

The sample is not a template that was copied. It uses `snyk/actions/php@master`, unpinned `actions/checkout@v4`, and a single MySQL service. This workflow keeps the checksum-pinned Snyk CLI, SHA-pinned actions, MySQL, both Redis processes, and MongoDB.

### Making it stick (slide 15)

| Item | Status | Reason |
| --- | --- | --- |
| Branch ruleset: required checks, no admin bypass, approving review | Deferred | Not changed. A sole maintainer can lock themselves out. The proposed required checks are `hygiene`, `tests`, `prefer-lowest`, `gitleaks`, and `snyk` |
| CODEOWNERS | Deferred | GitHub user `LewisUniUni` exists (user id 244789354). The account was not added to a CODEOWNERS file. A platform-team owner was not named |

Proposed file, not committed:

```text
/phpstan.neon @LewisUniUni
/phpmd.xml @LewisUniUni
/.sqlfluff @LewisUniUni
/.semgrep/ @LewisUniUni
/.github/workflows/ci.yml @LewisUniUni
```

Confirm that this account should be the owner before that file exists. Without branch protection, CODEOWNERS only requests review.

| Item | Status | Reason |
| --- | --- | --- |
| `composer ci` runs every gate | Implemented in part | `composer ci:static` is the gate that does not need databases or `SNYK_TOKEN`. Tests, coverage, Infection, and Snyk stay separate commands |
| CaptainHook | Implemented | See slide 5 |
| Small PRs and human review of intent | Deferred | A size cap was not added |

### Tool picks (slide 16) and the staged stack (slide 17)

| Job | Slide pick | This repository |
| --- | --- | --- |
| Formatter | Pint | Pint |
| Types | PHPStan + Larastan | PHPStan max + Larastan |
| Taint | Psalm only | Deferred |
| Custom rules | Semgrep | Semgrep plus PHPUnit scans for the same raw-SQL ban |
| Tests | Pest | PHPUnit, by decision |
| Layers | Deptrac | Existing PHPat |
| Pre-commit | CaptainHook | CaptainHook config |
| Dependency updates | Dependabot | Existing Dependabot |
| Secrets | gitleaks | gitleaks |
| Profiling | Blackfire | Deferred |
| Load | k6 | Deferred |
| Telemetry | OpenTelemetry, Grafana for visualization | Log correlation is implemented. OTLP export is opt-in and has no collector endpoint yet |
| Code health | PhpMetrics | PHPMD ceilings; PhpMetrics deferred |
| Agent file | `AGENTS.md` | `AGENTS.md` |

### Rollout (slide 18)

| Item | Status | Reason |
| --- | --- | --- |
| Turn tools on with `continue-on-error`, then ratchet | Conflict | Existing jobs already fail the build. New checks fail the build too, except diff coverage and Infection, which report numbers and do not enforce a blank threshold |
| Shared reusable workflow and a Composer package for every PHP repo | Deferred | This change is local to Quay. Packaging it for other repositories is a separate decision |

## Application read cap

The decision is every application database read, not API lists only. `ReadCap` throws `ProhibitedRead`. It does not rewrite SQL to add a LIMIT.

Enforced when the first stack frame outside the query builder and this guard is under `app/`:

- MySQL/MariaDB `SELECT` through `Connection::beforeExecuting`.
- MongoDB collection reads on `ScanEvent`, which does not emit SQL. `CappedMongoBuilder` checks the limit and the field list before `get()`.
- Explicit column lists on those models. `SELECT *` and an empty Mongo projection fail.
- `LIMIT` 1 through 100, including a bound integer. A limit above 100 fails. A missing limit fails. A non-integer binding fails instead of being treated as safe.
- A `LIMIT` inside a subquery does not cap the outer read.
- Joins are capped on returned rows, not on rows scanned per table.
- `COUNT`, `SUM`, `AVG`, `MIN`, and `MAX` without `GROUP BY` are one result row. The rows they scan are not the result size. `GROUP BY` still needs a limit, because it can return one row per group.
- A pagination count is that one-row aggregate. The page query still needs its own limit. This codebase has no paginator and no eager `with()`. An eager relation query would be a separate read and is not exempt.
- The outbox relay batch must be 1..100. The default is 50. A larger `OUTBOX_BATCH` fails. It is not clamped.
- `select 1 as ready` has no `FROM` and returns one row.

Not database reads, so the cap does not claim them: Redis (cache, session, queue, rate limit, metrics hash), the filesystem, and the Mongo `{ping: 1}` admin command.

These package operations are outside the guard because their originating frame is not under `app/`. They are not approved exemptions. There is no SQL allowlist.

| Proposed exception | What it does | Why it is not enforced yet |
| --- | --- | --- |
| `EloquentUserProvider` login | `select * from users ... limit 1` | Framework hydration. Applying the star ban here would reject every password login |
| Passport client and access-token lookup | `select *` by primary key | Library hydration during client credentials. Same star ban |
| Migrator and schema builder | `SHOW`, `information_schema`, the `migrations` table | Package metadata during `migrate` and `RefreshDatabase`, not an application read |
| Database session or database cache, if those drivers are selected later | Framework reads with `select *` | Today's session, cache, and queue use Redis. A driver change would not be covered by an approval that does not exist |

## AI guardrails

These checks are in addition to the full Pint, PHPStan, Composer audit, and Snyk high gates. They do not replace those gates. No second-model review has been run.

| Control | What it does | Enforcement |
| --- | --- | --- |
| Suppressions | Base-relative. A new or moved `@phpstan-ignore` or `@psalm-suppress` fails. Deleting one passes. Adding or editing a baseline fails. Deleting a baseline passes. `ignoreErrors` entries in `phpstan.neon` must stay identical to the base or be removed. The current `staticMethod.dynamicCall` entry is that frozen identity | Failing |
| Test edits | Deleted tests and removed assertion lines are printed. The print is not proof that a test was weakened. A path fails only when it is listed in `tools/Guardrails/approved-tests.txt` | Report only. The list is empty, so no production test is frozen |
| Changed-file Pint and PHPStan | Paths come from `git diff` against the merge base. Pint is `--test` on those files, not `--dirty`. Changed-file PHPStan does not see cross-file errors. Smell fixtures under `tools/Guardrails/fixtures/` are left out of this list because they are the negative cases | Extra. `composer lint` and `composer analyse` still run on the whole project |
| Package existence | A new direct Composer package that Packagist answers 404 for fails. HTTP 200 only means the name exists. A timeout or 5xx exits 2 and says the registry was unavailable | Failing for a missing name. Outage is a different exit code |
| Dependency review | `actions/dependency-review-action` `a1d282b36b6f3519aa1f3fc636f609c47dddb294` on pull requests. Vulnerability check at high for runtime and development. License check is off. It does not prove a package is trustworthy | Failing on high advisories. No license list |
| Code smells | Semgrep `quay-empty-catch` and `quay-debug-call`, local rules, no Semgrep account. A catch that logs and rethrows is clean. Mocking the class under test is not inferred | Failing |
| Pull request size | The changed-line count is printed | Report only. No numeric cap |

`AGENTS.md` still contains the generated Laravel Boost guidelines, and `CLAUDE.md` imports that file. How to keep a short tool-neutral file when `boost:install` rewrites both is unresolved. The approved workflow section was appended. Boost was not re-run.

## Questions

1. What changed-code coverage floor should fail CI? This diff is 84.21% of changed executable statements (16/19). The combined rate over all of `app/` is 71.75% (851/1186). The unit-only rate on that same denominator is 3.79% (45/1186).
2. Should Infection fail below a minimum MSI? The measured scope is 100% of 70 mutants and does not include inventory. Should that scope be gated at 100, and should the inventory actions be added even though that run has to avoid the Gesso PHPUnit extension?
3. The 100-row cap now applies to application-origin database reads. Package lookups below are not approved exemptions. Say if any of them should be enforced or explicitly allowed.
4. Should the Rector dry-run be applied, with `AssertSeeToAssertSeeHtmlRector` left out because `/metrics` is not HTML?
5. Is `LewisUniUni` the CODEOWNERS identity for the analysis and CI files, or should that wait for a platform-team name?
6. Should branch protection require the five workflow jobs, an approving review, and no admin bypass? That can lock out a sole maintainer, so it was not enabled.
7. When Psalm can emit taint findings without its type errors, should it be added, or is PHPStan plus the raw-SQL ban enough?
8. Should `composer-require-checker` and `composer-unused` be installed and tuned, knowing Laravel will need an allowlist?
9. Should `CLAUDE.md` stay as an import of `AGENTS.md` when the next `boost:install` will expand it again?
10. Do you want a PhpMetrics report artifact with no failing score, and k6, Blackfire, or SigNoz only after those systems exist?
