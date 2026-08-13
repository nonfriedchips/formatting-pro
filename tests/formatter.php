<?php

declare(strict_types=1);

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Formatting\ConfigureFormatterPlugins;
use s9e\TextFormatter\Configurator;
use Zephyrisle\FormattingPro\ConfigureFormatter;

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

foreach ($cases as $name => $case) {
    $xml = $parser->parse($case['url']);
    $html = $renderer->render($xml);

    assertTrue(strpos($xml, $case['xml']) !== false, "{$name} was not parsed into the expected tag: {$xml}");
    assertTrue(strpos($html, $case['html']) !== false, "{$name} rendered the wrong player: {$html}");
    assertTrue(strpos($html, $case['src']) !== false, "{$name} lost its identifier or URL: {$html}");
}

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

fwrite(STDOUT, 'Formatter integration tests passed.'.PHP_EOL);
