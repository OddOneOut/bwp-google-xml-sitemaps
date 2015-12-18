<?php

/**
 * @covers BWP_Sitemaps_Sitemap_Tag
 */
class BWP_Sitemaps_Sitemap_Tag_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::create_single_tag
	 * @covers BWP_Sitemaps_Sitemap_Tag::create_compound_tag
	 * @dataProvider get_invalid_tags
	 */
	public function test_should_throw_invalid_argument_exception_if_tag_name_is_not_string($tag_name, $is_compound)
	{
		$this->setExpectedException('InvalidArgumentException', 'tag name must be string');

		if ($is_compound) {
			$tag = BWP_Sitemaps_Sitemap_Tag::create_compound_tag($tag_name);
		} else {
			$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag($tag_name);
		}
	}

	public function get_invalid_tags()
	{
		return array(
			array(false, false),
			array(array(), false),
			array(false, true),
			array(array(), true),
		);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::create_single_tag
	 */
	public function test_create_single_tag_correctly()
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag');

		$this->assertInstanceOf('BWP_Sitemaps_Sitemap_Tag_SingleTag', $tag);
		$this->assertEquals('tag', $tag->get_name());
		$this->assertFalse($tag->is_compound());

		$this->assertEquals("\t" . '<%1$s>%2$s</%1$s>' . "\n", $tag->get_template());

		return $tag;
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::add_sanitizer
	 */
	public function test_add_sanitizer()
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag');

		$tag->add_sanitizer(new BWP_Sitemaps_Sitemap_Sanitizer_Xml_TextSanitizer());
		$tag->add_sanitizer(new BWP_Sitemaps_Sitemap_Sanitizer_Xml_TextSanitizer());
		$tag->add_sanitizer(new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer());

		$this->assertCount(2, $tag->get_sanitizers(), 'should not add duplicate sanitizer');
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::create_single_tag
	 * @depends test_create_single_tag_correctly
	 */
	public function test_create_single_tag_should_always_add_text_sanitizer(BWP_Sitemaps_Sitemap_Tag_SingleTag $tag)
	{
		$sanitizers = $tag->get_sanitizers();

		$this->assertCount(1, $sanitizers);
		$this->assertInstanceOf('BWP_Sitemaps_Sitemap_Sanitizer_Xml_TextSanitizer', $sanitizers[0]);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::create_single_tag
	 * @dataProvider get_single_tag_params
	 */
	public function test_create_single_tag_should_add_sanitizer_correctly($value, $sanitizers, $text_sanitizer_position)
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag', $value, $sanitizers);

		$added_sanitizers = $tag->get_sanitizers();

		$this->assertInstanceOf(
			'BWP_Sitemaps_Sitemap_Sanitizer_Xml_TextSanitizer',
			$added_sanitizers[$text_sanitizer_position]
		);
	}

	public function get_single_tag_params()
	{
		return array(
			'text sanitizer should be added last by default #1' => array(
				'value', new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer(), 1
			),

			'text sanitizer should be added last by default #2' => array('value', array(
				new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer(),
				new BWP_Sitemaps_Sitemap_Sanitizer_Xml_FrequencySanitizer()
			), 2),

			'text sanitizer can be moved by adding it explicitly' => array('value', array(
				new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer(),
				new BWP_Sitemaps_Sitemap_Sanitizer_Xml_TextSanitizer(),
				new BWP_Sitemaps_Sitemap_Sanitizer_Xml_FrequencySanitizer()
			), 1)
		);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::set_value
	 * @dataProvider get_invalid_tag_values
	 */
	public function test_set_value_should_throw_invalid_argument_exception_if_value_is_not_string($value)
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag');

		$this->setExpectedException('InvalidArgumentException', 'tag value must be string or numeric');

		$tag->set_value($value);
	}

	public function get_invalid_tag_values()
	{
		return array(
			array(false),
			array(null),
			array(array())
		);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::set_value
	 * @dataProvider get_values_that_might_need_sanitization
	 */
	public function test_set_value_should_sanitize_provided_value(
		$value,
		$sanitized_value,
		BWP_Sitemaps_Sitemap_Sanitizer $sanitizer = null
	) {
		$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag');

		if ($sanitizer) {
			$tag->add_sanitizer($sanitizer);
		}

		$tag->set_value($value);

		$this->assertSame($sanitized_value, $tag->get_value(), 'sanitized value must be valid and is of type string');
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::create_single_tag
	 * @dataProvider get_values_that_might_need_sanitization
	 */
	public function test_create_single_tag_with_value_should_sanitize_provided_value_if_needed(
		$value,
		$sanitized_value,
		BWP_Sitemaps_Sitemap_Sanitizer $sanitizer = null
	) {
		$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag('tag', $value, $sanitizer);

		$this->assertSame($sanitized_value, $tag->get_value(), 'sanitized value must be valid and is of type string');
	}

	public function get_values_that_might_need_sanitization()
	{
		return array(
			array('string',            'string'),
			array('<tag>string</tag>', 'string'),

			array(0.1234,   '0.1', new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer()),
			array('0.1234', '0.1', new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer()),
			array(-0.1234,  '0.5', new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer()),
			array(1.234,    '0.5', new BWP_Sitemaps_Sitemap_Sanitizer_Xml_PrioritySanitizer()),

			array('hourly', 'daily', new BWP_Sitemaps_Sitemap_Sanitizer_Xml_FrequencySanitizer()),
			array('random', 'daily', new BWP_Sitemaps_Sitemap_Sanitizer_Xml_FrequencySanitizer()),
			array('hourly', 'hourly', new BWP_Sitemaps_Sitemap_Sanitizer_Xml_FrequencySanitizer(array(
				'frequencies' => array('hourly')
			))),
		);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::create_compound_tag
	 */
	public function test_create_compound_tag_correctly()
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('tag');

		$this->assertInstanceOf('BWP_Sitemaps_Sitemap_Tag_CompoundTag', $tag);
		$this->assertEquals('tag', $tag->get_name());
		$this->assertTrue($tag->is_compound());

		$this->assertEquals(array(), $tag->get_tags());
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::get_xml
	 * @dataProvider get_xml_cases
	 */
	public function test_get_xml_for_single_tag($name, $value, $xml)
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag($name, $value);

		$this->assertEquals($xml, $tag->get_xml());
	}

	public function get_xml_cases()
	{
		return array(
			array('tag', false, ''),
			array('tag', 'value', "\t" . '<tag>value</tag>' . "\n"),
			array('ns:tag', 'value', "\t" . '<ns:tag>value</ns:tag>' . "\n")
		);
	}

	/**
	 * @covers BWP_Sitemaps_Sitemap_Tag::get_xml
	 * @covers BWP_Sitemaps_Sitemap_Tag::get_indentation
	 * @covers BWP_Sitemaps_Sitemap_Tag::get_template
	 * @dataProvider get_xml_with_parent_cases
	 */
	public function test_get_xml_for_single_tag_with_parents($name, BWP_Sitemaps_Sitemap_Tag_CompoundTag $parent, $xml)
	{
		$tag = BWP_Sitemaps_Sitemap_Tag::create_single_tag($name, 'value');
		$parent->add_tag($tag);

		$this->assertEquals($xml, $tag->get_xml(), 'indentation should be prepended correctly');
	}

	public function get_xml_with_parent_cases()
	{
		$parent     = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('tag');
		$top_parent = BWP_Sitemaps_Sitemap_Tag::create_compound_tag('tag');

		$top_parent->add_tag($parent);

		return array(
			array('tag', BWP_Sitemaps_Sitemap_Tag::create_compound_tag('tag'), "\t\t" . '<tag>value</tag>' . "\n"),
			array('tag', $parent, "\t\t\t" . '<tag>value</tag>' . "\n")
		);
	}
}
