<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Provider_Post extends BWP_Sitemaps_Provider
{
	/**
	 * Get all public post types, except for attachment
	 *
	 * @return array
	 */
	public function get_post_types()
	{
		$post_types = array();
		$all_post_types = $this->bridge->get_post_types(array('public' => true), 'objects');

		foreach ($all_post_types as $post_type) {
			if ('attachment' === $post_type->name) {
				continue;
			}

			$post_types[] = $post_type;
		}

		return $post_types;
	}

	/**
	 * Get all public posts of a specific post type, optionally filtered by
	 * post ids
	 *
	 * @param string $post_type
	 * @param array $ids
	 * @param array $excluded_ids
	 * @param int $limit default to retrieve only last 200 posts
	 * @return WP_Post[]
	 */
	public function get_public_posts($post_type, array $ids = array(), array $excluded_ids = array(), $limit = 200)
	{
		if ($ids && $excluded_ids) {
			throw new DomainException('only post ids or excluded post ids can be provided, not both');
		}

		$posts = $this->bridge->get_posts(array(
			'post_type'      => $post_type,
			'include'        => $ids,
			'exclude'        => $excluded_ids,
			'posts_per_page' => (int) $limit
		));

		return $posts;
	}

	/**
	 * Get all public posts, filtered by their titles
	 *
	 * This is a case insensitive search
	 *
	 * @param string $post_type
	 * @param string $title part of the title
	 * @return WP_Post[]
	 */
	public function get_public_posts_by_title($post_type, $title)
	{
		// search for posts with matching title, but dont take into
		// account already excluded posts
		$posts = $this->bridge->get_posts(array(
			'post_type'           => $post_type,
			'bwp_post_title_like' => $title,
			'suppress_filters'    => false,
			'exclude'             => $this->excluder->get_excluded_items($post_type)
		));

		return $posts;
	}
}
