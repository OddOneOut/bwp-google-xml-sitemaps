<?php
/**
 * Copyright (c) 2012 Khang Minh <betterwp.net>
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
		// For example when you are browsing to http://example.com/taxonomy_category.xml
		// $bwp_gxs->module_data['sub_module'] is 'category' (the singular name) and $bwp_gxs->module_data['module_key'] is 
		// 'taxonomy_category' (also singular). If you have a custom module for taxonomy_category, you must name your class 
		// BWP_GXS_MODULE_TAXONOMY_CATEGORY and save the file as taxonomy_category.php (similar to taxonomy_post_tag.php).
		// If no custom post type is requested, use the default post type
		$this->requested = (!empty($bwp_gxs->module_data['sub_module'])) ? $bwp_gxs->module_data['sub_module'] : 'post';
		// module_part let you determine whether or not to build a post sitemap as part of a large post sitemap. 
		// If this value is greater than 0, for example 2, or 3 it means that we are building part 2, 
		// or part 3 of that large sitemap, and we will have to modify our SQL query accordingly - @since 1.1.0
		$this->part = $bwp_gxs->module_data['module_part'];
		// Get the permalink this website uses, apply to normal posts
		$this->perma_struct = get_option('permalink_structure');
		// See if the current post_type is a hierarchical one or not
		$this->post_type = get_post_type_object($this->requested);
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

		// Can be something like: ` AND wposts.ID NOT IN (1,2,3,4) `
		$sql_where = apply_filters('bwp_gxs_post_where', '', $requested);

		// A standard custom query to fetch posts from database, sorted by their lastmod
		// You can use any type of queries for your modules
		// If $requested is 'post' and this site uses %category% in permalink structure,
		// we will have to use a complex SQL query so this plugin can scale up to millions of posts.
		if ('post' == $requested && strpos($this->perma_struct, '%category%') !== false)
			$latest_post_query = '
				SELECT * FROM ' . $wpdb->term_relationships . ' wprel
					INNER JOIN ' . $wpdb->posts . ' wposts
						ON wprel.object_id = wposts.ID' . "
						AND wposts.post_status = 'publish'" . '
					INNER JOIN ' . $wpdb->term_taxonomy . ' wptax
						ON wprel.term_taxonomy_id = wptax.term_taxonomy_id' . "
						AND wptax.taxonomy = 'category'" . '
					, ' . $wpdb->terms . ' wpterms
					WHERE wptax.term_id = wpterms.term_id ' 
					. "$sql_where" . '
				GROUP BY wposts.ID
				ORDER BY wposts.post_modified DESC';
		else
			$latest_post_query = '
				SELECT * FROM ' . $wpdb->posts . " wposts
					WHERE wposts.post_status = 'publish' AND wposts.post_type = %s $sql_where" . '
				ORDER BY wposts.post_modified DESC';
		// Use $this->get_results instead of $wpdb->get_results, remember to escape your query
		// using $wpdb->prepare or $wpdb->escape, @see http://codex.wordpress.org/Function_Reference/wpdb_Class
		$latest_posts = $this->get_results($wpdb->prepare($latest_post_query, $requested));
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
			// @since 1.1.0 - get permalink independently, as we don't need caching or some complicated stuff
			// If permalink is being used, yet postname is missing, ignore this item
			if ($using_permalinks && empty($post->post_name))
				$data['location'] = '';
			else
				$data['location'] = $this->get_permalink();
			$data['lastmod'] = $this->format_lastmod(strtotime($post->post_modified));
			$data['freq'] = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);
			// Pass data back to the plugin to handle
			$this->data[] = $data;
		}

		// Probably save some memory ;)
		unset($latest_posts);

		// Always return true if we can get here, otherwise you're stuck at the SQL cycling limit
		return true;
	}
}
?>