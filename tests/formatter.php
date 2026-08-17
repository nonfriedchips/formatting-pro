<?php

declare(strict_types=1);

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Settings\Event\Saved;
use FoF\Formatting\ConfigureFormatterPlugins;
use Illuminate\Container\Container;
use s9e\TextFormatter\Configurator;
use Zephyrisle\FormattingPro\ConfigureFormatter;
use Zephyrisle\FormattingPro\Listeners\ClearCache;

require dirname(__DIR__).'/vendor/autoload.php';

final class TestSettingsRepository implements SettingsRepositoryInterface
{
    /** @var array<string, mixed> */
    private array $settings;

    /** @param array<string, mixed> $settings */
    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function all(): array
    {
        return $this->settings;
    }

    public function get($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function set($key, $value)
    {
        $this->settings[$key] = $value;
    }

    public function delete($keyLike)
    {
        unset($this->settings[$keyLike]);
    }
}

/**
 * @param array<string, mixed> $settings
 * @return array{0: s9e\TextFormatter\Parser, 1: s9e\TextFormatter\Renderer}
 */
function formatter(array $settings = []): array
{
    $configurator = new Configurator();
    (new ConfigureFormatter(new TestSettingsRepository($settings)))($configurator);
    $objects = $configurator->finalize();

    return [$objects['parser'], $objects['renderer']];
}

/**
 * Verify both possible extension callback orders used by Flarum.
 *
 * @return array{0: s9e\TextFormatter\Parser, 1: s9e\TextFormatter\Renderer}
 */
function formatterWithFoF(bool $formattingProFirst): array
{
    $configurator = new Configurator();
    $settings = new TestSettingsRepository([
        'fof-formatting.plugin.mediaembed' => '1',
    ]);
    $formattingPro = new ConfigureFormatter($settings);
    $fofFormatting = new ConfigureFormatterPlugins($settings);

    if ($formattingProFirst) {
        $formattingPro($configurator);
        $fofFormatting($configurator);
    } else {
        $fofFormatting($configurator);
        $formattingPro($configurator);
    }

    $objects = $configurator->finalize();

    return [$objects['parser'], $objects['renderer']];
}

/** @param mixed $condition */
function assertTrue($condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

[$parser, $renderer] = formatter();

$cases = [
    'audio' => [
        'url' => 'https://cdn.example.com/podcast/episode.mp3?token=abc&download=1',
        'xml' => '<AUTOAUDIO',
        'html' => '<audio',
        'src' => 'https://cdn.example.com/podcast/episode.mp3?token=abc&amp;download=1',
    ],
    'netease song' => [
        'url' => 'https://music.163.com/#/song?id=123456',
        'xml' => '<NETEASE',
        'html' => 'music.163.com/outchain/player?type=2',
        'src' => 'id=123456',
    ],
    'netease album' => [
        'url' => 'https://music.163.com/album?id=654321',
        'xml' => '<NETEASE',
        'html' => 'music.163.com/outchain/player?type=1',
        'src' => 'id=654321',
    ],
    'netease playlist' => [
        'url' => 'https://music.163.com/playlist/789012',
        'xml' => '<NETEASE',
        'html' => 'music.163.com/outchain/player?type=0',
        'src' => 'id=789012',
    ],
    'bilibili bv' => [
        'url' => 'https://www.bilibili.com/video/BV1xx411c7mD?p=2&utm_source=test',
        'xml' => '<BILIBILI',
        'html' => 'player.bilibili.com/player.html?isOutside=true',
        'src' => 'bvid=BV1xx411c7mD&amp;p=2',
    ],
    'bilibili av' => [
        'url' => 'https://bilibili.com/video/av170001?from=search&p=3',
        'xml' => '<BILIBILI',
        'html' => 'player.bilibili.com/player.html?isOutside=true',
        'src' => 'aid=170001&amp;p=3',
    ],
];

$renderedCases = [];

foreach ($cases as $name => $case) {
    $xml = $parser->parse($case['url']);
    $html = $renderer->render($xml);
    $renderedCases[$name] = $html;

    assertTrue(strpos($xml, $case['xml']) !== false, "{$name} was not parsed into the expected tag: {$xml}");
    assertTrue(strpos($html, $case['html']) !== false, "{$name} rendered the wrong player: {$html}");
    assertTrue(strpos($html, $case['src']) !== false, "{$name} lost its identifier or URL: {$html}");
}

assertTrue(
    preg_match('/<audio[^>]*\\sautoplay(?:=|\\s|>)/i', $renderedCases['audio']) === 0,
    'direct audio autoplay was enabled by default'
);
assertTrue(strpos($renderedCases['netease song'], 'auto=0') !== false, 'NetEase autoplay was enabled by default');
assertTrue(strpos($renderedCases['bilibili bv'], 'autoplay=0') !== false, 'Bilibili autoplay was enabled by default');

foreach (['netease song', 'netease album', 'netease playlist'] as $name) {
    assertTrue(
        strpos($renderedCases[$name], 'allow="autoplay"') !== false,
        "{$name} did not delegate autoplay permission"
    );
}

[$neteaseAutoplayParser, $neteaseAutoplayRenderer] = formatter([
    'zephyrisle-formatting-pro.autoplay.netease' => '1',
]);

$neteaseAutoplayAudio = $neteaseAutoplayRenderer->render($neteaseAutoplayParser->parse($cases['audio']['url']));
$neteaseAutoplayNetEase = $neteaseAutoplayRenderer->render($neteaseAutoplayParser->parse($cases['netease song']['url']));
$neteaseAutoplayBilibili = $neteaseAutoplayRenderer->render($neteaseAutoplayParser->parse($cases['bilibili bv']['url']));

assertTrue(
    preg_match('/<audio[^>]*\\sautoplay(?:=|\\s|>)/i', $neteaseAutoplayAudio) === 0,
    'enabling NetEase autoplay also enabled direct audio autoplay'
);
assertTrue(strpos($neteaseAutoplayNetEase, 'auto=1') !== false, 'NetEase autoplay setting was ignored');
assertTrue(strpos($neteaseAutoplayBilibili, 'autoplay=0') !== false, 'NetEase autoplay also enabled Bilibili autoplay');

[$bilibiliAutoplayParser, $bilibiliAutoplayRenderer] = formatter([
    'zephyrisle-formatting-pro.autoplay.bilibili' => '1',
]);

$bilibiliAutoplayAudio = $bilibiliAutoplayRenderer->render($bilibiliAutoplayParser->parse($cases['audio']['url']));
$bilibiliAutoplayNetEase = $bilibiliAutoplayRenderer->render($bilibiliAutoplayParser->parse($cases['netease song']['url']));
$bilibiliAutoplayBilibili = $bilibiliAutoplayRenderer->render($bilibiliAutoplayParser->parse($cases['bilibili bv']['url']));

assertTrue(
    preg_match('/<audio[^>]*\\sautoplay(?:=|\\s|>)/i', $bilibiliAutoplayAudio) === 0,
    'enabling Bilibili autoplay also enabled direct audio autoplay'
);
assertTrue(strpos($bilibiliAutoplayNetEase, 'auto=0') !== false, 'Bilibili autoplay also enabled NetEase autoplay');
assertTrue(strpos($bilibiliAutoplayBilibili, 'autoplay=1') !== false, 'Bilibili autoplay setting was ignored');

[$autoAudioAutoplayParser, $autoAudioAutoplayRenderer] = formatter([
    'zephyrisle-formatting-pro.autoplay.autoaudio' => '1',
]);

$autoAudioAutoplayAudio = $autoAudioAutoplayRenderer->render($autoAudioAutoplayParser->parse($cases['audio']['url']));
$autoAudioAutoplayNetEase = $autoAudioAutoplayRenderer->render($autoAudioAutoplayParser->parse($cases['netease song']['url']));
$autoAudioAutoplayBilibili = $autoAudioAutoplayRenderer->render($autoAudioAutoplayParser->parse($cases['bilibili bv']['url']));

assertTrue(
    preg_match('/<audio[^>]*\\sautoplay(?:=|\\s|>)/i', $autoAudioAutoplayAudio) === 1,
    'direct audio autoplay setting was ignored'
);
assertTrue(strpos($autoAudioAutoplayNetEase, 'auto=0') !== false, 'direct audio autoplay also enabled NetEase autoplay');
assertTrue(strpos($autoAudioAutoplayBilibili, 'autoplay=0') !== false, 'direct audio autoplay also enabled Bilibili autoplay');

foreach ([true, false] as $formattingProFirst) {
    [$combinedParser, $combinedRenderer] = formatterWithFoF($formattingProFirst);
    $customHtml = $combinedRenderer->render($combinedParser->parse($cases['bilibili bv']['url']));
    $youtubeHtml = $combinedRenderer->render($combinedParser->parse('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
    $order = $formattingProFirst ? 'Formatting Pro before FoF' : 'FoF before Formatting Pro';

    assertTrue(strpos($customHtml, 'player.bilibili.com') !== false, "{$order}: custom embeds were lost");
    assertTrue(strpos($youtubeHtml, 'youtube-nocookie.com') !== false, "{$order}: FoF embeds were lost");
}

[$disabledParser] = formatter([
    'zephyrisle-formatting-pro.plugin.autoaudio' => '0',
    'zephyrisle-formatting-pro.plugin.netease' => '0',
    'zephyrisle-formatting-pro.plugin.bilibili' => '0',
]);

assertTrue(strpos($disabledParser->parse($cases['audio']['url']), '<AUTOAUDIO') === false, 'disabled AutoAudio still parsed URLs');
assertTrue(strpos($disabledParser->parse($cases['netease song']['url']), '<NETEASE') === false, 'disabled NetEase still parsed URLs');
assertTrue(strpos($disabledParser->parse($cases['bilibili bv']['url']), '<BILIBILI') === false, 'disabled Bilibili still parsed URLs');

$ordinaryBilibiliPage = 'https://www.bilibili.com/account?p=2';
assertTrue(strpos($parser->parse($ordinaryBilibiliPage), '<BILIBILI') === false, 'a non-video Bilibili page was embedded');

$malicious = 'https://cdn.example.com/file.mp3&quot; onerror=&quot;alert(1)';
$html = $renderer->render($parser->parse($malicious));
assertTrue(preg_match('/<audio[^>]+onerror=/i', $html) === 0, 'audio URL was able to inject an HTML attribute');

$previousContainer = Container::getInstance();
$testContainer = new Container();
$formatterCache = new class() {
    public bool $flushed = false;

    public function flush(): void
    {
        $this->flushed = true;
    }
};
$forumJsCache = new class() {
    public bool $flushed = false;

    public function flush(): void
    {
        $this->flushed = true;
    }
};
$forumAssets = new class($forumJsCache) {
    private object $js;

    public function __construct(object $js)
    {
        $this->js = $js;
    }

    public function makeJs(): object
    {
        return $this->js;
    }
};

$testContainer->instance('flarum.formatter', $formatterCache);
$testContainer->instance('flarum.assets.forum', $forumAssets);
Container::setInstance($testContainer);

$cacheListener = new ClearCache();
$cacheListener->handle(new Saved(['unrelated.setting' => '1']));
assertTrue(! $formatterCache->flushed, 'an unrelated setting flushed the formatter cache');
assertTrue(! $forumJsCache->flushed, 'an unrelated setting flushed the forum JS cache');

$cacheListener->handle(new Saved(['zephyrisle-formatting-pro.autoplay.bilibili' => '1']));
assertTrue($formatterCache->flushed, 'changing autoplay did not flush the formatter cache');
assertTrue($forumJsCache->flushed, 'changing autoplay did not flush the forum JS cache');

$formatterCache->flushed = false;
$forumJsCache->flushed = false;
$cacheListener->handle(new Saved(['zephyrisle-formatting-pro.plugin.netease' => '0']));
assertTrue($formatterCache->flushed, 'changing a plugin did not flush the formatter cache');
assertTrue($forumJsCache->flushed, 'changing a plugin did not flush the forum JS cache');

Container::setInstance($previousContainer);

fwrite(STDOUT, 'Formatter integration tests passed.'.PHP_EOL);
