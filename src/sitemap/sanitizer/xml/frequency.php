<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_Sanitizer_Xml_FrequencySanitizer extends BWP_Sitemaps_Sitemap_Sanitizer
{
	/**
	 * {@inheritDoc}
	 */
	public function sanitize($value)
	{
		$frequencies = $this->get_option('frequencies');
		if (! $frequencies || ! in_array($value, $frequencies)) {
			$value = $this->get_option('default_frequency');
		}

		if (! $value) {
			return null;
		}

		return $value;
	}

	protected function set_default_options()
	{
		$this->options = array(
			'frequencies'       => array(),
			'default_frequency' => 'daily'
		);
	}
}
