=== Better WordPress Google XML Sitemaps (with sitemapindex, multi-site and Google News sitemap support) ===
Contributors: OddOneOut
Donate link: http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#contributions
Tags: xml sitemaps, xml sitemap, google xml sitemaps, sitemapindex, sitemap, sitemaps, seo, bing, google, msn, ask, multi-site, multisite
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.2.1

The first WordPress XML Sitemap plugin that comes with comprehensive support for Sitemapindex, Multi-site and Google News sitemap.

== Description ==

With BWP GXS you will no longer have to worry about the 50,000 URL limit or the time it takes for a sitemap to be generated. This plugin is fast, consumes much less resource and can be extended via your very own modules (yes, no hooks needed!). Here's a [demo](http://betterwp.net/sitemapindex.xml) of the sitemapindex if you are interested.

**===New in 1.2.0!===**

The long-awaited BWP GXS 1.2.0 has finally been released with a new and very powerful feature: **Google News Sitemap creation**! All you have to do is click on the newly added tab (**News sitemap**), enable the module, choose some news categories as well as their genres and you're ready to go. Your news sitemap can also be used to ping Search Engines individually if you want. And of course, whenever you publish a new post in a news category, all selected Search Engines will be pinged!

**Sitemapindex Support**

What's so great about a sitemapindex you might say? Sitemapindex, as its name suggests, is one kind of sitemaps that allows  you to group multiple sitemaps files inside it. Sitemapindex, therefore, gives you many benefits, such as: possibility to bypass the 50,000 URL limit (you can have 10 custom sitemaps, each has 10000 URLs), or possibility to make the generation time much faster (because each sitemap is requested separately and is built by its own module), etc.

**Splitting post-based sitemaps (since 1.1.0)**

As of version 1.1.0, this plugin can automatically split large post sitemaps into smaller ones. You can set a limit for each small sitemap. For example if you have 200K posts and would like to have 10K posts for each sitemap, BWP GXS will then split `post.xml` into 20 parts (i.e. from `post_part1.xml` to `post_part20.xml`). This helps you bypass the 50,000 URLs limit without having to build your custom modules, and also helps make your sitemaps smaller, lighter, and of course faster to generate. This plugin has been tested on sites that have nearly 200K posts and it took less than 1 second to generate the sitemapindex.

**Multi-site Support**

Each website within your network will have its own sitemapindex and sitemaps. For sub-domain installation, your sitemapindex will appear at `http://sub-domain.example.com/sitemapindex.xml`. For sub-folder installation, your sitemapindex will appear at `http://example.com/sub-folder/sitemapindex.xml`. And of course, there's always a sitemapindex for your main site, available at `http://example.com/sitemapindex.xml`. If you choose the sub-domain approach, each sub-domain can also have its  own robots.txt.

**Custom sitemaps using modules**

The unrivaled flexibility this plugin offers is the ability to define your custom sitemaps using modules. Each module is a actually .php file that tell BWP Google XML Sitemap how to build a sitemap file. You can extend default modules or create completely new ones. This plugin also comes with a convenient base class for developing modules with easy to use and thoroughly documented API. Since modules can be defined by you, there's no limitation what a sitemap can have (for example you can bypass the 50,000 URL limit, as stated above). There's one limitation, though: your imagination ;) . Oh, did I mention that you can even use module to create another sitemapindex?

**Detailed Log and Debugging mode**

Developing modules needs debugging and this plugin makes that so easy for any developers. There are two kinds of logs: sitemap log and build log. Sitemap log tells you what sitemaps you have and when they were last requested/updated while build log tells you what's going on when a particular sitemap is built. The debug mode helps you trace errors in modules conveniently!

