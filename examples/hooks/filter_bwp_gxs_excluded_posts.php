<?php
add_filter('bwp_gxs_excluded_posts', 'bwp_gxs_exclude_posts', 10, 2);
function bwp_gxs_exclude_posts($post_ids, $post_type)
{
	// $post_type let you easily exclude posts from specific post types
	switch ($post_type)
	{
		case 'post': return array(1,2,3,4); break; // the default post type
		case 'movie': return array(5,6,7,8); break; // the 'movie' post type
	}

	return array();
}
