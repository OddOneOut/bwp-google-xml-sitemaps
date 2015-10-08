<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_Logger
{
	/**
	 * Log items
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Maximum number of items to keep
	 *
	 * @var int
	 */
	protected $limit;

	protected function __construct()
	{
		$this->items = array();
	}

	/**
	 * Create a message logger
	 *
	 * @param int $limit default to 0 for no limit
	 * @return BWP_Sitemaps_Logger_MessageLogger
	 */
	public static function create_message_logger($limit = 0)
	{
		$logger = new BWP_Sitemaps_Logger_MessageLogger();
		$logger->set_limit($limit);

		return $logger;
	}

	/**
	 * Create a sitemap logger
	 *
	 * @var int $limit default to 0 for no limit
	 * @return BWP_Sitemaps_Logger_SitemapLogger
	 */
	public static function create_sitemap_logger($limit = 0)
	{
		$logger = new BWP_Sitemaps_Logger_SitemapLogger();
		$logger->set_limit($limit);

		return $logger;
	}

	public function set_limit($limit)
	{
		$this->limit = (int) $limit;
	}

	/**
	 * Log an item
	 *
	 * @param BWP_Sitemaps_Logger_LogItem $item
	 */
	public function log(BWP_Sitemaps_Logger_LogItem $item)
	{
		if (!empty($this->limit) && count($this->items) >= $this->limit) {
			array_shift($this->items);
		}

		$this->items[] = $item;
	}

	/**
	 * Reset logger, remove all logged items
	 */
	public function reset()
	{
		$this->items = array();
	}

	/**
	 * @return int
	 */
	public function get_limit()
	{
		return (int) $this->limit;
	}

	/**
	 * @return bool
	 */
	public function is_empty()
	{
		return count($this->items) == 0;
	}

	/**
	 * @return BWP_Sitemaps_Logger_LogItem[] array of BWP_Sitemaps_Logger_LogItem
	 */
	public function get_log_items()
	{
		return $this->items;
	}

	/**
	 * Get a simple array representation of the log items
	 *
	 * This is intended to be stored in a persistence layer
	 *
	 * @return array
	 */
	public function get_log_item_data()
	{
		$data = array();

		/* @var $item BWP_Sitemaps_Logger_LogItem */
		foreach ($this->items as $item) {
			$data[] = $item->get_item_data();
		}

		return $data;
	}
}
