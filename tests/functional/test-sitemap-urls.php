<?php

class BWP_Sitemaps_Sitemap_Urls_Functional_Test extends BWP_Sitemaps_PHPUnit_WP_Functional_TestCase
{
	protected static $wp_options = array(
		'permalink_structure' => ''
	);

	protected static function set_plugin_default_options()
	{
		self::update_option(BWP_GXS_GENERATOR, array(
			'enable_cache' => ''
		));
	}

	public function get_extra_plugins()
	{
		$fixtures_dir = dirname(__FILE__) . '/data/fixtures';

		return array(
			$fixtures_dir . '/flush-rewrite-rules.php' => 'bwp-gxs-fixtures/flush-rewrite-rules.php',
		);
	}

	public function test_should_use_query_variable_when_permalink_is_not_used()
	{
		$this->assert_sitemaps_are_generated_with_correct_urls();
	}

	public function test_should_use_static_url_when_permalink_is_used()
	{
		self::update_option('permalink_structure', '/%year%/%monthnum%/%day%/%postname%/');

		// need this to generate the .htaccess file
		self::get_client(false)->request('GET', home_url());

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
