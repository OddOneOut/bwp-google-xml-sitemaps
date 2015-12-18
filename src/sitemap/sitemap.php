<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_Sitemap
{
	/**
	 * Sitemap item provider
	 *
	 * @var BWP_Sitemaps_Sitemap_Provider
	 */
	protected $provider;

	/**
	 * Sitemap sanitizer factory
	 *
	 * @var BWP_Sitemaps_Sitemap_Sanitizer_Factory
	 */
	protected $sanitizer_factory;

	/**
	 * Sitemap stylesheet
	 *
	 * @var string
	 */
	protected $stylesheet;

	/**
	 * Sitemap namespaces
	 *
	 * @var array
	 */
	protected $xml_headers = array(
		'xmlns:xsi'          => 'http://www.w3.org/2001/XMLSchema-instance',
		'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
		'xmlns'              => 'http://www.sitemaps.org/schemas/sitemap/0.9'
	);

	/**
	 * Sitemap XML root tag, e.g. 'urlset'
	 *
	 * @var string
	 */
	protected $xml_root_tag = 'urlset';

	/**
	 * Cached XML representation of this sitemap
	 *
	 * @var string
	 */
	protected $xml;

	/**
	 * Number of actual sitemap items in this sitemap
	 *
	 * @var int
	 */
	protected $item_count = 0;

	public function __construct(
		BWP_Sitemaps_Sitemap_Provider $provider,
		BWP_Sitemaps_Sitemap_Sanitizer_Factory $sanitizer_factory,
		$stylesheet = null
	) {
		$this->provider          = $provider;
		$this->sanitizer_factory = $sanitizer_factory;
		$this->stylesheet        = $stylesheet;

		$this->set_xml_headers();
		$this->set_properties();

		// build the output right away
		$this->get_xml();
	}

	/**
	 * Set additional and/or replace existing headers
	 */
	protected function set_xml_headers()
	{
		// should be implemented by child class
	}

	/**
	 * Set additional and/or replace existing properties of a sitemap
	 *
	 * This can be used to change the xml root tag for example.
	 */
	protected function set_properties()
	{
		// should be implemented by child class
	}

	/**
	 * Get xml body of a sitemap item
	 *
	 * @param array $item
	 * @return string
	 */
	abstract protected function get_xml_item_body(array $item);

	/**
	 * Get an xml representation of the sitemap
	 *
	 * @return string
	 */
	public function get_xml()
	{
		// use cached xml if available
		if (! is_null($this->xml)) {
			return $this->xml;
		}

		// no sitemap items, no xml
		if (! $items = $this->provider->get_items()) {
			return null;
		}

		$xml  = '';
		$xml .= $this->get_xml_header() . "\n\n";

		foreach ($items as $item) {
			$xml .= $this->get_xml_item_body($item) . "\n";
		}

		$this->item_count = count($items);
		$xml .= $this->get_xml_footer();

		$this->xml = $xml;

		return $xml;
	}

	/**
	 * Appends contents to the xml output, when applicable
	 *
	 * @param string $content
	 * @return $this
	 */
	public function append($content)
	{
		if (! $this->xml) {
			return $this;
		}

		$this->xml .= $content;

		return $this;
	}

	/**
	 * Whether this sitemap actually has items
	 *
	 * @return bool
	 */
	public function has_items()
	{
		return $this->item_count > 0;
	}

	/**
	 * @return int
	 */
	public function get_item_count()
	{
		return (int) $this->item_count;
	}

	private function get_xml_header()
	{
		$xml  = '<' . '?xml version="1.0" encoding="UTF-8"?'.'>' . "\n";
		$xml .= $this->stylesheet
			? '<?xml-stylesheet type="text/xsl" href="' . $this->stylesheet . '"?>' . "\n"
			: '';
		$xml .= "\n";

		$xml .= '<' . $this->xml_root_tag;

		foreach ($this->xml_headers as $header => $value) {
			$xml .= "\n\t" . $header . '="' . $value . '"';
		}

		$xml .= '>';

		return $xml;
	}

	private function get_xml_footer()
	{
		return '</' . $this->xml_root_tag . '>';
	}
}
