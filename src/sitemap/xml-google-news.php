<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_XmlGoogleNews extends BWP_Sitemaps_Sitemap_Xml
{
	protected $news_name;

	/**
	 * {@inheritDoc}
	 */
	protected function set_xml_headers()
	{
		// news sitemap needs additional namespaces
		// For namespaces: @link https://support.google.com/news/publisher/answer/74288
		// For validation: @link https://support.google.com/news/publisher/answer/184732
		$this->xml_headers['xmlns:news'] = 'http://www.google.com/schemas/sitemap-news/0.9';
		$this->xml_headers['xsi:schemaLocation'] = $this->xml_headers['xsi:schemaLocation']
			. "\n\t\t"
			. 'http://www.google.com/schemas/sitemap-news/0.9 '
			. 'http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd';

		// also use the standard xml sitemap headers
		parent::set_xml_headers();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function set_properties()
	{
		$this->news_name = $this->provider->get_plugin()->get_news_name();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_xml_item_body(array $item)
	{
		// allow overriding publication name via item data
		$item['name'] = !empty($item['name']) ? $item['name'] : $this->news_name;

		$url = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('url');

		$location = BWP_Sitemaps_Sitemap_Tag::create_single_tag('loc', $item['location']);
		$news = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('news:news');
		$news_publication = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('news:publication');

		$news_publication_tags = $this->create_tags_from_sitemap_item($item, array(
			'news:name'     => 'name',
			'news:language' => 'language'
		));

		$news_tags = $this->create_tags_from_sitemap_item(
			$item, array(
				'news:genres'           => 'genres',
				'news:publication_date' => 'pub_date',
				'news:title'            => 'title',
				'news:keywords'         => 'keywords'
			)
		);

		$url->add_tags(array(
			$location,
			$news
				->add_tag($news_publication->add_tags($news_publication_tags))
				->add_tags($news_tags)
			)
		);

		if ($image = $this->create_image_tag_from_sitemap_item($item)) {
			$url->add_tag($image);
		}

		return $url->get_xml();
	}
}
