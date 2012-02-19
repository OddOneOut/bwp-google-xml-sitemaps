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
		$time = strtotime('-2 days');
		$lang = $bwp_gxs->options['select_news_lang'];
		$news_genres = $bwp_gxs->options['input_news_genres'];
		$news_cats = $bwp_gxs->options['select_news_cats'];
		$news_cat_action = $bwp_gxs->options['select_news_cat_action'];
		$cat_query = ' AND wpterms.term_id NOT IN (' . $news_cats . ')';
		$cat_query = ('inc' == $news_cat_action) ? str_replace('NOT IN', 'IN', $cat_query) : $cat_query;

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
						$cat_query . '
				GROUP BY wposts.ID
				ORDER BY wposts.post_date DESC';

		$latest_posts = $this->get_results($wpdb->prepare($latest_post_query, date('Y-m-d', $time)));

		$using_permalinks = $this->using_permalinks();

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
			$data['genres'] = !empty($news_genres['cat_' . $post->term_id]) ? $news_genres['cat_' . $post->term_id] : '';
			$data['pub_date'] = $this->format_lastmod(strtotime($post->post_date));
			$data['title'] = $post->post_title;
			$keywords = (!empty($keywords_map[$post->name])) ? trim($keywords_map[$post->name]) : $post->name;
			$data['keywords'] = ('yes' == $bwp_gxs->options['enable_news_keywords']) ? $keywords : '';
			// Pass data back to the plugin to handle
			$this->data[] = $data;
		}
	}
}
?>