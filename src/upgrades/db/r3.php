<?php

if (!defined('ABSPATH')) { exit; }

// r3 2015-12-03
$news_keyword_source = '';

// change 'select_news_keyword_type' to 'select_news_keyword_source'
if (isset($this->options['select_news_keyword_type'])) {
	$news_keyword_source = $this->options['select_news_keyword_type'] == 'tag'
		? 'tag' : 'category';
}

$this->update_some_options(BWP_GXS_GOOGLE_NEWS, array(
	'select_news_keyword_source' => $news_keyword_source
));
