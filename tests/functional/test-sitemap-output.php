<?php

class BWP_Sitemaps_Sitemap_Output_Functional_Test extends BWP_Sitemaps_PHPUnit_WP_Functional_TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->plugin->options['input_cache_dir'] = self::$cache_dir;
	}

	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'enable_cache'    => '',
			'input_cache_dir' => self::$cache_dir,
		);

		self::update_option(BWP_GXS_GENERATOR, $default_options);
	}

	public function test_should_send_correct_headers_when_output_sitemap_without_cache()
	{
		$client = self::get_client(false);
		$client->request('GET', $this->plugin->get_sitemap_url('sitemapindex'));

		$this->assertEquals(200, $client->getResponse()->getStatus(), 'should have 200 SUCCESS status code');
		$this->assertEquals('noindex', $client->getResponse()->getHeader('X-Robots-Tag'), 'should have noindex X-Robots-Tag header');
	}

	/**
	 * @dataProvider get_accept_encoding_headers
	 */
	public function test_should_send_304_not_modified_response_status_when_output_sitemap_from_cache($accept_encoding)
	{
		self::set_options(BWP_GXS_GENERATOR, array(
			'enable_cache' => 'yes'
		));

		$src = $this->plugin->get_sitemap_index_url();

		$client = self::get_client(false);
		if ($accept_encoding) {
			$client->setHeader('Accept-Encoding', $accept_encoding);
		}

		// get and cache for the first time
		$client->request('GET', $src);
		$client->setHeader('If-Modified-Since', $client->getResponse()->getHeader('Last-Modified'));

		// request again
		$client->request('GET', $src);

		$this->assertEquals(304, $client->getResponse()->getStatus(), 'should have 304 NOT MODIFIED status code');
	}

	public function get_accept_encoding_headers()
	{
		return array(
			array(''),
			array('gzip'),
			array('deflate'),
			array('gzip, deflate')
		);
	}
}
