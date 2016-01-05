<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Handler_Ajax_ExternalPageHandler extends BWP_Sitemaps_Handler_AjaxHandler
{
	protected $provider;

	protected $frequencies;

	protected $priorities;

	protected $timezone;

	protected $date_format = 'Y-m-d H:i';

	protected $domain;

	public function __construct(
		BWP_Sitemaps_Provider_ExternalPage $provider,
		array $frequencies,
		array $priorities,
		DateTimeZone $timezone
	) {
		$this->provider = $provider;

		$this->frequencies = $frequencies;
		$this->frequencies[] = 'auto';

		$this->priorities  = $priorities;

		$this->timezone = $timezone;

		$this->bridge = $provider->get_bridge();
		$this->domain = $provider->get_domain();
	}

	/**
	 * Save an external page, be it a new or existing one
	 *
	 * Response should contain ESCAPED contents.
	 */
	public function save_external_page_action()
	{
		$this->bridge->check_ajax_referer('bwp_gxs_manage_external_page');

		// 'frequency' and 'priority' are not technically required, but if a
		// page is submitted via the form, they are always set anyway
		$required = array('url', 'frequency', 'priority');
		$values   = array();

		// return error if any required field is missing
		foreach ($required as $required_field) {
			if (! $values[$required_field] = BWP_Framework_Util::get_request_var($required_field)) {
				$this->response_with(array(
					'error'   => 1,
					'message' => __('Please provide all required fields.', $this->domain)
				));
			}
		}

		extract($values);

		// must be a valid url and starts with the Site Address (either scheme)
		if (! BWP_Sitemaps_Validator_Url::validate($url)
			|| (strpos($url, $this->bridge->home_url()) !== 0
				&& strpos($url, $this->bridge->home_url('', 'https')) !== 0)
		) {
			$this->response_with(array(
				'error'   => 1,
				'message' => sprintf(
					__('Please provide an absolute URL under your domain, '
					. 'for example: %s.', $this->domain),
					home_url('a-page/')
				)
			));
		}

		// frequency and priority must be valid if provided
		if (!in_array($frequency, $this->frequencies)
			|| !in_array($priority, $this->priorities)
		) {
			$this->response_with(array(
				'error'   => 1,
				'message' => __('Invalid frequency or priority.', $this->domain)
			));
		}

		$last_modified = isset($last_modified) ? $last_modified : null;

		// last_modified must be valid if provided
		if (isset($last_modified)) {
			try {
				// expect last modified date time in local timezone, but we will
				// save it in UTC timezone
				$last_modified = new DateTime($last_modified, $this->timezone);
				$last_modified->setTimezone(new DateTimeZone('UTC'));
			} catch (Exception $e) {
				$this->response_with(array(
					'error'   => 1,
					'message' => __('Please provide a valid last modified date time.', $this->domain)
				));
			}
		}

		$data = array(
			'frequency'     => $frequency,
			'priority'      => $priority,
			'last_modified' => $last_modified ? $last_modified->format($this->date_format) : null
		);

		if ($result = $this->save($url, $data)) {
			$data['url'] = $this->bridge->esc_html($url);

			// always display in local timezone
			$data['last_modified'] = $last_modified
				? $last_modified
					->setTimezone($this->timezone)
					->format($this->date_format)
				: null;

			$this->response_with(array(
				'data'    => $data,
				'updated' => $result === 2,
				'message' => __('External page has been successfully added/updated.', $this->domain)
			));
		} else {
			$this->response_with(array(
				'error'   => 1,
				'message' => __('Could not add/update page, please try again.', $this->domain)
			));
		}
	}

	/**
	 * Remove page action
	 */
	public function remove_external_page_action()
	{
		$this->bridge->check_ajax_referer('bwp_gxs_manage_external_page');

		if ($url = BWP_Framework_Util::get_request_var('url')) {
			// no matching page
			if (! ($page = $this->provider->get_page($url))) {
				$this->fail();
			}

			$this->remove($url);
			$this->succeed();
		}

		$this->fail();
	}

	/**
	 * Get pages action
	 *
	 * Response should contain ESCAPED contents.
	 */
	public function get_pages_action()
	{
		$items = array();
		$pages = $this->provider->get_pages_for_display();

		foreach ($pages as $url => $page) {
			$items[] = array(
				'url'           => $this->bridge->esc_html($url),
				'frequency'     => $this->bridge->esc_html($page['frequency']),
				'priority'      => $this->bridge->esc_html($page['priority']),
				'last_modified' => $page['last_modified']
			);
		}

		$this->response_with($items);
	}

	/**
	 * @param string $url
	 * @param array $data
	 * @return bool|int 1 if new, 2 if updated and false if failed
	 */
	protected function save($url, $data)
	{
		$pages = $this->provider->get_pages();

		// updating a page, but the data is the same, return 2 immediately
		if (isset($pages[$url]) && $data == $pages[$url]) {
			return 2;
		}

		$return = isset($pages[$url]) ? 2 : 1;
		$pages[$url] = $data;

		if ($this->bridge->update_option($this->provider->get_storage_key(), $pages)) {
			return $return;
		}

		return false;
	}

	/**
	 * @param string $url
	 */
	protected function remove($url)
	{
		$pages = $this->provider->get_pages();

		if (isset($pages[$url])) {
			unset($pages[$url]);
			$this->bridge->update_option($this->provider->get_storage_key(), $pages);
		}
	}
}
