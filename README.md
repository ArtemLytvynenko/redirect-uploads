# Redirect Uploads to Live Site

**Description:**
This plugin redirects media file requests to a live site. Useful for local development when you don't need to store and upload all media files locally.

## Features
- Automatically replace media file URLs on the local site with live site media file URLs.
- Supports live site domain customization via admin panel.
- URL replacement in any content.
- Support for domains with port.
- Handling URLs with and without protocols.

## Installation
1. Upload the plugin to the `/wp-content/plugins/` directory.
2. Activate the plugin via the ``Plugins'' menu in WordPress.
3. Go to Settings > Redirect Uploads to configure the live site domain.

## Customization
1. After activating the plugin, go to Settings > "Redirect Uploads" menu.
2. Enter the live site domain (e.g. `https://domain.com`) in the "Live Site Domain" field.
3. Click the "Save Settings" button.

## Requirements
- WordPress 4.6 or higher.
- PHP 5.6 or higher.

### Frequently Asked Questions

### How does this plugin work?
The plugin replaces media file URLs in your content with live site URLs, allowing you to display media files from a live site on your local WordPress installation.

### What if I want to change the domain of a live site?
Go to Settings > Redirect Uploads and change the domain in the Live Site Domain field.

### Is the plugin compatible with caching?
Yes, the plugin is compatible with most popular caching plugins. However, if you experience problems, please clear your cache after changing the settings.

## Support
If you have any questions or issues, please create a ticket on the plugin page in the WordPress repository or contact the developer.

## License

This plugin is licensed under the GNU General Public License v2.0 or later. The full license is available at [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

---

**Author:** Artem Lytvynenko  
**Version:** 1.0  
**License:** GPL-2.0+
