<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_ARCHIVE extends BWP_GXS_MODULE
{
	public function __construct()
	{
		// @since 1.3.0 this is left blank
	}

	protected function generate_data()
	{
		global $wpdb;

		$requested = $this->requested;

		if ('monthly' == $requested)
		{
			$latest_post_query = '
				SELECT
					YEAR(post_date) as year,
					MONTH(post_date) as month,
					MAX(post_modified) as post_modified,
					MAX(post_modified_gmt) as post_modified_gmt,
					MAX(comment_count) as comment_count
				FROM ' . $wpdb->posts . "
				WHERE post_status = 'publish'
					AND post_password = ''
					AND post_type = 'post'" . '
				GROUP BY year, month
				ORDER BY post_modified DESC';
		}
		else
		{
			$latest_post_query = '
				SELECT
					YEAR(post_date) as year,
					MAX(post_modified) as post_modified,
					MAX(post_modified_gmt) as post_modified_gmt,
					MAX(comment_count) as comment_count
				FROM ' . $wpdb->posts . "
				WHERE post_status = 'publish'
					AND post_password = ''
					AND post_type <> 'page'" . '
				GROUP BY year
				ORDER BY post_modified DESC';
		}

		$latest_posts = $this->get_results($latest_post_query);

		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		$data = array();

		for ($i = 0; $i < sizeof($latest_posts); $i++)
		{
			$post = $latest_posts[$i];
			$data = $this->init_data($data);

			if ('monthly' == $requested)
				$data['location'] = get_month_link($post->year, $post->month);
			else if ('yearly' == $requested)
				$data['location'] = get_year_link($post->year);

			$data['lastmod']  = $this->get_lastmod($post);
			$data['freq']     = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);

			$this->data[] = $data;
		}

		unset($latest_posts);

		return true;
	}
}
