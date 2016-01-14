<?php
/*
Plugin Name: Better WordPress Google XML Sitemaps
Plugin URI: http://betterwp.net/wordpress-plugins/google-xml-sitemaps/
Description: Generates XML sitemaps for your WordPress website with ease. This plugin comes with support for sitemap index, multisite WordPress and Google News sitemap. It also provides a powerful and flexible system for any customization need.
Version: 1.4.0
Text Domain: bwp-google-xml-sitemaps
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3 or later
*/

/**
 * The current file (`bwp-simple-gxs.php`) is used to make sure updates to
 * 1.4.0 is smooth.
 *
 * There's no easy way to change a plugin's main file, need to find a solution.
 * For now we load the actual main plugin file via a simple `include_once`
 * statement.
 */
include_once dirname(__FILE__) . '/bwp-gxs.php';
