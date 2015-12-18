<?php

use \Mockery as Mockery;

require_once dirname(__FILE__) . '/test-sitemap-base.php';

/**
 * @covers BWP_Sitemaps_Sitemap_XmlGoogleNews
 */
class BWP_Sitemaps_Sitemap_XmlGoogleNews_Test extends BWP_Sitemaps_Sitemap_Base_Test
{
	public function setUp()
	{
		parent::setUp();

		$this->plugin
			->shouldReceive('get_news_name')
			->andReturn('Publication Name')
			->byDefault();

		$this->sitemap = $this->create_sitemap('BWP_Sitemaps_Sitemap_XmlGoogleNews', null);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_XmlGoogleNews::get_xml
	 */
	public function test_get_xml_should_return_correct_xml()
	{
		$this->provider
			->shouldReceive('get_items')
			->andReturn(array(
				array(
					'location' => $this->get_location('news-article-#1'),
					'language' => 'en',
					'pub_date' => '2015-12-16T15:34:06Z',
					'title'    => 'News article #1'
				),
				array(
					'location' => $this->get_location('business/article55.html'),
					'name'     => 'Publication Name Overridden',
					'language' => 'en',
					'genres'   => 'PressRelease, Blog',
					'pub_date' => '2008-12-23',
					'title'    => 'Companies A, B in Merger Talks',
					'keywords' => 'business, merger, acquisition, A, B'
				),
			))
			->byDefault();

		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>

<urlset
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
		http://www.google.com/schemas/sitemap-news/0.9 http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd"
	xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">

	<url>
		<loc>http://example.com/news-article-#1</loc>
		<news:news>
			<news:publication>
				<news:name>Publication Name</news:name>
				<news:language>en</news:language>
			</news:publication>
			<news:publication_date>2015-12-16T15:34:06Z</news:publication_date>
			<news:title>News article #1</news:title>
		</news:news>
	</url>

	<url>
		<loc>http://example.com/business/article55.html</loc>
		<news:news>
			<news:publication>
				<news:name>Publication Name Overridden</news:name>
				<news:language>en</news:language>
			</news:publication>
			<news:genres>PressRelease, Blog</news:genres>
			<news:publication_date>2008-12-23</news:publication_date>
			<news:title>Companies A, B in Merger Talks</news:title>
			<news:keywords>business, merger, acquisition, A, B</news:keywords>
		</news:news>
	</url>

</urlset>
XML;

		$this->assertEquals($xml, $this->sitemap->get_xml());
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_XmlGoogleNews::get_xml
	 */
	public function test_get_xml_with_image()
	{
		$this->provider
			->shouldReceive('is_image_allowed')
			->andReturn(true)
			->byDefault();

		$this->provider
			->shouldReceive('get_items')
			->andReturn(array(
				array(
					'location' => $this->get_location('business/article55.html'),
					'language' => 'en',
					'genres'   => 'PressRelease, Blog',
					'pub_date' => '2008-12-23',
					'title'    => 'Companies A, B in Merger Talks',
					'keywords' => 'business, merger, acquisition, A, B',
					'image'    => array(
						'location' => 'http://external.com/image.jpg',
						'title'    => 'image title <>&',
						'caption'  => 'image caption <>&'
					)
				),
			))
			->byDefault();

		$this->sitemap = new BWP_Sitemaps_Sitemap_XmlGoogleNews(
			$this->provider,
			$this->sanitizer_factory
		);

		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>

<urlset
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
		http://www.google.com/schemas/sitemap-news/0.9 http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd"
	xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

	<url>
		<loc>http://example.com/business/article55.html</loc>
		<news:news>
			<news:publication>
				<news:name>Publication Name</news:name>
				<news:language>en</news:language>
			</news:publication>
			<news:genres>PressRelease, Blog</news:genres>
			<news:publication_date>2008-12-23</news:publication_date>
			<news:title>Companies A, B in Merger Talks</news:title>
			<news:keywords>business, merger, acquisition, A, B</news:keywords>
		</news:news>
		<image:image>
			<image:loc>http://external.com/image.jpg</image:loc>
			<image:title>image title &amp;</image:title>
			<image:caption>image caption &amp;</image:caption>
		</image:image>
	</url>

</urlset>
XML;

		$this->assertEquals($xml, $this->sitemap->get_xml());
	}
}
