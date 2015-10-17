<?php

use Symfony\Component\CssSelector\CssSelector;

class BWP_Sitemaps_Sitemap_Google_News_Functional_Test extends BWP_Framework_PHPUnit_WP_Functional_TestCase
{
	protected $plugin;

	public function setUp()
	{
		parent::setUp();

		global $bwp_gxs;

		$this->plugin = $bwp_gxs;

		self::reset_posts_terms();
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
		self::update_option(BWP_GXS_GENERATOR, array(
			'enable_cache' => ''
		));

		self::update_option(BWP_GXS_GOOGLE_NEWS, array(
			'select_news_cat_action'   => 'inc',
			'enable_news_sitemap'      => 'yes',
			'enable_news_keywords'     => 'yes',
			'select_news_keyword_type' => 'cat',
			'enable_news_multicat'     => ''
		));
	}

	public function test_should_generate_news_sitemap_correctly()
	{
		$this->prepare_for_tests();

		CssSelector::disableHtmlExtension();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post_google_news'));

		$this->assertCount(3, $crawler->filter('default|urlset default|url default|loc'), 'only 3 news posts should be eligible');

		$this->assertCount(
			3,
			$crawler->filter('default|urlset default|url news|news news|genres:contains("PressRelease, Satire")'),
			'multi-category support is not enabled, so only genres from first category should be used'
		);

		$this->assertCount(
			3,
			$crawler->filter('default|urlset default|url news|news news|keywords:contains("Term 1")'),
			'multi-category support is not enabled, so only first category should be used as keyword'
		);
	}

	/**
	 * @depends test_should_generate_news_sitemap_correctly
	 */
	public function test_should_generate_news_sitemap_correctly_when_multi_category_support_is_enabled()
	{
		self::set_options(BWP_GXS_GOOGLE_NEWS, array(
			'enable_news_multicat' => 'yes'
		));

		$this->prepare_for_tests();

		CssSelector::disableHtmlExtension();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post_google_news'));

		$this->assertCount(
			3,
			$crawler->filter('default|urlset default|url news|news news|genres:contains("PressRelease, Satire, Blog, OpEd, Opinion, UserGenerated")'),
			'multi-category support is enabled, genres from all categories should be used'
		);

		$this->assertCount(
			3,
			$crawler->filter('default|urlset default|url news|news news|keywords:contains("Term 1, Term 2, Term 3")'),
			'multi-category support is enabled, so all categories should be used as keywords'
		);
	}

	protected function create_posts($post_type = 'post', $count = 5, $post_date)
	{
		return $this->factory->post->create_many($count, array(
			'post_type' => $post_type
		));
	}

	protected function create_terms($taxonomy = 'category', $count = 5)
	{
		return $this->factory->term->create_many($count, array(
			'taxonomy' => $taxonomy,
			'slug'     => $taxonomy
		));
	}

	protected function prepare_for_tests()
	{
		$news_posts     = $this->create_posts('post', 3, current_time('mysql'));
		$not_news_posts = $this->create_posts('post', 3, current_time('mysql'));

		$two_days_ago = new DateTime('2 days 1 minute ago');
		$outdated_news_posts = $this->create_posts('post', 3, $two_days_ago->format('Y-m-d H:i:s'));

		// create 5 categories but only 3 will be used for news
		$categories = $this->create_terms('category', 5);

		foreach ($news_posts as $post_id) {
			$this->factory->term->add_post_terms($post_id, array_slice($categories, 0, 3), 'category');
		}

		foreach ($not_news_posts as $post_id) {
			$this->factory->term->add_post_terms($post_id, array_slice($categories, 4), 'category');
		}

		self::set_options(BWP_GXS_GOOGLE_NEWS, array(
			'select_news_cats' => '1,2,3',
			'input_news_genres' => array(
				'cat_1' => 'PressRelease, Satire',
				'cat_2' => 'Blog, OpEd',
				'cat_3' => 'Opinion, UserGenerated'
			)
		));
	}
}
