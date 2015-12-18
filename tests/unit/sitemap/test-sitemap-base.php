<?php

use \Mockery as Mockery;

abstract class BWP_Sitemaps_Sitemap_Base_Test extends PHPUnit_Framework_TestCase
{
	protected $plugin;

	protected $provider;

	protected $sanitizer_factory;

	protected $sitemap;

	public function setUp()
	{
		$this->plugin = Mockery::mock('BWP_Sitemaps');

		$this->plugin
			->shouldReceive('get_version')
			->andReturn('1.4.0')
			->byDefault();

		$this->provider = Mockery::mock('BWP_Sitemaps_Sitemap_Provider');

		$this->provider
			->shouldReceive('get_plugin')
			->andReturn($this->plugin)
			->byDefault();

		$this->provider
			->shouldReceive('is_image_allowed')
			->andReturn(false)
			->byDefault();

		$this->provider
			->shouldReceive('get_items')
			->andReturn(array())
			->byDefault();

		$this->sanitizer_factory = new BWP_Sitemaps_Sitemap_Sanitizer_Factory($this->plugin);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Xml::get_xml
	 * @covers BWP_Sitemaps_Sitemap_XmlGoogleNews::get_xml
	 * @dataProvider get_invalid_items
	 */
	public function test_get_xml_should_return_null_when_theres_no_items($invalid_items)
	{
		$this->provider
			->shouldReceive('get_items')
			->andReturn($invalid_items)
			->byDefault();

		$this->assertNull($this->sitemap->get_xml());
	}

	public function get_invalid_items()
	{
		return array(
			array(false),
			array(null),
			array(array()),
		);
	}

	protected function create_sitemap(
		$class_name = 'BWP_Sitemaps_Sitemap_Xml',
		$xsl = 'http://example.com/style.xsl'
	) {
		return new $class_name(
			$this->provider, $this->sanitizer_factory, $xsl
		);
	}

	protected function get_location($slug = '')
	{
		return 'http://example.com/' . $slug;
	}
}
