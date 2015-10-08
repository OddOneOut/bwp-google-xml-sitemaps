<?php

/**
 * @covers BWP_Sitemaps_Logger
 */
class BWP_Sitemaps_Logger_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers BWP_Sitemaps_Logger::create_message_logger
	 */
	public function test_create_message_logger_correctly()
	{
		$logger = BWP_Sitemaps_Logger::create_message_logger(25);

		$this->assertEquals(25, $logger->get_limit());
		$this->assertEquals(array(), $logger->get_log_items());
	}

	/**
	 * @covers BWP_Sitemaps_Logger::create_sitemap_logger
	 */
	public function test_create_sitemap_logger_correctly()
	{
		$logger = BWP_Sitemaps_Logger::create_sitemap_logger();

		$this->assertEquals(0, $logger->get_limit());
		$this->assertEquals(array(), $logger->get_log_items());
	}
}
