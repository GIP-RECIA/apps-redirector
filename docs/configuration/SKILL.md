---
name: configuration
description: Configuration variables and mapping examples for esco-apps-redirector. Use when editing conf files, application mappings, DOMAIN_MAP, USER_ATTRIBUTE, DEFAULT_LINK, LINK overrides, REGEX_LINK overrides, or access FILTERs.
---

# Configuration

This document describes the main configuration variables used by ESCO Apps Redirector.

Real configuration files are private and must not be committed. Use the `*.example.php` files as templates.

## Global Variables

`$LOG_LVL`

Minimum log level written by `log_action()`. Supported values are `TRACE`, `DEBUG`, `INFO`, `WARN`, and `ERROR`. The comparison is case-sensitive.

`DEBUG` and `TRACE` are intended for troubleshooting. They may include CAS attributes, request parameters, or mapping details. Do not keep these levels enabled for regular production usage unless the resulting logs are handled as sensitive data.

For CAS attributes, `DEBUG` should expose attribute names and decision context only. Full CAS attribute values are reserved for `TRACE` logs.

Example:

```php
$LOG_LVL='INFO';
```

`$DEV_MOD`

Enables diagnostic mode only when the request comes from an address allowed by `$AUTORIZED_IPS` or `$AUTORIZED_SUBNET`. It enables `display_errors` and shows the resolved 302 `Location` header instead of redirecting.

Keep it disabled by default:

```php
$DEV_MOD=false;
```

`$PATH_CAS_LIB`

Path to the phpCAS library entry point.

Example:

```php
$PATH_CAS_LIB='/path/to/phpCAS/CAS.php';
```

`$PATH_CAS_CONFIG`

Path to the CAS server configuration file loaded after the main application configuration.

Example:

```php
$PATH_CAS_CONFIG='conf/cas.inc.php';
```

`$cas_service_base_urls`

Allowed client base URLs used by phpCAS 1.6.x to build and validate the CAS service URL. Use all public domains that can serve this redirector. For multidomain deployments, provide an array; phpCAS uses the discovered request host only when it is present in this allowlist, otherwise it falls back to the first entry.

Example:

```php
$cas_service_base_urls=array(
  'https://domain.example.org',
  'https://other-domain.example.org',
);
```

`$LOG_FILENAME`

Application log file path. It is commonly date-based.

Example:

```php
$LOG_FILENAME='logs/' . date('Y-m-d') . '.log';
```

`$PHPCAS_LOG_FILENAME`

phpCAS debug log file path. It is used when the application log level enables phpCAS debug output.

Example:

```php
$PHPCAS_LOG_FILENAME='logs/phpCAS.log';
```

`$AUTORIZED_IPS`

List of explicitly authorized IP addresses for helper access checks.

Example:

```php
$AUTORIZED_IPS=array('127.0.0.1');
```

`$AUTORIZED_SUBNET`

List of authorized subnet prefixes for helper access checks.

Example:

```php
$AUTORIZED_SUBNET=array('192.168.0.');
```

`$mapping`

Main application routing configuration. Each key is an application name accepted through the `appli` query parameter.

Example:

```php
$mapping['APP_NAME']=array();
```

`$etab`

Optional establishment metadata table, mainly used by JSON/config helper endpoints.

Example:

```php
$etab['0000000A']['LABEL']='Example school';
$etab['0000000A']['TYPE']='LYCEE';
```

## Application Mapping

Each application is configured under `$mapping['APP_NAME']`.

The application name is selected from the URL:

```text
/esco-apps-redirector/index.php?appli=APP_NAME
```

## Attribute-Based Mapping

Use `USER_ATTRIBUTE` and `LINK` when the redirect depends on a CAS user attribute.

`USER_ATTRIBUTE`

CAS attribute used to select the target URL.

Example:

```php
$mapping['APP_NAME']['USER_ATTRIBUTE']='ENTPersonProfils';
```

`LINK`

Map of CAS attribute values to target URLs.

Example:

```php
$mapping['APP_NAME']['LINK']=array();
$mapping['APP_NAME']['LINK']['National_ELV']='https://student.example.org';
$mapping['APP_NAME']['LINK']['National_ENS']='https://staff.example.org';
```

If the CAS attribute is an array, the first matching key in `LINK` wins. Put the highest priority values first in the configuration.

`REGEX_LINK`

