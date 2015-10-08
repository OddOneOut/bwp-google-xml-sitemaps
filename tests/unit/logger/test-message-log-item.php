<?php

/**
 * @covers BWP_Sitemaps_Logger_Message_LogItem
 */
class BWP_Sitemaps_Logger_Message_LogItem_Test extends PHPUnit_Framework_TestCase
{
	protected $item;

	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Sitemaps_Logger_Message_LogItem::__construct
	 * @dataProvider get_invalid_messages
	 */
	public function test_should_throw_exception_when_invalid_message_provided($message)
	{
		$this->setExpectedException('DomainException', 'provided message must be string and not empty');

		$item = new BWP_Sitemaps_Logger_Message_LogItem($message, 'error');
	}

	public function get_invalid_messages()
	{
		return array(
			array(false),
			array(array()),
			array('')
		);
	}

	/**
	 * @covers BWP_Sitemaps_Logger_Message_LogItem::__construct
	 */
	public function test_should_throw_exception_when_invalid_type_provided()
	{
		$this->setExpectedException('DomainException', 'invalid message type provided: "invalid"');

		$item = new BWP_Sitemaps_Logger_Message_LogItem('message', 'invalid');
	}

	/**
	 * @covers BWP_Sitemaps_Logger_Message_LogItem::__construct
	 * @dataProvider get_item_data
	 */
	public function test_should_init_correctly($message, $type, $datetime)
	{
		$item = new BWP_Sitemaps_Logger_Message_LogItem($message, $type, $datetime);

		$this->assertEquals($message, $item->get_message());
		$this->assertEquals($type, $item->get_type());
		$this->assertEquals($datetime, $item->get_storage_datetime());

		$this->assertEquals(new DateTimeZone('UTC'), $item->get_datetime()->getTimezone(), 'datetime should always be converted to UTC');
	}

	public function get_item_data()
	{
		return array(
			array('message1', 'success', '2015-10-05 12:00:00'),
			array('message2', 'error',   '2015-10-05 12:00:01'),
			array('message3', 'notice',  '2015-10-05 12:00:02'),
		);
	}

	/**
	 * @covers BWP_Sitemaps_Logger_Message_LogItem::__construct
	 */
	public function test_should_default_datetime_to_current_time_and_UTC_timezone_if_none_provided()
	{
		$item = new BWP_Sitemaps_Logger_Message_LogItem('message', 'success');

		$this->assertInstanceOf('DateTime', $item->get_datetime());
		$this->assertEquals(new DateTimeZone('UTC'), $item->get_datetime()->getTimezone());
	}
}
