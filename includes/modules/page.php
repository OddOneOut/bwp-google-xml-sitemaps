<?php
/**
 * Copyright (c) 2014 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_PAGE extends BWP_GXS_MODULE
{
	public function __construct()
	{
		// @since 1.2.4 this method is empty
	}

	protected function generate_data()
	{
		global $wpdb, $bwp_gxs, $post;

		$sql_where = apply_filters('bwp_gxs_post_where', '', 'page');

		$latest_post_query = '
			SELECT *
			FROM ' . $wpdb->posts . " wposts
			WHERE wposts.post_status = 'publish'
				AND wposts.post_type = 'page' $sql_where" . '
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

			$data['lastmod']  = $this->get_lastmod($post);
			$data['freq']     = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);

			$this->data[] = $data;
		}

		unset($latest_posts);

		// always return true if we can get here,
		// otherwise we're stuck in a SQL cycling loop
		return true;
	}
}
