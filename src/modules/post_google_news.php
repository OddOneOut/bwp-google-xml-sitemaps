<?php

/**
 * Copyright (c) 2015 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_POST_GOOGLE_NEWS extends BWP_GXS_MODULE
{
	public function __construct()
	{
		$this->type = 'news';
		$this->perma_struct = get_option('permalink_structure');
	}

	/**
	 * Process the posts if Multi-term mode is enabled
	 */
	private static function process_posts($posts, $news_terms, $news_term_action)
	{
		// this $post array surely contains duplicate posts, fortunately they
		// are already sorted by post_date_gmt and ID, so we can group them
		// here by IDs
		$ord_num = 0;

		$excluded_terms = 'inc' == $news_term_action ? array() : explode(',', $news_terms);

		$processed_posts = array();

		for ($i = 0; $i < sizeof($posts); $i++)
		{
			$post = $posts[$i];

			if ($ord_num == $post->ID)
			{
				$cur_position = sizeof($processed_posts) - 1;

				// nothing to do, continue
				if ($cur_position < 0)
					continue;

				$current_post = $processed_posts[$cur_position];

				// not correct post, continue
				if ($current_post->ID != $ord_num)
					continue;

				// users choose to exclude terms, and this $post is assigned to
				// one of those excluded terms
				if (in_array($post->term_id, $excluded_terms)
					|| in_array($current_post->terms[0], $excluded_terms)
				) {
					array_pop($processed_posts);
				}
				else
				{
					if (!in_array($post->term_id, $current_post->terms))
						$current_post->terms[] = $post->term_id;

					if (!in_array($post->name, $current_post->term_names))
						$current_post->term_names[] = $post->name;
				}

			}
			else
			{
				$post->terms      = array($post->term_id);
				$post->term_names = array($post->name);

				$processed_posts[] = $post;

				$ord_num = $post->ID;
			}
		}

		return $processed_posts;
	}

	/**
	 * Google news articles should be published in the last two days
	 *
	 * @link http://www.google.com/support/news_pub/bin/answer.py?answer=74288
	 */
	private static function news_time()
	{
		$news_post_date = new DateTime('-2 days', new DateTimeZone('UTC'));

		return $news_post_date->format('Y-m-d H:i:s');
	}

	/**
	 * Map keyword in site language to its English counterpart
	 */
	private static function map_keyword($keyword)
	{
		$keywords_map = apply_filters('bwp_gxs_news_keyword_map', array(
			// Use keyword as the key, example:
			// '電視台' => 'television',
			// '名人'=> 'celebrities'
		));

		return !empty($keywords_map[$keyword]) ? trim($keywords_map[$keyword]) : $keyword;
	}

	protected function generate_data()
	{
		global $wpdb, $post, $bwp_gxs;

		$lang = $bwp_gxs->options['select_news_lang'];

		// @since 1.4.0 support custom post type for google news sitemap
		$news_post_type = $bwp_gxs->options['select_news_post_type'];
		$news_taxonomy  = $bwp_gxs->options['select_news_taxonomy'];

		$news_terms       = $bwp_gxs->options['select_news_cats'];
		$news_term_action = $bwp_gxs->options['select_news_cat_action'];
		$news_genres      = $bwp_gxs->options['input_news_genres'];

		if ($news_term_action == 'inc' && empty($news_terms))
		{
			// if we have to look for news post with certain terms, but news
			// term list is empty, nothing to do. This should stop the SQL
			// cycling btw.
			return false;
		}

		$term_query = '';
		if ($news_terms)
		{
			$term_query = ' AND t.term_id NOT IN (' . $news_terms . ')';
			$term_query = $news_term_action == 'inc'
				? str_replace('NOT IN', 'IN', $term_query) : $term_query;
			$term_query = $news_term_action != 'inc'
				&& $bwp_gxs->options['enable_news_multicat'] == 'yes'
				? '' : $term_query;
		}

		$group_by = empty($bwp_gxs->options['enable_news_multicat'])
			? ' GROUP BY p.ID' : '';

		$latest_post_query = '
			SELECT *
			FROM ' . $wpdb->term_relationships . ' tr
			INNER JOIN ' . $wpdb->posts . ' p
				ON tr.object_id = p.ID' . "
				AND p.post_type = %s
				AND p.post_status = 'publish'
				AND p.post_password = ''" . '
				AND p.post_date_gmt > %s
			INNER JOIN ' . $wpdb->term_taxonomy . ' tt
				ON tr.term_taxonomy_id = tt.term_taxonomy_id' . "
				AND tt.taxonomy = %s" . '
			INNER JOIN ' . $wpdb->terms . ' t
				ON tt.term_id = t.term_id
			WHERE 1 = 1 '
				. $term_query
				. $group_by . '
			ORDER BY p.post_date_gmt, p.ID DESC
			LIMIT 0, ' . $this->limit;

		$latest_posts = $wpdb->get_results(
			$wpdb->prepare(
				$latest_post_query,
				$news_post_type,
				self::news_time(),
				$news_taxonomy
			)
		);

		if ('yes' == $bwp_gxs->options['enable_news_multicat'])
		{
			// if Multi-term mode is enabled we will need to process fetched posts
			$latest_posts = self::process_posts($latest_posts, $news_terms, $news_term_action);
		}

		if (!isset($latest_posts) || 0 == sizeof($latest_posts))
			return false;

		$using_permalinks = $this->using_permalinks();

		$genres_cache = array();

		for ($i = 0; $i < sizeof($latest_posts); $i++)
		{
			$post = $latest_posts[$i];

			$data = array();

			// @since 1.1.0 - get permalink independently, as we don't need
			// caching or some complicated stuff
			if ($using_permalinks && empty($post->post_name))
				$data['location'] = '';
			else
				$data['location'] = $this->get_permalink();

			$data['language'] = $lang;

			if (isset($post->terms))
			{
				$genres_cache_key = md5(implode('|', $post->terms));

				if (!isset($genres_cache[$genres_cache_key])
					|| !is_array($genres_cache[$genres_cache_key])
				) {
					$genres_cache[$genres_cache_key] = array();

					foreach ($post->terms as $term_id)
					{
						$cur_genres = !empty($news_genres['term_' . $term_id])
							? explode(', ', $news_genres['term_' . $term_id])
							: '';

						if (is_array($cur_genres))
						{
							foreach ($cur_genres as $cur_genre)
								if (!in_array($cur_genre, $genres_cache[$genres_cache_key]))
									$genres_cache[$genres_cache_key][] = $cur_genre;
						}
					}
				}

				$data['genres'] = implode(', ', $genres_cache[$genres_cache_key]);
			}
			else
			{
				$data['genres'] = !empty($news_genres['term_' . $post->term_id])
					? $news_genres['term_' . $post->term_id]
					: '';
			}

			$data['pub_date'] = $bwp_gxs->options['enable_gmt']
				? $this->format_lastmod(strtotime($post->post_date_gmt), false)
				: $this->format_lastmod(strtotime($post->post_date));

			$data['title']    = $post->post_title;
			$data['keywords'] = '';

			// stop here if we do not need to add keywords
			if ('yes' != $bwp_gxs->options['enable_news_keywords'])
			{
				$this->data[] = $data;
				continue;
			}

			$keywords       = array();
			$keyword_source = $bwp_gxs->options['select_news_keyword_source'];

			// if we take keywords from the selected news taxonomy, or the
			// selected keyword source is the same as the selected news
			// taxonomy, they have already been fetched
			if (empty($keyword_source) || $keyword_source == $news_taxonomy)
			{
				// we have multiple terms to use as keywords
				if (isset($post->term_names))
				{
					foreach ($post->term_names as $term_name)
						$keywords[] = self::map_keyword($term_name);
				}
				else
				{
					// only one term, so only one keyword
					$keywords[] = self::map_keyword($post->name);
				}
			}
			else
			{
				$terms = get_the_terms($post->ID, $keyword_source);

				if (is_array($terms))
				{
					foreach ($terms as $term)
						$keywords[] = self::map_keyword($term->name);
				}
			}


			$data['keywords'] = implode(', ', $keywords);

			$this->data[] = $data;
		}

		// @since 1.4.0 we don't use SQL cyclying for google news sitemap
		return false;
	}
}
