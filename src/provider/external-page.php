<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Provider_ExternalPage
{
	protected $plugin;

	protected $bridge;

	protected $storage_key;

	public function __construct(BWP_Sitemaps $plugin, $storage_key)
	{
		$this->plugin = $plugin;
		$this->bridge = $plugin->get_bridge();

		$this->storage_key = $storage_key;
	}

	/**
	 * Get external pages
	 *
	 * @param int $limit default = null i.e. get all pages
	 * @return array an associative array of 'url' => array
	 */
	public function get_pages($limit = null)
	{
		$pages = $this->bridge->get_option($this->storage_key);
		$pages = $pages && is_array($pages) ? $pages : array();

		return $limit ? array_slice($pages, 0, (int) $limit) : $pages;
	}

	/**
	 * Get external pages with local timezone properly set and url included in data
	 *
	 * This is intended to be consumed by ajax handlers or regular handlers
	 *
	 * @param int $limit default = null i.e. get all pages
	 * @return array an associative array of 'url' => array
	 */
	public function get_pages_for_display($limit = null)
	{
		$pages = $this->get_pages($limit);
		$items = array();

		foreach ($pages as $url => $page) {
			$last_modified = isset($page['last_modified']) ? $page['last_modified'] : null;

			if (isset($last_modified)) {
				try {
					// expect UTC timezone from db, convert to local timezone
					$last_modified = new DateTime($page['last_modified'], new DateTimeZone('UTC'));
					$last_modified->setTimezone($this->plugin->get_current_timezone());
				} catch (Exception $e) {
					// invalid datetime, ignore because last modified is optional
				}
			}

			$items[$url] = array(
				'url'           => $url,
				'frequency'     => isset($page['frequency']) ? $page['frequency'] : null,
				'priority'      => isset($page['priority']) ? (float) $page['priority'] : null,
				'last_modified' => isset($last_modified)
					? $last_modified->format('Y-m-d H:i') // @todo remove this date format duplication
					: null
			);
		}

		return $items;
	}

	/**
	 * Get an external page by its url
	 *
	 * @param string $url
	 * @return array the page's data
	 */
	public function get_page($url)
	{
		$pages = $this->get_pages();

		foreach ($pages as $page_url => $page) {
			if ($page_url === $url) {
				return $page;
			}
		}

		return null;
	}

	/**
	 * Check if we have external pages in db
	 *
	 * @return bool
	 */
	public function has_pages()
	{
		return count($this->get_pages()) > 0;
	}

	public function get_bridge()
	{
		return $this->bridge;
	}

	public function get_storage_key()
	{
		return $this->storage_key;
	}

	public function get_domain()
	{
		return $this->plugin->domain;
	}
}
