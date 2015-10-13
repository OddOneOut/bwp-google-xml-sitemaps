<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Excluder
{
	protected $bridge;

	protected $cache;

	protected $cache_key;

	protected $storage_key;

	public function __construct(
		BWP_WP_Bridge $bridge,
		BWP_Cache $cache,
		$cache_key,
		$storage_key)
	{
		$this->bridge = $bridge;
		$this->cache  = $cache;

		$this->cache_key   = $cache_key;
		$this->storage_key = $storage_key;
	}

	/**
	 * Get currently excluded items
	 *
	 * @param string $group default to get all, when expected items are posts,
	 *                      $group is actually post type, when expected items
	 *                      are terms, $group is taxonomy.
	 * @return array of item ids if $group is provided
	 *         array of group => array of item ids otherwise
	 */
	public function get_excluded_items($group = null)
	{
		if (! ($excluded_items = $this->cache->get($this->cache_key))) {
			if (! ($excluded_items = $this->bridge->get_option($this->storage_key))) {
				return array();
			}

			// cache all excluded items
			$this->cache->set($this->cache_key, $excluded_items);
		}

		// return all excluded items with group if no group is specified
		if (is_null($group)) {
			return $excluded_items;
		}

		// return excluded items for a specific group, item ids are stored in a
		// comma separated string
		$excluded_items = !empty($excluded_items[$group])
			? explode(',', $excluded_items[$group]) : array();

		return $excluded_items;
	}

	/**
	 * Update excluded items
	 *
	 * @param string $group
	 * @param array $ids all item ids under specified $group that should be excluded
	 */
	public function update_excluded_items($group, array $ids)
	{
		// post ids are stored in a comma separated string
		$items_to_exclude = implode(',', $ids);

		$excluded_items = $this->get_excluded_items();
		$excluded_items[$group] = $items_to_exclude;

		$this->bridge->update_option($this->storage_key, $excluded_items);
		$this->cache->set($this->cache_key, $excluded_items);
	}
}
