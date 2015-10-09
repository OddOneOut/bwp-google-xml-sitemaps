<?php

/**
 * @covers BWP_Sitemaps_Logger_SitemapLogger
 */
class BWP_Sitemaps_Logger_SitemapLogger_Test extends PHPUnit_Framework_TestCase
{
	protected $logger;

	protected function setUp()
	{
		$this->logger = BWP_Sitemaps_Logger::create_sitemap_logger();
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Sitemaps_Logger_SitemapLogger::log
	 */
	public function test_should_throw_exception_when_invalid_item_provided()
	{
		$item = $this->getMockForAbstractClass('BWP_Sitemaps_Logger_LogItem');
		$this->setExpectedException('InvalidArgumentException', sprintf('expect an item of type BWP_Sitemaps_Logger_Sitemap_LogItem, "%s" provded.', get_class($item)));

		$this->logger->log($item);
	}

	/**
	 * @covers BWP_Sitemaps_Logger_SitemapLogger::log
	 */
	public function test_should_log_new_item_correctly()
	{
		$item1 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug1', '2015-10-05 12:00:00 GMT');
		$item2 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug2', '2015-10-05 13:00:00 GMT');
		$item3 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug1', '2015-10-05 14:00:00 GMT');

		$this->logger->log($item1);
		$this->logger->log($item2);
		$this->logger->log($item3);

		$this->assertCount(2, $this->logger->get_log_items(), 'should replace sitemap log item with same slug with later one');

		$this->assertEquals(array(
			'slug'     => 'slug1',
			'datetime' => '2015-10-05 14:00:00'
		), $this->logger->get_sitemap_log_item('slug1')->get_item_data());

		return $this->logger;
	}

	/**
	 * @covers BWP_Sitemaps_Logger_SitemapLogger::log
	 * @covers BWP_Sitemaps_Logger_SitemapLogger::set_limit
	 * @depends test_should_log_new_item_correctly
	 */
	public function test_should_not_log_more_than_allowed(BWP_Sitemaps_Logger_SitemapLogger $logger)
	{
		$logger = clone $logger;

		$logger->set_limit(4);

		$item4 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug4');
		$item5 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug5');

		$logger->log($item4);
		$logger->log($item5);

		$this->assertCount(4, $logger->get_log_items());

		$this->assertFalse(in_array(array(
			'slug'     => 'slug1',
			'datetime' => '2015-10-05 12:00:00'
		), $logger->get_log_items()), 'item1 should have been removed from logged items');
	}

	/**
	 * @covers BWP_Sitemaps_Logger_SitemapLogger::get_log_item_data
	 * @depends test_should_log_new_item_correctly
	 */
	public function test_get_log_item_data(BWP_Sitemaps_Logger_SitemapLogger $logger)
	{
		$this->assertEquals(array(
			array(
				'slug'     => 'slug1',
				'datetime' => '2015-10-05 14:00:00'
			),
			array(
				'slug'     => 'slug2',
				'datetime' => '2015-10-05 13:00:00'
			),
		), $logger->get_log_item_data());
	}

	/**
	 * @covers BWP_Sitemaps_Logger_SitemapLogger::get_sitemap_log_item
	 * @depends test_should_log_new_item_correctly
	 */
	public function test_get_sitemap_log_item(BWP_Sitemaps_Logger_SitemapLogger $logger)
	{
		$item = $logger->get_sitemap_log_item('slug1');

		$this->assertInstanceOf('BWP_Sitemaps_Logger_Sitemap_LogItem', $item);

		$this->assertEquals(array(
			'slug'     => 'slug1',
			'datetime' => '2015-10-05 14:00:00'
		), $item->get_item_data());
	}

	/**
	 * @covers BWP_Sitemaps_Logger_SitemapLogger::get_log_items
	 */
	public function test_get_log_items_should_filter_out_obsolete_items()
	{
		$item1 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug1', '2015-10-05 12:00:00 GMT');
		$item2 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug2', '2015-09-05 13:00:00 GMT');

		$this->logger->log($item1);
		$this->logger->log($item2);

		$this->assertEquals(
			array('slug1' => $item1),
			$this->logger->get_log_items(), 'item2 is obsolete, it should be filtered out'
		);
	}

	/**
	 * @covers BWP_Sitemaps_Logger_SitemapLogger::get_log_item_data
	 */
	public function test_get_log_item_data_should_filter_out_obsolete_items()
	{
		$item1 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug1', '2015-10-05 12:00:00 GMT');
		$item2 = new BWP_Sitemaps_Logger_Sitemap_LogItem('slug2', '2015-09-05 13:00:00 GMT');

		$this->logger->log($item1);
		$this->logger->log($item2);

		$this->assertEquals(
			array($item1->get_item_data()),
			$this->logger->get_log_item_data(), 'item2 is obsolete, it should be filtered out'
		);
	}
}
