<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_TAXONOMY extends BWP_GXS_MODULE
{
	public function __construct()
	{
		// @since 1.3.0 use ::set_sort_column to set the appropriate column to
		// sort data by
		$this->set_sort_column('lastmod');
	}

	protected function generate_data()
	{
		global $wpdb, $bwp_gxs;

		$requested = $this->requested;

		// @since 1.3.0 do not fetch posts that are password-protected
		$latest_post_query = '
			SELECT
				MAX(p.post_modified) as post_modified,
				MAX(p.post_modified_gmt) as post_modified_gmt,
				MAX(p.comment_count) as comment_count,
				tt.term_id
			FROM ' . $wpdb->term_relationships . ' tr
			INNER JOIN ' . $wpdb->posts . ' p
				ON tr.object_id = p.ID
			INNER JOIN ' . $wpdb->term_taxonomy . ' tt
				ON tr.term_taxonomy_id = tt.term_taxonomy_id' . "
			WHERE p.post_status = 'publish'
				AND p.post_password = ''" . '
				AND tt.taxonomy = %s
				AND tt.count > 0
			GROUP BY tt.term_id
			ORDER BY tt.term_id DESC';

		$latest_posts = $this->get_results($wpdb->prepare($latest_post_query, $requested));

		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		/**
		 * Filter terms that are added to a taxonomy-based sitemaps.
		 *
		 * @example hooks/filter_bwp_gxs_excluded_terms.php 2
		 *
		 * @param array $term_ids  Term IDs to exclude.
		 * @param string $taxonomy The current taxonomy. See
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy for
		 * more info.
		 *
		 * @since 1.3.0
		 */
		$excluded_term_ids = apply_filters('bwp_gxs_excluded_terms', array(), $requested);

		$excluded_term_ids_sql = count($excluded_term_ids) > 0
			? ' AND t.term_id NOT IN (' . implode(',', array_map('intval', $excluded_term_ids)) . ') ' : '';

		// @deprecated 1.3.0, use `bwp_gxs_excluded_term_slugs` instead
		$excluded_terms = apply_filters('bwp_gxs_term_exclude', array(), $requested);

		/**
		 * Filter terms that are added to a taxonomy-based sitemaps using their slugs.
		 *
		 * @example hooks/filter_bwp_gxs_excluded_term_slugs.php 2
		 *
		 * @param array $term_slugs Term slugs to exclude, e.g. 'tag1', 'category-2' etc.
		 * @param string $taxonomy The current taxonomy.
		 *
		 * @since 1.4.0
		 */
		$excluded_term_slugs = apply_filters('bwp_gxs_excluded_term_slugs', $excluded_terms, $requested);
		$excluded_term_slugs_placeholders = array_fill(0, count($excluded_term_slugs), '%s');

		$excluded_term_slugs_sql = count($excluded_term_slugs) > 0
			? ' AND t.slug NOT IN (' . implode(',', $excluded_term_slugs_placeholders) . ') ' : '';

		$excluded_term_slugs_sql = !empty($excluded_term_slugs_sql)
			? $wpdb->prepare($excluded_term_slugs_sql, $excluded_term_slugs) : '';

		$term_query = '
			SELECT t.*, tt.*
			FROM ' . $wpdb->terms  . ' as t
			INNER JOIN ' . $wpdb->term_taxonomy . ' as tt
				ON t.term_id = tt.term_id
			WHERE tt.taxonomy = %s
				AND tt.count > 0 '
				. $excluded_term_ids_sql
				. $excluded_term_slugs_sql . '
			ORDER BY t.term_id DESC';

		$terms = $this->get_results($wpdb->prepare($term_query, $requested));

		if (!isset($terms) || 0 == sizeof($terms))
			return false;

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
