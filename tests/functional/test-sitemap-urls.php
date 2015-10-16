<?php

class BWP_Sitemaps_Sitemap_Urls_Functional_Test extends BWP_Framework_PHPUnit_WP_Functional_TestCase
{
	protected $plugin;

	public function setUp()
	{
		parent::setUp();

		global $bwp_gxs;

		$this->plugin = $bwp_gxs;
	}

	public function get_plugins()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$root_dir . '/bwp-gxs.php' => 'bwp-google-xml-sitemaps/bwp-gxs.php'
		);
	}

	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'enable_cache'    => ''
		);

		self::update_option(BWP_GXS_GENERATOR, $default_options);
	}

	public function test_should_use_query_variable_when_permalink_is_not_used()
	{
		$this->assert_sitemaps_are_generated_with_correct_urls();
	}

	public function test_should_use_static_url_when_permalink_is_used()
	{
		self::update_option('permalink_structure', '/%year%/%monthnum%/%day%/%postname%/');

		flush_rewrite_rules();

		self::commit_transaction();

		$this->assert_sitemaps_are_generated_with_correct_urls();
	}

	protected function assert_sitemaps_are_generated_with_correct_urls()
	{
		$client = self::get_client(false);

		$client->request('GET', $this->plugin->get_sitemap_index_url());

		$this->assertEquals(
			200,
			$client->getResponse()->getStatus(),
			sprintf('sitemap index should exist at %s', $this->plugin->get_sitemap_index_url())
		);

		$this->assertContains('text/xml', $client->getResponse()->getHeader('Content-Type'));

		$this->factory->post->create(array(
			'public' => true
		));

		self::commit_transaction();

		$client->request('GET', $this->plugin->get_sitemap_url('post'));

		$this->assertEquals(
			200,
			$client->getResponse()->getStatus(),
			sprintf('post sitemap should exist at %s', $this->plugin->get_sitemap_url('post'))
		);

		$this->assertContains('text/xml', $client->getResponse()->getHeader('Content-Type'));
	}
}
