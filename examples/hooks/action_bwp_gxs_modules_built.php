<?php
// add to theme's functions.php
add_action('bwp_gxs_modules_built', 'bwp_gxs_modules_built');
function bwp_gxs_modules_built($modules, $post_types, $taxonomies)
{
	// need to import plugin instance
	global $bwp_gxs;

	// add "movie" post sitemap
	$bwp_gxs->add_module('post', 'movie');

	// remove "private" post sitemap
	$bwp_gxs->remove_module('post', 'private');

	// remove all page-related sitemaps
	$bwp_gxs->remove_module('page');

	// add "post format" taxonomy sitemap
	$bwp_gxs->add_module('taxonomy', 'post_format');

	// remove "tag" taxonomy sitemap
	$bwp_gxs->remove_module('taxonomy', 'post_tag');
}
