<?php

use Symfony\Component\CssSelector\CssSelector;

class BWP_Sitemaps_Sitemap_Exclude_Items_Functional_Test extends BWP_Framework_PHPUnit_WP_Functional_TestCase
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
		$fixtures_dir = dirname(__FILE__) . '/data/fixtures';
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$fixtures_dir . '/post-types-and-taxonomies.php' => 'bwp-gxs-fixtures/post-types-and-taxonomies.php',
			$fixtures_dir . '/excluded-terms-slugs.php' => 'bwp-gxs-fixtures/excluded-terms-slugs.php',
			$root_dir . '/bwp-gxs.php' => 'bwp-google-xml-sitemaps/bwp-gxs.php'
		);
	}

	protected static function set_wp_default_options()
	{
		self::update_option('permalink_structure', '/%year%/%monthnum%/%day%/%postname%/');
		self::update_option('bwp_gxs_generator_exclude_terms_by_slugs', '');

		flush_rewrite_rules();

		self::commit_transaction();
	}

	protected static function set_plugin_default_options()
	{
		self::update_option(BWP_GXS_GENERATOR, array(
			'input_exclude_post_type' => '',
			'input_exclude_taxonomy'  => '',
			'enable_sitemap_taxonomy' => 'yes',
			'enable_cache'            => ''
		));
	}

	public function test_should_exclude_sitemaps_correctly()
	{
		$this->create_posts('post', 1);
		$this->create_posts('movie', 1);

		$this->create_terms('category', 1);
		$this->create_terms('post_tag', 1);

		self::set_options(BWP_GXS_GENERATOR, array(
			'input_exclude_post_type' => 'movie',
			'input_exclude_taxonomy'  => 'post_tag'
		));

		CssSelector::disableHtmlExtension();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_index_url());

		$this->assertCount(1, $crawler->filter('default|sitemapindex default|sitemap default|loc:contains("post.xml")'));
		$this->assertCount(0, $crawler->filter('default|sitemapindex default|sitemap default|loc:contains("post_movie.xml")'));
		$this->assertCount(1, $crawler->filter('default|sitemapindex default|sitemap default|loc:contains("taxonomy_category.xml")'));
		$this->assertCount(0, $crawler->filter('default|sitemapindex default|sitemap default|loc:contains("taxonomy_post_tag.xml")'));
	}

	public function test_should_exclude_posts_correctly_if_specified()
	{
		$this->create_posts('post');
		$this->create_posts('movie');

		self::set_options(BWP_GXS_EXCLUDED_POSTS, array(
			'post'  => '1,2,3',
			'movie' => '8,9,10'
		));

		CssSelector::disableHtmlExtension();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post'));

		$this->assertCount(2, $crawler->filter('default|urlset default|url default|loc'));

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post_movie'));

		$this->assertCount(2, $crawler->filter('default|urlset default|url default|loc'));
	}

	public function test_should_exclude_terms_correctly_if_specified()
	{
		$this->prepare_for_taxonomy_tests();

		self::set_options(BWP_GXS_EXCLUDED_TERMS, array(
			'category' => '1,2,3',
			'genre'    => '8,9,10'
		));

		CssSelector::disableHtmlExtension();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('taxonomy_category'));

		$this->assertCount(2, $crawler->filter('default|urlset default|url default|loc'));

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('taxonomy_genre'));

		$this->assertCount(2, $crawler->filter('default|urlset default|url default|loc'));
	}

	public function test_should_exclude_terms_using_slugs_correctly()
	{
		$this->prepare_for_taxonomy_tests();

		self::update_option('bwp_gxs_generator_exclude_terms_by_slugs', 'yes');

		CssSelector::disableHtmlExtension();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('taxonomy_category'));

		$this->assertCount(2, $crawler->filter('default|urlset default|url default|loc'));

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('taxonomy_genre'));

		$this->assertCount(2, $crawler->filter('default|urlset default|url default|loc'));
	}

	protected function create_posts($post_type = 'post', $count = 5)
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

	protected function prepare_for_taxonomy_tests()
	{
		$posts = $this->create_posts('post');
		$movies = $this->create_posts('movie');

		$categories = $this->create_terms('category');
		$genres = $this->create_terms('genre');

		$this->factory->term->add_post_terms($posts[0], $categories, 'category');
		$this->factory->term->add_post_terms($movies[0], $genres, 'genre');
	}
}
