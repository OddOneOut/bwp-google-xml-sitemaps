<?php

class BWP_Sitemaps_Sitemap_Output_Functional_Test extends BWP_Sitemaps_PHPUnit_WP_Functional_TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->plugin->options['input_cache_dir'] = self::$cache_dir;
	}

	public function tearDown()
	{
		if (isset($_SERVER['HTTPS'])) {
			unset($_SERVER['HTTPS']);
		}

		parent::tearDown();
	}

	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'enable_cache'    => '',
			'input_cache_dir' => self::$cache_dir,
		);

		self::update_option(BWP_GXS_GENERATOR, $default_options);
	}

	/**
	 * @dataProvider is_ssl
	 */
	public function test_should_use_correct_scheme_for_sitemap_items($is_ssl)
	{
		$incorrect_scheme = 'https';

		if ($is_ssl) {
			$_SERVER['HTTP']  = 'on';
			$incorrect_scheme = 'http';
		}

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_index_url());

		$this->assertCount(
			0,
			$crawler->filter(sprintf('default|sitemapindex default|sitemap default|loc:contains(%s\:\/\/)', $incorrect_scheme)),
			sprintf('sitemap index should not contain sitemap items with incorrect scheme "%s"', $incorrect_scheme)
		);

		$this->create_posts('post');
		self::commit_transaction();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post'));

		$this->assertCount(
			0,
			$crawler->filter(sprintf('default|urlset default|url default|loc:contains(%s\:\/\/)', $incorrect_scheme)),
			sprintf('regular sitemaps should not contain sitemap items with incorrect scheme "%s"', $incorrect_scheme)
		);
	}

	/**
	 * @dataProvider is_ssl
	 */
	public function test_should_use_correct_scheme_for_stylesheet($is_ssl)
	{
		$xslt       = $this->plugin->plugin_wp_url . 'assets/xsl/bwp-sitemap.xsl';
		$xslt_index = $this->plugin->plugin_wp_url . 'assets/xsl/bwp-sitemapindex.xsl';

		if ($is_ssl) {
			$_SERVER['HTTPS'] = 'on';

			$xslt       = str_replace('http:', 'https:', $xslt);
			$xslt_index = str_replace('http:', 'https:', $xslt_index);
		}

		self::set_options(BWP_GXS_GENERATOR, array(
			'enable_xslt' => 'yes'
		));

		$client = self::get_client(false);
		$client->request('GET', $this->plugin->get_sitemap_index_url());

		$this->assertContains($xslt_index, $client->getResponse()->getContent());

		$this->create_posts();
		self::commit_transaction();

		$client->request('GET', $this->plugin->get_sitemap_url('post'));

		$this->assertContains($xslt, $client->getResponse()->getContent());
	}

	/**
	 * @dataProvider is_ssl
	 */
	public function test_should_create_separate_cached_sitemap_for_https($is_ssl)
	{
		self::set_options(BWP_GXS_GENERATOR, array(
			'enable_cache' => 'yes',
			'enable_xslt'  => 'yes'
		));

		$correct_scheme   = 'http';
		$incorrect_scheme = 'https';

		if ($is_ssl) {
			$_SERVER['HTTP']  = 'on';

			$correct_scheme   = 'https';
			$incorrect_scheme = 'http';
		}

		$url = $this->plugin->get_sitemap_index_url();

		// first request the url with incorrect scheme to create the cache
		$client = self::get_client(false);
		$client->request('GET', str_replace(array('http://', 'https://'), array('https://', 'http://'), $url));

		// crawl the url with correct scheme
		$crawler = self::get_crawler_from_url($url);

		$this->assertCount(
			0,
			$crawler->filter(sprintf('default|sitemapindex default|sitemap default|loc:contains(%s\:\/\/)', $incorrect_scheme)),
			sprintf('the %s version of sitemap index should not use the cached %s version', $correct_scheme, $incorrect_scheme)
		);
	}

	/**
	 * @dataProvider is_ssl
	 */
	public function test_should_send_correct_headers_when_output_sitemap_without_cache($is_ssl)
	{
		if ($is_ssl) {
			$_SERVER['HTTPS'] = 'on';
		}

		$client = self::get_client(false);
		$client->request('GET', $this->plugin->get_sitemap_index_url());

		$this->assertEquals(200, $client->getResponse()->getStatus(), 'should have 200 SUCCESS status code');
		$this->assertEquals('noindex', $client->getResponse()->getHeader('X-Robots-Tag'), 'should have noindex X-Robots-Tag header');
	}

	public function is_ssl()
	{
		return array(
			array(0),
			array(1)
		);
	}

	/**
	 * @dataProvider get_accept_encoding_headers_and_schemes
	 */
	public function test_should_send_304_not_modified_response_status_when_output_sitemap_from_cache($accept_encoding, $is_ssl = 0)
	{
		if ($is_ssl) {
			$_SERVER['HTTPS'] = 'on';
		}

		self::set_options(BWP_GXS_GENERATOR, array(
			'enable_cache' => 'yes'
		));

		$url = $this->plugin->get_sitemap_index_url();

		$client = self::get_client(false);
		if ($accept_encoding) {
			$client->setHeader('Accept-Encoding', $accept_encoding);
		}

		// get and cache for the first time
		$client->request('GET', $url);
		$client->setHeader('If-Modified-Since', $client->getResponse()->getHeader('Last-Modified'));

		// request again
		$client->request('GET', $url);

		$this->assertEquals(304, $client->getResponse()->getStatus(), 'should have 304 NOT MODIFIED status code');
	}

	public function get_accept_encoding_headers_and_schemes()
	{
		return array(
			array('', 0),
			array('', 1),
			array('gzip'),
			array('gzip', 1),
			array('deflate'),
			array('deflate', 1),
			array('gzip, deflate'),
			array('gzip, deflate', 1)
		);
	}

	/**
	 * @dataProvider get_blank_sitemap_names
	 */
	public function test_should_send_404_not_found_response_status_when_sitemap_is_blank($sitemap_name)
	{
		if ($sitemap_name == 'post') {
			self::reset_posts();
		} else {
			self::reset_terms();
		}

		$client = self::get_client(false);
		$client->request('GET', $this->plugin->get_sitemap_url($sitemap_name));

		$this->assertEquals(404, $client->getResponse()->getStatus());
	}

	public function get_blank_sitemap_names()
	{
		return array(
			array('post'),
			array('taxonomy_category')
		);
	}
}
