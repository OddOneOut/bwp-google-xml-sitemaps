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
	 * Get all public taxonomies, except for post_format
	 *
	 * @return array
	 */
	public function get_taxonomies()
	{
		$taxonomies = array();
		$all_taxonomies = $this->bridge->get_taxonomies(array('public' => true), 'objects');

		foreach ($all_taxonomies as $taxonomy) {
			if ('post_format' === $taxonomy->name) {
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
