<?php
// add to theme's functions.php
add_filter('bwp_gxs_news_keyword_map', 'bwp_gxs_news_keyword_map');
function bwp_gxs_news_keyword_map($keywords_map)
{
	return array(
		'エンターテインメント' => 'entertainment',
		'ビジネス'=> 'business'
	);
}
