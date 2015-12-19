<?php

use \Mockery as Mockery;

require_once dirname(__FILE__) . '/test-sitemap-base.php';

/**
 * @covers BWP_Sitemaps_Sitemap_Xml
 */
class BWP_Sitemaps_Sitemap_Xml_Test extends BWP_Sitemaps_Sitemap_Base_Test
{
	public function setUp()
	{
		parent::setUp();

		$this->plugin->options = array(
			'select_default_pri'  => 0.5,
			'select_default_freq' => 'daily'
		);

		$this->sitemap = $this->create_sitemap();
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Xml::get_xml
	 */
	public function test_get_xml_should_return_correct_xml()
	{
		$this->provider
			->shouldReceive('get_items')
			->andReturn(array(
				array(
					'location' => $this->get_location(),
					'lastmod'  => '2005-01-01',
					'freq'     => 'monthly',
					'priority' => '0.8'
				),
				array(
					'location' => $this->get_location('catalog?item=12&desc=vacation_hawaii'),
					'lastmod'  => '2004-12-23',
					'freq'     => 'weekly'
				),
				array(
					'location' => $this->get_location('catalog?item=74&desc=vacation_newfoundland'),
					'lastmod'  => '2004-12-23T18:00:15+00:00',
					'priority' => '0.3'
				),
				array(
					'location' => $this->get_location('catalog?item=83&desc=vacation_usa'),
					'lastmod'  => '2004-11-23'
				),
				// the below item's values should be sanitized
				array(
					'location' => $this->get_location('<>&\'"'),
					'lastmod'  => '<tag>2004-11-23</tag>',
					'priority' => '-0.3',
					'freq'     => 'biweekly'
				)
			))
			->byDefault();

		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="http://example.com/style.xsl"?>

<urlset
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
	xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

	<url>
		<loc>http://example.com/</loc>
		<lastmod>2005-01-01</lastmod>
		<changefreq>monthly</changefreq>
		<priority>0.8</priority>
	</url>

	<url>
		<loc>http://example.com/catalog?item=12&amp;desc=vacation_hawaii</loc>
		<lastmod>2004-12-23</lastmod>
		<changefreq>weekly</changefreq>
	</url>

	<url>
		<loc>http://example.com/catalog?item=74&amp;desc=vacation_newfoundland</loc>
		<lastmod>2004-12-23T18:00:15+00:00</lastmod>
		<priority>0.3</priority>
	</url>

	<url>
		<loc>http://example.com/catalog?item=83&amp;desc=vacation_usa</loc>
		<lastmod>2004-11-23</lastmod>
	</url>

	<url>
		<loc>http://example.com/&amp;&#039;&quot;</loc>
		<lastmod>2004-11-23</lastmod>
		<changefreq>daily</changefreq>
		<priority>0.5</priority>
	</url>

</urlset>
XML;

		$this->assertEquals($xml, $this->sitemap->get_xml());
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Xml::get_xml
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
					'location' => $this->get_location('post-with-featured-image-#1'),
					'image' => array(
						'location' => $this->get_location('image.jpg')
					)
				),
				array(
					'location' => $this->get_location('post-with-featured-image-#2'),
					'image' => array(
						'location' => 'http://external.com/image.jpg',
						'title'    => 'image title <>&',
						'caption'  => 'image caption <>&'
					)
				)
			))
			->byDefault();

		$this->sitemap = $this->create_sitemap();

		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="http://example.com/style.xsl"?>

<urlset
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
	xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

	<url>
		<loc>http://example.com/post-with-featured-image-#1</loc>
		<image:image>
			<image:loc>http://example.com/image.jpg</image:loc>
		</image:image>
	</url>

	<url>
		<loc>http://example.com/post-with-featured-image-#2</loc>
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