Optional map of regular expressions to target URLs. It is evaluated after exact `LINK` matches and before `DEFAULT_LINK`. For domain-based mappings, it is evaluated before `DOMAIN_MAP`.

The regex is applied to `USER_ATTRIBUTE`; if no URL is found and `USER_ATTRIBUTE_FALLBACK` is configured, the fallback attribute is tested too. If the CAS attribute is an array, the first matching regex in `REGEX_LINK` wins.

Example:

```php
$mapping['APP_NAME']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['APP_NAME']['LINK']=array();
$mapping['APP_NAME']['REGEX_LINK']=array();
$mapping['APP_NAME']['REGEX_LINK']['/^018[0-9]{4}[A-Z]$/i']='https://service-18.example.org';
$mapping['APP_NAME']['REGEX_LINK']['/^028[0-9]{4}[A-Z]$/i']='https://service-28.example.org';
```

For `ESCOUAICourant`, use fully anchored regexes that enforce the UAI format: 7 digits followed by one letter. A department rule such as `018` should therefore use `/^018[0-9]{4}[A-Z]$/i` rather than `/^018/`. The `i` modifier avoids casing issues on the final letter because `REGEX_LINK` does not normalize CAS values before matching. This strict format is required when `USER_ATTRIBUTE_FALLBACK` is also configured, otherwise a loose regex could accidentally match fallback values such as `ESCOSIRENCourant`.

Exact `LINK` entries remain useful for exceptions:

```php
$mapping['APP_NAME']['LINK']['0180847Y']='https://specific-school.example.org';
```

## Fallback Attribute

`USER_ATTRIBUTE_FALLBACK`

Optional secondary CAS attribute used if `USER_ATTRIBUTE` does not produce a redirect target.

Example:

```php
$mapping['APP_NAME']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['APP_NAME']['USER_ATTRIBUTE_FALLBACK']='ESCOSIRENCourant';
$mapping['APP_NAME']['LINK']=array();
```

## Default Link

`DEFAULT_LINK`

Fallback target URL used when no specific `LINK` entry matches in supported branches.

Example:

```php
$mapping['APP_NAME']['DEFAULT_LINK']='https://default.example.org';
```

For attribute-based mappings, the redirect resolution order is:

1. explicit `LINK` match for the application
2. `REGEX_LINK` match for the application
3. application `DEFAULT_LINK`
4. standard access problem message

For domain-based mappings, the redirect resolution order is:

1. explicit `LINK` match for the application, when `USER_ATTRIBUTE`/`USER_ATTRIBUTE_FALLBACK` and `LINK` are also configured
2. `REGEX_LINK` match for the application, when `USER_ATTRIBUTE`/`USER_ATTRIBUTE_FALLBACK` are configured
3. `DOMAIN_MAP` match for the current domain
4. application `DEFAULT_LINK`
5. standard access problem message

The redirector treats these values as no redirect target:

```php
$mapping['APP_NAME']['LINK']['0000000A']='';
$mapping['APP_NAME']['LINK']['0000000B']='null';
```

## Access Filter

Use `FILTER` to allow redirection only for users whose CAS attributes match configured regex rules.

`FILTER.USER_ATTRIBUTE`

CAS attribute checked by the filter.

`FILTER.REGEX`

Regular expression applied to the selected CAS attribute. If the attribute is an array, at least one value must match.

Example:

```php
$mapping['APP_NAME']['FILTER']['USER_ATTRIBUTE']='ENTPersonProfils';
$mapping['APP_NAME']['FILTER']['REGEX']='/^((National_ENS)|(National_DOC)|(National_ELV))/';
```

### Compound Filter Rules

To combine several conditions, configure `FILTER` with `OPERATOR` and `RULES`. Supported operators are `AND` and `OR`. Rules can be nested to express parentheses.

Each leaf rule uses the same `USER_ATTRIBUTE`/`REGEX` pair as the historical form. The historical single-condition form remains supported.

Example: access requires a teacher profile AND either a matching UAI or a matching fallback SIREN/SIRET:

```php
$mapping['APP_NAME']['FILTER']=array(
  'OPERATOR' => 'AND',
  'RULES' => array(
    array(
      'USER_ATTRIBUTE' => 'ENTPersonProfils',
      'REGEX'          => '/^National_ENS$/',
    ),
    array(
      'OPERATOR' => 'OR',
      'RULES' => array(
        array(
          'USER_ATTRIBUTE' => 'ESCOUAICourant',
          'REGEX'          => '/^018[0-9]{4}[A-Z]$/i',
        ),
        array(
          'USER_ATTRIBUTE' => 'ESCOSIRENCourant',
          'REGEX'          => '/^19180585200081$/',
        ),
      ),
    ),
  ),
);
```

