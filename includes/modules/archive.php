<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_ARCHIVE extends BWP_GXS_MODULE {

	function __construct()
	{
		$this->set_current_time();
		$this->build_data();
	}

	function build_data()
	{
		global $wpdb, $bwp_gxs;

		// There's no easy way getting the date archive
		// We will just call wp_get_archives and get the cache it creates
		$requested = $bwp_gxs->module_data['sub_module'];
		wp_get_archives(array('echo' => 0, 'type' => $requested));
		$archives = current(wp_cache_get('wp_get_archives' , 'general'));
		
		$top_date = $archives[0]->year . '-12-31 12:00:00';
		$bottom_date = $archives[count($archives)-1]->year . '-01-01 00:00:00';
		$latest_post_query = '
			SELECT * FROM ' . $wpdb->posts . "
				WHERE post_status = 'publish' AND post_type <> 'page'
					AND post_modified >= '$bottom_date' AND post_modified <= '$top_date'" . '
			ORDER BY post_modified DESC';
		$latest_posts = $wpdb->get_results($latest_post_query);

		if ('monthly' == $requested)
		{
			foreach ($archives as $archive)
			{
				$data = array();
				$data['location'] = get_month_link($archive->year, $archive->month);
				foreach ($latest_posts as $post)
				{
					if ($archive->month == (int) date('n', strtotime($post->post_modified)))
					{
						$data['lastmod'] = $this->format_lastmod(strtotime($post->post_modified));
						$data['freq'] = $this->cal_frequency($post);
						$data['priority'] = $this->cal_priority($post, $data['freq']);
						break;
					}
				}
				$this->data[] = $data;
			}
		}
		else if ('yearly' == $requested)
		{
			foreach ($archives as $archive)
			{
				$data = array();
				$data['location'] = get_year_link($archive->year);
				foreach ($latest_posts as $post)
				{
					if ($archive->year == (int) date('Y', strtotime($post->post_modified)))
					{
						$data['lastmod'] = $this->format_lastmod(strtotime($post->post_modified));
						$data['freq'] = $this->cal_frequency($post);
						$data['priority'] = $this->cal_priority($post, $data['freq']);
						break;
					}
				}
				$this->data[] = $data;
			}			
		}
	}
}
?>