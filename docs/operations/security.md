# Security

Local passwords in `.env.example` are for the development Compose network. Replace them before any shared environment. Do not commit `.env` or `storage/oauth-*.key`.

The runtime MySQL user has `SELECT`, `INSERT`, `UPDATE`, and `DELETE`. The migrator user is the one that can change schema. Application containers use the runtime user. The migrate service uses the migrator.

API authorization is a Passport bearer token plus a scope. The warehouse UI uses a session and gates: planners can read, operators can mutate. `/horizon` uses the same operator gate and is denied to anonymous requests, including when `APP_ENV=local`.

`/ready` returns component names and booleans. It does not return DSNs, passwords, or exception text. Problem responses for unexpected errors do not include SQL. The log processor redacts tokens, passwords, secrets, and bearer strings. Audit rows are a separate MySQL table.

Rate limits: 60 requests per minute per API client, 30 for clients whose name starts with `partner:`, and 5 login attempts per minute per email and IP.

`METRICS_TOKEN`, when set, is required as a bearer token on `/metrics`.

GitHub secret scanning and push protection are not enabled by this draft. Turn them on when a GitHub repository exists. The Snyk job needs the `SNYK_TOKEN` secret and a plan that includes the scanners you want. Without the token the job fails. That failure means the scans did not run.

TLS, private networking, and a production secret store are not configured here. Compose publishes database ports on `127.0.0.1` for host tests. Do not publish those ports on a shared host.
