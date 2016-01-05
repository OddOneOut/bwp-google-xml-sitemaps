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

// in case someone integrates this plugin in a theme or calling this directly
global $bwp_gxs;

if ((isset($bwp_gxs) && $bwp_gxs instanceof BWP_Sitemaps) || !defined('ABSPATH'))
	return;

// require libs manually if PHP version is lower than 5.3.2
// @todo remove this when WordPress drops support for PHP version < 5.3.2
if (version_compare(PHP_VERSION, '5.3.2', '<'))
{
	require_once dirname(__FILE__) . '/autoload.php';
}
else
{
	// load dependencies using composer autoload
	require_once dirname(__FILE__) . '/vendor/autoload.php';
}

/**
 * Global instance of the plugin
 *
 * TODO in 2.0.0 `$bwp_gxs` should be changed to `$bwp_sitemaps`
 *
 * @var BwP_Sitemaps
 */
$bwp_gxs = new BWP_Sitemaps(array(
	'title'   => 'Better WordPress Google XML Sitemaps',
	'version' => '1.4.0',
	'domain'  => 'bwp-google-xml-sitemaps'
));
