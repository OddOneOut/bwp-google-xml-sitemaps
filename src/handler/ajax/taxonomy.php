<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Handler_Ajax_TaxonomyHandler extends BWP_Sitemaps_Handler_Ajax_WPContentHandler
{
	public function __construct(BWP_Sitemaps_Provider $provider)
	{
		if (! ($provider instanceof BWP_Sitemaps_Provider_Taxonomy)) {
			throw new InvalidArgumentException(sprintf(
				'expect a provider of type "%s", type "%s" provided.',
				'BWP_Sitemaps_Provider_Taxonomy',
				get_class($provider)
			));
		}

		parent::__construct($provider);
	}

	/**
	 * Get taxonomies action
	 *
	 * Response should contain ESCAPED contents.
	 */
	public function get_taxonomies_action()
	{
		$items = array();

		if ($post_type = BWP_Framework_Util::get_request_var('post_type')) {
			$taxonomies = $this->provider->get_taxonomies($post_type);

			foreach ($taxonomies as $taxonomy) {
				$items[] = array(
					'name'  => esc_attr($taxonomy->name),
					'title' => esc_html($taxonomy->labels->singular_name)
				);
			}
		}

		$this->response_with($items);
	}

	/**
	 * Get terms action
	 *
	 * Response should contain UNESCAPED contents.
	 */
	public function get_terms_action()
	{
		$items = array();

		if (($taxonomy = BWP_Framework_Util::get_request_var('group'))
			&& ($name = BWP_Framework_Util::get_request_var('q'))
		) {
			$terms = $this->provider->get_terms_by_name($taxonomy, $name);

			foreach ($terms as $term) {
				$items[] = array(
					'id'    => (int) $term->term_id,
					'title' => $term->name
				);
			}
		}

		$this->response_with(array('items' => $items));
	}

	/**
	 * Get excluded terms action
	 *
	 * Response should contain ESCAPED contents
	 */
	public function get_excluded_terms_action()
	{
		$items = array();

		if (($taxonomy = BWP_Framework_Util::get_request_var('group'))
			&& ($excluded_items = $this->excluder->get_excluded_items($taxonomy))
		) {
			$terms = $this->provider->get_terms(
				$taxonomy, $excluded_items
			);

			foreach ($terms as $term) {
				$items[] = array(
					'id'    => (int) $term->term_id,
					'title' => esc_html($term->name)
				);
			}
		}

		$this->response_with($items);
	}
}
