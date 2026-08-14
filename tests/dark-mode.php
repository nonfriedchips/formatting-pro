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

$lightCss = compileForumCss(false);
$darkCss = compileForumCss(true);

assertCss(strpos($darkCss, 'color-scheme: dark') !== false, 'direct audio did not opt into dark native controls');
assertCss(strpos($darkCss, 'backdrop-filter: invert(90%) hue-rotate(180deg)') !== false, 'NetEase dark mode treatment was not compiled');
assertCss(strpos($darkCss, 'iframe[src*="height=66"]') !== false, 'NetEase song layout was not targeted');
assertCss(strpos($darkCss, 'iframe[src*="height=430"]') !== false, 'NetEase album and playlist layouts were not targeted');
assertCss(strpos($darkCss, 'border: 10px solid var(--body-bg)') !== false, 'NetEase white iframe margin was not covered');
assertCss(
    preg_match('/data-s9e-mediaembed="netease"\] iframe\s*\{[^}]*\bfilter:/s', $darkCss) === 0,
    'NetEase dark mode still filters the entire iframe, including its cover'
);
assertCss(strpos($lightCss, 'color-scheme: dark') === false, 'direct audio dark controls leaked into light mode');
assertCss(strpos($lightCss, 'backdrop-filter: invert(90%) hue-rotate(180deg)') === false, 'NetEase dark mode treatment leaked into light mode');
assertCss(strpos($lightCss, 'border: 10px solid var(--body-bg)') === false, 'NetEase dark frame leaked into light mode');

fwrite(STDOUT, 'Dark mode stylesheet tests passed.'.PHP_EOL);
