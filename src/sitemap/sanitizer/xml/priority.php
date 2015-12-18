<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer extends BWP_Sitemaps_Sitemap_Sanitizer
{
	/**
	 * {@inheritDoc}
	 */
	public function sanitize($value)
	{
		if ($value > 1 || $value < 0) {
			$value = $this->get_option('default_priority');
		}

		if (! $value) {
			return null;
		}

		return sprintf('%.1f', $value);
	}

	protected function set_default_options()
	{
		$this->options = array(
			'default_priority' => 0.5
		);
	}
}
