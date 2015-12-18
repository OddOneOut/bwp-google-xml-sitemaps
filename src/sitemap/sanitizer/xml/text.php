<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_Sanitizer_Xml_TextSanitizer extends BWP_Sitemaps_Sitemap_Sanitizer
{
	/**
	 * {@inheritDoc}
	 */
	public function sanitize($value)
	{
		return trim(htmlspecialchars(strip_tags($value), ENT_QUOTES));
	}
}
