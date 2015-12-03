<?php

use Symfony\Component\CssSelector\CssSelector;

class BWP_Sitemaps_Sitemap_Google_News_Functional_Test extends BWP_Sitemaps_PHPUnit_WP_Functional_TestCase
{
	public function setUp()
	{
		parent::setUp();

		self::reset_posts_terms();
	}

	protected static function set_plugin_default_options()
	{
		self::update_option(BWP_GXS_GENERATOR, array(
			'enable_cache' => ''
		));

		self::update_option(BWP_GXS_GOOGLE_NEWS, array(
			'select_news_cat_action'     => 'inc',
			'enable_news_sitemap'        => 'yes',
			'enable_news_keywords'       => 'yes',
			'select_news_keyword_source' => '',
			'enable_news_multicat'       => ''
		));
	}

	public function get_extra_plugins()
	{
		$fixtures_dir = dirname(__FILE__) . '/data/fixtures';

		return array(
			$fixtures_dir . '/post-types-and-taxonomies.php' => 'bwp-gxs-fixtures/post-types-and-taxonomies.php'
		);
	}

	/**
	 * @dataProvider get_news_post_type_and_taxonomy
	 */
	public function test_should_generate_news_sitemap_correctly($post_type, $taxonomy, $multi_term = false)
	{
		self::set_options(BWP_GXS_GOOGLE_NEWS, array(
			'enable_news_multicat' => $multi_term ? 'yes' : ''
		));

		$this->prepare_for_tests($post_type, $taxonomy);

		CssSelector::disableHtmlExtension();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post_google_news'));

		$this->assertCount(3, $crawler->filter('default|urlset default|url default|loc'), 'only 3 news posts should be eligible');

		if (! $multi_term) {
			$this->assertCount(
				3,
				$crawler->filter('default|urlset default|url news|news news|genres:contains("PressRelease, Satire")'),
				'multi-term support is not enabled, so only genres from first term should be used'
			);
		} else {
			$this->assertCount(
				3,
				$crawler->filter('default|urlset default|url news|news news|genres:contains("PressRelease, Satire, Blog, OpEd, Opinion, UserGenerated")'),
				'multi-term support is enabled, genres from all terms should be used'
			);
		}
	}

	/**
	 * @depends test_should_generate_news_sitemap_correctly
	 * @dataProvider get_news_post_type_and_taxonomy
	 */
	public function test_should_generate_news_sitemap_with_correct_keywords_from_news_taxonomy($post_type, $taxonomy, $multi_term, $expected_keywords)
	{
		self::set_options(BWP_GXS_GOOGLE_NEWS, array(
			'enable_news_multicat' => $multi_term ? 'yes' : ''
		));

		$this->prepare_for_tests($post_type, $taxonomy);

		CssSelector::disableHtmlExtension();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post_google_news'));

		if (! $multi_term) {
			$this->assertCount(
				3,
				$crawler->filter('default|urlset default|url news|news news|keywords:contains("' . $expected_keywords . '")'),
				'multi-term support is not enabled, so only first term should be used as keyword'
			);
		} else {
			$this->assertCount(
				3,
				$crawler->filter('default|urlset default|url news|news news|keywords:contains("' . $expected_keywords . '")'),
				'multi-term support is enabled, so all terms should be used as keyword'
			);
		}
	}

	public function get_news_post_type_and_taxonomy()
	{
		return array(
			array('post', 'category', false, 'Term 1'),
			array('post', 'category', true, 'Term 1, Term 2, Term 3'),
			array('movie', 'genre', false, 'Term 1'),
			array('movie', 'genre', true, 'Term 1, Term 2, Term 3')
		);
	}

