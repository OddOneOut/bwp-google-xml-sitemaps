/* global jQuery,ajaxurl,bwp_gxs */
function bwp_disable_inputs($) {
	// disable all form inputs
	$('#select_news_taxonomy').prop('disabled', true);
	$('#select_news_cat_action').prop('disabled', true);
	$('#table-selected-term-genres :input').prop('disabled', true);
	$('input[name="submit_bwp_gxs_google_news"]').prop('disabled', true);
}

function bwp_enable_inputs($) {
	// enable all form inputs
	$('#select_news_taxonomy').prop('disabled', false);
	$('#select_news_cat_action').prop('disabled', false);
	$('#table-selected-term-genres :input').prop('disabled', false);
	$('input[name="submit_bwp_gxs_google_news"]').prop('disabled', false);
}

function bwp_select_news_post_type_cb($, $t) {
	var post_type = $t.val();

	bwp_disable_inputs($);

	$.get(ajaxurl, {
		action: 'bwp-gxs-get-object-taxonomies',
		post_type: post_type
	}, function(r) {
		if ($.isArray(r)) {
			$('#select_news_taxonomy option:gt(0)').remove();
			$.each(r, function(i, o) {
				$('#select_news_taxonomy').append(
					$('<option></option>')
						.val(o.name)
						.text(o.title)
				);
			});

			$('#select_news_taxonomy').trigger('change');
		}

		bwp_enable_inputs($);
	});
}

function bwp_select_news_taxonomy_cb($, $t, hide_loader_cb) {
	if ($.fn.dataTable.isDataTable('#table-selected-term-genres')) {
		$('#table-selected-term-genres').DataTable().clear().destroy();

		// hide term genres wrapper, since all inputs in the table are gone need to
		// make sure that we do not submit term genres
		$('#wrapper-selected-term-genres')
			.addClass('bwp-no-display')
			.find('input[name="term_genre_can_submit"]')
			.val(0);
	}

	var taxonomy = $t.val();

	if (taxonomy) {
		$('#button-toggle-selected-term-genres').click();
	}
}

function bwp_button_view_selected_term_genres_cb($, $t, hide_loader_cb) {
	// init datatable if not already done so
	if (! $.fn.dataTable.isDataTable('#table-selected-term-genres')) {
		$.get(ajaxurl, {
			action: 'bwp-gxs-get-news-term-genres',
			news_taxonomy: $('#select_news_taxonomy').val()
		}, function(r) {
			var tbl = $('#table-selected-term-genres').DataTable({
				deferRender: true,
				autoWidth: false,
				order: [
					[1, 'asc']
				],
				columns: [{
					data: 'id',
					width: '10%',
					orderable: false,
					render: function(data, type, row) {
						return '<input type="checkbox" '
							+ 'id="news_term_' + data + '" '
							+ 'name="term_' + data + '" '
							+ 'value="1" '
							+ (row.selected ? 'checked="checked" ' : '')
							+ '/>'
						;
					}
				}, {
					data: 'name',
					width: '25%',
					render: function(data, type, row) {
						return '<label for="news_term_' + row.id + '">' + data + '</label>';
					}
				}, {
					data: 'genres',
					width: '65%',
					orderable: false,
					render: function(genres, type, row) {
						var html = '';
						$.each(genres, function(i, v) {
							html
								+= '<label>'
								+ '<input type="checkbox" '
								+ 'name="term_' + row.id + '_genres[]" '
								+ 'value="' + v.name + '" '
								+ (v.selected ? 'checked="checked" ' : '')
								+ '/> '
								+ v.name
								+ '</label>'
								+ '&nbsp;&nbsp;&nbsp;'
							;
						});

						return $.trim(html);
					}
				}]
			});

			tbl
				.columns.adjust()
				.clear()
				.rows.add(r)
				.draw();

			// make sure we can submit term genres now
			$('#wrapper-selected-term-genres')
				.find('input[name="term_genre_can_submit"]')
				.val(1);

			hide_loader_cb();
		}, 'json');
	} else {
		// hide loader immediately
		hide_loader_cb();
	}
}
