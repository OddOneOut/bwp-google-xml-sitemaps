<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Logger_Message_LogItem extends BWP_Sitemaps_Logger_LogItem
{
	const TYPE_SUCCESS = 'success';
	const TYPE_ERROR   = 'error';
	const TYPE_NOTICE  = 'notice';

	/**
	 * The message of this log item
	 *
	 * @var string
	 */
	protected $message;

	/**
	 * Message type
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * @param string $message
	 * @param string $type
	 * @param string $datetime default to now, expect to be in UTC timezone
	 */
	public function __construct($message, $type, $datetime = null)
	{
		if (!is_string($message) || empty($message)) {
			throw new DomainException('provided message must be string and not empty');
		}

		$this->message = $message;

		if (!in_array($type, self::get_allowed_types())) {
			throw new DomainException(sprintf('invalid message type provided: "%s"', $type));
		}

		$this->type = $type;

		// datetime will be converted to UTC timezone
		$this->datetime = new DateTime($datetime, new DateTimeZone('UTC'));
	}

	public static function get_allowed_types()
	{
		return array(
			self::TYPE_SUCCESS,
			self::TYPE_ERROR,
			self::TYPE_NOTICE
		);
	}

	public function get_message()
	{
		return $this->message;
	}

	public function is_error()
	{
		return $this->type === self::TYPE_ERROR;
	}

	public function is_success()
	{
		return $this->type === self::TYPE_SUCCESS;
	}

	public function is_notice()
	{
		return $this->type === self::TYPE_NOTICE;
	}

	public function get_type()
	{
		return $this->type;
	}

	public function get_item_data()
	{
		return array(
			'message'  => $this->message,
			'type'     => $this->type,
			'datetime' => $this->get_storage_datetime()
		);
	}
}
