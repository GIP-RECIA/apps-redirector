# ESCO Apps Redirector Context

## Project Overview

This project is a small PHP CAS-based redirector. It authenticates the user with phpCAS, reads CAS attributes, receives an `appli` query parameter, finds the target URL from configured mappings, optionally checks access filters, and redirects the user.

PHP 7.4 backward compatibility may be required temporarily. Before introducing PHP 8.x-only syntax or APIs, such as named arguments, union types, attributes, `match`, constructor property promotion, nullsafe operator, or `str_contains()`, confirm whether PHP 7.4 compatibility is still needed.

There is no `index.html`. The main entry points are:

- `index.php`: production entry point, loads `conf/conf.inc.php`.
- `index_test.php`: dev/test entry point, loads `conf/conf.inc.test.php`.

`index_test.php` is intentionally available to test code changes before reporting validated changes back to `index.php`.

## Key Files

- `index.php`: production CAS redirect flow.
- `index_test.php`: test/dev CAS redirect flow.
- `commonFunction.php`: shared logging and authorized IP/subnet helper.
- `docs/CONFIGURATION.md`: configuration variables and mapping examples.
- `docs/DEPLOY_APP.md`: application deployment script documentation.
- `conf/conf.inc.example.php`: example app mapping and global settings.
- `conf/conf.inc.test.example.php`: example test/dev app configuration overlay.
- `conf/cas.inc.example.php`: example CAS server/session configuration.
- `deploy-app.sh`: application deployment helper, dry-run by default.
- `.htaccess`: disables caching and sets PHP session cookie path for Apache/mod_php5.
- `conf/.htaccess`: denies direct access to config files except localhost.

The private configuration repository is separate from this application repository:

- `/home/jgribonvald/Travail/www/esco-apps-redirector-conf`: private configuration repository.
- `deploy-conf.sh`: private configuration deployment helper, dry-run by default.
- `docs/DEPLOY_CONF.md`: private configuration deployment documentation.
- `prod/conf.inc.php`: production private configuration.
- `test/`: test private configuration files.
- `etablissements_uai.csv`: establishment reference file used to compare structures and contexts.

## Runtime Flow

`index.php` and `index_test.php` do the following:

1. Load app config and CAS config.
2. Configure PHP session settings.
3. Initialize phpCAS with `phpCAS::client($protocol, $host, $port, $uri, true)`.
4. Configure phpCAS debug, language, CA certificate, rebroadcast nodes, and logout handling.
5. Force authentication using `phpCAS::forceAuthentication()`.
6. Handle logout if `$_REQUEST['logout']` is set.
7. Read `$CAS_attrs = phpCAS::getAttributes()` and `$CAS_user = phpCAS::getUser()`.
8. Read `$_GET['appli']`.
9. Resolve redirect URL from `$mapping[$appli]`.
10. Check optional access filter with `can_access()`.
11. Redirect with `do_redirect()` or display `$msg_access_problem`.

## Mapping Modes

Two mapping modes exist:

- Domain mapping: requires `DOMAIN` and `DOMAIN_MAP` under `$mapping[$appli]`.
- CAS attribute mapping: requires `USER_ATTRIBUTE` and `LINK` under `$mapping[$appli]`.

Attribute mapping can use `USER_ATTRIBUTE_FALLBACK` if the main attribute does not produce a redirect.

Domain mapping can also use application-level `LINK` entries as establishment overrides before falling back to `DOMAIN_MAP`.

`DEFAULT_LINK` can be used as a fallback in supported branches.

The previous global `$context` and `CONTEXT_DEFAULT_LINK` mechanism was removed because it is not used in production. Use domain mapping plus application-level `LINK` overrides instead.

## Redirect Resolution

For CAS attribute mappings, the resolution order is:

1. Match `USER_ATTRIBUTE` against application `LINK`.
2. If needed, match `USER_ATTRIBUTE_FALLBACK` against application `LINK`.
3. Use application `DEFAULT_LINK` when configured.
4. Show the standard access problem message.

