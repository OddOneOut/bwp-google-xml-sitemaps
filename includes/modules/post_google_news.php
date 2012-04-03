<?php
/**
 * Copyright (c) 2012 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 *
 */

class BWP_GXS_MODULE_POST_GOOGLE_NEWS extends BWP_GXS_MODULE {

	function __construct()
	{
		$this->type = 'news';
		$this->set_current_time();
		$this->perma_struct = get_option('permalink_structure');
		$this->build_data();
	}

	/**
	 * Process the posts if Multi-cat mode is enabled
	 */
	private static function process_posts($posts, $news_cats, $news_cat_action)
	{
		// This $post array surely contains duplicate posts (fortunately they are already sorted)
		// let's group 'em
		$ord_num = 0;
		$excluded_cats = ('inc' == $news_cat_action) ? array() : explode(',', $news_cats);
		$processed_posts = array();
		for ($i = 0; $i < sizeof($posts); $i++)
		{
			$post = $posts[$i];
			if ($ord_num == $post->ID)
			{
				$cur_position = sizeof($processed_posts) - 1;
				// Nothing to do, continue
				if ($cur_position < 0)
					continue;
				$current_post = $processed_posts[$cur_position];
				// Not correct post, continue
				if ($current_post->ID != $ord_num)
					continue;
				// Users choose to exclude cats, and this $post is assigned to one of those excluded cats
				if (in_array($post->term_id, $excluded_cats) || in_array($current_post->terms[0], $excluded_cats))
					array_pop($processed_posts);
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
				$post->terms = array($post->term_id);
				$post->term_names = array($post->name);
				$processed_posts[] = $post;
				$ord_num = $post->ID;
			}
		}

		return $processed_posts;
	}

	private static function news_time()
	{
		return gmdate('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600 - 48 * 3600);
	}

	/**
	 * This is the main function that generates our data.
	 */
	function generate_data()
	{
		global $wpdb, $post, $bwp_gxs;

		$keywords_map = apply_filters('bwp_gxs_news_keyword_map', array( 
			// This is an array to map foreign categories to its English counterpart
			// Use category title (name) as the key
			// Below is an example:
			// '電視台' => 'television',
			// '名人'=> 'celebrities'
		));

		// @see http://www.google.com/support/news_pub/bin/answer.py?answer=74288
		$time = self::news_time();
		$lang = $bwp_gxs->options['select_news_lang'];
		$news_genres = $bwp_gxs->options['input_news_genres'];
		$news_cats = $bwp_gxs->options['select_news_cats'];
		$news_cat_action = $bwp_gxs->options['select_news_cat_action'];
		$cat_query = ' AND wpterms.term_id NOT IN (' . $news_cats . ')';
		$cat_query = ('inc' == $news_cat_action) ? str_replace('NOT IN', 'IN', $cat_query) : $cat_query;
		$cat_query = ('inc' != $news_cat_action && 'yes' == $bwp_gxs->options['enable_news_multicat']) ? '' : $cat_query;
		$group_by = (empty($bwp_gxs->options['enable_news_multicat'])) ? ' GROUP BY wposts.ID' : '';

		$latest_post_query = '
				SELECT * FROM ' . $wpdb->term_relationships . ' wprel
					INNER JOIN ' . $wpdb->posts . ' wposts
						ON wprel.object_id = wposts.ID' . "
						AND wposts.post_status = 'publish'" . '
					INNER JOIN ' . $wpdb->term_taxonomy . ' wptax
						ON wprel.term_taxonomy_id = wptax.term_taxonomy_id' . "
						AND wptax.taxonomy = 'category'" . '
					, ' . $wpdb->terms . ' wpterms
					WHERE wptax.term_id = wpterms.term_id
						AND wposts.post_date > %s' . 
						$cat_query . $group_by . '
				ORDER BY wposts.post_date DESC';

		$latest_posts = $this->get_results($wpdb->prepare($latest_post_query, $time));

		// If Multi-cat mode is enabled we will need to process fetched posts
		if ('yes' == $bwp_gxs->options['enable_news_multicat'])
			$latest_posts = self::process_posts($latest_posts, $news_cats, $news_cat_action);

		$using_permalinks = $this->using_permalinks();

		$genres_cache = array();

		for ($i = 0; $i < sizeof($latest_posts); $i++)
		{
			$post = $latest_posts[$i];
			// Init your $data with the previous item's data. This makes sure no item is mal-formed.
			$data = array();
			// @since 1.1.0 - get permalink independently, as we don't need caching or some complicated stuff
			if ($using_permalinks && empty($post->post_name))
				$data['location'] = '';
			else
				$data['location'] = $this->get_permalink();
			$data['language'] = $lang;
			// Multi-cat support for genres
			if (isset($post->terms))
			{
				$genres_cache_key = md5(implode('|', $post->terms));
				if (!isset($genres_cache[$genres_cache_key]) || !is_array($genres_cache[$genres_cache_key]))
				{
					$genres_cache[$genres_cache_key] = array();
					foreach ($post->terms as $term_id)
					{
						$cur_genres = !empty($news_genres['cat_' . $term_id]) ? explode(', ', $news_genres['cat_' . $term_id]) : '';
						if (is_array($cur_genres)) :
							foreach ($cur_genres as $cur_genre)
								if (!in_array($cur_genre, $genres_cache[$genres_cache_key]))
									$genres_cache[$genres_cache_key][] = $cur_genre;
						endif;
					}
				}

				$data['genres'] = implode(', ', $genres_cache[$genres_cache_key]);
			}
			else
				$data['genres'] = !empty($news_genres['cat_' . $post->term_id]) ? $news_genres['cat_' . $post->term_id] : '';
			$data['pub_date'] = $this->format_lastmod(strtotime($post->post_date));
			$data['title'] = $post->post_title;
			// Multi-cat support for news categories as keywords
			if ('cat' == $bwp_gxs->options['select_news_keyword_type'] && isset($post->term_names))
			{
				$keywords = array();
				foreach ($post->term_names as $term_name)
					$keywords[] = (!empty($keywords_map[$term_name])) ? trim($keywords_map[$term_name]) : $term_name;
				$keywords = implode(', ', $keywords);
			}
			// Temporary support for news tags as keywords
			else if ('tag' == $bwp_gxs->options['select_news_keyword_type'])
			{
				$keywords = array();
				$tags = get_the_tags($post->ID);
				if (is_array($tags)) :
					foreach (get_the_tags($post->ID) as $tag)
						$keywords[] = (!empty($keywords_map[$tag->name])) ? trim($keywords_map[$tag->name]) : $tag->name;
				endif;
				$keywords = implode(', ', $keywords);
			}
			else
				$keywords = (!empty($keywords_map[$post->name])) ? trim($keywords_map[$post->name]) : $post->name;
			$data['keywords'] = ('yes' == $bwp_gxs->options['enable_news_keywords']) ? $keywords : '';
			// Pass data back to the plugin to handle
			$this->data[] = $data;
		}
	}
}
?>