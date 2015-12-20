<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_Provider
{
	protected $plugin;

	protected $bridge;

	protected $module;

	protected $image_allowed;

	public function __construct(BWP_Sitemaps $plugin, BWP_GXS_MODULE $module)
	{
		$this->plugin = $plugin;
		$this->bridge = $plugin->get_bridge();

		$this->module = $module;
	}

	/**
	 * @return BWP_Sitemaps
	 */
	public function get_plugin()
	{
		return $this->plugin;
	}

	/**
	 * Filter a sitemap item to make sure it is valid
	 *
	 * @param array $item
	 * @return bool
	 */
	public function filter_sitemap_item(array $item)
	{
		if (empty($item['location']) || ! $this->is_url_valid($item['location'])) {
			return false;
		}

		return true;
	}

	/**
	 * Get all sitemap items
	 *
	 * @return array
	 */
	public function get_items()
	{
		return array_values(
			array_filter(
				$this->module->get_data(),
				array($this, 'filter_sitemap_item')
			)
		);
	}

	/**
	 * @return bool
	 */
	public function is_image_allowed()
	{
		$this->image_allowed = !is_null($this->image_allowed)
			? $this->image_allowed
			: $this->module->is_image_allowed();

		return $this->image_allowed;
	}

	private function is_local($url)
	{
		static $blog_url;

		if (empty($blog_url)) {
			$home_url = $this->bridge->home_url();
			$blog_url = @parse_url($home_url);
		}

		$url = @parse_url($url);
		if (false === $url) {
			return false;
		}

		// according to sitemap protocol the host must be exactly the same
		// @link http://www.sitemaps.org/protocol.html#location
		// @todo allow logging all invalid urls so they can be fixed if needed
		if (0 <> strcmp($url['host'], $blog_url['host'])) {
			return false;
		}

		return true;
	}

	private function is_url_valid($url)
	{
		$url = trim($url);

		if ('#' == $url || 0 !== strpos($url, 'http') || ! $this->is_local($url)) {
			return false;
		}

		// url must use the current scheme
		$is_ssl = $this->bridge->is_ssl();
		if (($is_ssl && strpos($url, 'https') !== 0)
			|| (! $is_ssl && strpos($url, 'https') === 0)
		) {
			return false;
		}

		return true;
	}
}