## URL Replacement

Use `REPLACE` when target URLs contain a placeholder based on a CAS attribute.

`REPLACE.USER_ATTRIBUTE`

CAS attribute used as the replacement value. The placeholder format is `%ATTRIBUTE_NAME%`.

Example:

```php
$mapping['APP_NAME']['DEFAULT_LINK']='https://%ESCOUAICourant%.example.org';
$mapping['APP_NAME']['REPLACE']['USER_ATTRIBUTE']='ESCOUAICourant';
```

`REPLACE.VALUE_TO_LOWERCASE`

Controls the case applied to the CAS attribute value before replacement.

By default, the value is lowercased. If `VALUE_TO_LOWERCASE` is set to `false`, the value is uppercased.

Current behavior:

- option not defined: the replacement value is lowercased
- `VALUE_TO_LOWERCASE=true`: the replacement value is lowercased
- `VALUE_TO_LOWERCASE=false`: the replacement value is uppercased

There is currently no option to keep the original CAS attribute casing unchanged.

Example:

```php
$mapping['APP_NAME']['REPLACE']['VALUE_TO_LOWERCASE']=false;
```

Replacement is applied centrally before redirecting, so it works for URLs coming from `LINK`, `DEFAULT_LINK`, and `DOMAIN_MAP`.

## Domain-Based Mapping

Use `DOMAIN` and `DOMAIN_MAP` when the redirect depends on the current HTTP host rather than a CAS attribute.

This is the preferred model when the application URL is controlled and already identifies the user context, for example one service URL per ENT domain. Establishment-specific `LINK` entries can still be added to override the domain result for known exceptions.

`DOMAIN`

Current value to test. It is usually set from `$_SERVER['SERVER_NAME']`.

Example:

```php
$mapping['APP_NAME']['DOMAIN']=$_SERVER['SERVER_NAME'];
```

`DOMAIN_MAP`

Map of domain values to target URLs.

Example:

```php
$mapping['APP_NAME']['DOMAIN_MAP']=array();
$mapping['APP_NAME']['DOMAIN_MAP']['site-a.example.org']='https://service-a.example.org';
$mapping['APP_NAME']['DOMAIN_MAP']['site-b.example.org']='https://service-b.example.org';
```

In short:

- `DOMAIN` is the current value being tested.
- `DOMAIN_MAP` is the lookup table that maps this value to a redirect URL.

This is similar to `USER_ATTRIBUTE` and `LINK`, but the selected value comes from the request domain instead of CAS attributes.

If the application also defines `USER_ATTRIBUTE`, `USER_ATTRIBUTE_FALLBACK`, `LINK`, or `REGEX_LINK`, attribute-based overrides are checked first. This allows an application to use domain routing as the normal behavior and keep explicit establishment overrides for exceptions.

Example:

```php
$mapping['APP_NAME']['DOMAIN']=$_SERVER['SERVER_NAME'];
$mapping['APP_NAME']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['APP_NAME']['USER_ATTRIBUTE_FALLBACK']='ESCOSIRENCourant';
$mapping['APP_NAME']['LINK']=array();
$mapping['APP_NAME']['LINK']['0000000A']='https://specific-school.example.org';
$mapping['APP_NAME']['DOMAIN_MAP']=array();
$mapping['APP_NAME']['DOMAIN_MAP']['site-a.example.org']='https://service-a.example.org';
$mapping['APP_NAME']['DOMAIN_MAP']['site-b.example.org']='https://service-b.example.org';
$mapping['APP_NAME']['DEFAULT_LINK']='https://default.example.org';
```

If no `LINK` override matches and the current domain is not present in `DOMAIN_MAP`, the redirector uses `DEFAULT_LINK` when it is configured. If neither a matching override, domain, nor `DEFAULT_LINK` is available, the user receives the standard access problem message.

## Examples

### Mapping By CAS Profile

```php
$mapping['PROFILE_APP']['USER_ATTRIBUTE']='ENTPersonProfils';
$mapping['PROFILE_APP']['LINK']=array();
$mapping['PROFILE_APP']['LINK']['National_ELV']='https://student.example.org';
$mapping['PROFILE_APP']['LINK']['National_ENS']='https://teacher.example.org';
```

