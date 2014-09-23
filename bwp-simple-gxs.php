<?php
/*
Plugin Name: Better WordPress Google XML Sitemaps
Plugin URI: http://betterwp.net/wordpress-plugins/google-xml-sitemaps/
Description: A more lightweight Google XML Sitemap WordPress plugin that generates a <a href="http://en.wikipedia.org/wiki/Sitemap_index">Sitemap index</a> rather than a single sitemap. Despite its simplicity, it is still very powerful and has plenty of options to choose.
Version: 1.3.1
Text Domain: bwp-simple-gxs
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3 or later
*/

// In case someone integrates this plugin in a theme or calling this directly
if (class_exists('BWP_SIMPLE_GXS') || !defined('ABSPATH'))
	return;

// init plugin
require_once dirname(__FILE__) . '/includes/class-bwp-simple-gxs.php';
$bwp_gxs = new BWP_SIMPLE_GXS();
