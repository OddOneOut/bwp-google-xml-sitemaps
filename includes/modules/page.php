<?php
/**
 * Copyright (c) 2012 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_PAGE extends BWP_GXS_MODULE {

	function __construct()
	{
		global $bwp_gxs;

		$this->set_current_time();
		$this->part = $bwp_gxs->module_data['module_part'];
		$this->build_data();
	}

	function generate_data()
	{
		global $wpdb, $bwp_gxs, $post;

		$sql_where = apply_filters('bwp_gxs_post_where', '', 'page');

		$latest_post_query = '
			SELECT * FROM ' . $wpdb->posts . " wposts
				WHERE wposts.post_status = 'publish' AND wposts.post_type = 'page' $sql_where" . '
			ORDER BY wposts.post_modified DESC';

		$latest_posts = $this->get_results($latest_post_query);

		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		$using_permalinks = $this->using_permalinks();

		$data = array();
		for ($i = 0; $i < sizeof($latest_posts); $i++)
		{
			$post = $latest_posts[$i];
			$data = $this->init_data($data);
			if ($using_permalinks && empty($post->post_name))
				$data['location'] = '';
			else
			{
				wp_cache_add($post->ID, $post, 'posts');
				$data['location'] = get_permalink();
			}
			$data['lastmod'] = $this->format_lastmod(strtotime($post->post_modified));
			$data['freq'] = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);
			$this->data[] = $data;
		}

		// Probably save some memory ;)
		unset($latest_posts);

		// Always return true if we can get here, otherwise you're stuck at the SQL cycling limit
		return true;
	}
}
?>