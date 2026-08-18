# Configuration

This document describes the main configuration variables used by ESCO Apps Redirector.

Real configuration files are private and must not be committed. Use the `*.example.php` files as templates.

## Global Variables

`$LOG_LVL`

Minimum log level written by `log_action()`. Supported values are `TRACE`, `DEBUG`, `INFO`, `WARN`, and `ERROR`. The comparison is case-sensitive.

Example:

```php
$LOG_LVL='INFO';
```

`$DEV_MOD`

Enables development output and prevents real redirects. In dev mode, `do_redirect()` prints the target header instead of sending it.

Use `false` for production.

Example:

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

The redirector treats these values as no redirect target:

```php
$mapping['APP_NAME']['LINK']['0000000A']='';
$mapping['APP_NAME']['LINK']['0000000B']='null';
```

## Access Filter

Use `FILTER` to allow redirection only for users whose CAS attribute matches a regex.

`FILTER.USER_ATTRIBUTE`

CAS attribute checked by the filter.

`FILTER.REGEX`

Regular expression applied to the selected CAS attribute. If the attribute is an array, at least one value must match.

Example:

```php
$mapping['APP_NAME']['FILTER']['USER_ATTRIBUTE']='ENTPersonProfils';
$mapping['APP_NAME']['FILTER']['REGEX']='/^((National_ENS)|(National_DOC)|(National_ELV))/';
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

Example:

```php
$mapping['APP_NAME']['REPLACE']['VALUE_TO_LOWERCASE']=false;
```

Replacement is applied centrally before redirecting, so it works for URLs coming from `LINK`, `DEFAULT_LINK`, and `DOMAIN_MAP`.

## Domain-Based Mapping

Use `DOMAIN` and `DOMAIN_MAP` when the redirect depends on the current HTTP host rather than a CAS attribute.

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

If the current domain is not present in `DOMAIN_MAP`, the redirector uses `DEFAULT_LINK` when it is configured. If neither a matching domain nor `DEFAULT_LINK` is available, the user receives the standard access problem message.

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

### Mapping With Access Filter

```php
$mapping['FILTERED_APP']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['FILTERED_APP']['DEFAULT_LINK']='https://service.example.org';
$mapping['FILTERED_APP']['LINK']=array();
$mapping['FILTERED_APP']['FILTER']['USER_ATTRIBUTE']='ENTPersonProfils';
$mapping['FILTERED_APP']['FILTER']['REGEX']='/^((National_ENS)|(National_DOC))/';
```
