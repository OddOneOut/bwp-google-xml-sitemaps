<?php
/**
 * Copyright (c) 2014 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_TAXONOMY extends BWP_GXS_MODULE
{
	public function __construct()
	{
		global $bwp_gxs;

		// set module data
		$this->set_module_data($bwp_gxs->module_data);

		// @since 1.2.4 use ::set_sort_column to set the appropriate column to
		// sort data by
		$this->set_sort_column('lastmod');
	}

	protected function generate_data()
	{
		global $wpdb, $bwp_gxs;

		$requested = $this->requested;

		$latest_post_query = '
			SELECT MAX(wposts.post_modified) as post_modified, MAX(wposts.comment_count) as comment_count, wptax.term_id FROM ' . $wpdb->term_relationships . ' wprel
				INNER JOIN ' . $wpdb->posts . ' wposts
					ON wprel.object_id = wposts.ID
				INNER JOIN ' . $wpdb->term_taxonomy . ' wptax
					ON wprel.term_taxonomy_id = wptax.term_taxonomy_id' . "
				WHERE wposts.post_status = 'publish'" . '
					AND wptax.taxonomy = %s
					AND wptax.count > 0
			GROUP BY wptax.term_id
			ORDER BY wptax.term_id DESC';

		$latest_posts = $this->get_results($wpdb->prepare($latest_post_query, $requested));

		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		$term_query = '
			SELECT t.*, tt.*
			FROM ' . $wpdb->terms  . ' AS t
			INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt
				ON t.term_id = tt.term_id
			WHERE tt.taxonomy = %s AND tt.count > 0
			ORDER BY t.term_id DESC';

		$terms = $this->get_results($wpdb->prepare($term_query, $requested));

		if (!isset($terms) || 0 == sizeof($terms))
			return false;

		// can be something like array('cat1', 'cat2', 'cat3')
		$exclude_terms = (array) apply_filters('bwp_gxs_term_exclude', array(''), $requested);

		// build an array with term_id as key
		$term2post = array();

		for ($i = 0; $i < sizeof($latest_posts); $i++)
		{
			$post = $latest_posts[$i];

			if (!empty($post->term_id) && !isset($term2post[$post->term_id]))
				$term2post[$post->term_id] = $post;

			unset($post);
		}

		$data = array();

		for ($i = 0; $i < sizeof($terms); $i++)
		{
			$term = $terms[$i];

			if (in_array($term->slug, $exclude_terms))
				continue;

			$data = $this->init_data($data);

			$data['location'] = $this->get_term_link($term, $requested);

			if (isset($term2post[$term->term_id]))
			{
				// this term has at least one post
				$post = $term2post[$term->term_id];

				$data['lastmod']  = $this->get_lastmod($post);
				$data['freq']     = $this->cal_frequency($post);
				$data['priority'] = $this->cal_priority($post, $data['freq']);
			}
			else
			{
				// this term does not have any post, no lastmod can be generated
				$data['freq']     = $this->cal_frequency();
				$data['priority'] = $this->cal_priority(false, $data['freq']);
			}

			$this->data[] = $data;

			unset($post);
			unset($term);
		}

		unset($latest_posts);
		unset($term2post);
		unset($terms);

		return true;
	}
}
