<?php

use \Mockery as Mockery;

/**
 * @covers BWP_Sitemaps_Provider_ExternalPage
 */
class BWP_Sitemaps_Provider_ExternalPage_Test extends BWP_Sitemaps_PHPUnit_Provider_Unit_TestCase
{
	protected $provider;

	protected function setUp()
	{
		parent::setUp();

		$this->provider = new BWP_Sitemaps_Provider_ExternalPage($this->plugin, 'storage_key');
	}

	protected function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @covers BWP_Sitemaps_Provider_ExternalPage::get_pages
	 */
	public function test_get_pages_with_no_limit()
	{
		$pages = array('page1', 'page2');

		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($pages)
			->byDefault();

		$this->assertEquals($pages, $this->provider->get_pages());
	}

	/**
	 * @covers BWP_Sitemaps_Provider_ExternalPage::get_pages
	 */
	public function test_get_pages_with_limit()
	{
		$pages = array('page1', 'page2', 'page3', 'page4');

		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($pages)
			->byDefault();

		$this->assertEquals(array('page1', 'page2'), $this->provider->get_pages(2));
	}

	/**
	 * @covers BWP_Sitemaps_Provider_ExternalPage::get_pages_for_display
	 */
	public function test_get_pages_for_display()
	{
		$pages = array(
			'page1' => array(
				'frequency'     => 'always',
				'priority'      => '1.0',
				'last_modified' => '2015-10-20 20:00'
			),
			'page2' => array(
				'frequency'     => 'hourly',
				'priority'      => '0.8',
				'last_modified' => '2015-10-20 12:00'
			),
			'page3-only-url' => array()
		);

		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($pages)
			->byDefault();

		$this->plugin
			->shouldReceive('get_current_timezone')
			->andReturn(new DateTimeZone('Asia/Bangkok'))
			->byDefault();

		$this->assertEquals(array(
			'page1' => array(
				'url'           => 'page1',
				'frequency'     => 'always',
				'priority'      => 1.0,
				'last_modified' => '2015-10-21 03:00'
			),
			'page2' => array(
				'url'           => 'page2',
				'frequency'     => 'hourly',
				'priority'      => 0.8,
				'last_modified' => '2015-10-20 19:00'
			),
			'page3-only-url' => array(
				'url'           => 'page3-only-url',
				'frequency'     => null,
				'priority'      => null,
				'last_modified' => null
			)
		), $this->provider->get_pages_for_display());
	}

	/**
	 * @covers BWP_Sitemaps_Provider_ExternalPage::get_page
	 */
	public function test_get_page()
	{
		$pages = array(
			'page1' => array('data1'),
			'page2' => array('data2'),
			'page3' => array('data3'),
			'page4' => array('data4')
		);

		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($pages)
			->byDefault();

		$this->assertEquals(array('data1'), $this->provider->get_page('page1'));
	}
}
