<?php

/**
 * @covers BWP_Sitemaps_Sitemap_Tag_CompoundTag
 */
class BWP_Sitemaps_Sitemap_Tag_CompoundTag_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag_CompoundTag::add_tag
	 */
	public function test_add_tag()
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('url');

		$this->assertEmpty($tag->get_tags());

		$compound_tag = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('image:image');
		$compound_tag->add_tag(BWP_Sitemaps_Sitemap_Tag::create_single_tag('image:loc', 'loc'));
		$compound_tag->add_tag(BWP_Sitemaps_Sitemap_Tag::create_single_tag('image:title', 'title'));
		$compound_tag->add_tag(BWP_Sitemaps_Sitemap_Tag::create_single_tag('image:caption', 'caption'));

		$tag->add_tag(BWP_Sitemaps_Sitemap_Tag::create_single_tag('loc', 'loc'));
		$tag->add_tag(BWP_Sitemaps_Sitemap_Tag::create_single_tag('lastmod', 'lastmod'));
		$tag->add_tag($compound_tag);

		$this->assertCount(3, $tag->get_tags());

		return $tag;
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag_CompoundTag::add_tag
	 */
	public function test_add_tag_should_throw_logic_exception_if_child_tag_already_has_a_parent()
	{
		$tag       = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('tag');
		$child_tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag');

		$this->setExpectedException('LogicException', 'child tag already has a parent');

		$tag->add_tag($child_tag);
		$tag->add_tag($child_tag);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag_CompoundTag::add_tag
	 */
	public function test_add_tag_should_not_add_duplicate_tag()
	{
		$tag                 = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('tag');
		$child_tag           = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag');
		$duplicate_child_tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag');

		$tag->add_tag($child_tag);
		$tag->add_tag($duplicate_child_tag);

		$this->assertCount(1, $tag->get_tags());
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag_CompoundTag::add_tag
	 */
	public function test_add_tag_should_return_self_to_support_chaining()
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('tag');

		$this->assertSame($tag, $tag->add_tag(BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag')));
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag_CompoundTag::add_tags
	 * @depends test_add_tag
	 */
	public function test_add_tags_correctly(BWP_Sitemaps_Sitemap_Tag_CompoundTag $tag)
	{
		$tag->add_tags(array(
			BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag4'),
			BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag5'),
			BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag6'),
		));

		$this->assertCount(6, $tag->get_tags());
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag_CompoundTag::add_tags
	 */
	public function test_add_tags_should_return_self_to_support_chaining()
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('tag');

		$this->assertSame($tag, $tag->add_tags(array(
			BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag')
		)));
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag_CompoundTag::get_xml
	 * @depends test_add_tag
	 */
	public function test_get_xml(BWP_Sitemaps_Sitemap_Tag_CompoundTag $tag)
	{
		$xml = <<<XML
	<url>
		<loc>loc</loc>
		<lastmod>lastmod</lastmod>
		<image:image>
			<image:loc>loc</image:loc>
			<image:title>title</image:title>
			<image:caption>caption</image:caption>
		</image:image>
	</url>

XML;

		$this->assertEquals($xml, $tag->get_xml(), 'the whole block should have an indentation of 1 level (1 tab)');
	}
}
