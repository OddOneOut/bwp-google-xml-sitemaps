<?php if (!defined('ABSPATH')) { exit; } ?>

<div id="wrapper-external-pages" class="bwp-inline-form-wrapper bwp-inline-form-wrapper-full">
	<div class="bwp-button-group">
		<button type="button" data-target="#modal-external-page"
			class="button-secondary bwp-button-modal"
			name="add_external_page"><?php _e('Add new page', $this->domain); ?></button>
		&nbsp;
		<button type="button" class="button-secondary bwp-switch-button"
			data-callback="bwp_button_view_external_pages_cb"
			data-target="wrapper-list-external-pages"
			data-loader="loader-external-pages"><?php _e('Show/Hide external pages', $this->domain); ?></button>
		<span id="loader-external-pages" style="display: none;"><em><?php _e('... loading', $this->domain); ?></em></span>
	</div>

	<div id="wrapper-list-external-pages" class="bwp-no-display">
		<table id="table-external-pages"
			class="wp-list-table widefat striped hover bwp-table bwp-table-inline"
			border="0" cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th><?php _e('Url', $this->domain); ?></th>
					<th><?php _e('Change frequency', $this->domain); ?></th>
					<th><?php _e('Priority', $this->domain); ?></th>
					<th><?php _e('Last modified', $this->domain); ?></th>
					<th><?php _e('Actions', $this->domain); ?></th>
				</tr>
			</thead>
		</table>
	</div>
</div>
