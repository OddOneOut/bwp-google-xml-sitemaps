<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_PAGE extends BWP_GXS_MODULE_POST
{
	public function __construct()
	{
		// @since 1.3.0 this is left blank
	}

	protected function generate_data()
	{
		global $wpdb, $post;

		// @since 1.3.1 use a different filter hook that expects an array instead
		$excluded_posts   = apply_filters('bwp_gxs_excluded_posts', array(), 'page');
		$exclude_post_sql = sizeof($excluded_posts) > 0
			? ' AND p.ID NOT IN (' . implode(',', $excluded_posts) . ') '
			: '';

		// @since 1.3.1 this should be used to add other things to the SQL
		// instead of excluding posts
		$sql_where = apply_filters('bwp_gxs_post_where', '', 'page');
		$sql_where = str_replace('wposts', 'p', $sql_where);

		$latest_post_query = '
			SELECT *
			FROM ' . $wpdb->posts . " p
			WHERE p.post_status = 'publish'
				AND p.post_password = ''
				AND p.post_type = 'page'"
				. $exclude_post_sql
				. $sql_where . '
			ORDER BY p.post_modified DESC';

		$latest_posts = $this->get_results($latest_post_query);

		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		$using_permalinks = $this->using_permalinks();

		$data = array();

		// @since 1.4.0 try to get image ids as well
		$image_ids = $this->get_image_ids_from_posts($latest_posts);

		for ($i = 0; $i < sizeof($latest_posts); $i++)
		{
			$post = $latest_posts[$i];
			$data = $this->init_data($data);

			if ($using_permalinks && empty($post->post_name))
			{
				$data['location'] = '';
			}
			else
			{
				wp_cache_add($post->ID, $post, 'posts');
				$data['location'] = get_permalink();
			}

			$data['lastmod']  = $this->get_lastmod($post);
			$data['freq']     = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);

			// prepare an image if there's any
			if (isset($image_ids[$post->ID]))
				$data['image'] = $this->get_image_data($image_ids[$post->ID]);

			$this->data[] = $data;
		}

		unset($latest_posts);

		// always return true if we can get here,
		// otherwise we're stuck in a SQL cycling loop
		return true;
	}
}
