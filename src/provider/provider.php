<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_Provider
{
	protected $plugin;

	protected $bridge;

	protected $excluder;

	public function __construct(BWP_Sitemaps $plugin, BWP_Sitemaps_Excluder $excluder)
	{
		$this->plugin = $plugin;
		$this->bridge = $plugin->get_bridge();

		$this->excluder = $excluder;
	}

	public function get_exluder()
	{
		return $this->excluder;
	}

	public function get_bridge()
	{
		return $this->bridge;
	}

	public function get_domain()
	{
		return $this->plugin->domain;
	}
}
