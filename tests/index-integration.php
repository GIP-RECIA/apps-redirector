<?php
/**
 * Integration tests for index.php orchestration using CI-only configuration and
 * a fake phpCAS implementation. Each scenario runs in a child process because
 * the controller deliberately exits after producing its response.
 */

declare(strict_types=1);
error_reporting(E_ALL);

$tests = 0;
$fails = 0;

function access_denied_page(): string
{
    return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Accès refusé</title><script type="text/javascript" src="/resource-server/webjars/gip-recia__ui-webcomponents/dist/r-header.js"></script><script type="text/javascript" src="/resource-server/webjars/gip-recia__ui-webcomponents/dist/r-page-layout.js"></script><script type="text/javascript" src="/resource-server/webjars/gip-recia__ui-webcomponents/dist/r-footer.js"></script><style>html{-webkit-text-size-adjust:100%;box-sizing:border-box;-moz-tab-size:4;tab-size:4;word-break:normal}*,:after,:before{background-repeat:no-repeat;box-sizing:inherit}:after,:before{text-decoration:inherit;vertical-align:inherit}*{margin:0;padding:0}hr{color:inherit;height:0;overflow:visible}details,main{display:block}summary{display:list-item}small{font-size:80%}[hidden]{display:none}abbr[title]{border-bottom:none;text-decoration:underline;text-decoration:underline dotted}a{background-color:transparent}a:active,a:hover{outline-width:0}code,kbd,pre,samp{font-family:monospace,monospace}pre{font-size:1em}b,strong{font-weight:bolder}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-0.25em}sup{top:-0.5em}table{border-color:inherit;text-indent:0}iframe{border-style:none}[aria-busy=true]{cursor:progress}[aria-controls]{cursor:pointer}input{border-radius:0}[type=number]::-webkit-inner-spin-button,[type=number]::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}[type=search]::-webkit-search-decoration{-webkit-appearance:none}textarea{overflow:auto;resize:vertical}button,input,optgroup,select,textarea{font:inherit}optgroup{font-weight:700}button{overflow:visible}button,select{text-transform:none}[role=button],[type=button],[type=reset],[type=submit],button{cursor:pointer}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button::-moz-focus-inner{border-style:none;padding:0}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button:-moz-focusring{outline:1px dotted ButtonText}[type=reset],[type=submit],button,html [type=button]{-webkit-appearance:button}button,input,select,textarea{background-color:transparent;border-style:none}a:focus,button:focus,input:focus,select:focus,textarea:focus{outline-width:0}select{-moz-appearance:none;-webkit-appearance:none}select::-ms-expand{display:none}select::-ms-value{color:currentColor}legend{border:0;color:inherit;display:table;max-width:100%;white-space:normal}::-webkit-file-upload-button{-webkit-appearance:button;color:inherit;font:inherit}[aria-disabled=true],[disabled]{cursor:default}img{border-style:none}progress{vertical-align:baseline}h6,.h6,h5,.h5,h4,.h4,h3,.h3,h2,.h2,h1,.h1{font-family:"Sora","sans-serif";color:var(--recia-body);margin-bottom:var(--recia-font-size-base)}h1,.h1{font-size:var(--recia-font-size-h1);font-weight:bold}h2,.h2{font-size:var(--recia-font-size-h2);font-weight:bold}h3,.h3{font-size:var(--recia-font-size-h3);font-weight:600}h4,.h4{font-size:var(--recia-font-size-h4);font-weight:600}h5,.h5{font-size:var(--recia-font-size-h5)}h6,.h6{font-size:var(--recia-font-size-h6)}.container-fluid,.container{width:100%;padding-right:16px;padding-left:16px;margin-right:auto;margin-left:auto}@media screen and (width >= 576px){.container{max-width:540px}}@media screen and (width >= 768px){.container{max-width:720px}}@media screen and (width >= 992px){.container{max-width:960px}}@media screen and (width >= 1200px){.container{max-width:1140px}}@media screen and (width >= 1400px){.container{max-width:1320px}}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-Bold.ttf") format("truetype");font-weight:bold;font-style:normal;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-BoldItalic.ttf") format("truetype");font-weight:bold;font-style:italic;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-Italic.ttf") format("truetype");font-weight:normal;font-style:italic;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-Medium.ttf") format("truetype");font-weight:500;font-style:normal;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-MediumItalic.ttf") format("truetype");font-weight:500;font-style:italic;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-Regular.ttf") format("truetype");font-weight:normal;font-style:normal;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-SemiBold.ttf") format("truetype");font-weight:600;font-style:normal;font-display:swap}@font-face{font-family:"Sora";src:url("/commun/fonts/Sora/static/Sora-Bold.ttf") format("truetype");font-weight:bold;font-style:normal;font-display:swap}@font-face{font-family:"Sora";src:url("/commun/fonts/Sora/static/Sora-Regular.ttf") format("truetype");font-weight:normal;font-style:normal;font-display:swap}@font-face{font-family:"Sora";src:url("/commun/fonts/Sora/static/Sora-SemiBold.ttf") format("truetype");font-weight:600;font-style:normal;font-display:swap}html,body{height:100%}html>body{background-color:var(--recia-body-bg);font-family:"DM Sans","sans-serif";font-style:normal;font-weight:normal;font-size:var(--recia-body-font-size);letter-spacing:0;color:var(--recia-body);display:grid;grid-template-rows:auto auto 1fr auto;grid-template-areas:"header" "alert" "main" "footer"}html>body>header{grid-area:header;height:var(--recia-header-height, 68px)}html>body>.alert{grid-area:alert}html>body>main{grid-area:main;padding-top:32px;padding-bottom:40px;display:grid;grid-auto-rows:min-content;row-gap:40px}html>body>main>*{min-width:0}html>body>footer{grid-area:footer}@media(width >= 768px){html>body>main{padding-bottom:60px;row-gap:60px}}</style></head><body><header><r-header template-api-path="/commun/portal_template_api.tpl.json" fname="ESCO Apps Redirector" navigation-drawer-visible></r-header></header><main><div class="container"><r-page-layout back-link="{&quot;name&quot;: &quot;Retour à l\'accueil&quot;,&quot;href&quot;: &quot;/portail&quot;,&quot;target&quot;: &quot;_self&quot;,&quot;rel&quot;: &quot;noopener noreferrer&quot;}" page-title="Accès refusé"><p>Vous n\'avez pas acc&egrave;s &agrave; ce service !</p></r-page-layout></div></main><footer><r-footer template-api-path="/commun/portal_template_api.tpl.json"></r-footer></footer></body></html>';
}

