=== Better WordPress Google XML Sitemaps (with sitemapindex, multi-site and Google News sitemap support) ===
Contributors: OddOneOut
Donate link: http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#contributions
Tags: xml sitemaps, xml sitemap, google xml sitemaps, sitemapindex, sitemap, sitemaps, sitemap.xml, seo, bing, google, msn, ask, google news, news sitemap, google news sitemap
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 1.3.1
License: GPLv3 or later

The first WordPress XML Sitemap plugin that comes with comprehensive support for Sitemapindex, Multi-site and Google News sitemap.

== Description ==

With BWP GXS you will no longer have to worry about the 50,000 URL limit or the time it takes for a sitemap to be generated. This plugin is fast, consumes much less resource and can be extended via your very own modules (yes, no hooks needed!). Here's a [demo](http://betterwp.net/sitemapindex.xml) of the sitemapindex if you are interested.

**Google News Sitemap Support (since 1.2.0)**

Add a [Google News sitemap](https://support.google.com/news/publisher/answer/75717?hl=en&ref_topic=4359874) to your sitemapindex easily. News sitemap can be used to ping search engines individually if you want. And of course, whenever you publish a new post in a news category, all selected search engines will be pinged.

**Sitemapindex Support**

Sitemapindex, as its name suggests, is one kind of sitemaps that allows  you to group multiple sitemaps files inside it. Sitemapindex, therefore, gives you many benefits, such as: possibility to bypass the 50,000 URL limit (you can have 10 custom sitemaps, each has 10000 URLs), or possibility to make the generation time much faster (because each sitemap is requested separately and is built by its own module), etc.

**Splitting post-based sitemaps (since 1.1.0)**

As of version 1.1.0, this plugin can automatically split large post sitemaps into smaller ones. You can set a limit for each small sitemap. For example if you have 200K posts and would like to have 10K posts for each sitemap, BWP GXS will then split `post.xml` into 20 parts (i.e. from `post_part1.xml` to `post_part20.xml`). This helps you bypass the 50,000 URLs limit without having to build your custom modules, and also helps make your sitemaps smaller, lighter, and of course faster to generate. This plugin has been tested on sites that have nearly 200K posts and it took less than 1 second to generate the sitemapindex.

**Multi-site Support**

Each website within your network will have its own sitemapindex and sitemaps. For sub-domain installation, your sitemapindex will appear at `http://sub-domain.example.com/sitemapindex.xml`. For sub-folder installation, your sitemapindex will appear at `http://example.com/sub-folder/sitemapindex.xml`. And of course, there's always a sitemapindex for your main site, available at `http://example.com/sitemapindex.xml`. If you choose the sub-domain approach, each sub-domain can also have its  own robots.txt.

**Custom sitemaps using modules**

The unrivaled flexibility this plugin offers is the ability to define your custom sitemaps using modules. Each module is a actually .php file that tell BWP Google XML Sitemap how to build a sitemap file. You can extend default modules or create completely new ones. This plugin also comes with a convenient base class for developing modules with easy to use and thoroughly documented API. Since modules can be defined by you, there's no limitation what a sitemap can have (for example you can bypass the 50,000 URL limit, as stated above). There's one limitation, though: your imagination ;) . Oh, did I mention that you can even use module to create another sitemapindex?

**Detailed Sitemap Log and Debug**

Developing modules needs debugging and this plugin makes that so easy for any developers.

There are two kinds of logs: sitemap item log and sitemap generation log. Sitemap item log tells you what and when sitemaps are generated while sitemap generation log tells you how they are generated.

