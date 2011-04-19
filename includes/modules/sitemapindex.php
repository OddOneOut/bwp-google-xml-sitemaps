<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_INDEX extends BWP_GXS_MODULE {

	// Declare all properties you need for your modules here
	var $requested_modules = array();

	function __construct($requested)
	{
		// Give your properties value here
		$this->set_current_time();
		$this->requested_modules = $requested;
		// Always call this to start building data
		$this->build_data();
	}

	/**
	 * This is the main function that generates our data.
	 *
	 * If your module deals with heavy queries, for example selecting all posts from the database,
	 * you should not use build_data() directly but rather use generate_data(). Open term.php for more details.
	 */
	function build_data()
	{
		global $wpdb, $bwp_gxs;

		// A better limit for sites that have posts with same last modified date - @since 1.0.2
		$limit = sizeof(get_post_types(array('public' => true))) + 1000;

		$latest_post_query = '
			SELECT *
				FROM
				(
					SELECT post_type, max(post_modified) AS mpmd
					FROM ' . $wpdb->posts . "
					WHERE post_status = 'publish'" . '
					GROUP BY post_type
				) AS f
				INNER JOIN ' . $wpdb->posts . ' AS s ON s.post_type = f.post_type
				AND s.post_modified = f.mpmd
			LIMIT ' . (int) $limit;

		$latest_posts = $wpdb->get_results($latest_post_query);

		// Build a temporary array holding post type and their latest modified date, sorted by post_modified
		foreach ($latest_posts as $a_post)
		{
			$temp_posts[$a_post->post_type] = $this->format_lastmod(strtotime($a_post->post_modified));
		}
		arsort($temp_posts);
		$prime_lastmod = current($temp_posts);

		$taxonomies = $bwp_gxs->taxonomies;

		$data = array();
		foreach ($this->requested_modules as $item)
		{
			$data = $this->init_data($data);
			$data['location'] = $this->get_xml_link($item[0]);
			if (isset($item[1]))
			{
				if (isset($item[1]['post']))
				{
					$the_post = $this->get_post_by_post_type($item[1]['post'], $latest_posts);
					if ($the_post)
						$data['lastmod'] = $this->format_lastmod(strtotime($the_post->post_modified));
				}
				else if (isset($item[1]['taxonomy']))
				{
					foreach ($temp_posts as $post_type => $modified_time)
					{
						if ($this->post_type_uses($post_type, $taxonomies[$item[1]['taxonomy']]))
							$data['lastmod'] = $this->format_lastmod(strtotime($modified_time));
					}
				}
				else if (isset($item[1]['archive']))
					$data['lastmod'] = $prime_lastmod;
			}
			// Just in case something went wrong - @since 1.0.2
			if (empty($data['lastmod']))
				$data['lastmod'] = $prime_lastmod;
			// Pass data back to the plugin
			$this->data[] = $data;
		}
	}
}
?>