$accessProblem = access_denied_page();

function assertEquals($expected, $actual, string $label): void
{
    global $tests, $fails;
    $tests++;
    if ($expected !== $actual) {
        $fails++;
        fwrite(STDERR, "FAIL: $label\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n");
        return;
    }
    print "PASS: $label\n  resultat: " . var_export($actual, true) . "\n";
}

function run_index($application, array $attributes, string $domain): array
{
    $parts = array(
        'CI_CAS_ATTRIBUTES=' . escapeshellarg(json_encode($attributes)),
        'CI_SERVER_NAME=' . escapeshellarg($domain),
        'REDIRECTOR_CONFIG=ci/conf/conf.inc.php',
    );
    if ($application !== null) {
        $parts[] = 'CI_APPLICATION=' . escapeshellarg($application);
    }
    $parts[] = escapeshellarg(PHP_BINARY);
    $parts[] = 'ci/run-index.php';
    $output = array();
    $status = 0;
    exec(implode(' ', $parts) . ' 2>&1', $output, $status);
    return array($status, implode("\n", $output));
}

function assert_redirect(string $label, $application, array $attributes, string $domain, string $url): void
{
    list($status, $output) = run_index($application, $attributes, $domain);
    print "Attributs CAS testés: " . json_encode($attributes, JSON_UNESCAPED_SLASHES) . "\n";
    print "Domaine testé: $domain\n";
    assertEquals(0, $status, "$label: code de sortie");
    assertEquals('header("Location: "' . $url . '", true, 302);', $output, "$label: URL résolue");
}

assert_redirect(
    'Attribut: LINK exact prioritaire',
    'TEST_ROUTING',
    array('TestIdentifier' => '1234567A'),
    'redirector.test',
    'https://redirector.test/target-c'
);

assert_redirect(
    'Attribut: REGEX_LINK',
    'TEST_ROUTING',
    array('TestIdentifier' => '9870000A'),
    'redirector.test',
    'https://redirector.test/target-b'
);

assert_redirect(
    'Attribut: fallback après attribut principal absent',
    'TEST_ROUTING',
    array('TestFallbackIdentifier' => '19999999999999'),
    'redirector.test',
    'https://redirector.test/target-a'
);

assert_redirect(
    'Attribut: DEFAULT_LINK sans fallback',
    'TEST_DEFAULT',
    array('TestDefaultIdentifier' => '5550000A'),
    'redirector.test',
    'https://redirector.test/target-default'
);

assert_redirect(
    'Domaine: override LINK exact',
    'TEST_DOMAIN',
    array('TestDomainIdentifier' => '7771234A'),
    'redirector.test',
    'https://redirector.test/target-domain-exact'
);

assert_redirect(
    'Domaine: override REGEX_LINK',
    'TEST_DOMAIN',
    array('TestDomainIdentifier' => '7770000A'),
    'redirector.test',
    'https://redirector.test/target-domain-regex'
);

assert_redirect(
    'Domaine: DOMAIN_MAP',
    'TEST_DOMAIN',
    array(),
    'redirector.test',
    'https://service.test/domain-map'
);

assert_redirect(
    'Domaine: DEFAULT_LINK',
    'TEST_DOMAIN',
    array(),
    'unknown.test',
    'https://service.test/domain-default'
);

list($status, $output) = run_index('TEST_FILTER_DENIED', array('TestDeniedIdentifier' => '8880000A', 'TestAccess' => 'denied'), 'redirector.test');
assertEquals(0, $status, 'Filtre refusé: code de sortie');
assertEquals($accessProblem, $output, 'Filtre refusé: message d’accès');

list($status, $output) = run_index('TEST_REPLACE_FAILURE', array('TestReplaceIdentifier' => '9990000A'), 'redirector.test');
assertEquals(0, $status, 'REPLACE invalide: code de sortie');
assertEquals($accessProblem, $output, 'REPLACE invalide: message d’accès');

list($status, $output) = run_index('UNKNOWN', array(), 'redirector.test');
assertEquals(0, $status, 'Application inconnue: code de sortie');
assertEquals($accessProblem, $output, 'Application inconnue: message d’accès');

list($status, $output) = run_index(null, array(), 'redirector.test');
assertEquals(0, $status, 'Application absente: code de sortie');
assertEquals($accessProblem, $output, 'Application absente: message d’accès');

if ($fails === 0) {
    print "Tests d’intégration index: OK ($tests assertions)\n";
    exit(0);
}

fwrite(STDERR, "$fails assertion(s) en échec sur $tests\n");
exit(1);
