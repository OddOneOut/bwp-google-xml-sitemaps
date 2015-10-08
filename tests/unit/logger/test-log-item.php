<?php

/**
 * @covers BWP_Sitemaps_Logger_LogItem
 */
class BWP_Sitemaps_Logger_LogItem_Test extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Sitemaps_Logger_LogItem::get_datetime
	 * @covers BWP_Sitemaps_Logger_LogItem::get_formatted_datetime
	 * @dataProvider get_datetime_zones
	 */
	public function test_get_datetime_should_return_datetime_with_correct_timezone_set($datetime, $timezone_string, $expected_formatted_datetime)
	{
		$item = $this->getMockForAbstractClass('BWP_Sitemaps_Logger_LogItem');

		$item->set_datetime(new DateTime($datetime, new DateTimeZone('UTC')));
		$item->set_datetimezone(new DateTimeZone($timezone_string));

		$this->assertEquals($expected_formatted_datetime, $item->get_datetime()->format('Y-m-d H:i:s'));
		$this->assertEquals($expected_formatted_datetime, $item->get_formatted_datetime('Y-m-d H:i:s'));
	}

	public function get_datetime_zones()
	{
		return array(
			array('2015-10-05 12:00:00', 'UTC', '2015-10-05 12:00:00'),
			array('2015-10-05 12:00:00', 'Asia/Bangkok', '2015-10-05 19:00:00')
		);
	}

	/**
	 * @covers BWP_Sitemaps_Logger_LogItem::get_datetime
	 */
	public function test_get_datetime_should_return_a_clone_of_datetime_property()
	{
		$item = $this->getMockForAbstractClass('BWP_Sitemaps_Logger_LogItem');

		$datetime = new DateTime(null, new DateTimeZone('UTC'));
		$item->set_datetime($datetime);

		$datetime_clone = $item->get_datetime();

		$this->assertEquals($datetime, $datetime_clone);
		$this->assertNotSame($datetime, $datetime_clone);
	}
}
