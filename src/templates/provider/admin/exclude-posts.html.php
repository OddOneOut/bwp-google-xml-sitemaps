<?php if (!defined('ABSPATH')) { exit; } ?>

<div id="wrapper-exclude-posts" class="bwp-inline-form-wrapper bwp-inline-form-wrapper-lg bwp-no-display bwp-clear">
	<div class="bwp-form-group bwp-form-group-first">
		<select data-placeholder="<?php _e('Search for posts to exclude', $this->domain); ?>"
			class="bwp-typeahead" name="select-exclude-posts[]" id="select-exclude-posts"
			multiple>
		</select>
	</div>

	<div class="bwp-button-group">
		<button type="submit" class="button-primary" name="exclude_posts"><?php _e('Exclude selected items', $this->domain); ?></button>
		&nbsp;
		<button type="button" class="button-secondary bwp-switch-button"
			data-callback="bwp_button_view_excluded_posts_cb"
			data-target="wrapper-excluded-posts"
			data-loader="loader-excluded-posts"><?php _e('Show/Hide excluded items', $this->domain); ?></button>
		<span id="loader-excluded-posts" style="display: none;"><em><?php _e('... loading', $this->domain); ?></em></span>
	</div>

	<div id="wrapper-excluded-posts" class="bwp-no-display">
		<table id="table-excluded-posts"
			class="wp-list-table widefat striped hover bwp-table bwp-table-inline"
			border="0" cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th><?php _e('ID'); ?></th>
					<th><?php _e('Title', $this->domain); ?></th>
					<th><?php _e('Remove', $this->domain); ?></th>
				</tr>
			</thead>
		</table>
	</div>
</div>
