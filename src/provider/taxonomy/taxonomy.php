<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Provider_Taxonomy extends BWP_Sitemaps_Provider
{
	/**
	 * Get all public taxonomies, optionally filtered by post type
	 *
	 * This should never return post format
	 *
	 * @param string $post_type optional, default to get all taxonomies
	 * @return array
	 */
	public function get_taxonomies($post_type = null)
	{
		$taxonomies = array();

		$all_taxonomies = !empty($post_type)
			? $this->bridge->get_object_taxonomies($post_type, 'objects')
			: $this->bridge->get_taxonomies(array('public' => true), 'objects');

		foreach ($all_taxonomies as $taxonomy) {
			// do not return post format or private taxonomy
			if ('post_format' === $taxonomy->name || !$taxonomy->public) {
				continue;
			}

			$taxonomies[] = $taxonomy;
		}

		return $taxonomies;
	}

	/**
	 * Get all terms of a specific taxonomy, optionally filtered by term ids
	 *
	 * @param string $taxonomy
	 * @param array $ids
	 * @param array $excluded_ids
	 * @param int $limit default to retrieve only last 200 terms
	 * @return array
	 */
	public function get_terms($taxonomy, array $ids = array(), array $excluded_ids = array(), $limit = 200)
	{
		if ($ids && $excluded_ids) {
			throw new DomainException('only term ids or excluded term ids can be provided, not both');
		}

		$terms = $this->bridge->get_terms($taxonomy, array(
			'include'    => $ids,
			'exclude'    => $excluded_ids,
			'number'     => (int) $limit,
			'hide_empty' => false
		));

		return $terms;
	}

	/**
	 * Get all terms of a specific taxonomy, without a limit
	 *
	 * @param string $taxonomy
	 * @return array
	 */
	public function get_all_terms($taxonomy)
	{
		return $this->get_terms($taxonomy, array(), array(), 0);
	}

	/**
	 * Get all terms, filtered by their names
	 *
	 * This is a case insensitive search
	 *
	 * @param string $taxonomy
	 * @param string $name part of the name
	 * @return array
	 */
	public function get_terms_by_name($taxonomy, $name)
	{
		// search for terms with matching name, but dont take into account
		// already excluded terms
		$terms = $this->bridge->get_terms($taxonomy, array(
			'name__like' => $name,
			'exclude'    => $this->excluder->get_excluded_items($taxonomy),
			'hide_empty' => false
		));

		return $terms;
	}
}
