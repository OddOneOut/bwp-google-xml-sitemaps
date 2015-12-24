<?php

use \Mockery as Mockery;

/**
 * @covers BWP_Sitemaps
 */
class BWP_Sitemaps_Test extends BWP_Framework_PHPUnit_Unit_TestCase
{
	protected $plugin_slug = 'bwp-google-xml-sitemaps';

	protected $excluder;

	protected function setUp()
	{
		parent::setUp();

		$this->excluder = Mockery::mock('BWP_Sitemaps_Excluder');

		$this->plugin = Mockery::mock('BWP_Sitemaps')
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$this->plugin->shouldReceive('pre_init_hooks')->byDefault();

		$this->plugin->__construct(array(
			'title'       => 'BWP Google XML Sitemaps',
			'version'     => '1.4.0',
			'php_version' => '5.2.0',
			'wp_version'  => '3.6',
			'domain'      => 'bwp-google-xml-sitemaps'
		), $this->bridge, $this->cache);

		$this->plugin->set_post_excluder($this->excluder);
		$this->plugin->set_term_excluder($this->excluder);
	}

	protected function tearDown()
	{
		parent::tearDown();
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

	/**
	 * @covers BWP_Sitemaps::init_properties
	 * @dataProvider get_test_xslt_stylesheet_should_use_same_http_host_as_sitemap_url_cases
	 */
	public function test_xslt_stylesheet_should_use_same_http_host_as_sitemap_url($http_host, $home_url, $custom_xslt, $expected_xslt_urls)
	{
		$this->plugin->options['enable_xslt']       = 'yes';
		$this->plugin->options['input_custom_xslt'] = $custom_xslt;

		$_SERVER['HTTP_HOST'] = $http_host;

		$this->bridge
			->shouldReceive('site_url')
			->andReturn($home_url)
			->byDefault();

		$plugin_wp_url = $home_url . '/wp-content/plugins/' . $this->plugin_slug . '/';
		$this->bridge->shouldReceive('plugins_url')->andReturn($plugin_wp_url)->byDefault();
		$this->bridge->shouldReceive('plugin_dir_url')->andReturn($plugin_wp_url)->byDefault();

		$this->call_protected_method('build_wp_properties');
		$this->call_protected_method('init_properties');

		$this->assertEquals($expected_xslt_urls[0], $this->plugin->xslt);
		$this->assertEquals($expected_xslt_urls[1], $this->plugin->xslt_index);
	}

	public function get_test_xslt_stylesheet_should_use_same_http_host_as_sitemap_url_cases()
	{
		return array(
			'all settings correct' => array(
				'domain.com',
				'http://domain.com',
				'',
				array(
					'http://domain.com/wp-content/plugins/bwp-google-xml-sitemaps/assets/xsl/bwp-sitemap.xsl',
					'http://domain.com/wp-content/plugins/bwp-google-xml-sitemaps/assets/xsl/bwp-sitemapindex.xsl'
				)
			),

			'http host and home url are different' => array(
				'www.domain.com',
				'http://domain.com',
				'',
				array(
					'http://www.domain.com/wp-content/plugins/bwp-google-xml-sitemaps/assets/xsl/bwp-sitemap.xsl',
					'http://www.domain.com/wp-content/plugins/bwp-google-xml-sitemaps/assets/xsl/bwp-sitemapindex.xsl'
				)
			),

			'http host and home url are different #2' => array(
				'domain.com',
				'http://www.domain.com',
				'',
				array(
					'http://domain.com/wp-content/plugins/bwp-google-xml-sitemaps/assets/xsl/bwp-sitemap.xsl',
					'http://domain.com/wp-content/plugins/bwp-google-xml-sitemaps/assets/xsl/bwp-sitemapindex.xsl'
				)
			),

			'custom xslt stylesheet correct setting' => array(
				'domain.com',
				'http://domain.com',
				'http://domain.com/sitemap.xsl',
				array(
					'http://domain.com/sitemap.xsl',
					'http://domain.com/sitemapindex.xsl'
				)
			),

			'custom xslt stylesheet and http host are differnet' => array(
				'www.domain.com',
				'http://domain.com',
				'http://domain.com/sitemap.xsl',
				array(
					'http://www.domain.com/sitemap.xsl',
					'http://www.domain.com/sitemapindex.xsl'
				)
			),
		);
	}

	/**
	 * @covers BWP_Sitemaps::add_excluded_posts
	 * @dataProvider get_flatten_parameter
	 */
	public function test_add_excluded_posts($flatten)
	{
		$group = 'post';

		$user_filtered_excluded_items = array(1,2,3,4);

		$this->excluder
			->shouldReceive('get_excluded_items')
			->with($group, $flatten)
			->andReturn(array(2,3,4,5,6))
			->byDefault();

		$this->assertEquals(
			array(1,2,3,4,5,6),
			$this->plugin->add_excluded_posts($user_filtered_excluded_items, $group, $flatten)
		);
	}

	/**
	 * @covers BWP_Sitemaps::add_excluded_terms
	 * @dataProvider get_flatten_parameter
	 */
	public function test_add_excluded_terms($flatten)
	{
		$group = 'category';

		$user_filtered_excluded_items = array(1,2,3,4);

		$this->excluder
			->shouldReceive('get_excluded_items')
			->with($group, $flatten)
			->andReturn(array(2,3,4,5,6))
			->byDefault();

		$this->assertEquals(
			array(1,2,3,4,5,6),
			$this->plugin->add_excluded_terms($user_filtered_excluded_items, $group, $flatten)
		);
	}

	public function get_flatten_parameter()
	{
		return array(
			array(false),
			array(true),
		);
	}

	/**
	 * @covers BWP_Sitemaps::add_post_title_like_query_variable
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider get_bwp_post_title_like
	 */
	public function test_add_post_title_like_query_variable($post_title_like)
	{
		$wp_query = Mockery::mock('WP_Query');

		$wp_query
			->shouldReceive('get')
			->with('bwp_post_title_like')
			->andReturn($post_title_like)
			->byDefault();

		global $wpdb;

		$wpdb = Mockery::mock('wpdb_mock');

		$wpdb->posts = 'posts';

		$wpdb
			->shouldReceive('esc_like')
			->andReturnUsing(function($like) {
				return $like . '_esc_like';
			})
			->byDefault();

		$this->bridge
			->shouldReceive('esc_sql')
			->andReturnUsing(function($like) {
				return $like . '_esc_sql';
			})
			->byDefault();

		$where = $this->plugin->add_post_title_like_query_variable('SQL', $wp_query);

		if ($post_title_like) {
			$this->assertEquals("SQL AND LOWER(posts.post_title) LIKE '%title_esc_like_esc_sql%'", $where);
		} else {
			$this->assertEquals('SQL', $where);
		}
	}

	public function get_bwp_post_title_like()
	{
		return array(
			array(false),
			array(null),
			array(''),
			array('title')
		);
	}

	/**
	 * @covers BWP_Sitemaps::ping
	 */
	public function test_ping_should_ping_with_correct_url()
	{
		$post = $this->prepare_for_ping_test($this->plugin->get_sitemap_index_url());

		$this->plugin->ping($post);
	}

	/**
	 * @covers BWP_Sitemaps::ping_google_news
	 */
	public function test_ping_google_news_should_ping_with_correct_url()
	{
		$this->bridge
			->shouldReceive('get_the_category')
			->andReturn(array())
			->byDefault();

		$this->plugin->options['select_news_cat_action'] = 'exc';

		$post = $this->prepare_for_ping_test($this->plugin->get_sitemap_url('post_google_news'));

		$this->plugin->ping_google_news($post);
	}

	protected function prepare_for_ping_test($sitemap_url)
	{
		$this->bridge
			->shouldReceive('get_post_types')
			->andReturn(array('post'))
			->byDefault();

		$this->bridge
			->shouldReceive('current_time')
			->andReturn(time())
			->byDefault();

		$post = new stdClass();
		$post->ID = 1;
		$post->post_type = 'post';

		$sitemap_url = urlencode($sitemap_url);

		$this->bridge
			->shouldReceive('wp_remote_post')
			->with(
				'http://www.google.com/webmasters/sitemaps/ping?sitemap=' . $sitemap_url,
				Mockery::type('array')
			)
			->once();

		$this->bridge
			->shouldReceive('wp_remote_post')
			->with(
				'http://www.bing.com/webmaster/ping.aspx?siteMap=' . $sitemap_url,
				Mockery::type('array')
			)
			->once();

		$this->plugin
			->shouldReceive('commit_logs')
			->byDefault();

		return $post;
	}
}
