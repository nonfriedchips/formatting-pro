<?php

/*
 * This file is part of nonfriedchips/formatting-pro.
 *
 * Copyright (c) FriendsOfFlarum, Zephyr Isle, and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephyrisle\FormattingPro;

use Flarum\Settings\SettingsRepositoryInterface;
use s9e\TextFormatter\Configurator;

class ConfigureFormatter
{
    private SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function __invoke(Configurator $configurator): void
    {
        if ($this->enabled('autoaudio')) {
            $this->configureAutoAudio($configurator, $this->autoplayEnabled('autoaudio'));
        }

        if ($this->enabled('netease')) {
            $this->configureNetEase($configurator, $this->autoplayEnabled('netease'));
        }

        if ($this->enabled('bilibili')) {
            $this->configureBilibili($configurator, $this->autoplayEnabled('bilibili'));
        }
    }

    private function enabled(string $plugin): bool
    {
        $value = $this->settings->get('zephyrisle-formatting-pro.plugin.'.$plugin, true);

        return $value === true || $value === 1 || $value === '1';
    }

    private function autoplayEnabled(string $plugin): bool
    {
        $value = $this->settings->get('zephyrisle-formatting-pro.autoplay.'.$plugin, false);

        return $value === true || $value === 1 || $value === '1';
    }

    private function configureAutoAudio(Configurator $configurator, bool $autoplay): void
    {
        $autoplayAttribute = $autoplay ? ' autoplay=""' : '';

        $configurator->Preg->replace(
            '#(?<src>https?://[^\\s<>"\']+\\.(?:mp3|m4a|ogg|oga|wav|flac|aac|opus)(?:[?\\#][^\\s<>"\']*)?)#i',
            '<audio class="FormattingPro-audio" controls="" preload="metadata"'.$autoplayAttribute.' src="$1">'
                .'<a href="$1" rel="nofollow ugc noopener">$1</a>'
                .'</audio>',
            'AUTOAUDIO'
        );
    }

    private function configureNetEase(Configurator $configurator, bool $autoplay): void
    {
        $autoplayValue = $autoplay ? '1' : '0';

        $configurator->MediaEmbed->add('netease', [
            'host' => ['music.163.com', 'y.music.163.com'],
            'extract' => [
                "!music\\.163\\.com/(?:#/|m/)?(?<mode>song|album|playlist)(?:/|\\?id=)(?<id>\\d+)!",
                "!music\\.163\\.com/(?<mode>song|album|playlist)/(?<id>\\d+)!",
                "!y\\.music\\.163\\.com/m/(?<mode>song|album|playlist)\\?id=(?<id>\\d+)!",
            ],
            'choose' => [
                'when' => [
                    [
                        'test' => "@mode = 'song'",
                        'iframe' => [
                            'width' => 380,
                            'height' => 86,
                            'max-width' => 380,
                            'src' => 'https://music.163.com/outchain/player?type=2&id={@id}&auto='.$autoplayValue.'&height=66',
                        ],
                    ],
                    [
                        'test' => "@mode = 'album'",
                        'iframe' => [
                            'width' => 380,
                            'height' => 450,
                            'max-width' => 380,
                            'src' => 'https://music.163.com/outchain/player?type=1&id={@id}&auto='.$autoplayValue.'&height=430',
                        ],
                    ],
                ],
                'otherwise' => [
                    'iframe' => [
                        'width' => 380,
                        'height' => 450,
                        'max-width' => 380,
                        'src' => 'https://music.163.com/outchain/player?type=0&id={@id}&auto='.$autoplayValue.'&height=430',
                    ],
                ],
            ],
        ]);
    }

    private function configureBilibili(Configurator $configurator, bool $autoplay): void
    {
        $configurator->MediaEmbed->add('bilibili', [
            'host' => ['bilibili.com', 'www.bilibili.com', 'm.bilibili.com'],
            'extract' => [
                "!bilibili\\.com/video/(?<bvid>BV[0-9A-Za-z]+)[^\\s<]*[?&]p=(?<page>\\d+)!",
                "!bilibili\\.com/video/(?<bvid>BV[0-9A-Za-z]+)!",
                "!bilibili\\.com/video/av(?<aid>\\d+)[^\\s<]*[?&]p=(?<page>\\d+)!i",
                "!bilibili\\.com/video/av(?<aid>\\d+)!i",
            ],
            'choose' => [
                'when' => [
                    [
                        'test' => '@bvid and @page',
                        'iframe' => $this->bilibiliIframe('bvid={@bvid}&p={@page}', $autoplay),
                    ],
                    [
                        'test' => '@bvid',
                        'iframe' => $this->bilibiliIframe('bvid={@bvid}', $autoplay),
                    ],
                    [
                        'test' => '@aid and @page',
                        'iframe' => $this->bilibiliIframe('aid={@aid}&p={@page}', $autoplay),
                    ],
                ],
                'otherwise' => [
                    'iframe' => $this->bilibiliIframe('aid={@aid}', $autoplay),
                ],
            ],
        ]);
    }

    /** @return array<string, int|string> */
    private function bilibiliIframe(string $query, bool $autoplay): array
    {
        return [
            'width' => 720,
            'height' => 405,
            'allow' => 'autoplay; fullscreen; picture-in-picture',
            'src' => 'https://player.bilibili.com/player.html?isOutside=true&'.$query.'&autoplay='.($autoplay ? '1' : '0'),
        ];
    }
}
