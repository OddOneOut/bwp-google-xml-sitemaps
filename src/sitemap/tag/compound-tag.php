<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Sitemap_Tag_CompoundTag extends BWP_Sitemaps_Sitemap_Tag
{
	/**
	 * Child tags
	 *
	 * @var BWP_Sitemaps_Sitemap_Tag[]
	 */
	protected $tags = array();

	protected function __construct($name)
	{
		parent::__construct($name, true);

		$this->template =
			'<%1$s>' . "\n"
				. '%2$s'
			. '</%1$s>' . "\n";
	}

	/**
	 * Add child tag
	 *
	 * Both single tags and compound tags can be added, to allow a nested structure.
	 *
	 * @param BWP_Sitemaps_Sitemap_Tag $tag the child tag to add
	 * @throws LogicException if child tag already has a parent
	 * @return $this
	 */
	public function add_tag(BWP_Sitemaps_Sitemap_Tag $tag)
	{
		if ($tag->parent instanceof BWP_Sitemaps_Sitemap_Tag) {
			throw new LogicException('child tag already has a parent');
		}

		$tag->set_parent($this);

		// duplicate tag detected
		if (in_array($tag, $this->tags)) {
			return $this;
		}

		$this->tags[] = $tag;

		return $this;
	}

	/**
	 * Add multiple tags at once
	 *
	 * @param array BWP_Sitemaps_Sitemap_Tag[] $tags
	 * @return $this
	 */
	public function add_tags(array $tags)
	{
		foreach ($tags as $tag) {
			$this->add_tag($tag);
		}

		return $this;
	}

	/**
	 * Get all child tags
	 *
	 * @return BWP_Sitemaps_Sitemap_Tag[]
	 */
	public function get_tags()
	{
		return $this->tags;
	}

	/**
	 * Get template, with indentation properly prepended
	 *
	 * Indentation should be prepended to both the opening tag and the closing tag.
	 *
	 * @return string
	 */
	public function get_template()
	{
		$indentation = $this->get_indentation();

		return str_replace('<', $indentation . '<', $this->template);
	}

	public function get_xml()
	{
		// no child tags, no xml
		if (count($this->tags) == 0) {
			return '';
		}

		$value = '';

		foreach ($this->tags as $tag) {
			$value .= $tag->get_xml();
		}

		return sprintf($this->get_template(), $this->get_name(), $value);
	}
}
