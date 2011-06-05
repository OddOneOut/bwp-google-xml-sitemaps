<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_ARCHIVE extends BWP_GXS_MODULE {

	var $requested = '';

	function __construct()
	{
		global $bwp_gxs;

		$this->set_current_time();
		$this->requested = $bwp_gxs->module_data['sub_module'];
		$this->build_data();
	}

	function generate_data()
	{
		global $wpdb;

		$requested = $this->requested;

		if ('monthly' == $requested)
			$latest_post_query = '
				SELECT YEAR(post_date) AS year, MONTH(post_date) as month, COUNT(ID) as posts, comment_count, post_modified
					FROM ' . $wpdb->posts . "
					WHERE post_status = 'publish' AND post_type <> 'page'" . '
					GROUP BY YEAR(post_date), MONTH(post_date)
				ORDER BY post_modified DESC';
		else
			$latest_post_query = '
				SELECT YEAR(post_date) AS year, COUNT(ID) as posts, comment_count, post_modified
					FROM ' . $wpdb->posts . "
					WHERE post_status = 'publish' AND post_type <> 'page'" . '
					GROUP BY YEAR(post_date)
				ORDER BY post_modified DESC';

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
			$data['lastmod'] = $this->format_lastmod(strtotime($post->post_modified));
			$data['freq'] = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);
			$this->data[] = $data;
		}
		
		unset($latest_posts);
		
		return true;
	}
}
?>