<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Logger_Sitemap_LogItem extends BWP_Sitemaps_Logger_LogItem
{
	/**
	 * Slug of the logged sitemap
	 *
	 * @var string
	 */
	protected $sitemap_slug;

	/**
	 * @param string $slug
	 * @param string $datetime default to now, expect to be in UTC timezone
	 */
	public function __construct($slug, $datetime = null)
	{
		if (!is_string($slug) || empty($slug)) {
			throw new DomainException('provided slug must be string and not empty');
		}

		$this->sitemap_slug = $slug;

		$this->datetime = new DateTime($datetime, new DateTimeZone('UTC'));
	}

	/**
	 * Check whether this log item is obsolete
	 *
	 * An item is obsolete when it's at least 1-month old
	 *
	 * @return bool
	 */
	public function is_obsolete()
	{
		$last_month = new DateTime('-1 month', new DateTimeZone('UTC'));

		return $this->datetime <= $last_month;
	}

	public function get_sitemap_slug()
	{
		return $this->sitemap_slug;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_item_data()
	{
		return array(
			'slug'     => $this->sitemap_slug,
			'datetime' => $this->get_storage_datetime()
		);
	}
}
