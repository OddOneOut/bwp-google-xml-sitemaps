<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_Handler_Ajax_NewsTermsHandler extends BWP_Sitemaps_Handler_AjaxHandler
{
	protected $provider;

	protected $news_terms;

	protected $news_genres;

	public function __construct(
		BWP_Sitemaps_Provider_Taxonomy $provider,
		array $news_terms,
		array $news_genres
	) {
		$this->provider = $provider;

		$this->news_terms  = $news_terms;
		$this->news_genres = $news_genres;

		$this->bridge = $provider->get_bridge();
		$this->domain = $provider->get_domain();
	}

	/**
	 * Get term genres action
	 *
	 * Response should contain ESCAPED contents.
	 */
	public function get_term_genres_action()
	{
		$items = array();

		// @link http://support.google.com/news/publisher/bin/answer.py?hl=en&answer=93992
		$genres = array(
			'PressRelease',
			'Satire',
			'Blog',
			'OpEd',
			'Opinion',
			'UserGenerated'
		);

		if ($taxonomy = BWP_Framework_Util::get_request_var('news_taxonomy')) {
			$terms = $this->provider->get_all_terms($taxonomy);

			foreach ($terms as $term) {
				$item = array(
					'id'       => (int) $term->term_id,
					'name'     => esc_html($term->name),
					'slug'     => esc_attr($term->slug),
					'selected' => in_array($term->term_id, $this->news_terms),
					'genres'   => array()
				);

				$item_genres = array();

				foreach ($genres as $genre) {
					$item_genre_selected = false;
					if (isset($this->news_genres['term_' . $term->term_id])
						&& stripos($this->news_genres['term_' . $term->term_id], $genre) !== false
					) {
						$item_genre_selected = true;
					}

					$item_genres[] = array(
						'name'     => $genre,
						'selected' => $item_genre_selected
					);
				}

				$item['genres'] = $item_genres;

				$items[] = $item;
			}
		}

		$this->response_with($items);
	}
}
