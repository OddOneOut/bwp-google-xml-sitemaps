<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * The standard XML sitemap
 *
 * This follows closely @link http://www.sitemaps.org/protocol.html
 *
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_Xml extends BWP_Sitemaps_Sitemap
{
	/**
	 * {@inheritDoc}
	 */
	protected function set_xml_headers()
	{
		// image sitemap allowed, need google's "image" namespace
		// @link https://support.google.com/webmasters/answer/178636?hl=en
		if ($this->provider->is_image_allowed()) {
			$this->xml_headers['xmlns:image'] = 'http://www.google.com/schemas/sitemap-image/1.1';
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_xml_item_body(array $item)
	{
		$url = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('url');

		$tags = $this->create_tags_from_sitemap_item(
			$item, array(
				'loc'        => 'location',
				'lastmod'    => 'lastmod',
				'changefreq' => 'freq',
				'priority'   => 'priority'
			), array(
				'changefreq' => $this->sanitizer_factory->get_frequency_sanitizer(),
				'priority'   => $this->sanitizer_factory->get_priority_sanitizer()
			)
		);

		$url->add_tags($tags);

		if ($image = $this->create_image_tag_from_sitemap_item($item)) {
			$url->add_tag($image);
		}

		return $url->get_xml();
	}

	/**
	 * Create single tags from a sitemap item
	 *
	 * @param array $item
	 * @param array $tag_names a map from tag's name to item's array key
	 * @param array BWP_Sitemaps_Sitemap_Sanitizer[] $sanitizers default to null
	 *
	 * @return array
	 */
	protected function create_tags_from_sitemap_item(
		array $item,
		array $tag_names,
		array $sanitizers = array()
	) {
		$tags = array();

		foreach ($tag_names as $tag_name => $item_key) {
			if (! isset($item[$item_key])) {
				continue;
			}

			$tags[] = BWP_Sitemaps_Sitemap_Tag::create_single_tag(
				$tag_name,
				$item[$item_key],
				isset($sanitizers[$tag_name]) ? $sanitizers[$tag_name] : null
			);
		}

		return $tags;
	}

	/**
	 * Create an image tag from image item
	 *
	 * @param array $item
	 * @return mixed BWP_Sitemaps_Sitemap_Tag_CompoundTag|null
	 */
	protected function create_image_tag_from_sitemap_item(array $item)
	{
		if (! $this->provider->is_image_allowed()
			|| !isset($item['image'])
			|| !is_array($item['image'])
		) {
			return;
		}

		$image = $item['image'];

		// not valid location for image
		if (empty($image['location'])) {
			return;
		}

		$tags = $this->create_tags_from_sitemap_item($image, array(
			'image:loc'     => 'location',
			'image:title'   => 'title',
			'image:caption' => 'caption'
		));

		$image_tag = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('image:image')
			->add_tags($tags);

		return $image_tag;
	}
}
