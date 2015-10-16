<?php

if (!defined('ABSPATH')) { exit; }

add_action('init', 'bwp_gxs_register_custom_post_types');
add_action('init', 'bwp_gxs_register_custom_taxonomies');

function bwp_gxs_register_custom_post_types()
{
	register_post_type('movie', array(
		'public' => true,
		'label'  => 'Movies'
	));
}

function bwp_gxs_register_custom_taxonomies()
{
	register_taxonomy('genre', 'movie');
}
