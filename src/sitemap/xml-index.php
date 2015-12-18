<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * The XML sitemap index
 *
 * This follows closely @link http://www.sitemaps.org/protocol.html#index
 *
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_XmlIndex extends BWP_Sitemaps_Sitemap
{
	/**
	 * {@inheritDoc}
	 */
	protected function set_xml_headers()
	{
		$this->xml_headers['xsi:schemaLocation'] = 'http://www.sitemaps.org/schemas/sitemap/0.9 '
			. 'http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function set_properties()
	{
		$this->xml_root_tag = 'sitemapindex';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_xml_item_body(array $item)
	{
		$sitemap = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('sitemap');

		$sitemap->add_tag(BWP_Sitemaps_Sitemap_Tag::create_single_tag('loc', $item['location']));

		if (!empty($item['lastmod'])) {
			$sitemap->add_tag(BWP_Sitemaps_Sitemap_Tag::create_single_tag('lastmod', $item['lastmod']));
		}

		return $sitemap->get_xml();
	}
}