**For a complete feature list, please visit this [plugin's official page](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/)**

**Important Notes**

If you have any problem using this plugin, refer to the [FAQ section](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/faq/) for possible solutions, and workarounds.

**Get in touch**

* I'm available at [BetterWP.net](http://betterwp.net) and you can also follow me on [Twitter](http://twitter.com/0dd0ne0ut).
* Check out [latest WordPress Tips and Ideas](http://feeds.feedburner.com/BetterWPnet) from BetterWP.net.

**Languages**

* English (default)
* Malaysian (ms_MY) - Thanks to [d4rkcry3r](http://d4rkcry3r.com)!
* Traditional Chinese (zh_TW) - Thanks to Richer Yang!
* Romanian (ro_RO) - Thanks to Luke Tyler!

Please [help translate](http://betterwp.net/wordpress-tips/create-pot-file-using-poedit/) this plugin!

== Installation ==

1. Upload the `bwp-google-xml-sitemaps` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the Plugins menu in WordPress. After activation, you should see a menu of this plugin on your left. If you can not locate it, click on Settings under the plugin's name.
3. Configure the plugin
4. Build your sitemap for the first time or just submit the sitemapindex to all major search engines.
5. Enjoy!

[View instructions with images](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/installation/).

== Frequently Asked Questions ==

**Q1: I got white pages and/or 'Content Encoding Error' error?**

1. PHP error messages from other plugins or from this plugin itself can cause this issue, especially when you have `WP_DEBUG` set to true (try commenting out this line in your `wp-config.php` file: `define(WP_DEBUG, true);`). Refer to the second question to know how to trace the actual errors.

2. BWP GXS runs out of memory or reaches maximum execution time. This often happens when you try to build large sitemaps. I've tried to optimize this plugin a lot since the first version, but if you are using a lot of memory eating plugins on your website, it is very hard for BWP GXS to build huge sitemaps (containing thousands of items). Anyway, to resolve this issue, try decreasing the three limit options in the Generator tab: max number of items per sitemap (first option), max number of items per split sitemap (first option in Sitemap Index Options) and max items to get in one SQL query (second option in Module Options). You might try the following presets (in the same order as above):
	* 1000, 1000, 100 (for sites with low memory limit like 32MB)
	* 5000, 5000, 500 (for sites with lots of posts, tags, categories, etc.)
	* 5000, 5000, 2500 (if you have problem with max execution time)

	Also, you can try the tips mentioned in [this post of mine](http://betterwp.net/6-wordpress-memory-usage/).

3. A caching plugin is interfering. Tell that caching plugin to ignore `.xml` file. For example, if you use WP Super Cache, on the Advanced Tab, scroll to the 'Accepted Filenames & Rejected URIs' section, and then in the first textarea, type in `\.xml`. Now save the changes and flush the all caches. See if that works for you.

**Q2: I've tried the tips mentioned in Question #1, but still got white pages / plain text sitemaps?**

It's very likely that you're enabling this option: 'Clean unexpected output before sitemap generation', try disabling it and your sitemaps will show up as expected.

**Q3: I got the error 'Content Encoding Error', what should I do?**

If you are enabling this plugin's debug mode and/or WP_DEBUG, this error is very normal because the module you use might print errors on pages, thus corrupting your xml sitemaps. To view the actual errors without being greeted with the 'Content Encoding Error', please follow these steps:

1. Enable this plugin's debug mode if you haven't done so.
2. Open the file `class-bwp-simple-gxs.php` inside `bwp-google-xml-sitemaps/includes/` and look for this function: `output_sitemap()`.
3. Place an `exit;` right after the opening bracket, like so:
<pre><code>
function output_sitemap()
{
	exit;
</code></pre>
4. Refresh the sitemaps with problems.
5. Kindly report the errors you see by commenting or through the [contact form](http://betterwp.net/contact/). Thanks!

Note that, however, some error messages will never show up. In such case, you might want to locate the `error_log` file within your WordPress installation's root directory and read its contents for the error messages.

**Q4: I got an 'Error loading stylesheet' error, what should I do?**

As of version 1.1.0 it is almost impossible for such error to show up, but if you set the site url and the home url differently (one with `www` and one isn't), you will see this error. Just choose to disable stylesheet in Generator tab or change your site / home URL settings.

**Q5: I got a HTTP parse error when I submit sitemap to Google Webmaster Tools, what should I do?**

Please first see the answer to the first question, if it didn't work, and you are using a cache plugin such as W3 Total Cache, it is possible that such plugin wrongly assigns HTTP status headers to my sitemaps.

For example, in W3 Total Cache 0.9.2.2 or possibly older, go to **Performance -> Browser Cache**, and then go to '404 error exception list' in the 'General' option block, and find this line:

<pre><code>sitemap(_index|[0-9]+)?\.xml(\.gz)?</code></pre>

OR this line:

<pre><code>sitemap\.xml(\.gz)?</code></pre>

and change it to:

<pre><code>(sitemapindex|[a-z0-9_-]+)\.xml</code></pre>

Save the changes and then tell W3TC to auto-install the rewrite rules to your `.htaccess` file.

BWP GXS's sitemaps will now have correct HTTP status headers.

**Q6: When I visit `http://example.com/sitemapindex.xml` , WordPress returns a 404 page. What should I do?**

This might be caused by unflushed rewrite rules, which should have been flushed when you activate this plugin. You can try flushing them manually by visiting Settings -> Permalinks and then clicking Save Changes.

**Q7: When I visit any sitemap, a plain (no theme) 404 error page appears, what's the problem?**

You are possibly using some caching plugins that redirect all non-existent pages to 404 error page. Sitemaps produced by this plugin are virtual sitemaps so they will all be redirected. Just open the .htaccess file and change the `sitemap\.xml` part to something like `[a-z0-9-_]+\.xml` and you're fine.

**Q8: I choose not to display certain sitemaps but the sitemapindex still displays the them?**

What you see is actually a cached version of the sitemapindex. You can wait for it to be refreshed automatically or simply choose to 'Flush the Cache'.

**Q9: BWP GXS tells me that 'Requested module not found or not allowed', what should I do?**

This depends on specific situations and your current settings. Basically this means that the module you're trying to access has not been registered with BWP GXS or that module has been disabled but the cached sitemapindex still displays it  (which is related to the question above). For a list of default modules (or sitemaps), please read this section.

**Q10: Is there anyway to rename sitemapindex.xml to sitemap.xml?**

You don't have to. A visit to `http://example.com/sitemap.xml` will show you the same sitemapindex. This is done to make BWP GXS more compatible with blogs that have lots of real `robots.txt`. Please note that you must remove any real sitemap.xml file in your website's root for this feature to work.

**Q11: The custom sitemapindex I create seems to be nested inside the default sitemapindex, is that a bug or something?**

That's the default behaviour of this plugin and I plan to improve it in future versions. Don't worry, though, you might see a red X next to your sitemapindex's name in Google's Webmaster Tools but that's only because you haven't submitted your custom sitemapindex. If you submit it separately, the error will go away :).

[Check plugin news and ask questions](http://betterwp.net/topic/google-xml-sitemaps/).

== Screenshots ==

1. A sample Sitemap Index this plugin produces. Large post-based sitemap was split into two parts.
2. A Custom Post Type Sitemap
3. An External Pages' Sitemap
4. The Configuration Page
5. Google News Sitemap

== Changelog ==

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
* Hot fix for WordPress in other languages, such as French, Russian. Prior to this version this plugin tries to use posts' and taxonomies' labels to build sitemaps' URLs in plural forms (e.g. taxonomy_categories). Unfortunately this breaks sitemaps when labels contain UTF8 characters with marks (such as catégories). All sitemaps now have singular forms. Hope that we will have a better solution in the future.

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