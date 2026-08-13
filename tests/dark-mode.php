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
assertCss(strpos($darkCss, 'filter: invert(90%) hue-rotate(180deg)') !== false, 'NetEase dark mode filter was not compiled');
assertCss(strpos($lightCss, 'color-scheme: dark') === false, 'direct audio dark controls leaked into light mode');
assertCss(strpos($lightCss, 'filter: invert(90%) hue-rotate(180deg)') === false, 'NetEase dark mode filter leaked into light mode');

fwrite(STDOUT, 'Dark mode stylesheet tests passed.'.PHP_EOL);
