<?php

/**
 * @covers BWP_Sitemaps_Logger_MessageLogger
 */
class BWP_Sitemaps_Logger_MessageLogger_Test extends PHPUnit_Framework_TestCase
{
	protected $logger;

	protected function setUp()
	{
		$this->logger = BWP_Sitemaps_Logger::create_message_logger();
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Sitemaps_Logger_MessageLogger::log
	 */
	public function test_should_throw_exception_when_invalid_item_provided()
	{
		$item = $this->getMockForAbstractClass('BWP_Sitemaps_Logger_LogItem');
		$this->setExpectedException('InvalidArgumentException', sprintf('expect an item of type BWP_Sitemaps_Logger_Message_LogItem, "%s" provded.', get_class($item)));

		$this->logger->log($item);
	}

	/**
	 * @covers BWP_Sitemaps_Logger_MessageLogger::log
	 */
	public function test_should_log_new_item_correctly()
	{
		$item1 = new BWP_Sitemaps_Logger_Message_LogItem('message1', 'success', '2015-10-05 12:00:00 GMT');
		$item2 = new BWP_Sitemaps_Logger_Message_LogItem('message2', 'error',   '2015-10-05 13:00:00 GMT');
		$item3 = new BWP_Sitemaps_Logger_Message_LogItem('message1', 'success', '2015-10-05 14:00:00 GMT');

		$this->logger->log($item1);
		$this->logger->log($item2);
		$this->logger->log($item3);

		$this->assertCount(3, $this->logger->get_log_items(), 'should not check for item\'s uniqueness');

		return $this->logger;
	}

	/**
	 * @covers BWP_Sitemaps_Logger_MessageLogger::log
	 * @covers BWP_Sitemaps_Logger_MessageLogger::set_limit
	 * @depends test_should_log_new_item_correctly
	 */
	public function test_should_not_log_more_than_allowed(BWP_Sitemaps_Logger_MessageLogger $logger)
	{
		$logger = clone $logger;

		$logger->set_limit(4);

		$item4 = new BWP_Sitemaps_Logger_Message_LogItem('message4', 'error');
		$item5 = new BWP_Sitemaps_Logger_Message_LogItem('message5', 'success');

		$logger->log($item4);
		$logger->log($item5);

		$this->assertCount(4, $logger->get_log_items());

		$this->assertFalse(in_array(array(
			'message'  => 'message1',
			'type'     => 'success',
			'datetime' => '2015-10-05 12:00:00'
		), $logger->get_log_items()), 'item1 should have been removed from logged items');
	}

	/**
	 * @covers BWP_Sitemaps_Logger_MessageLogger::get_log_item_data
	 * @depends test_should_log_new_item_correctly
	 */
	public function test_get_log_item_data(BWP_Sitemaps_Logger_MessageLogger $logger)
	{
		$this->assertEquals(array(
			array(
				'message'  => 'message1',
				'type'     => 'success',
				'datetime' => '2015-10-05 12:00:00'
			),
			array(
				'message'  => 'message2',
				'type'     => 'error',
				'datetime' => '2015-10-05 13:00:00'
			),
			array(
				'message'  => 'message1',
				'type'     => 'success',
				'datetime' => '2015-10-05 14:00:00'
			)
		), $logger->get_log_item_data());
	}
}
