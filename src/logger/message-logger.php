<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Logger_MessageLogger extends BWP_Sitemaps_Logger
{
	/**
	 * {@inheritDoc}
	 */
	public function log(BWP_Sitemaps_Logger_LogItem $item)
	{
		if (!($item instanceof BWP_Sitemaps_Logger_Message_LogItem)) {
			throw new InvalidArgumentException(sprintf('expect an item of type BWP_Sitemaps_Logger_Message_LogItem, "%s" provded.', get_class($item)));
		}

		parent::log($item);
	}
}
