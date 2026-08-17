# Formatting Pro for Flarum 1

English | [简体中文](README.zh-CN.md)

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Flarum](https://img.shields.io/badge/flarum-1.8+-orange.svg)](https://flarum.org/)

A Flarum 1.8-compatible port of
[Zephyr-Isle/formatting-pro](https://github.com/Zephyr-Isle/formatting-pro).
It adds direct audio players and embeds for NetEase Cloud Music and Bilibili.

This package complements, and depends on, `fof/formatting`. It does not replace
FoF Formatting or remove any of its existing plugins.

## Features

- Convert direct MP3, M4A, OGG/OGA, WAV, FLAC, AAC, and Opus URLs into HTML5
  audio players.
- Embed NetEase Cloud Music song, album, and playlist links.
- Embed Bilibili BV and AV video links, including multi-part `p` links.
- Configure autoplay separately for direct audio, NetEase Cloud Music, and Bilibili; all are disabled by default.
- Adapt NetEase embeds and direct audio controls to Flarum dark mode, including
  dynamic theme switching provided by FoF Night Mode.
- Add optional custom CSS from the Flarum administration panel.
- Clear the TextFormatter cache automatically when formatter options change.

## Requirements

- Flarum 1.8.x
- PHP 8.0 or newer
- `fof/formatting` 1.1.x

The upstream `zephyrisle/formatting-pro` package targets Flarum 2.x. Use this
package only for Flarum 1.x installations.

## Installation

This repository is installable directly through Composer:

```sh
composer config repositories.formatting-pro vcs https://github.com/nonfriedchips/formatting-pro.git
composer require nonfriedchips/formatting-pro:^1.0
php flarum cache:clear
```

Enable **Formatting Pro for Flarum 1** in the administration panel, then open
its settings page to select the formatters you want.

Direct audio, NetEase Cloud Music, and Bilibili have separate autoplay options.
All three are disabled by default. Browsers may still block audible autoplay
until the visitor interacts with the site, even when an autoplay option is enabled.

If the package is later registered on Packagist, the first `composer config`
command is no longer needed.

## Usage

Post a supported URL on its own or alongside other text:

```text
https://example.com/audio/episode.mp3
https://music.163.com/#/song?id=123456
https://www.bilibili.com/video/BV1xx411c7mD?p=2
```

Previously saved posts are stored as parsed TextFormatter XML. Edit and save an
old post once if you want newly supported URLs in that post to be reparsed.

## Updating

```sh
composer update nonfriedchips/formatting-pro -W
php flarum cache:clear
```

## Security notes

- Direct audio sources are restricted to HTTP(S) URLs with supported file
  extensions and pass through TextFormatter's URL filter.
- Media identifiers are restricted by regular expressions before being placed
  into fixed HTTPS embed URLs.
- Custom CSS is an administrator-only setting and is injected verbatim. Only
  trusted administrators should receive access to Flarum settings.
- Browser or reverse-proxy Content Security Policy rules must permit frames from
  `https://music.163.com` and `https://player.bilibili.com`.

## Compatibility and support

This compatibility port intentionally keeps the upstream setting keys and PHP
namespace so configuration can be migrated more easily. Report issues at
[nonfriedchips/formatting-pro](https://github.com/nonfriedchips/formatting-pro/issues).

## Credits

- [FriendsOfFlarum/formatting](https://github.com/FriendsOfFlarum/formatting)
- [Zephyr-Isle/formatting-pro](https://github.com/Zephyr-Isle/formatting-pro)

This independently maintained compatibility port retains the upstream Git
history and MIT license. It is not an official FriendsOfFlarum extension.
