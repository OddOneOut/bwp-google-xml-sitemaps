<?php if (!defined('ABSPATH')) { exit; } ?>

<div id="wrapper-exclude-terms" class="bwp-inline-form-wrapper bwp-inline-form-wrapper-md bwp-no-display bwp-clear">
	<div class="bwp-form-group bwp-form-group-first">
		<select data-placeholder="<?php _e('Search for terms to exclude', $this->domain); ?>"
			class="bwp-typeahead" name="select-exclude-terms[]" id="select-exclude-terms"
			multiple>
		</select>
	</div>

	<div class="bwp-button-group">
		<button type="submit" class="button-primary" name="exclude_terms"><?php _e('Exclude selected items', $this->domain); ?></button>
		&nbsp;
		<button type="button" class="button-secondary bwp-switch-button"
			data-callback="bwp_button_view_excluded_terms_cb"
			data-target="wrapper-excluded-terms"
			data-loader="loader-excluded-terms"><?php _e('Show/Hide excluded items', $this->domain); ?></button>
		<span id="loader-excluded-terms" style="display: none;"><em><?php _e('... loading', $this->domain); ?></em></span>
	</div>

	<div id="wrapper-excluded-terms" class="bwp-no-display">
		<table id="table-excluded-terms"
			class="wp-list-table widefat striped hover bwp-table bwp-table-inline"
			border="0" cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th><?php _e('ID'); ?></th>
					<th><?php _ex('Name', 'term name', $this->domain); ?></th>
					<th><?php _e('Remove', $this->domain); ?></th>
				</tr>
			</thead>
		</table>
	</div>
</div>
