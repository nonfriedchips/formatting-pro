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

use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Settings\Event\Saved;

return [
    (new Extend\Frontend('forum'))
        ->css(__DIR__.'/resources/less/forum.less')
        ->content(function (Document $document) {
            $settings = resolve('flarum.settings');
            $customCss = (string) $settings->get('zephyrisle-formatting-pro.audio_css', '');

            if ($customCss) {
                $safeCss = str_ireplace('</style', '<\/style', $customCss);
                $document->head[] = '<style data-extension="zephyrisle-formatting-pro">'.$safeCss.'</style>';
            }
        }),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Formatter())
        ->configure(ConfigureFormatter::class),

    (new Extend\Settings())
        ->default('zephyrisle-formatting-pro.plugin.autoaudio', true)
        ->default('zephyrisle-formatting-pro.plugin.netease', true)
        ->default('zephyrisle-formatting-pro.plugin.bilibili', true)
        ->default('zephyrisle-formatting-pro.audio_css', ''),

    (new Extend\Event())
        ->listen(Saved::class, Listeners\ClearCache::class),
];
