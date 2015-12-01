<?php if (!defined('ABSPATH')) { exit; } ?>

<div id="wrapper-selected-term-genres" class="bwp-no-display bwp-inline-form-wrapper bwp-inline-form-wrapper-lg">
	<table id="table-selected-term-genres"
			class="wp-list-table widefat striped bwp-table bwp-table-md bwp-table-inline">
		<thead>
			<tr>
				<th><?php _e('Select', $this->domain); ?></th>
				<th><?php _e('Name', $this->domain); ?></th>
				<th>
					<?php _e('Genres', $this->domain); ?>
					<a href="https://support.google.com/news/publisher/answer/116037?hl=en&amp;ref_topic=4359874"
							title="<?php _e('View more info in a separate tab', $this->domain); ?>" target="_blank"
						class="bwp-field-help-link">&nbsp;<span class="dashicons dashicons-editor-help bwp-field-help"></span></a>
				</th>
			</tr>
		</thead>
	</table>

	<input type="hidden" name="term_genre_can_submit" value="0" />
</div>
