<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_Logger_LogItem
{
	/**
	 * Datetime of the item
	 *
	 * @var DateTime
	 */
	protected $datetime;

	/**
	 * Timezone of the datetime of this item
	 *
	 * @var DateTimeZone
	 */
	protected $timezone;

	/**
	 * Get a simple array representation of the item
	 *
	 * This is intended to be stored in a persistence layer
	 *
	 * @return array
	 */
	abstract public function get_item_data();

	public function set_datetime(DateTime $datetime)
	{
		$this->datetime = $datetime;
	}

	public function set_datetimezone(DateTimeZone $timezone)
	{
		$this->timezone = $timezone;
	}

	/**
	 * Get datetime of this item, with proper timezone set if applicable
	 *
	 * @return DateTime
	 */
	public function get_datetime()
	{
		$datetime = clone $this->datetime;

		if ($this->timezone) {
			$datetime->setTimezone($this->timezone);
		}

		return $datetime;
	}

	/**
	 * @return int
	 */
	public function get_timestamp()
	{
		return $this->datetime->getTimestamp();
	}

	/**
	 * Get datetime formatted in a way that's suitable for storage
	 *
	 * This should use $datetime with UTC timezone
	 *
	 * @return string
	 */
	public function get_storage_datetime()
	{
		return $this->datetime->format('Y-m-d H:i:s');
	}

	/**
	 * Get datetime formatted in a way that is suitable for displaying
	 *
	 * This should use $datetime with local timezone set
	 *
	 * @return string
	 */
	public function get_formatted_datetime($format = 'M d, Y h:i:s A')
	{
		return $this->get_datetime()->format($format);
	}
}
