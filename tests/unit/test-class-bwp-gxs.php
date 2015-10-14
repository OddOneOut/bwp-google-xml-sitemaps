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
			'php_version' => '5.1.2',
			'wp_version'  => '3.0',
			'domain'      => 'bwp-google-xml-sitemaps'
		), $this->bridge, $this->cache);

		$this->plugin->set_post_excluder($this->excluder);
		$this->plugin->set_term_excluder($this->excluder);

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

	/**
	 * @covers BWP_Sitemaps::add_excluded_posts
	 */
	public function test_add_excluded_posts()
	{
		$group = 'post';

		$user_filtered_excluded_items = array(1,2,3,4);

		$this->excluder
			->shouldReceive('get_excluded_items')
			->with($group)
			->andReturn(array(2,3,4,5,6))
			->byDefault();

		$this->assertEquals(
			array(1,2,3,4,5,6), $this->plugin->add_excluded_posts($user_filtered_excluded_items, $group)
		);
	}

	/**
	 * @covers BWP_Sitemaps::add_excluded_terms
	 */
	public function test_add_excluded_terms()
	{
		$group = 'category';

		$user_filtered_excluded_items = array(1,2,3,4);

		$this->excluder
			->shouldReceive('get_excluded_items')
			->with($group)
			->andReturn(array(2,3,4,5,6))
			->byDefault();

		$this->assertEquals(
			array(1,2,3,4,5,6), $this->plugin->add_excluded_terms($user_filtered_excluded_items, $group)
		);
	}

	/**
	 * @covers BWP_Sitemaps::add_post_title_like_query_variable
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

		$wpdb = Mockery::mock('wpdb');

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
