=== GatherContent Import ===
Contributors: Mathew Chapman, namshee
Tags: structured content, gather content, gathercontent, import, migrate, export, mapping, production, writing, collaboration, platform, connect, link, gather, client, word, production
Requires at least: 3.5.0
Tested up to: 4.0
Stable tag: 2.6.34
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The GatherContent Import plugin allows you to quickly import structured content from your GatherContent projects into your WordPress site.

== Description ==

This plugin allows you to quickly import content from your GatherContent projects into your WordPress site.

GatherContent is an online platform for pulling together, editing, and reviewing website content with your clients and colleagues. It's a reliable alternative to emailing around Word documents and pasting content into your CMS. This plugin replaces that process of copying and pasting content and allows you to bulk import structured content, and then continue to update it in WordPress with a few clicks.

Connecting a powerful content production platform, to a powerful content publishing platform.

Content can be imported as pages, posts, media or custom post types. And you can choose to create new pages etc. or overwrite existing entities.

The plugin allows you to specifically map each field on your pages in GatherContent to various fields in WordPress, these include; title, body content, custom fields, tags, categories, Yoast fields, advanced custom fields, featured images … and many more. It also allows you to directly embed images and files.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `gathercontent-import` to the `/wp-content/plugins/` directory
2. Activate the GatherContent plugin through the 'Plugins' menu in WordPress
3. Click on the menu item "GatherContent"
3. Link your accounts. You will need to enter your GatherContent account URL (e.g. http://mywebsite.gathercontent.com) and your personal GatherContent API key. You can find your API key in your Settings area within GatherContent, by opening the API tab.

== Screenshots ==

1. Quickly find your pages using filters and live search.
2. Import GatherContent pages as Pages, Posts, Media and various Custom Post Types.
3. Map individual fields to a huge range of places in WordPress.

== Changelog ==

= 2.6.3 =
* Better integration with yoast and ACF pro. Map to author. Added post format option

= 2.6.2 =
* Remove inline comments from text content

= 2.6.1 =
* Fix bug for multi site installs

= 2.6.0 =
* Add support for custom tabs feature within GatherContent

= 2.5.0 =
* Import hierarchy from GatherContent. Added publish state dropdown to

= 2.4.1 =
* Integrated a few updates from github and fixed coding standard to match WordPress coding standards

= 2.4.0 =
* Changed how the plugin stores page data to allow a larger amount of pages with larger content

= 2.3.0 =
* Updated GatherContent API requests to match current API version and minor UI updates for WP 3.8

= 2.2.1 =
* Added check to makesure cURL is enabled

= 2.2.0 =
* Reworked pages importing to work via ajax. Should fix problems importing too many fields (`max_input_vars`)

= 2.1.0 =
* Added repeatable field mapping

= 2.0.4 =
* Fixed a bug where tag strings weren't being separated by commas

= 2.0.3 =
* Added an alert when pages have no fields to import

= 2.0.2 =
* Fixed line break issues

= 2.0.1 =
* Fixed errors that were only displaying in WP_DEBUG mode

= 2.0 =
* Complete rewrite of old plugin
