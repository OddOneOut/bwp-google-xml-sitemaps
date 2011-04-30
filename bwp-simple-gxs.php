<?php
/*
Plugin Name: BWP Google XML Sitemaps
Plugin URI: http://betterwp.net/wordpress-plugins/google-xml-sitemaps/
Description: A more lightweight Google XML Sitemap WordPress plugin that generates a <a href="http://en.wikipedia.org/wiki/Sitemap_index">Sitemap index</a> rather than a single sitemap. Despite its simplicity, it is still very powerful and has plenty of options to choose.
Version: 1.0.5
Text Domain: bwp-simple-gxs
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3
*/

// Frontend
require_once(dirname(__FILE__) . '/includes/class-bwp-simple-gxs.php');
$bwp_gxs = new BWP_SIMPLE_GXS();

// Backend
add_action('admin_menu', 'bwp_gxs_init_admin', 1);

function bwp_gxs_init_admin()
{
	global $bwp_gxs;
	$bwp_gxs->init_admin();
}
?>