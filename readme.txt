=== Better WordPress Google XML Sitemaps (support Sitemap Index, Multi-site and Google News) ===
Contributors: OddOneOut
Donate link: http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#contributions
Tags: xml sitemaps, xml sitemap, google xml sitemaps, sitemapindex, sitemap index, sitemap, sitemaps, sitemap.xml, seo, bing, google, msn, ask, google news, news sitemap, google news sitemap, image sitemap
Requires at least: 3.6
Tested up to: 4.4
Stable tag: 1.4.0
License: GPLv3 or later

A WordPress XML Sitemap plugin that comes with support for Sitemap Index,
Multi-site and Google News sitemap. Image sitemap is supported, too.

== Description ==

With BWP GXS you will no longer have to worry about the 50,000 URL limit or the
time it takes for a sitemap to be generated. This plugin is fast, consumes much
less resource and can be extended via your very own modules (yes, no hooks
needed!). Here's a [demo](http://betterwp.net/sitemapindex.xml) of the sitemap
index if you are interested.

= Google News Sitemap =

Add a *Google News sitemap* to your sitemap index easily. News sitemap can be
used to ping search engines individually if you want. And of course, whenever
you publish a new post in a news category, all selected search engines will be
pinged.

*As of 1.4.0, you can use custom post types and custom taxonomies for your news
sitemap.*

= Image Sitemap =

If you have any post (of any post type) that supports the *Featured image*
feature, you will be able to add the current featured image to a post-based
sitemap with ease.

This feature is available since 1.4.0.

= Sitemap Index =

A sitemap index, as its name suggests, is a sitemap that allows you to group
several sitemaps inside it.

It gives you many benefits such as: possibility to bypass the 50,000 URL limit
(for example you can have 10 custom sitemaps, each has 10,000 URLs), or
possibility to make the generation time much faster (because each sitemap is
requested separately and is built by its own module), etc.

For a search engine to acknowledge your sitemaps, you only have to submit the
sitemap index and you're done, no need to submit each sitemap individually.

= Splitting post-based sitemaps =

As of version 1.1.0, this plugin can automatically split large post sitemaps
into smaller ones when limit reached. For example if you have 200K posts and
would like to have 10K posts for each sitemap, BWP GXS will then split `post.xml`
into 20 parts (i.e. from `post_part1.xml` to `post_part20.xml`).

This not only helps you bypass the 50,000 URLs limit without having to build
your custom modules, but also helps make your sitemaps smaller, lighter, and of
course faster to generate. This plugin has been tested on sites that have
nearly 200K posts and it took less than 1 second to generate the sitemap index.

Furthermore, you can set a separate limit for split sitemaps or simply use the
global limit.

= Multi-site compatible =

Each website within your network will have its own sitemap index and sitemaps.

For sub-domain installation, your sitemap index will appear at `http://sub-domain.example.com/sitemapindex.xml`.
For sub-folder installation, your sitemap index will appear at `http://example.com/sub-folder/sitemapindex.xml`.

There's always a sitemap index for your main site, available at `http://example.com/sitemapindex.xml`.

If you choose the sub-domain approach, each sub-domain can also have its own `robots.txt`.

= Custom sitemaps using modules =

The unrivaled flexibility this plugin offers is the ability to define your
custom sitemaps using modules. Each module is a actually .php file that tell
BWP Google XML Sitemap how to build a sitemap file. You can extend default
modules or create completely new ones.

This plugin also comes with a convenient base class for developing modules with
easy to use and thoroughly documented API. Since modules can be defined by you,
there's no limitation what a sitemap can have (for example you can bypass the
50,000 URL limit, as stated above). There's one limitation, though: your
imagination ;). Oh, did I mention that you can even use module to create
another sitemap index?

**For a complete feature list, please visit this [plugin's official
documentation](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#full-feature-list)**.

= Support this plugin =

Don't forget to rate this plugin [5 shining stars](http://wordpress.org/support/view/plugin-reviews/bwp-google-xml-sitemaps?filter=5) if you like it, thanks!

= Get in touch =

* Found a bug? Have a feature request? [Let me know](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/#report-issues)!
* Follow me on [Twitter](http://twitter.com/0dd0ne0ut).
* Check out [latest WordPress Tips and Ideas](http://feeds.feedburner.com/BetterWPnet) from BetterWP.net.

= Languages =

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
4. Build your sitemap for the first time or just submit the sitemap index to all major search engines.
5. Enjoy!

[View instructions with images](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/installation/).

== Frequently Asked Questions ==

If you have trouble using this plugin, consider giving the [Frequently Asked
Questions](http://betterwp.net/wordpress-plugins/google-xml-sitemaps/faq/)
page a look.

== Screenshots ==

1. A Sitemap Index with large post-based sitemaps split into smaller parts.
2. A Sitemap Index with external sitemaps (`listing.xml` and `items.xml`).
3. A custom post type sitemap.
4. Exclude sitemap items in admin.
5. A Sitemap with images enabled.
6. Google News sitemap (with images).
7. Add external pages in admin.
8. ... and the result!

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

== Upgrade Notice ==

Nothing here.
