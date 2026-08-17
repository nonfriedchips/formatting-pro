<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

function compileForumCss(bool $darkMode): string
{
    $parser = new Less_Parser();
    $parser->parse('@config-dark-mode: '.($darkMode ? 'true' : 'false').';');
    $parser->parseFile(dirname(__DIR__).'/resources/less/forum.less');

    return $parser->getCss();
}

/** @param mixed $condition */
function assertCss($condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function hasCssDeclaration(string $css, string $property, string $value): bool
{
    $valueParts = preg_split('/\s+/', trim($value));

    if ($valueParts === false) {
        return false;
    }

    $valuePattern = implode(
        '\\s+',
        array_map(static fn (string $part): string => preg_quote($part, '/'), $valueParts)
    );
    $pattern = '/(?:^|[;{])\s*'
        .preg_quote($property, '/')
        .'\s*:\s*'
        .$valuePattern
        .'\s*(?:;|})/';

    return preg_match($pattern, $css) === 1;
}

function hasNetEaseIframeFilter(string $css): bool
{
    return preg_match(
        '~\[data-s9e-mediaembed\s*=\s*(["\']?)netease\1\s*\](?:\s+|\s*>\s*)iframe\s*\{[^}]*(?<![-\w])filter\s*:~s',
        $css
    ) === 1;
}

$lightCss = compileForumCss(false);
$darkCss = compileForumCss(true);

$netEaseIframeFilterCases = [
    'double-quoted' => '[data-s9e-mediaembed="netease"] iframe { filter: invert(90%); }',
    'single-quoted' => "[data-s9e-mediaembed='netease'] iframe { filter : invert(90%); }",
    'unquoted' => '[data-s9e-mediaembed = netease] iframe{filter:invert(90%);}',
    'child-combinator' => '[data-s9e-mediaembed=netease]>iframe{filter:invert(90%);}',
];

foreach ($netEaseIframeFilterCases as $case => $css) {
    assertCss(hasNetEaseIframeFilter($css), "NetEase iframe filter detector missed {$case} case");
}

assertCss(
    ! hasNetEaseIframeFilter('[data-s9e-mediaembed=netease] iframe{backdrop-filter:invert(90%);}'),
    'NetEase iframe filter detector mistook backdrop-filter for filter'
);

assertCss(hasCssDeclaration($darkCss, 'color-scheme', 'dark'), 'direct audio did not opt into dark native controls');
assertCss(hasCssDeclaration($darkCss, 'backdrop-filter', 'invert(90%) hue-rotate(180deg)'), 'NetEase dark mode treatment was not compiled');
assertCss(strpos($darkCss, 'iframe[src*="height=66"]') !== false, 'NetEase song layout was not targeted');
assertCss(strpos($darkCss, 'iframe[src*="height=430"]') !== false, 'NetEase album and playlist layouts were not targeted');
assertCss(hasCssDeclaration($darkCss, 'border', '10px solid var(--body-bg)'), 'NetEase white iframe margin was not covered');
assertCss(
    ! hasNetEaseIframeFilter($darkCss),
    'NetEase dark mode still filters the entire iframe, including its cover'
);
assertCss(! hasCssDeclaration($lightCss, 'color-scheme', 'dark'), 'direct audio dark controls leaked into light mode');
assertCss(! hasCssDeclaration($lightCss, 'backdrop-filter', 'invert(90%) hue-rotate(180deg)'), 'NetEase dark mode treatment leaked into light mode');
assertCss(! hasCssDeclaration($lightCss, 'border', '10px solid var(--body-bg)'), 'NetEase dark frame leaked into light mode');

fwrite(STDOUT, 'Dark mode stylesheet tests passed.'.PHP_EOL);
