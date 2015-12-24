<?php

/**
 * @covers BWP_Sitemaps_Excluder
 */
class BWP_Sitemaps_Excluder_Test extends BWP_Sitemaps_PHPUnit_Provider_Unit_TestCase
{
	protected $excluder;

	protected function setUp()
	{
		parent::setUp();

		$this->excluder = new BWP_Sitemaps_Excluder(
			$this->bridge, $this->cache, 'cache_key', 'storage_key'
		);
	}

	protected function tearDown()
	{
	}

	/**
	 * @covers BWP_Sitemaps_Excluder::get_excluded_items
	 * @dataProvider get_invalid_excluded_items
	 */
	public function test_get_excluded_items_should_return_an_empty_array_when_no_excluded_items_are_found($excluded_items)
	{
		$this->cache
			->shouldReceive('get')
			->with('cache_key')
			->andReturn($excluded_items)
			->byDefault();

		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($excluded_items)
			->byDefault();

		$this->assertEquals(array(), $this->excluder->get_excluded_items());
	}

	public function get_invalid_excluded_items()
	{
		return array(
			array(false),
			array(null),
			array(array())
		);
	}

	/**
	 * @covers BWP_Sitemaps_Excluder::get_excluded_items
	 * @dataProvider get_excluded_items
	 */
	public function test_get_excluded_items_should_cache_and_return_all_excluded_items_if_no_group_is_specified(array $excluded_items)
	{
		$this->cache
			->shouldReceive('set')
			->with('cache_key', $excluded_items)
			->once();

		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($excluded_items)
			->byDefault();

		$this->assertEquals($excluded_items, $this->excluder->get_excluded_items());
	}

	/**
	 * @covers BWP_Sitemaps_Excluder::get_excluded_items
	 * @dataProvider get_excluded_items
	 */
	public function test_get_excluded_items_should_flatten_all_groups_when_needed(array $excluded_items)
	{
		$this->cache
			->shouldReceive('set')
			->byDefault();

		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($excluded_items)
			->byDefault();

		$this->assertEquals(array(1,2,3,4,5,6,7,8), $this->excluder->get_excluded_items(null, true));
	}

	/**
	 * @covers BWP_Sitemaps_Excluder::get_excluded_items
	 * @dataProvider get_excluded_items
	 */
	public function test_get_excluded_items_should_cache_all_excluded_items_and_return_excluded_items_for_specified_group_only(array $excluded_items)
	{
		$this->cache
			->shouldReceive('set')
			->with('cache_key', $excluded_items)
			->times(3);

		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($excluded_items)
			->byDefault();

		$this->assertEquals(array(1,2,3,4), $this->excluder->get_excluded_items('group1'));
		$this->assertEquals(array(5,6,7,8), $this->excluder->get_excluded_items('group2'));
		$this->assertEquals(array(), $this->excluder->get_excluded_items('group3'), 'empty group should return an emtpy array');
	}

	/**
	 * @covers BWP_Sitemaps_Excluder::get_excluded_items
	 * @dataProvider get_excluded_items
	 */
	public function test_get_excluded_items_should_not_cache_excluded_items_and_not_get_from_storage_if_cache_still_valid(array $excluded_items)
	{
		$this->cache
			->shouldReceive('get')
			->with('cache_key')
			->andReturn($excluded_items)
			->byDefault();

		$this->cache
			->shouldNotReceive('set')
			->with('cache_key', $excluded_items);

		$this->bridge
			->shouldNotReceive('get_option')
			->with('storage_key');

		$this->assertEquals($excluded_items, $this->excluder->get_excluded_items());
	}

	public function get_excluded_items()
	{
		$excluded_items = array(
			'group1' => '1,2,3,4',
			'group2' => '5,6,7,8',
			'group3' => '',
		);

		return array(
			array($excluded_items)
		);
	}

	/**
	 * @covers BWP_Sitemaps_Excluder::update_excluded_items
	 * @dataProvider get_test_update_excluded_items_cases
	 */
	public function test_update_excluded_items(array $excluded_items, $group, array $ids)
	{
		$this->bridge
			->shouldReceive('get_option')
			->with('storage_key')
			->andReturn($excluded_items)
			->byDefault();

		$this->cache
			->shouldReceive('set')
			->with('cache_key', $excluded_items)
			->ordered();

		$updated_excluded_items = $excluded_items;
		$updated_excluded_items[$group] = implode(',', $ids);

		$this->bridge
			->shouldReceive('update_option')
			->with('storage_key', $updated_excluded_items)
			->once();

		// should update the cache again
		$this->cache
			->shouldReceive('set')
			->with('cache_key', $updated_excluded_items)
			->ordered();

		$this->excluder->update_excluded_items($group, $ids);
	}

	public function get_test_update_excluded_items_cases()
	{
		$excluded_items = array(
			'group1' => '1,2,3,4',
			'group2' => '5,6,7,8',
			'group3' => '',
		);

		return array(
			'remove items'                           => array($excluded_items, 'group1', array(3,4)),
			'add items'                              => array($excluded_items, 'group1', array(1,2,3,4,5)),
			'remove and add items'                   => array($excluded_items, 'group1', array(1,2,4,5)),
			'remove and add items to an empty group' => array($excluded_items, 'group3', array(1,2,3))
		);
	}
}
