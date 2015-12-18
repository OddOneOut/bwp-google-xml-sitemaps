<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * Create sanitizer with options from plugin
 *
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_Sanitizer_Factory
{
	protected $plugin;

	protected $priority_sanitizer;

	protected $frequency_sanitizer;

	public function __construct(BWP_Sitemaps $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @return BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer
	 */
	public function get_priority_sanitizer()
	{
		$this->priority_sanitizer = $this->priority_sanitizer
			? $this->priority_sanitizer
			: new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer(array(
				'default_priority' => $this->plugin->options['select_default_pri']
			));

		return $this->priority_sanitizer;
	}

	/**
	 * @return BWP_Sitemaps_Sitemap_Sanitizer_Xml_FrequencySanitizer
	 */
	public function get_frequency_sanitizer()
	{
		$this->frequency_sanitizer = $this->frequency_sanitizer
			? $this->frequency_sanitizer
			: new BWP_Sitemaps_Sitemap_Sanitizer_Xml_FrequencySanitizer(array(
				'frequencies'       => $this->plugin->frequencies,
				'default_frequency' => $this->plugin->options['select_default_freq']
			));

		return $this->frequency_sanitizer;
	}
}
