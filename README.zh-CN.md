# Formatting Pro for Flarum 1

[English](README.md) | 简体中文

[![许可证](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Flarum](https://img.shields.io/badge/flarum-1.8+-orange.svg)](https://flarum.org/)

这是 [Zephyr-Isle/formatting-pro](https://github.com/Zephyr-Isle/formatting-pro)
的 Flarum 1.8 兼容版本，为论坛增加直链音频播放器、网易云音乐嵌入和哔哩哔哩视频嵌入功能。

本扩展依赖并补充 `fof/formatting`，不会替换 FoF Formatting，也不会移除其现有功能。

## 功能

- 将 MP3、M4A、OGG/OGA、WAV、FLAC、AAC 和 Opus 直链转换成 HTML5 音频播放器。
- 嵌入网易云音乐的歌曲、专辑和歌单链接。
- 嵌入哔哩哔哩 BV、AV 视频链接，并支持通过 `p` 参数指定分 P。
- 在 Flarum 管理后台添加可选的自定义音频 CSS。
- 格式化选项变更后自动清理 TextFormatter 缓存。

## 环境要求

- Flarum 1.8.x
- PHP 8.0 或更高版本
- `fof/formatting` 1.1.x

上游 `zephyrisle/formatting-pro` 软件包面向 Flarum 2.x。本软件包仅用于 Flarum 1.x。

## 安装

目前可以通过 Composer 直接从本 GitHub 仓库安装：

```sh
composer config repositories.formatting-pro vcs https://github.com/nonfriedchips/formatting-pro.git
composer require nonfriedchips/formatting-pro:^1.0
php flarum cache:clear
```

安装完成后，在 Flarum 管理后台启用 **Formatting Pro for Flarum 1**，然后打开扩展设置页选择需要启用的格式化功能。

如果本软件包以后登记到 Packagist，第一条 `composer config` 命令将不再需要。

## 使用方法

在帖子中发送受支持的链接即可：

```text
https://example.com/audio/episode.mp3
https://music.163.com/#/song?id=123456
https://www.bilibili.com/video/BV1xx411c7mD?p=2
```

Flarum 会将已经保存的帖子存储为解析后的 TextFormatter XML。如果希望旧帖子中的链接使用新格式重新解析，请编辑并再次保存该帖子。

## 更新

```sh
composer update nonfriedchips/formatting-pro -W
php flarum cache:clear
```

## 安全说明

- 音频源仅支持带有指定扩展名的 HTTP(S) URL，并会经过 TextFormatter URL 过滤器处理。
- 媒体标识符会先通过正则表达式限制，再被写入固定的 HTTPS 嵌入地址。
- 自定义 CSS 仅供管理员设置，并会按原内容注入页面。请只向可信管理员授予 Flarum 设置权限。
- 浏览器或反向代理的内容安全策略必须允许来自 `https://music.163.com` 和 `https://player.bilibili.com` 的框架内容。

## 兼容性与支持

为了便于迁移配置，本兼容版本保留了上游设置键和 PHP 命名空间。如需报告问题，请前往
[nonfriedchips/formatting-pro](https://github.com/nonfriedchips/formatting-pro/issues)。

## 致谢

- [FriendsOfFlarum/formatting](https://github.com/FriendsOfFlarum/formatting)
- [Zephyr-Isle/formatting-pro](https://github.com/Zephyr-Isle/formatting-pro)

这是一个独立维护的兼容版本，保留了上游 Git 历史并采用 MIT 许可证。它不是 FriendsOfFlarum 官方扩展。
