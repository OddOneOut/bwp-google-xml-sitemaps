<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_Handler_AjaxHandler
{
	/**
	 * @var BWP_Sitemaps_Provider
	 */
	protected $provider;

	/**
	 * @var BWP_Sitemaps_Excluder
	 */
	protected $excluder;

	/**
	 * @var BWP_WP_Bridge
	 */
	protected $bridge;

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

	protected function response_with(array $data)
	{
		@header('Content-Type: application/json');

		echo json_encode($data);

		exit;
	}

	protected function fail()
	{
		echo 0; exit;
	}

	protected function succeed()
	{
		echo 1; exit;
	}
}
