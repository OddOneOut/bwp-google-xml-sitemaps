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
	 * Get currently excluded items.
	 *
	 * The result of this function is cached using {@see BWP_Cache}.
	 *
	 * @param string $group default to get all, when expected items are posts,
	 *                      `$group` is actually post type, when expected items
	 *                      are terms, `$group` is taxonomy.
	 * @param bool $flatten whether to flatten the items into a one dimensional
	 *                      array of ids instead of grouping by `$group`. To use
	 *                      this `$group` must be set to `NULL`.
	 *
	 * @return array
	 * * An array of item ids if `$group` is provided
	 * * An array of group => item ids string (comma separated) otherwise, example:
	 *     ```
	 *     array(
	 *         'post'  => '1,2,3,4',
	 *         'movie' => '5,6,7,8'
	 *     )
	 *     ```
	 *
	 * @uses BWP_Cache
	 */
	public function get_excluded_items($group = null, $flatten = false)
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
			// need to flatten into a one dimensional array
			if ($flatten) {
				$excluded_items_flattened = array();

				foreach ($excluded_items as $_group => $_group_items) {
					// group has no items, nothing to do
					if (! $_group_items) {
						continue;
					}

					$excluded_items_flattened = array_merge(
						$excluded_items_flattened,
						array_map('intval', explode(',', $_group_items))
					);
				}

				return $excluded_items_flattened;
			}

			return $excluded_items;
		}

		// return excluded items for a specific group, item ids are stored in a
		// comma separated string
		$excluded_items = !empty($excluded_items[$group])
			? explode(',', $excluded_items[$group]) : array();

		return $excluded_items;
	}

	/**
	 * Update excluded items.
	 *
	 * @param string $group
	 * @param array $ids all item ids under specified `$group` that should be excluded
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