For domain mappings, the resolution order is:

1. Match application-level `LINK` overrides when `USER_ATTRIBUTE` or `USER_ATTRIBUTE_FALLBACK` are configured.
2. Match the current `DOMAIN` value against application `DOMAIN_MAP`.
3. Use application `DEFAULT_LINK` when configured.
4. Show the standard access problem message.

This model is used for applications such as `PDFONLINE` and `MYPADS`: the controlled request domain provides the default routing, while establishment-level `LINK` entries handle explicit exceptions such as agricultural structures.

## Important Functions

- `can_access($conf_property)`: checks optional `FILTER` configuration against a CAS attribute using a regex.
- `do_replacement($conf_property, $chaine)`: replaces `%USER_ATTRIBUTE%` placeholder using a CAS attribute value.
- `do_redirect($conf_property, $url)`: validates the redirect target, applies replacements, checks access, then sends redirect headers unless `$DEV_MOD` is true.
- `find_default_link($conf_property)`: resolves an application default URL from `DEFAULT_LINK`.
- `find_link_override($conf_property)`: resolves an optional application-level `LINK` override before domain mapping.
- `find_cas_attr($user_attr, $appli)`: finds a redirect URL from the configured CAS attribute value.

## Configuration Rules

Keep generic application defaults near the application declaration:

- `DEFAULT_LINK`
- `DOMAIN`
- `DOMAIN_MAP`
- `USER_ATTRIBUTE`
- `USER_ATTRIBUTE_FALLBACK`
- empty `LINK` initialization

Keep establishment-specific `LINK` overrides in the establishment sections with the other per-establishment application mappings. This keeps generic behavior separate from structure-specific exceptions.

Treat empty strings and the literal string `null` as intentional no-redirect values.

Real configuration must stay out of the application repository. Use `conf/conf.inc.example.php` only as a public template.

## Deployment

Application deployment is handled by `deploy-app.sh` from the application repository.

Configuration deployment is handled by `deploy-conf.sh` from the private configuration repository.

Both deployment scripts are dry-run by default. Run them without `DRY_RUN=0` to preview operations, then run with `DRY_RUN=0` to perform actual copies.

Application deployment excludes private config files, logs, Git metadata, and GitHub workflow metadata. Private configuration must be deployed separately.

## Known Issues And Risks

- Debug/dev output prints CAS data without HTML escaping. This is acceptable only if `$DEV_MOD=true` is restricted to the test/dev entry point and never exposed in production.
- Logging can include full CAS attributes and request values. Be careful with sensitive personal data and log levels.
- `commonFunction.php` includes `conf/conf.inc.php`, but entry points already include config. `include_once` prevents double inclusion, but the dependency is redundant and production-specific.

## Recent Fixes

- `can_access()` no longer uses `found` as an undefined constant.
- `can_access()` now logs `$CAS_attrs[$filter_attr]` only after checking that the attribute exists.
- Redirects now use HTTP 302 instead of HTTP 301.
- Empty redirect targets and the literal string `"null"` are treated as no redirect.
- `do_redirect()` applies `do_replacement()` to all redirect targets before sending the `Location` header.
- Domain mappings can use application-level `LINK` overrides for establishment-specific exceptions.
- The unused context-based routing mechanism was removed from code, public examples, documentation, and private production configuration.
- Deployment scripts now run in dry-run mode by default and require `DRY_RUN=0` for actual copy operations.

## Private Configuration

The real configuration files are private and ignored by Git:

- `conf/conf.inc.php`
- `conf/conf.inc.test.php`
- `conf/cas.inc.php`
- `conf/cas-test.inc.php`

Do not commit real local values from these files.

Before modifying any private configuration file, create a backup copy next to the original file. The backup filename must include the current date and time down to the second using the `YYYYMMDDHHMMSS` format, without spaces.

The private configuration deployment script creates timestamped backups automatically when run with `DRY_RUN=0`.

Example:

```text
conf/conf.inc.php.bkp.20260818143015
```
