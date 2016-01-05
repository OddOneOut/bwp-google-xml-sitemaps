<?php
// add to theme's functions.php
add_filter('bwp_gxs_post_where', 'bwp_gxs_post_where', 10, 2);
function bwp_gxs_post_where($where, $post_type)
{
	// $post_type let you easily exclude posts from specific post types
	switch ($post_type)
	{
		case 'post': return ' AND p.ID NOT IN (1,2,3,4) '; break; // the default post type
		case 'movie': return ' AND p.ID NOT IN (5,6,7,8) '; break; // the 'movie' post type
	}

	return '';
}
