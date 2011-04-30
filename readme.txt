=== Better WordPress Google XML Sitemaps (with sitemapindex and Multi-site support) ===
Contributors: OddOneOut
Donate link: http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#contributions
Tags: xml sitemaps, google xml sitemaps, sitemapindex, sitemap, bing, google, msn, ask, multi-site, multisite
Requires at least: 3.0
Tested up to: 3.1.2
Stable tag: 1.0.5

The first WordPress XML Sitemap plugin that comes with comprehensive support for Sitemapindex and Multi-site.

== Description ==

Welcome to the first WordPress sitemap plugin that has support for both sitemapindex and Multi-site websites! You will no longer have to worry about the 50,000 URL limit or the time it takes for a sitemap to be generated. This plugin is fast, consumes much fewer resources and can be extended via your very own modules (yes, no hooks needed!). Here's a [demo](http://betterwp.net/sitemapindex.xml) of the sitemapindex if you are interested.

**Sitemapindex Support**

What's so great about a sitemapindex you might say? Sitemapindex, as its name suggests, is one kind of sitemaps that allows  you to group multiple sitemaps files inside it. Sitemapindex, therefore, gives you many benefits, such as: possibility to bypass the 50,000 URL limit (you can have 10 custom sitemaps, each has 10000 URLs), or possibility to make the generation time much faster (because each sitemap is requested separately and is built by its own module), etc.

**Multi-site Support**

Each website within your network will have its own sitemapindex and sitemaps. For sub-domain installation, your sitemapindex will appear at `http://sub-domain.example.com/sitemapindex.xml`. For sub-folder installation, your sitemapindex will appear at `http://example.com/sub-folder/sitemapindex.xml`. And of course, there's always a sitemapindex for your main site, available at `http://example.com/sitemapindex.xml`. If you choose the sub-domain approach, each sub-domain can also have its  own robots.txt.

**Custom sitemaps using modules**

The unrivaled flexibility this plugin offers is the ability to define your custom sitemaps using modules. Each module is a actually .php file that tell BWP Google XML Sitemap how to build a sitemap file. You can extend default modules or create completely new ones. This plugin also comes with a convenient base class for developing modules with easy to use and thoroughly documented API. Since modules can be defined by you, there's no limitation what a sitemap can have (for example you can bypass the 50,000 URL limit, as stated above). There's one limitation, though: your imagination ;) . Oh, did I mention that you can even use module to create another sitemapindex?

**Detailed Log and Debugging mode**

Developing modules needs debugging and this plugin makes that so easy for any developers. There are two kinds of logs: sitemap log and build log. Sitemap log tells you what sitemaps you have and when they were last requested/updated while build log tells you what's going on when a particular sitemap is built. The debug mode helps you trace errors in modules conveniently!

**For a complete feature list, please visit this [plugin's official page](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/)**

**Important Notes**
If you encounter the 'Content Encoding Error' error page when trying to view a sitemap, please refer to the [FAQ section](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/faq/) to know how to trace the actual error messages.

**Languages**

* English (default)
* Malaysian (ms_MY) - Thanks to [d4rkcry3r](http://d4rkcry3r.com)!

Please [help translate](http://betterwp.net/wordpress-tips/create-pot-file-using-poedit/) this plugin!

**Get in touch**

I'm available at [BetterWP.net](http://betterwp.net) and you can also follow me on [Twitter](http://twitter.com/0dd0ne0ut).

== Installation ==

1. Upload the `bwp-google-xml-sitemaps` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the Plugins menu in WordPress. After activation, you should see a menu of this plugin on your left. If you can not locate it, click on Settings under the plugin's name.
3. Configure the plugin
4. Build your sitemap for the first time or just submit the sitemapindex to all major search engines.
5. Enjoy!

[View instructions with images](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/installation/).

== Frequently Asked Questions ==

**Q: When I visit `http://example.com/sitemapindex.xml`, WordPress returns a 404 page. What should I do?**

This might be caused by unflushed rewrite rules, which should have been flushed when you activate this plugin. You can try flushing them manually by visiting Settings -> Permalinks and then clicking Save Changes.

**Q: I choose not to display certain sitemaps but the sitemapindex still displays them?**

What you see is actually a cached version of the sitemapindex. You can wait for it to be refreshed automatically or simply choose to 'Flush the Cache'.

**Q: Is there anyway to rename sitemapindex.xml to sitemap.xml?**

You don't have to. A visit to `http://example.com/sitemap.xml` will show you the same sitemapindex. This is done to make BWP GXS more compatible with blogs that have lots of real `robots.txt`. Please note that you must remove any real `sitemap.xml` file in your website's root for this feature to work.

**Q: I got the error 'Content Encoding Error', what should I do?**

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

[Check plugin news and ask questions](http://betterwp.net/topic/google-xml-sitemaps/).

== Screenshots ==

1. The default sitemapindex
2. A custom post type sitemap
3. The Configuration page

== Changelog ==

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