<?php

use \Mockery as Mockery;

require_once dirname(__FILE__) . '/test-sitemap-base.php';

/**
 * @covers BWP_Sitemaps_Sitemap_XmlIndex
 */
class BWP_Sitemaps_Sitemap_XmlIndex_Test extends BWP_Sitemaps_Sitemap_Base_Test
{
	public function setUp()
	{
		parent::setUp();

		$this->sitemap = $this->create_sitemap(
			'BWP_Sitemaps_Sitemap_XmlIndex',
			'http://example.com/style_index.xsl'
		);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_XmlIndex::get_xml
	 */
	public function test_get_xml_should_return_correct_xml()
	{
		$this->provider
			->shouldReceive('get_items')
			->andReturn(array(
				array(
					'location' => $this->get_location('sitemap1.xml.gz'),
					'lastmod'  => '2004-10-01T18:23:17+00:00',
				),
				array(
					'location' => $this->get_location('sitemap2.xml.gz'),
					'lastmod'  => '2005-01-01',
				),
			))
			->byDefault();

		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="http://example.com/style_index.xsl"?>

<sitemapindex
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"
	xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

	<sitemap>
		<loc>http://example.com/sitemap1.xml.gz</loc>
		<lastmod>2004-10-01T18:23:17+00:00</lastmod>
	</sitemap>

	<sitemap>
		<loc>http://example.com/sitemap2.xml.gz</loc>
		<lastmod>2005-01-01</lastmod>
	</sitemap>

</sitemapindex>
XML;

		$this->assertEquals($xml, $this->sitemap->get_xml());
	}
}
