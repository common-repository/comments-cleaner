=== Plugin Name ===
Contributors: sirzooro
Tags: anti-spam, antispam, bbcode, comment, comments, html, link, links
Requires at least: 2.7
Tested up to: 3.2.9
Stable tag: 1.3

This plugin removes all HTML tags, BBCode tags and links from added comments (including link to author's website)

== Description ==

Sometimes there are situations when you want to have your comments completely clean - no HTML, no links. This plugin helps to achieve this - it removes all HTML tags and links (both from comment text and entered in Website field). Additionally it removes BBCode markup (in WordPress it is also used for Shortcodes), if someone tried to use it.

You can remove them permanently when comment is added, or remove them on the fly (without modifying database) when comment is displayed.

You can also configure what exactly you want to remove - e.g. you may want to remove HTML and BBCode tags, but leave link to author website and links entered as plain text in comment.

Available translations:

* English
* Polish (pl_PL) - done by me
* Belorussian (be_BY) - thanks [Marcis G.](http://pc.de/)
* Dutch (nl_NL) - thanks [Olivier](http://www.ocjanssen.nl/)

[Changelog](http://wordpress.org/extend/plugins/comments-cleaner/changelog/)

== Installation ==

1. Upload `comments-cleaner` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure and enjoy :)

== Changelog ==

= 1.3 =
* Fix: WP makes clickable URLs which starts from "www" - remove them too

= 1.2 =
* Added options to remove tags and links when comment is displayed (on the fly, without modifying database);
* Marked as compatible with WP 3.2.x

= 1.1.3 =
* Added Dutch translation (thanks Olivier)

= 1.1.2 =
* Marked as compatible with WP 3.0.x

= 1.1.1 =
* Added Belorussian translation (thanks Marcis G.)

= 1.1 =
* Added configuration page;
* Make plugin translatable;
* Added Polish translation

= 1.0.3 =
* Marked as compatible with WP 2.9.x

= 1.0.2 =
* Mark plugin as compatible with WP 2.8.5

= 1.0.1 =
* Mark plugin as tested with WP 2.8

= 1.0 =
* Initial version
