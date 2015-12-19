<?php

use \Mockery as Mockery;

/**
 * @covers BWP_Sitemaps_Sitemap_Provider
 */
class BWP_Sitemaps_Sitemap_Provider_Test extends PHPUnit_Framework_TestCase
{
	protected $plugin;

	protected $bridge;

	protected $module;

	protected $provider;

	protected $host = 'http://example.com';

	public function setUp()
	{
		$this->bridge = Mockery::mock('BWP_WP_Bridge');

		$this->bridge
			->shouldReceive('home_url')
			->andReturn($this->host)
			->byDefault();

		$this->plugin = Mockery::mock('BWP_Sitemaps');

		$this->plugin
			->shouldReceive('get_version')
			->andReturn('1.4.0')
			->byDefault();

		$this->plugin
			->shouldReceive('get_bridge')
			->andReturn($this->bridge)
			->byDefault();

		$this->module = Mockery::mock('BWP_GXS_MODULE');

		$this->provider = new BWP_Sitemaps_Sitemap_Provider($this->plugin, $this->module);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Provider::get_items
	 * @dataProvider get_test_get_items_cases
	 */
	public function test_get_items(array $module_data, array $expected)
	{
		$this->module
			->shouldReceive('get_data')
			->andReturn($module_data)
			->byDefault();

		$this->assertEquals($expected, $this->provider->get_items());
	}

	public function get_test_get_items_cases()
	{
		return array(
			'no invalid item' => array(
				array(
					array('location' => $this->host . '/a-url')
				),
				array(
					array('location' => $this->host . '/a-url')
				)
			),

			'some invalid items' => array(
				array(
					array('location' => ''),
					array('no-location' => ''),
					array('location' => $this->host . '/a-url'),
					/* array('location' => 'https://example.com'), */
					array('location' => 'http://domain.com')
				),
				array(
					array('location' => $this->host . '/a-url')
				)
			)
		);
	}
}
