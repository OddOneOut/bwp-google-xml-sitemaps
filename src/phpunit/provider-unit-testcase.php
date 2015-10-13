<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

use \Mockery as Mockery;

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_PHPUnit_Provider_Unit_TestCase extends PHPUnit_Framework_TestCase
{
	protected $bridge;

	protected $cache;

	protected $plugin;

	protected function setUp()
	{
		$this->bridge = Mockery::mock('BWP_WP_Bridge');

		$this->cache  = Mockery::mock('BWP_Cache');

		$this->cache
			->shouldReceive('get')
			->andReturn(false)
			->byDefault();

		$this->plugin = Mockery::mock('BWP_Sitemaps');

		$this->plugin
			->shouldReceive('get_bridge')
			->andReturn($this->bridge)
			->byDefault();
	}

	protected function tearDown()
	{
	}
}
