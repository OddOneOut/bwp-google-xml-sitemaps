<?php

if (!defined('ABSPATH')) { exit; }

// r3 2015-12-03
$news_keyword_source = '';

// no google news settings
if (! $this->is_option_key_valid(BWP_GXS_GOOGLE_NEWS)) {
	return;
}

// change 'select_news_keyword_type' to 'select_news_keyword_source'
if (isset($this->options['select_news_keyword_type'])) {
	$news_keyword_source = $this->options['select_news_keyword_type'] == 'tag'
		? 'tag' : 'category';
}

// change news genres array keys' prefix to 'term_'
$news_genres = array();
if (isset($this->options['input_news_genres']) && is_array($this->options['input_news_genres'])) {
	foreach ($this->options['input_news_genres'] as $key => $term_genres) {
		// perhaps already updated
		if (! preg_match('/^cat_(\d+)$/i', $key, $matches)) {
			continue;
		}

		$news_genres['term_' . $matches[1]] = $term_genres;
	}
}

$this->update_some_options(BWP_GXS_GOOGLE_NEWS, array(
	'input_news_genres'          => $news_genres,
	'select_news_keyword_source' => $news_keyword_source
));
