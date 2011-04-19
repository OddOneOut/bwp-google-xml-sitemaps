<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_PAGE extends BWP_GXS_MODULE {

	function __construct()
	{
		$this->set_current_time();
		$this->build_data();
	}

	function generate_data()
	{
		global $wpdb, $bwp_gxs, $post;

		$latest_post_query = '
			SELECT * FROM ' . $wpdb->posts . "
				WHERE post_status = 'publish' AND post_type = 'page'" . '
			ORDER BY post_modified DESC';

		$latest_posts = $this->get_results($latest_post_query);

		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		$data = array();
		foreach ($latest_posts as $item)
		{
			$post = $item;
			$data = $this->init_data($data);
			// Benefit from WP Caching
			wp_cache_add($post->ID, $post, 'posts');
			$data['location'] = get_permalink();
			$data['lastmod'] = $this->format_lastmod(strtotime($post->post_modified));
			$data['freq'] = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);
			$this->data[] = $data;
			unset($item);
		}

		// Some memory saver ;)
		unset($latest_posts);
		
		// Always return true if we can get here, otherwise you're stuck at the SQL cycling limit
		return true;
	}
}
?>