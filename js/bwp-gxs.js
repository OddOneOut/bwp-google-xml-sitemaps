jQuery(function($){
	// general options setting page
	function toggle_split_limit() {
		var shown = $('#enable_sitemap_split_post').prop('checked') ? true : false;
		var $i    = $('#input_split_limit_post').parents('.bwp-clear');

		$i.toggle(shown);
	}

	toggle_split_limit();

	$('#enable_sitemap_split_post').on('change', function() {
		toggle_split_limit();
	});

	// google news setting page
	function toggle_keyword_source() {
		var shown = $('#enable_news_keywords').prop('checked') ? true : false;
		var $i    = $('#select_news_keyword_type').parents('.bwp-clear');

		$i.toggle(shown);
	}

	toggle_keyword_source();

	$('#enable_news_keywords').on('change', function() {
		toggle_keyword_source();
	});
});
