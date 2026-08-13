import app from 'flarum/admin/app';

app.initializers.add('zephyrisle-formatting-pro', () => {
  app.extensionData
    .for('nonfriedchips-formatting-pro')
    .registerSetting({
      setting: 'zephyrisle-formatting-pro.plugin.autoaudio',
      label: app.translator.trans('zephyrisle-formatting-pro.admin.plugins.autoaudio'),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'zephyrisle-formatting-pro.plugin.netease',
      label: app.translator.trans('zephyrisle-formatting-pro.admin.plugins.netease'),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'zephyrisle-formatting-pro.plugin.bilibili',
      label: app.translator.trans('zephyrisle-formatting-pro.admin.plugins.bilibili'),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'zephyrisle-formatting-pro.audio_css',
      label: app.translator.trans('zephyrisle-formatting-pro.admin.settings.audio_css'),
      help: app.translator.trans('zephyrisle-formatting-pro.admin.settings.audio_css_help'),
      placeholder: app.translator.trans('zephyrisle-formatting-pro.admin.settings.audio_css_placeholder'),
      rows: 10,
      type: 'textarea',
    });
});
