<?php

/*
 * This file is part of nonfriedchips/formatting-pro.
 *
 * Copyright (c) FriendsOfFlarum, Zephyr Isle, and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephyrisle\FormattingPro\Listeners;

use Flarum\Settings\Event\Saved;

class ClearCache
{
    public function handle(Saved $event): void
    {
        foreach ($event->settings as $key => $setting) {
            if (strpos($key, 'zephyrisle-formatting-pro.plugin.') === 0) {
                resolve('flarum.formatter')->flush();

                return;
            }
        }
    }
}
