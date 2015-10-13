<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Handler_Ajax_TaxonomyHandler extends BWP_Sitemaps_Handler_AjaxHandler
{
	public function __construct(BWP_Sitemaps_Provider_Taxonomy $provider)
	{
		$this->provider = $provider;
		$this->excluder = $provider->get_exluder();
		$this->bridge   = $provider->get_bridge();
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
