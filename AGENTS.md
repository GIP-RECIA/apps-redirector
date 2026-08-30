# AGENTS.md

## Verification

There is no test suite, no Composer, no lint config, and no CI. Verify changes with:

```bash
php -l index.php && php -l commonFunction.php
```

The app cannot run standalone: it needs the external phpCAS library (`$PATH_CAS_LIB` in `conf/conf.inc.php`, e.g. `/var/www/phpCAS/phpCAS-1.6.2/CAS.php`) plus real CAS/conf files that are not in this repo.

## Entrypoints

- `index.php`: production redirect entrypoint, loads `conf/conf.inc.php`.
- `getConfJson.php`: standalone configuration JSON entrypoint, loads `conf/conf.inc.php`, exposes `$mapping[$appli]` as JSON, and restricts access by IP via `check_authorized_access()`.

Any change to mapping keys or configuration structure impacts both entrypoints.

`$DEV_MOD=true` is a diagnostic setting for the redirect entrypoint. It is effective only when `check_authorized_access()` allows the request IP; it enables PHP error display and outputs the resolved redirect instead of sending it.

## Logging

`log_action($lvl, $msg)` (in `commonFunction.php`) appends to the daily file from `$LOG_FILENAME` (default `logs/YYYY-MM-DD.log`; some entrypoints override it with their own prefix). Levels are `TRACE`, `DEBUG`, `INFO`, `WARN`, `ERROR` — case-sensitive — and only messages at or above `$LOG_LVL` are written.

## Private configuration

Real files `conf/{conf,cas}{,-test}.inc.php` and `conf/*.bkp.*` are gitignored. Only commit the `*.example.php` templates. Never commit real values (hosts, secrets, establishment data).

The committed CI configuration is `ci/conf/conf.inc.php`. It must contain only synthetic application names, identifiers, domains, and URL paths. The GitHub Action copies it to `conf/conf.inc.php` before linting and tests.

Before editing any private config file, create a backup next to it named with format `YYYYMMDDHHMMSS` (e.g. `conf.inc.php.bkp.20260821234356`). Existing backups follow this convention.

Do not "fix" the spelling of the config variables `$AUTORIZED_IPS` / `$AUTORIZED_SUBNET`: they are read by `commonFunction.php` and defined in real private config files in both repos. Renaming requires coordinated changes outside this repository.

## Coordination With Configuration Repository

The real deployed configuration is maintained in `../esco-apps-redirector-conf`. This application repository must stay compatible with that private configuration.

Any change to mapping keys, redirect resolution order, or configuration syntax must be coordinated with `../esco-apps-redirector-conf` before private configuration starts using it.

Examples:

- `REGEX_LINK` support in `index.php` and `getConfJson.php` must exist before `prod/conf.inc.php` uses it.
- Compound `FILTER` rules using `OPERATOR` and `RULES` must be supported here before they are used in private config.
- Public examples in `conf/conf.inc.example.php` must not include private hostnames, secrets, or establishment data from the config repository.

When changing mapping behavior, update human-readable skills in both repositories:

- `docs/configuration/SKILL.md`
- `docs/project-context/SKILL.md`
- `../esco-apps-redirector-conf/docs/configuration/SKILL.md`
- `../esco-apps-redirector-conf/docs/project-context/SKILL.md`

## PHP version

PHP 7.3 through 8.3 compatibility is required (local CLI here is 8.x). Before using newer syntax/APIs (`match`, named args, union types, nullsafe operator, `str_contains()`, constructor promotion, enums, readonly properties...), confirm they are safe for the oldest supported version (7.3: no typed properties, no arrow functions).

## Deployment

- App code: `./deploy-app.sh [DEST_DIR]` is dry-run by default; `DRY_RUN=0` performs a real rsync (with automatic destination backup). See `docs/deploy-app/SKILL.md`.
- The `.htaccess` files must be deployed: root one disables caching and sets the PHP session cookie path, `conf/.htaccess` blocks direct web access to real config files (except localhost). Do not exclude them from rsync.
- Private config lives in `../esco-apps-redirector-conf` and is deployed by its own `deploy-conf.sh`. This repo's deploy script excludes private conf and logs.

## Repo conventions

- Docs live in `docs/<skill>/SKILL.md` and double as opencode skills (`opencode.json` sets `skills.paths: ["docs"]`). When adding/moving docs, keep frontmatter (`name`, `description`) and cross-references between SKILL.md files valid.
- Commits follow Conventional Commits style (`feat:`, `fix:`, `docs:`, `chore:`).
- Redirects use HTTP 302; empty strings and the literal string `"null"` mean "no redirect".
