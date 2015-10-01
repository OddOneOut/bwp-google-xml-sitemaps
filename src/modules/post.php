<?php
/**
 * Copyright (c) 2014 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 *
 * You can take this as a sample module, it is documented rather well ;)
 */

class BWP_GXS_MODULE_POST extends BWP_GXS_MODULE
{
	public function __construct()
	{
		// $this->set_current_time() should always be called, it will allow you
		// to use $this->now (the current Unix Timestamp).
		// @since 1.3.0 this is called from main class
		/* $this->set_current_time(); */

		// $this->module_data hold four things, but you only need to take
		// note of 'sub_module' and 'module_name'. For example when you are
		// browsing to http://example.com/taxonomy_category.xml
		// $this->module_data['sub_module'] is 'category' (the singular
		// name) and $this->module_data['module_name'] is 'taxonomy_category'
		// (also singular). If you have a custom module for taxonomy_category,
		// you must name your class BWP_GXS_MODULE_TAXONOMY_CATEGORY and save
		// the file as taxonomy_category.php (same goes for taxonomy_post_tag.php).
		// If no custom post type is requested, use the default post type
		// @since 1.3.0 this is set in main class
		/* $this->requested = !empty($this->module_data['sub_module']) */
		/* 	? $this->module_data['sub_module'] : 'post'; */

		// $this->module_data['part'] let you determine whether or not to build
		// a post sitemap as part of a large post sitemap. If this value is
		// greater than 0, for example 2, or 3 it means that we are building
		// part 2, or part 3 of that large sitemap, and we will have to modify
		// our SQL query accordingly - @since BWP GXS 1.1.0
		// @since 1.3.0 this is set in main class
		/* $this->part = $bwp_gxs->module_data['module_part']; */

		// properties that are not dependent on module data can be initiated here,
		// otherwise must be initiated using ::init_module_properties method
		$this->perma_struct = get_option('permalink_structure');

		// @since 1.3.0 no need to manually call this anymore
		/* $this->build_data(); */
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

		$requested = $this->requested;

		// @since 1.3.0 use a different filter hook that expects an array instead
		$excluded_posts   = apply_filters('bwp_gxs_excluded_posts', array(), $requested);
		$exclude_post_sql = sizeof($excluded_posts) > 0
			? ' AND p.ID NOT IN (' . implode(',', $excluded_posts) . ') '
			: '';

		// @since 1.3.0 this should be used to add other things to the SQL
		// instead of excluding posts
		$sql_where = apply_filters('bwp_gxs_post_where', '', $requested);
		$sql_where = str_replace('wposts', 'p', $sql_where); // @since 1.3.0 use a different alias for post table

		if ('post' == $requested && strpos($this->perma_struct, '%category%') !== false)
		{
			// If $requested is 'post' and this site uses %category% in
			// permalink structure, we will have to use a complex SQL query so
			// this plugin can scale up to millions of posts.
			// @since 1.3.0 do not fetch posts that are password-protected
			$latest_post_query = '
				SELECT *
				FROM ' . $wpdb->term_relationships . ' tr
				INNER JOIN ' . $wpdb->posts . ' p
					ON tr.object_id = p.ID' . "
					AND p.post_status = 'publish'
					AND p.post_password = ''" . '
				INNER JOIN ' . $wpdb->term_taxonomy . ' tt
					ON tr.term_taxonomy_id = tt.term_taxonomy_id' . "
					AND tt.taxonomy = 'category'" . '
				, ' . $wpdb->terms . ' t
				WHERE tt.term_id = t.term_id '
					. "$exclude_post_sql"
					. "$sql_where" . '
				GROUP BY p.ID
				ORDER BY p.post_modified DESC';
		}
		else
		{
			// @since 1.3.0 do not fetch posts that are password-protected
			$latest_post_query = '
				SELECT *
				FROM ' . $wpdb->posts . " p
				WHERE p.post_status = 'publish'
					AND p.post_password = ''
					AND p.post_type = %s
					$exclude_post_sql
					$sql_where" . '
				ORDER BY p.post_modified DESC';
		}

		// Use $this->get_results instead of $wpdb->get_results, remember to
		// escape your query using $wpdb->prepare or $wpdb->escape, @see
		// http://codex.wordpress.org/Function_Reference/wpdb_Class
		$latest_posts = $this->get_results($wpdb->prepare($latest_post_query, $requested));

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

			// init your $data with the previous item's data. This makes sure
			// no item is mal-formed.
			$data = $this->init_data($data);

			// @since 1.1.0 - get permalink independently, as we don't need
			// caching or some complicated stuff. If permalink is being used,
			// yet postname is missing, ignore this item
			if ($using_permalinks && empty($post->post_name))
				$data['location'] = '';
			else
				$data['location'] = $this->get_permalink();

			$data['lastmod']  = $this->get_lastmod($post);
			$data['freq']     = $this->cal_frequency($post);
			$data['priority'] = $this->cal_priority($post, $data['freq']);

			// Pass data back to the plugin to handle
			$this->data[] = $data;
		}

		unset($latest_posts);

		// always return true if we can get here, otherwise you're stuck in a
		// SQL cycling loop
		return true;
	}
}
