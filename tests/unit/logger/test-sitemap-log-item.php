<?php

/**
 * @covers BWP_Sitemaps_Logger_Sitemap_LogItem
 */
class BWP_Sitemaps_Logger_Sitemap_LogItem_Test extends PHPUnit_Framework_TestCase
{
	protected $item;

	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Sitemaps_Logger_Sitemap_LogItem::__construct
	 * @dataProvider get_invalid_slugs
	 */
	public function test_should_throw_exception_when_invalid_slug_provided($slug)
	{
		$this->setExpectedException('DomainException', 'provided slug must be string and not empty');

		$item = new BWP_Sitemaps_Logger_Sitemap_LogItem($slug);
	}

	public function get_invalid_slugs()
	{
		return array(
			array(false),
			array(array()),
			array('')
		);
	}

	/**
	 * @covers BWP_Sitemaps_Logger_Sitemap_LogItem::__construct
	 * @dataProvider get_item_data
	 */
	public function test_should_init_correctly($slug, $datetime)
	{
		$item = new BWP_Sitemaps_Logger_Sitemap_LogItem($slug, $datetime);

		$this->assertEquals($slug, $item->get_sitemap_slug());
		$this->assertEquals($datetime, $item->get_storage_datetime());

		$this->assertEquals(new DateTimeZone('UTC'), $item->get_datetime()->getTimezone(), 'datetime should always be converted to UTC');
	}

	public function get_item_data()
	{
		return array(
			array('slug1', '2015-10-05 12:00:00'),
			array('slug2', '2015-10-05 12:00:01'),
			array('slug3', '2015-10-05 12:00:02'),
		);
	}

	/**
	 * @covers BWP_Sitemaps_Logger_Sitemap_LogItem::__construct
	 */
	public function test_should_default_datetime_to_current_time_and_UTC_timezone_if_none_provided()
	{
		$item = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug');

		$this->assertInstanceOf('DateTime', $item->get_datetime());
		$this->assertEquals(new DateTimeZone('UTC'), $item->get_datetime()->getTimezone());
	}
}
