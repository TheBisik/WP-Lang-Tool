=== WP Lang Tool ===
Contributors: TheBisik
Tags: translation, language, upgrader, bulk install, tools, open source
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A tool for bulk installation of language packs without changing the site's global language. Completely free and open source.

== Description ==

By default, WordPress only allows selecting user languages that are already installed on the server or configured via the site language setting. If you want to offer different languages to your users without changing the global locale first, you'd usually have to do it manually.

**WP Lang Tool** allows WordPress administrators to bulk install any available language packs directly from the official WordPress.org repository. It does this quietly in the background without affecting your front end or triggering unnecessary locale changes.

It simply pulls exactly the language packs you want straight from translate.wordpress.org! 

= Features =
* Fully open-source and free, forever. No premium versions or hidden upsells.
* See all 200+ available languages in a neat, native WordPress-styled table.
* Bulk select and install as many languages as you want simultaneously.
* See exactly which languages are currently installed and which ones are not.
* Safe and secure: utilizes native WordPress Upgrader APIs underneath for background installation without garbled text outputs.

== Installation ==

1. Upload the entire `wp-lang-tool` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Access the plugin via **Tools -> WP Lang Tool** in the dashboard.
4. Select the languages you want to install, and click "Bulk Install Selected".

== Frequently Asked Questions ==

= Does this plugin change my site's language? =
No. It only downloads and installs the translation files so that users can select them in their personal profile settings, or so that other plugins (like multilingual plugins) can use them.

= Will this plugin cost me anything? =
No. It has been built to be 100% free with all functionality open from the start.

== Screenshots ==

1. The main settings page under Tools -> WP Lang Tool.

== Changelog ==

= 1.0.0 =
* Initial open-source release.
