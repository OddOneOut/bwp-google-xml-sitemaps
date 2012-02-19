<?php
/**
 * Copyright (c) 2012 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * 
 * This is a sample custom module. Some if about the module developer here would be nice!
 */

class BWP_GXS_MODULE_POST_MOST_POPULAR extends BWP_GXS_MODULE {

	function __construct()
	{
		// Give your properties value here
		// $this->set_current_time() should always be called, it will allow you to use $this->now (the current Unix Timestamp).
		$this->set_current_time();
		$this->perma_struct = get_option('permalink_structure');
		// Always call this to start building data
		// If you want to make use of SQL cycling (a method to reduce heavy queries), don't use build_data() like other modules.
		// Just call it here and use function generate_data() to build actual data, just like below. Use SQL cycling when
		// you are dealing with post-based sitemaps.
		$this->build_data();
	}

	/**
	 * This is the main function that generates our data.
	 *
	 * Since we are dealing with heavy queries here, it's better that you use 
	 * generate_data() which will get called by build_data(). This way you will query for no more than 
	 * the SQL limit configurable in this plugin's option page.
	 * If you happen to use LIMIT in your SQL statement for other reasons then use build_data() instead.
	 */
	function generate_data()
	{
		global $wpdb, $bwp_gxs, $post;

		$latest_post_query = '
			SELECT * FROM ' . $wpdb->posts . "
				WHERE post_status = 'publish' AND post_type = 'post' AND comment_count > 2" . '
			ORDER BY comment_count, post_modified DESC';

		// Use $this->get_results instead of $wpdb->get_results, remember to escape your query
		// using $wpdb->prepare or $wpdb->escape, @see http://codex.wordpress.org/Function_Reference/wpdb_Class
		$latest_posts = $this->get_results($latest_post_query);

		// This check helps you stop the cycling sooner
		// It basically means if there is nothing to loop through anymore we return false so the cycling can stop.
		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		$using_permalinks = $this->using_permalinks();

		// Always init your $data
		$data = array();
		for ($i = 0; $i < sizeof($latest_posts); $i++)
		{
			$post = $latest_posts[$i];
			// Init your $data with the previous item's data. This makes sure no item is mal-formed.
			$data = $this->init_data($data);
			if ($using_permalinks && empty($post->post_name))
				$data['location'] = '';
			else
				$data['location'] = get_permalink();
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