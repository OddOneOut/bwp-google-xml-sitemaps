<?php if (!defined('ABSPATH')) { exit; } ?>

<div class="bwp-modal" id="modal-external-page">
	<div class="bwp-modal-dialog">
		<div class="bwp-modal-content">
			<div class="bwp-modal-header">
				<button type="button" class="bwp-close" data-dismiss="modal">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="bwp-modal-title"><?php _e('Add/Update an external page', $this->domain); ?></h4>
			</div>
			<div class="bwp-modal-body">
				<?php echo $this->get_template_contents(
					'templates/provider/admin/external-page-form.html.php',
					array_merge($data, array(
					))
				); ?>
			</div>
			<div class="bwp-modal-footer">
				<span class="bwp-modal-message text-primary" style="display: none"
					data-working-text="<?php _e('working ...', $this->domain); ?>"></span>

				<button type="button" class="bwp-btn bwp-btn-default button-secondary"
					data-dismiss="modal"><?php _e('Close', $this->domain); ?></button>

				<button type="button" class="bwp-btn bwp-btn-default button-secondary bwp-button-modal-reset">
					<?php _e('Reset', $this->domain); ?>
				</button>

				<button type="button"
					data-ajax-callback="bwp_button_add_new_external_page_cb"
					class="bwp-btn bwp-btn-primary button-primary bwp-button-modal-submit">
					<?php _e('Save Changes', $this->domain); ?>
				</button>
			</div>
		</div>
	</div>
</div>
