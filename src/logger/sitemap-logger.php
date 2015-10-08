<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Logger_SitemapLogger extends BWP_Sitemaps_Logger
{
	/**
	 * {@inheritDoc}
	 */
	public function log(BWP_Sitemaps_Logger_LogItem $item)
	{
		if (!($item instanceof BWP_Sitemaps_Logger_Sitemap_LogItem)) {
			throw new InvalidArgumentException(sprintf('expect an item of type BWP_Sitemaps_Logger_Sitemap_LogItem, "%s" provded.', get_class($item)));
		}

		// replace existing item
		$this->items[$item->get_sitemap_slug()] = $item;
	}

	/**
	 * Get a log item based on sitemap slug
	 *
	 * @param string $slug
	 * @return BWP_Sitemaps_Logger_Sitemap_LogItem
	 */
	public function get_sitemap_log_item($slug)
	{
		foreach ($this->items as $item) {
			if ($item->get_sitemap_slug() === $slug) {
				return $item;
			}
		}
	}
}
