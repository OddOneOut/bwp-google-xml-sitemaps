<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_POST_MOST_POPULAR extends BWP_GXS_MODULE
{
	public function __construct()
	{
		$this->perma_struct = get_option('permalink_structure');
	}

	protected function init_module_properties()
	{
		$this->post_type = get_post_type_object($this->requested);
	}

	/**
	 * This is the main function that generates our data.
	 *
	 * Since we are dealing with heavy queries here, it's better that you use
	 * generate_data() which will get called by build_data(). This way you will
	 * query for no more than the SQL limit configurable in this plugin's
	 * option page. If you happen to use LIMIT in your SQL statement for other
	 * reasons then use build_data() instead.
	 */
	protected function generate_data()
	{
		global $wpdb, $bwp_gxs, $post;

		$latest_post_query = '
			SELECT *
			FROM ' . $wpdb->posts . "
			WHERE post_status = 'publish'
				AND post_type = 'post'
				AND comment_count > 2" . '
			ORDER BY comment_count DESC, post_modified DESC';

		// Use $this->get_results instead of $wpdb->get_results, remember to
		// escape your query using $wpdb->prepare or $wpdb->escape,
		// @see http://codex.wordpress.org/Function_Reference/wpdb_Class
		$latest_posts = $this->get_results($latest_post_query);

		// This check helps you stop the cycling sooner. It basically means if
		// there is nothing to loop through anymore we return false so the
		// cycling can stop.
		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		$using_permalinks = $this->using_permalinks();

		// always init your $data
		$data = array();

		for ($i = 0; $i < sizeof($latest_posts); $i++)
		{
			$post = $latest_posts[$i];
			$data = $this->init_data($data);

			if ($using_permalinks && empty($post->post_name))
				$data['location'] = '';
			else
				$data['location'] = get_permalink();

			$data['lastmod']  = $this->get_lastmod($post);
			$data['freq']     = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);

			$this->data[] = $data;
		}

		unset($latest_posts);

		// always return true if we can get here, otherwise you're stuck in the
		// SQL cycling loop
		return true;
	}
}