As of version 1.3.0 there are two debug modes, namely "Debug" and "Debug extra", read more [here](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#sitemap_log_debug) to know how to make the most out of them.

**For a complete feature list, please visit this [plugin's official page](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/)**

Please don't forget to rate this plugin [5 shining stars](http://wordpress.org/support/view/plugin-reviews/bwp-google-xml-sitemaps?filter=5) if you like it, thanks!

**Important Notes**

If you have any problem using this plugin, refer to the [FAQ section](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/faq/) for possible solutions, and workarounds.

**Get in touch**

* Support is provided via [BetterWP.net Community](http://betterwp.net/community/).
* Follow and contribute to development via [Github](https://github.com/OddOneOut/Better-WordPress-Google-XML-Sitemaps).
* You can also follow me on [Twitter](http://twitter.com/0dd0ne0ut).
* Check out [latest WordPress Tips and Ideas](http://feeds.feedburner.com/BetterWPnet) from BetterWP.net.

**Languages**

* English (default)
* Malaysian (ms_MY) - Thanks to [d4rkcry3r](http://d4rkcry3r.com)!
* Traditional Chinese (zh_TW) - Thanks to Richer Yang!
* Romanian (ro_RO) - Thanks to Luke Tyler!
* Spanish (es_ES) - Thanks to Ruben Hernandez - http://usitility.es

Please [help translate](http://betterwp.net/wordpress-tips/create-pot-file-using-poedit/) this plugin!

== Installation ==

1. Upload the `bwp-google-xml-sitemaps` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the Plugins menu in WordPress. After activation, you should see a menu of this plugin on your left. If you can not locate it, click on Settings under the plugin's name.
3. Configure the plugin
4. Build your sitemap for the first time or just submit the sitemapindex to all major search engines.
5. Enjoy!

[View instructions with images](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/installation/).

== Frequently Asked Questions ==

[Visit this link for the complete FAQ](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/faq/)

== Screenshots ==

1. A sample Sitemap Index this plugin produces. Large post-based sitemaps are split into smaller parts.
2. A custom post type sitemap
3. An external pages sitemap

== Changelog ==

= 1.3.1 =
    * Marked as WordPress 4.0 compatible.
    * Added `bwp_gxs_excluded_posts` filter hook to page sitemap module. It should be possible to use an array of page ids to exclude certain pages from the page sitemap.
    * Added a `News name` setting (in *XML Sitemaps >> Google News Sitemap*).
    * Other minor fixes and enhancements.

= 1.3.0 =
* **New features**
    * Added a new setting to control which post types can be used to ping search engines
    * Added a ping limit setting
    * Added a "Save changes and Flush cache" button

* **Enhancements**
    * Plugin's admin areas have been re-organized to be easier to use.
    * Updated all sitemap modules with easier to use APIs.
    * Last modified dates are now more properly handled.
    * Error log has been refined to be more friendly and useful.
    * Post types that do not have any post should not be listed in `sitemapindex.xml` by default.
    * Posts that are password-protected should not be listed in sitemap files.
    * Be more selective when pinging search engines, this is to avoid double pinging when using plugin like Snitch.
    * Replaced the "Clean PHP errors" function with a "Debug extra" mode that can be used to easily debug "Content Encoding error" and similar errors, more info about "Debug extra" [here](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#sitemap_log_debug).
    * When debug or debug extra is on, `no-cache` headers are sent to ensure a proper debugging environment.
    * Caching is now disabled by default to avoid confusion when setting up the sitemapindex.
    * Cache directory can now be edited via:
        * Plugin's admin area: *BWP Sitemaps >> XML Sitemaps >> Caching*
        * PHP Constant: e.g. `define('BWP_GXS_CACHE_DIR', 'path/to/cache/directory');`
        * Filters: use `bwp_gxs_cache_dir`
    * Added `bwp_gxs_excluded_posts` filter hook to let users exclude posts using an array of IDs instead of an SQL string. This is meant to replace `bwp_gxs_post_where` filter hook when using to exclude certain posts. More info [here](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#exclude_items).
    * Added `bwp_gxs_excluded_terms` filter hook as a taxonomy-equivalent to `bwp_gxs_excluded_posts`, this works the same as (and deprecates) `bwp_gxs_term_exclude`.
    * Added `bwp_gxs_sitemap_lastmod` filter hook to let users modify last modified dates of sitemaps inside a sitemapindex programmatically when needed.
    * Ping settings can now be set per blog instead of site-wise.
    * Added a Spanish translation - Thanks to Ruben!
    * Added more News languages:
        * Hebrew (he)
        * Traditional Chinese (zh-tw)
        * Arabic (ar)
        * Hindi (hi)
        * Japanese (ja)
    * Other enhancements.

* **Bugfixes**:
    * Fixed an issue that might cause an extraneous split part to appear.
    * Fixed an issue that might cause "Invalid last modified date" error
    * Fixed an issue that might cause the sitemapindex to use the regular XSLT stylesheet
    * Sitemapindex should now respect excluded posts when splitting post-based sitemaps
    * Other bugfixes.

**Important Update Note**:

* if you're using custom modules make sure that you re-read the [documentation](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#module_api) for updated info about the module API.
* When the sitemapindex is generated for the first time, you won't see any <em>Last modified date</em> for any child sitemaps because none of them have been generated yet. This is expected and adhered to the <a href="http://www.sitemaps.org/protocol.html#sitemapIndexTagDefinitions">official sitemap protocol</a>.

**Next major version (1.4.0) will have Image and Video sitemap support, so make sure you [stay alerted](http://feeds.feedburner.com/BetterWPnet)!**

= 1.2.3 =
* Temporary fix for unexpected character that appears on every page.

= 1.2.2 =

* Marked as WordPress 3.7 compatible.
* Added two new Google News Sitemap languages: Turkish (tr) and Russian (ru).
* Updated BWP Framework to fix a possible bug that caues BWP setting pages to go blank.
* Removed Ask.com's pinging service as it has been retired.
* **Good news**: ManageWP.com has become the official sponsor for BWP Google Xml Sitemaps - [Read more](http://betterwp.net/319-better-wordpress-plugins-updates-2013/).

= 1.2.1 =
As you might have guessed, this release focuses on improving the new Google News Sitemap Module which was introduced in 1.2.0. Below are some enhancements:

* Added new languages (Spanish, German, Polish, Portuguese, etc.).
* Added a new hook (`bwp_gxs_news_name`) that allows you to set a custom sitename without having to change the sitename setting inside WordPress.
* Added a new option that allows you to choose whether to use news categories or news tags as keywords (very experimental and can be inefficient if you have a lot of posts).
* WordPress's timezone settings are now properly respected.
* Genres tags are omitted entirely if no genres are set for a particular categories.
* A new Multi-category mode (disabled by default) has been added that offers the following features:
	* If a post is in both an included and an excluded category, it's now excluded.
	* If a post is in two or more categories, it can now have all genres that are assigned to those categories.
	* If a post is in two or more categories, it can now have all categories as its keywords (only if you choose to use news categories as keywords of course)

Other functionality of BWP GXS remains the same, so test the News sitemap as much as you can and report any bug you may stumble upon. Enjoy :).

= 1.2.0 =
* Added a Google News sitemap module. Creating a news sitemap has never been so easy! More information can be found [here](http://betterwp.net/314-bwp-gxs-1-2-0-with-news-sitemap/).
* WPMU Domain Mapping is now supported better (robots.txt, site.xml, sitemap urls in Statistics tab).
* BWP GXS's menu can now be put under **Settings** using a simple filter.
* BWP GXS's admin style is now enqueued correctly so no more warning from WordPress.
* Added a Traditional Chinese and a Romanian transation, thanks to Richer Yang and Luke Tyler!
* All invalid URls, such as `#` and external or empty ones, are now removed from sitemaps.
* Removed Yahoo's pinging service.
* Fixed a bug that causes duplicate author entries to appear in author sitemap.
* Fixed a bug that causes a "class not found" error with paginated custom post type sitemap modules.
* Other fixes and improvements.

**Report bugs, request features here: http://betterwp.net/community/forum/2/bwp-google-xml-sitemaps/**

= 1.1.6 =
* Temporary fix for 1.1.5's broken custom post type URL issue.

= 1.1.5 =
* Added a new 'site.xml' sitemap that lists all blogs within your site / network. 
* Added a new 'author.xml' sitemap that lists all authors contributing to a blog.
* BWP GXS should now show the correct mapped sitemap URLs in the Statistics tab if you use WPMU Domain Mapping plugin.
* Fixed a bug that causes duplicate items to appear on sitemap parts, thanks to Aahan for reporting!
* Fixed a bug that causes the `post` post type sitemaps to show up even when disabled.

**Note that the site sitemap will be enabled, while the author sitemap will be disabled by default.**

= 1.1.4 =
* Changed some options' descriptions.
* Fixed a possible incompatibility issue with FeedWordPress plugin and Suffusion theme.
* Other minor bug fixes.

= 1.1.3 =
* Gzip is now off by default as it was causing issue on some hosts.
* In previous versions, this plugin automatically cleaned unexpected outputs before sitemap generation so that sitemaps are generated properly. Such feature also causes issues on certain hosts. As of 1.1.3 this is an option in Generator tab, and it is enabled by default.
* Fixed a possible bug in the taxonomy module that could cause a maximum execution time error. Thanks to David Killingsworth for reporting this bug!
* Other minor bug fixes and improvements.

= 1.1.2 =
* Fixed a possible memory leak.
* Clear PHP errors in a friendlier way.
* Other minor bug fixes and improvements.

= 1.1.1 =
* Added an option for users to choose whether to use GMT for Last Modified time or not.
* Improved the taxonomy module a bit.
* Fixed a minor bug in the page module.
* Fixed a bug that affects rewrite rules of custom post types and taxonomies in some cases. A big thanks to crowinck!
* Other minor bug fixes and improvements.

= 1.1.0 =

**New Features:**

* This plugin can now automatically split large post sitemaps into smaller ones. You can set a limit for each small sitemap. For example if you have 200K posts and would like to have 10K posts for each sitemap, BWP GXS will then split `post.xml` into 20 parts (i.e. from `post_part1.xml` to `post_part20.xml`). This helps you bypass the 50,000 URLs limit without having to build your custom modules, and also helps make your sitemaps smaller, lighter, and of course faster to generate. This plugin has been tested on sites that have nearly 200K posts and it took less than 1 second to generate the sitemapindex.
* Added a new sitemap, called External Pages' sitemap, using which you can easily add links to pages that do not belong to WordPress to the Sitemap Index. Please refer to the [customization section](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#external_sitemap) for more details.
* Added options in the Generator to exclude certain post types, taxonomies without having to use filters.
* Added new hooks to default post-based and taxonomy-based modules to allow easier SQL query customization (you don't have to develop custom modules anymore just to change minor things). Read [here](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#exclude_items) for more details.

**Improvements:**

* Major overhaul of all modules to make them faster, more efficient, more accurate, and of course, consume much less memory. This version should eliminate most white page problems happened in previous versions.
* Improved compatibility with cache plugins.
* Improved support for sites that uses `%category%` in permalink structure.
* The plugin should now display style sheet correctly in tight situations.
* Added a clear log button to clear redundant log (available to network admin only).
* Added an option to include links to all sitemapindex.xml files in your network in the primary website's `robots.txt`.
* Tag archive sitemap can now be disabled properly.
* Fixed permalinks for people using index.php in permalink structure.
* Other minor bug fixes and improvements.

For a detailed description of each new feature, please refer to the [release announcement](http://betterwp.net/257-bwp-google-xml-sitemaps-1-1-0/).

**Due to major changes in core it is suggested that you clear the logs using the new 'Clear All Logs' button and double-check the Generator's settings. Have fun and please [rate this plugin](http://wordpress.org/extend/plugins/bwp-google-xml-sitemaps/) 5 stars if you like it, thanks!**

= 1.0.5 =
* Unregistered modules (such as redundant modules from 1.0.3) will now have a 404 HTTP status to prevent search engines from requesting them again.
* Time for each log will now make use of your current timezone setting in Settings -> General.
* And other minor fixes.

**Thanks everyone for using this plugin!**

= 1.0.4 =
* Hot fix for WordPress in other languages, such as French, Russian. Prior to this version this plugin tries to use posts' and taxonomies' labels to build sitemaps' URLs in plural forms (e.g. taxonomy_categories). Unfortunately this breaks sitemaps when labels contain UTF8 characters with marks (such as cat�gories). All sitemaps now have singular forms. Hope that we will have a better solution in the future.

**This change will make all logs' contents change as well. To remove redundant logs, please deactivate this plugin and then reactivate it.**

= 1.0.3 =
* Fixed incorrect regex for rewrite rules.
* Added a check to make sure all necessary rewrite rules are added. No need to manually flush rewrite rules anymore.
* `bwp_gxs_add_rewrite_rules` action now becomes `bwp_gxs_rewrite_rules` filter (the hook used to add your own sitemaps).

**For people using a cache plugin, please visit the [FAQ section](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/faq/) for a possible compatibility fix.**

= 1.0.2 =
* Fixed a bug that could produce wrong or empty last modified dates in sitemapindex.
* Corrected a typo in page.php module.
* Added Malaysian translation, thanks to d4rkcry3r!

= 1.0.1 =
* Now you can browser to `http://example.com/sitemap.xml` to view your sitemapindex. You can submit it too if you want. **Important**: Make sure you don't have any real sitemap.xml file in your website's root. Also, you will have to flush all rewrite rules, by either deactivating and then reactivating this plugin, or simply go to [Permalink Settings](http://example.com/wp-admin/options-permalink.php) and click on Save Changes.
* Build stats (build time, number of queries, memory usage) should be more accurate now.
* Add a canonical redirection for sitemap URL to avoid problems with XSLT style sheet's absolute URL.
* Fixed a minor error in the base module class.

= 1.0.0 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
* Enjoy the plugin!
