<?php

use \Mockery as Mockery;

/**
 * @covers BWP_Sitemaps_Provider_Post
 */
class BWP_Sitemaps_Provider_Post_Test extends BWP_Sitemaps_PHPUnit_Provider_Unit_TestCase
{
	protected $excluder;

	protected $provider;

	protected function setUp()
	{
		parent::setUp();

		$this->excluder = Mockery::mock('BWP_Sitemaps_Excluder');

		$this->provider = new BWP_Sitemaps_Provider_Post($this->plugin, $this->excluder);
	}

	protected function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @covers BWP_Sitemaps_Provider_Post::get_post_types
	 */
	public function test_get_post_types()
	{
		$attachment = new stdClass();
		$attachment->name = 'attachment';

		$post = new stdClass();
		$post->name = 'post';

		$post_types = array(
			$attachment, $post
		);

		$this->bridge
			->shouldReceive('get_post_types')
			->with(array('public' => true), 'objects')
			->andReturn($post_types)
			->byDefault();

		$this->assertEquals(array($post), $this->provider->get_post_types());
	}

	/**
	 * @covers BWP_Sitemaps_Provider_Post::get_public_posts
	 * @dataProvider get_test_get_public_posts_arguments
	 */
	public function test_get_public_posts($post_type, array $ids = array(), array $excluded_ids = array(), $limit = 200)
	{
		$post1 = new stdClass();
		$post2 = new stdClass();

		$posts = array($post1, $post2);

		$this->bridge
			->shouldReceive('get_posts')
			->with(array(
				'post_type'      => $post_type,
				'include'        => $ids,
				'exclude'        => $excluded_ids,
				'posts_per_page' => $limit
			))
			->andReturn($posts)
			->byDefault();

		$this->assertEquals($posts, $this->provider->get_public_posts($post_type, $ids, $excluded_ids, $limit));
	}

	public function get_test_get_public_posts_arguments()
	{
		return array(
			array('post'),
			array('page')
		);
	}

	/**
	 * @covers BWP_Sitemaps_Provider_Post::get_public_posts
	 */
	public function test_get_public_posts_should_throw_exception_when_both_ids_and_excluded_ids_are_provided()
	{
		$this->setExpectedException('DomainException', 'only post ids or excluded post ids can be provided, not both');

		$this->provider->get_public_posts('post', array(1,2), array(3,4));
	}

	/**
	 * @covers BWP_Sitemaps_Provider_Post::get_public_posts_by_title
	 */
	public function test_get_public_posts_by_title()
	{
		$this->excluder
			->shouldReceive('get_excluded_items')
			->andReturn(array(1,2))
			->byDefault();

		$post1 = new stdClass();
		$post2 = new stdClass();

		$posts = array($post1, $post2);

		$this->bridge
			->shouldReceive('get_posts')
			->with(array(
				'post_type'           => 'post',
				'bwp_post_title_like' => 'title',
				'exclude'             => array(1,2),
				'suppress_filters'    => false
			))
			->andReturn($posts)
			->byDefault();

		$this->assertEquals($posts, $this->provider->get_public_posts_by_title('post', 'title'));
	}
}
