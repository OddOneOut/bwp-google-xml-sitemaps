<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_Sitemap_Tag
{
	/**
	 * Namespace of the tag
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Name of the tag
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Value of the tag
	 *
	 * @var string
	 */
	protected $value;

	/**
	 * Parent of this tag
	 *
	 * @var BWP_Sitemaps_Sitemap_Tag_CompoundTag
	 */
	protected $parent;

	/**
	 * Whether this is a compound tag
	 *
	 * @var bool
	 */
	protected $compound;

	/**
	 * Sanitizers to apply on this tag's value
	 *
	 * This is mostly relevant with a single tag because a compound tag does
	 * not actually have any value.
	 *
	 * @var BWP_Sitemaps_Sitemap_Sanitizer[]
	 */
	protected $sanitizers = array();

	/**
	 * Template to be used when rendering this tag
	 *
	 * @var string
	 */
	protected $template;

	/**
	 * Level of indentation used when rendering this tag
	 *
	 * @var int
	 */
	protected $indent_level = 0;

	protected function __construct($name, $is_compound = false)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('tag name must be string');
		}

		if (strpos($name, ':') !== false) {
			$names = explode(':', $name);

			$this->namespace = $names[0];
			$this->name      = $names[1];
		} else {
			$this->name = $name;
		}

		$this->compound = $is_compound;

		$this->template = '<%1$s>%2$s</%1$s>' . "\n";
	}

	/**
	 * Create a single tag
	 *
	 * @param string $name
	 * @param string $value default to null
	 * @param mixed BWP_Sitemaps_Sitemap_Sanitizer|array BWP_Sitemaps_Sitemap_Sanitizer[]
	 */
	public static function create_single_tag($name, $value = null, $sanitizers = null)
	{
		$tag = new BWP_Sitemaps_Sitemap_Tag_SingleTag($name);

		// add sanitizer if any
		if ($sanitizers) {
			// convert to array if needed
			$sanitizers = is_array($sanitizers) ? $sanitizers : array($sanitizers);

			foreach ($sanitizers as $sanitizer) {
				$tag->add_sanitizer($sanitizer);
			}
		}

		// make sure the text sanitizer is always registered, and is set as
		// the last sanitizer by default
		$tag->add_sanitizer(new BWP_Sitemaps_Sitemap_Sanitizer_Xml_TextSanitizer());

		if ($value) {
			$tag->set_value($value);
		}

		return $tag;
	}

	/**
	 * Create a compound tag
	 *
	 * @param string $name
	 */
	public static function create_compound_tag($name)
	{
		return new BWP_Sitemaps_Sitemap_Tag_CompoundTag($name);
	}

	/**
	 * Set value for this tag
	 *
	 * @param string $value
	 */
	public function set_value($value)
	{
		if (!is_string($value) && !is_numeric($value)) {
			throw new InvalidArgumentException('tag value must be string or numeric');
		}

		// sanitize the value in the same order by which they were added
		foreach ($this->sanitizers as $sanitizer) {
			$value = $sanitizer->sanitize($value);
		}

		// do not set the value if it is invalid after sanitization
		if (! $value) {
			return;
		}

		$this->value = $value;
	}

	/**
	 * Set a parent for this tag
	 *
	 * @param BWP_Sitemaps_Sitemap_Tag_CompoundTag $tag
	 */
	public function set_parent(BWP_Sitemaps_Sitemap_Tag_CompoundTag $tag)
	{
		$this->parent = $tag;
	}

	/**
	 * Add a sanitizer to apply upon this tag's value when set (if not existed)
	 *
	 * @param BWP_Sitemaps_Sitemap_Sanitizer $sanitizer
	 */
	public function add_sanitizer(BWP_Sitemaps_Sitemap_Sanitizer $sanitizer)
	{
		if (! in_array($sanitizer, $this->sanitizers)) {
			$this->sanitizers[] = $sanitizer;
		}
	}

	/**
	 * Set template for this tag
	 *
	 * @param string $template
	 */
	public function set_template($template)
	{
		$this->template = $template;
	}

	/**
	 * Check whether this tag is a compound tag
	 *
	 * @return bool
	 */
	public function is_compound()
	{
		return (bool) $this->compound;
	}

	/**
	 * Get tag name, with namespace
	 *
	 * @return string
	 */
	public function get_name()
	{
		if ($this->namespace) {
			return $this->namespace . ':' . $this->name;
		}

		return $this->name;
	}

	/**
	 * Get tag value
	 *
	 * @return string
	 */
	public function get_value()
	{
		return $this->value;
	}

	/**
	 * Get registered sanitizers
	 */
	public function get_sanitizers()
	{
		return $this->sanitizers;
	}

	/**
	 * Get tag template, with indentation prepended
	 *
	 * @return string
	 */
	public function get_template()
	{
		return $this->get_indentation() . $this->template;
	}

	/**
	 * Get indentation for this tag
	 *
	 * Each parent corresponds to one level of indentation
	 *
	 * @return string
	 */
	public function get_indentation()
	{
		// there's always a root tag so by default we indent by one level
		$indentation = "\t";

		if ($this->parent) {
			$indentation = "\t" . $this->parent->get_indentation();
		}

		return $indentation;
	}

	/**
	 * Get XML representation of this tag
	 *
	 * @return string
	 */
	public function get_xml()
	{
		// no value, no xml
		if (empty($this->value)) {
			return '';
		}

		return sprintf($this->get_template(), $this->get_name(), $this->get_value());
	}
}
