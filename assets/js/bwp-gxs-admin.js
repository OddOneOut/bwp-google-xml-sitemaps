/* global jQuery,ajaxurl,bwp_gxs */
function bwp_reset_exclude_form($, $t, type) {
	// init advanced dropdown
	$('#select-exclude-' + type).select2({
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				data: function(params) {
					return {
						action: 'bwp-gxs-get-' + type,
						group: $t.val(),
						q: params.term
					};
				},
				processResults: function(data) {
					return {
						results: data.items
					};
				},
			},
			minimumInputLength: 2,
			templateResult: function(items) {
				return items.title;
			},
			templateSelection: function(selection) {
				return selection.title;
			}
	});

	// reset selected items
	$('#select-exclude-' + type).val(null).trigger('change');

	// reset datatable if needed
	if ($.fn.dataTable.isDataTable('#table-excluded-' + type)) {
		$('#table-excluded-' + type).DataTable().destroy();
		$('#wrapper-excluded-' + type).addClass('bwp-no-display');
	}
}

function bwp_select_exclude_post_cb($, $t) {
	bwp_reset_exclude_form($, $t, 'posts');
}

function bwp_select_exclude_term_cb($, $t) {
	bwp_reset_exclude_form($, $t, 'terms');
}

function bwp_view_excluded_items($, $t, hide_loader_cb, type) {
	var select_id = type == 'posts' ? 'select_exclude_post_type' : 'select_exclude_taxonomy';

	// init datatable if not already done so
	if (! $.fn.dataTable.isDataTable('#table-excluded-' + type)) {
		$.get(ajaxurl, {
				action: 'bwp-gxs-get-excluded-' + type,
				group: $('#' + select_id).val()
		}, function(r) {
			var tbl = $('#table-excluded-' + type).DataTable({
				autoWidth: false,
				columns: [{
					data: 'id',
					width: '15%'
				}, {
					data: 'title',
					width: '65%'
				}, {
					data: 'id',
					render: function(data) {
						return '<button type="button" '
							+ 'data-item-id="' + data + '" '
							+ 'title="' + bwp_gxs.text.exclude_items.remove_title + '" '
							+ 'class="button-secondary bwp-ua-remove">&times;</button>';
					},
					orderable: false,
					width: '20%'
				}]
			});

			// populate data
			tbl
				.columns.adjust()
				.clear()
				.rows.add(r)
				.draw();

			// hide loader
			hide_loader_cb();
		}, 'json');
	} else {
		// hide loader immediately
		hide_loader_cb();
	}
}

function bwp_button_view_excluded_posts_cb($, $t, hide_loader_cb) {
	bwp_view_excluded_items($, $t, hide_loader_cb, 'posts');
}

function bwp_button_view_excluded_terms_cb($, $t, hide_loader_cb) {
	bwp_view_excluded_items($, $t, hide_loader_cb, 'terms');
}

function bwp_remove_excluded_item($, $t, group, type) {
	if (!window.confirm(bwp_gxs.text.exclude_items.remove_warning)) {
		return;
	}

	jQuery.post(ajaxurl, {
		action: 'bwp-gxs-remove-excluded-' + type,
		_ajax_nonce: bwp_gxs.nonce.remove_excluded_item,
		group: group,
		id: $t.data('itemId')
	}, function(r) {
		if (r != 1) {
			return;
		}

		if ($.fn.dataTable.isDataTable('#table-excluded-' + type + 's')) {
			$('#table-excluded-' + type + 's').DataTable()
				.row($t.parents('tr'))
				.remove()
				.draw();
		}
	});
}

jQuery(function($){
	"use strict";

	// remove an excluded post
	$('#table-excluded-posts').on('click', '.bwp-ua-remove', function(e) {
		e.preventDefault();
		bwp_remove_excluded_item($, $(this), $('#select_exclude_post_type').val(), 'post');
	});

	// remove an excluded term
	$('#table-excluded-terms').on('click', '.bwp-ua-remove', function(e) {
		e.preventDefault();
		bwp_remove_excluded_item($, $(this), $('#select_exclude_taxonomy').val(), 'term');
	});
});
