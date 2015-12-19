<?php

use Symfony\Component\DomCrawler\Crawler;

class BWP_Sitemaps_Sitemap_Image_Functional_Test extends BWP_Sitemaps_PHPUnit_WP_Functional_TestCase
{
	public function setUp()
	{
		parent::setUp();

		self::reset_posts_terms();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	protected static function set_plugin_default_options()
	{
		self::update_option(BWP_GXS_GENERATOR, array(
			'enable_cache' => '',
			'enable_image_sitemap'   => 'yes',
			'input_image_post_types' => 'post'
		));
	}

	public function test_should_add_image_tag_to_sitemap_item_if_any()
	{
		$post_without_image = $this->create_post();

		$post = $this->create_post();
		$this->create_featured_image_for_post($post);

		self::commit_transaction();

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post'));

		$image = $crawler->filter('default|urlset default|url image|image');

		$this->assertCount(1, $image, 'there are two posts but only one has image');
		$this->assert_correct_image_tag($image, $post);
	}

	public function test_should_add_image_tag_google_news_sitemap_item()
	{
		$news_post = $this->create_post();
		$this->create_featured_image_for_post($news_post);

		$taxonomy = 'category';
		$terms = $this->create_terms($taxonomy, 1);
		$this->factory->term->add_post_terms($news_post->ID, $terms, $taxonomy);

		self::set_options(BWP_GXS_EXTENSIONS, array(
			'enable_news_sitemap'    => 'yes',
			'select_news_post_type'  => 'post',
			'select_news_taxonomy'   => 'category',
			'select_news_cat_action' => 'inc',
			'select_news_cats'       => '1'
		));

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post_google_news'));

		$image = $crawler->filter('default|urlset default|url image|image');

		$this->assertCount(1, $image);
		$this->assert_correct_image_tag($image, $news_post);
	}

	protected function create_featured_image_for_post(WP_Post $post)
	{
		$original_file = dirname(__FILE__) . '/data/images/bwp.png';
		$filename      = basename($original_file);
		$filetype      = wp_check_filetype($filename, null );

		$upload_dir = wp_upload_dir();
		if(wp_mkdir_p($upload_dir['path'])) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}

		file_put_contents($file, file_get_contents($original_file));

		$featured_image_id = $this->factory->attachment->create_object(
			$file, $post->ID, array(
				'post_title'     => 'BWP',
				'post_excerpt'   => 'featured image',
				'post_mime_type' => $filetype['type']
			)
		);

		$meta_data = wp_generate_attachment_metadata($featured_image_id, $file);

		wp_update_attachment_metadata($featured_image_id, $meta_data);

		set_post_thumbnail($post, $featured_image_id);
	}

	protected function assert_correct_image_tag(Crawler $image, WP_Post $post)
	{
		$this->assertEquals(wp_get_attachment_thumb_url(get_post_thumbnail_id($post->ID)), $image->children()->first()->text(), 'image location should be correct');
		$this->assertEquals('BWP', $image->children()->eq(1)->text(), 'image title should be correct');
		$this->assertEquals('featured image', $image->children()->eq(2)->text(), 'image caption should be correct');
	}
}
