<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_Handler_Ajax_WPContentHandler extends BWP_Sitemaps_Handler_AjaxHandler
{
	/**
	 * @var BWP_Sitemaps_Provider
	 */
	protected $provider;

	/**
	 * @var BWP_Sitemaps_Excluder
	 */
	protected $excluder;

	public function __construct(BWP_Sitemaps_Provider $provider)
	{
		$this->provider = $provider;
		$this->excluder = $provider->get_exluder();
		$this->bridge   = $provider->get_bridge();
	}

	/**
	 * Remove excluded item action
	 */
	public function remove_excluded_item_action()
	{
		$this->bridge->check_ajax_referer('bwp_gxs_remove_excluded_item');

		if (($group = BWP_Framework_Util::get_request_var('group'))
			&& ($id = BWP_Framework_Util::get_request_var('id'))
		) {
			$excluded_items = $this->excluder->get_excluded_items($group);

			$key = array_search($id, $excluded_items);

			if ($key !== false) {
				unset($excluded_items[$key]);
				$this->excluder->update_excluded_items($group, $excluded_items);
				$this->succeed();
			}
		}

		$this->fail();
	}
}
