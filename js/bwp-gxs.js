jQuery(function($){
	function toggle_split_limit() {
		var shown = $('#enable_sitemap_split_post').prop('checked') ? true : false;
		var $i    = $('#input_split_limit_post').parents('.bwp-clear');

		$i.toggle(shown);
	}

	toggle_split_limit();

	$('#enable_sitemap_split_post').on('change', function() {
		toggle_split_limit();
	});
});
