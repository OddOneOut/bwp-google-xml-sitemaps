<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * 
 * You can take this as a sample module, it is documented rather well ;)
 */

class BWP_GXS_MODULE_POST extends BWP_GXS_MODULE {

	// Declare all properties you need for your modules here
	var $requested;

	function __construct()
	{
		global $bwp_gxs;
		// Give your properties value here
		// $this->set_current_time() should always be called, it will allow you to use $this->now (the current Unix Timestamp).
		$this->set_current_time();
		// $bwp_gxs->module_data hold four things, but you only need to take note of 'sub_module' and 'module_key'
		// For example when you are browsing to http://example.com/taxonomy_categories.xml
		// $bwp_gxs->module_data['sub_module'] is 'category' (the singular name) and $bwp_gxs->module_data['module_key'] is 
		// 'taxonomy_category' (also singular). If you have a custom module for taxonomy_category, you must name your class 
		// BWP_GXS_MODULE_TAXONOMY_CATEGORY and save the file as taxonomy_category.php (similar to taxonomy_post_tag.php).
		$this->requested = $bwp_gxs->module_data['sub_module'];
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

		$requested = $this->requested;

		// If no custom post type is requested, use the default post type
		if (empty($requested))
			$requested = 'post';

		// A standard custom query to fetch posts from database, sorted by their lastmod
		// You can use any type of queries for your modules
		$latest_post_query = '
			SELECT * FROM ' . $wpdb->posts . "
				WHERE post_status = 'publish' AND post_type = %s" . '
			ORDER BY post_modified DESC';
		// Use $this->get_results instead of $wpdb->get_results, remember to escape your query
		// using $wpdb->prepare or $wpdb->escape, @see http://codex.wordpress.org/Function_Reference/wpdb_Class
		$latest_posts = $this->get_results($wpdb->prepare($latest_post_query, $requested));

		// This check helps you stop the cycling sooner
		// It basically means if there is nothing to loop through anymore we return false so the cycling can stop.
		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		// Always init your $data
		$data = array();
		foreach ($latest_posts as $item)
		{
			$post = $item;
			// Init your $data with the previous item's data. This makes sure no item is mal-formed.
			$data = $this->init_data($data);
			// Make use of WP's cache @see http://betterwp.net/9-latest-posts-from-each-post-type/
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