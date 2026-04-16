# Translation Helper Tool

[![WordPress Version](https://img.shields.io/badge/wordpress-%3E%3D%205.0-blue.svg)](https://wordpress.org)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Translation Helper Tool** is a lightweight and efficient open-source tool for WordPress that allows for bulk installation of language packs directly from the official WordPress.org repository, without needing to change the site's global language.

## 🚀 Description
By default, WordPress only allows users to select languages that are already installed on the server. To install a new language, an administrator usually has to change the entire site's language, which forces the download of translation files.

**Translation Helper Tool** eliminates this problem. It allows for "quiet" installation of any number of language packs in the background. This way, your users can use their preferred languages in their profiles, and you don't have to change the global settings of your website.

## ✨ Features
* **Bulk Installation:** Install multiple languages at once with a single click.
* **Full Language List:** Access to over 200 languages supported by WordPress.org.
* **Intuitive Interface:** A native WordPress-style table with search functionality and status indicators (Installed/Not Installed).
* **Package Management:** Bulk delete installed languages to free up disk space.
* **Security:** The plugin uses the native `WordPress Upgrader` API, ensuring safe and correct file downloads.

## 🛠 Minimum Requirements
* **WordPress Version:** 5.0 or higher.
* **PHP Version:** 7.4 or higher.
* **Permissions:** An account with Administrator privileges (requires `install_languages` capability).
* **Server:** Active outbound connection to `api.wordpress.org`.

## 📦 Installation
1. Download the repository as a `.zip` file or clone it directly.
2. Upload the `wp-lang-tool` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin via the WordPress dashboard (**Plugins** -> **Installed Plugins**).
4. Go to **Tools** -> **Translation Helper Tool** to start managing languages.

## 📖 FAQ
**Does installing a language change my site's language?** No. The plugin only downloads and installs the translation files to the server. Your site's language remains unchanged until you manually modify it in the General Settings.

**Why install languages if I don't use them on the front end?** This is useful for large teams (each editor can have the dashboard in their own language) and for multilingual plugins that require `.mo`/`.po` files to be present on the server.

## 📝 License
This project is released under the GPLv2 license or later.

---
**Author:** [Fabian Baranski (TheBisik)](https://github.com/TheBisik)