### Mapping By Establishment UAI

```php
$mapping['UAI_APP']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['UAI_APP']['LINK']=array();
$mapping['UAI_APP']['LINK']['0000000A']='https://school-a.example.org';
$mapping['UAI_APP']['LINK']['0000000B']='https://school-b.example.org';
```

### Mapping With Attribute Fallback

```php
$mapping['FALLBACK_APP']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['FALLBACK_APP']['USER_ATTRIBUTE_FALLBACK']='ESCOSIRENCourant';
$mapping['FALLBACK_APP']['DEFAULT_LINK']='https://default.example.org';
$mapping['FALLBACK_APP']['LINK']=array();
```

### Mapping With Placeholder Replacement

```php
$mapping['REPLACE_APP']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['REPLACE_APP']['DEFAULT_LINK']='https://%ESCOUAICourant%.example.org';
$mapping['REPLACE_APP']['LINK']=array();
$mapping['REPLACE_APP']['REPLACE']['USER_ATTRIBUTE']='ESCOUAICourant';
```

### Mapping By Domain

```php
$mapping['DOMAIN_APP']['DOMAIN']=$_SERVER['SERVER_NAME'];
$mapping['DOMAIN_APP']['DEFAULT_LINK']='https://default.example.org';
$mapping['DOMAIN_APP']['DOMAIN_MAP']=array();
$mapping['DOMAIN_APP']['DOMAIN_MAP']['site-a.example.org']='https://service-a.example.org';
$mapping['DOMAIN_APP']['DOMAIN_MAP']['site-b.example.org']='https://service-b.example.org';
```

### Mapping By Domain With Establishment Overrides

```php
$mapping['DOMAIN_OVERRIDE_APP']['DOMAIN']=$_SERVER['SERVER_NAME'];
$mapping['DOMAIN_OVERRIDE_APP']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['DOMAIN_OVERRIDE_APP']['USER_ATTRIBUTE_FALLBACK']='ESCOSIRENCourant';
$mapping['DOMAIN_OVERRIDE_APP']['LINK']=array();
$mapping['DOMAIN_OVERRIDE_APP']['LINK']['0000000A']='https://specific-school.example.org';
$mapping['DOMAIN_OVERRIDE_APP']['REGEX_LINK']=array();
$mapping['DOMAIN_OVERRIDE_APP']['REGEX_LINK']['/^000[0-9]{4}[A-Z]$/i']='https://regex-school.example.org';
$mapping['DOMAIN_OVERRIDE_APP']['DOMAIN_MAP']=array();
$mapping['DOMAIN_OVERRIDE_APP']['DOMAIN_MAP']['site-a.example.org']='https://service-a.example.org';
$mapping['DOMAIN_OVERRIDE_APP']['DOMAIN_MAP']['site-b.example.org']='https://service-b.example.org';
$mapping['DOMAIN_OVERRIDE_APP']['DEFAULT_LINK']='https://default.example.org';
```

### Mapping By Establishment Prefix

```php
$mapping['PREFIX_APP']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['PREFIX_APP']['DEFAULT_LINK']='https://default.example.org';
$mapping['PREFIX_APP']['LINK']=array();
$mapping['PREFIX_APP']['LINK']['0180847Y']='https://specific-school.example.org';
$mapping['PREFIX_APP']['REGEX_LINK']=array();
$mapping['PREFIX_APP']['REGEX_LINK']['/^018[0-9]{4}[A-Z]$/i']='https://department-18.example.org';
$mapping['PREFIX_APP']['REGEX_LINK']['/^028[0-9]{4}[A-Z]$/i']='https://department-28.example.org';
$mapping['PREFIX_APP']['REGEX_LINK']['/^036[0-9]{4}[A-Z]$/i']='https://department-36.example.org';
```

### Mapping With Access Filter

```php
$mapping['FILTERED_APP']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['FILTERED_APP']['DEFAULT_LINK']='https://service.example.org';
$mapping['FILTERED_APP']['LINK']=array();
$mapping['FILTERED_APP']['FILTER']['USER_ATTRIBUTE']='ENTPersonProfils';
$mapping['FILTERED_APP']['FILTER']['REGEX']='/^((National_ENS)|(National_DOC))/';
```
