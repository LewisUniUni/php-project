# Verification

Recorded from commands run on this draft on 6 November 2026. PHP was 8.5.12.

| Check | Result |
| --- | --- |
| `composer validate --no-check-publish` | Valid |
| `composer install` / lock resolve on PHP 8.5.12 | Locked Laravel 13.40.0, Octane 2.23.0, Horizon 5.50.1, Passport 13.10.0, mongodb/mongodb 2.5.1, laravel-mongodb 5.12.0, Pint 1.32.1, Larastan 3.14.0, PHPStan 2.3.5, Gesso 2.9.0, PHPUnit 12.5.43 |
| `composer audit` | No security vulnerability advisories found |
| `vendor/bin/pint --test` | Passed |
| `phpstan analyse` level 8 | Passed, 0 errors |
| `gesso doctor --spec=docs/api/openapi.yaml` | Compatible. OpenAPI 3.1.0, 13 operations, 51 responses. OAuth2 scheme recognized and not enforced by the doctor. Feature tests cover the bearer behaviour. |
| `@redocly/cli@2.76.0 lint` | Valid, with warnings for readiness having no 4xx response |
| `php artisan test` | Passed, 17 tests, 100 assertions. MySQL 8.4.13, Redis 8.10.3 cache and queue, MongoDB 8.0.34 |
| ext-redis 6.3.0 on PHP 8.5.12 | Extension loaded |
| ext-mongodb on the host | 2.5.5, which satisfies `^2.5`. Image install requests 2.5.7 |
| `docker compose build web` | Passed. Image `quay-platform:local` runs PHP 8.5.12 ZTS with `redis` 6.3.0, `mongodb` 2.5.7, `pdo_mysql`, and `pcntl` |
| Migrate service, then web, worker, scheduler | Passed. `/up` returned 200 from FrankenPHP. `/ready` reported mysql, redis_cache, redis_queue, and mongodb true. Horizon logged "started successfully". `schedule:work` logged "Running scheduled tasks" |
| Warehouse UI | Operator login, receipt of 24 into LOC-A-01, balance row visible. Planner session does not render the receipt form. Deliveries and scans return 200. Desktop and narrow layouts both render |
| This VM's Docker bridge | Host-published ports worked immediately. Container-to-container traffic needed an iptables FORWARD accept on the Compose bridge because the legacy FORWARD policy is DROP. That is an environment quirk, not a Compose defect |
| Snyk Open Source, Code, Container, IaC | Not run. No `SNYK_TOKEN`. The workflow fails closed |
| GitHub Actions on a GitHub repository | Not run. This draft is not a GitHub repository |
| OpenTelemetry under Octane | Not run. Not installed |
| Production deploy, restore drill, load test | Not run |

PHPUnit 13.4.6 was not used. The Laravel 13.11 skeleton constraint is PHPUnit `^12.5.12`.
