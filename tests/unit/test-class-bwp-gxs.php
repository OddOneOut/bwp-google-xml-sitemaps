<?php

use \Mockery as Mockery;

/**
 * @covers BWP_Sitemaps
 */
class BWP_Sitemaps_Test extends BWP_Framework_PHPUnit_Unit_TestCase
{
    protected $plugin_slug = 'bwp-google-xml-sitemaps';

	protected function setUp()
	{
        parent::setUp();

        $this->plugin = Mockery::mock('BWP_Sitemaps')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

		$this->plugin->shouldReceive('pre_init_hooks')->byDefault();

		$this->plugin->__construct(array(
            'title'       => 'BWP Google XML Sitemaps',
			'version'     => '1.4.0',
			'php_version' => '5.1.2',
			'wp_version'  => '3.0',
			'domain'      => 'bwp-google-xml-sitemaps'
		), $this->bridge);

        $_SERVER['HTTP_HOST'] = 'example.com';
	}

    protected function tearDown()
    {
        parent::tearDown();

        $_SERVER['HTTP_HOST'] = null;
    }

    /**
     * @covers BWP_Sitemaps::build_properties
     */
    public function test_xslt_stylesheet_should_be_disabled_by_default()
    {
        $this->assertNotEquals('yes', $this->plugin->options['enable_xslt']);
    }

    /**
     * @covers BWP_Sitemaps::init_properties
     */
    public function test_xslt_stylesheet_should_be_init_correctly_when_enabled()
    {
        $this->plugin->options['enable_xslt'] = 'yes';

        $this->call_protected_method('build_wp_properties');
        $this->call_protected_method('init_properties');

        $this->assertEquals('http://example.com/wp-content/plugins/bwp-google-xml-sitemaps/assets/xsl/bwp-sitemap.xsl', $this->plugin->xslt);
        $this->assertEquals('http://example.com/wp-content/plugins/bwp-google-xml-sitemaps/assets/xsl/bwp-sitemapindex.xsl', $this->plugin->xslt_index);
    }
}