	/**
	 * @depends test_should_generate_news_sitemap_correctly
	 * @dataProvider get_news_post_type_and_taxonomy_with_keyword_source
	 */
	public function test_should_generate_news_sitemap_with_correct_keywords($post_type, $taxonomy, $keyword_source, $multi_term, $expected_keywords)
	{
		self::set_options(BWP_GXS_GOOGLE_NEWS, array(
			'enable_news_multicat'       => $multi_term ? 'yes' : '',
			'select_news_keyword_source' => $keyword_source
		));

		$news_posts = $this->prepare_for_tests($post_type, $taxonomy);

		CssSelector::disableHtmlExtension();

		// keyword source is the same as news taxonomy, test multi-term
		// setting as well
		if ($keyword_source == $taxonomy) {
			$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post_google_news'));

			if (! $multi_term) {
				$this->assertCount(
					3,
					$crawler->filter('default|urlset default|url news|news news|keywords:contains("' . $expected_keywords . '")'),
					'multi-term support is not enabled, so only first category should be used as keyword'
				);
			} else {
				$this->assertCount(
					3,
					$crawler->filter('default|urlset default|url news|news news|keywords:contains("' . $expected_keywords . '")'),
					'multi-term support is not enabled, so only first category should be used as keyword'
				);
			}
		} else {
			// setup taxonomy used as keyword source
			$terms = $this->create_terms($keyword_source, 5);
			foreach ($news_posts as $post_id) {
				$this->factory->term->add_post_terms($post_id, $terms, $keyword_source);
			}

			self::commit_transaction();

			// start crawling once we're done setting up terms as keywords
			$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post_google_news'));

			$this->assertCount(
				3,
				$crawler->filter('default|urlset default|url news|news news|keywords:contains("' . $expected_keywords . '")')
			);
		}
	}

	public function get_news_post_type_and_taxonomy_with_keyword_source()
	{
		return array(
			'use keyword from news taxonomy ("category"), multi-term off' => array('post', 'category', 'category', false, 'Term 1'),
			'use keyword from news taxonomy ("category"), multi-term on' => array('post', 'category', 'category', true, 'Term 1, Term 2, Term 3'),
			'use keyword from "post_tag"' => array('post', 'category', 'post_tag', false, 'Term 10, Term 6, Term 7, Term 8, Term 9'),
			'use keyword from news taxonomy ("genre"), multi-term off' => array('movie', 'genre', 'genre', false, 'Term 1'),
			'use keyword from news taxonomy ("genre"), multi-term on' => array('movie', 'genre', 'genre', true, 'Term 1, Term 2, Term 3'),
			'use keyword from "category"' => array('movie', 'genre', 'category', false, 'Term 10, Term 6, Term 7, Term 8, Term 9'),
		);
	}

	protected function prepare_for_tests($post_type = 'post', $taxonomy = 'category')
	{
		$this->load_fixtures('post-types-and-taxonomies.php');

		bwp_gxs_register_custom_post_types();
		bwp_gxs_register_custom_taxonomies();

		$news_posts     = $this->create_posts($post_type, 3);
		$not_news_posts = $this->create_posts($post_type, 3);

		$two_days_ago = new DateTime('2 days 1 minute ago');
		$outdated_news_posts = $this->create_posts($post_type, 3, $two_days_ago->format('Y-m-d H:i:s'));

		// create 5 terms but only 3 will be used for news
		$terms = $this->create_terms($taxonomy, 5);

		foreach ($news_posts as $post_id) {
			$this->factory->term->add_post_terms($post_id, array_slice($terms, 0, 3), $taxonomy);
		}

		foreach ($not_news_posts as $post_id) {
			$this->factory->term->add_post_terms($post_id, array_slice($terms, 4), $taxonomy);
		}

		self::set_options(BWP_GXS_GOOGLE_NEWS, array(
			'select_news_post_type' => $post_type,
			'select_news_taxonomy'  => $taxonomy,
			'select_news_cats' => '1,2,3',
			'input_news_genres' => array(
				'cat_1' => 'PressRelease, Satire',
				'cat_2' => 'Blog, OpEd',
				'cat_3' => 'Opinion, UserGenerated'
			)
		));

		return $news_posts;
	}
}
