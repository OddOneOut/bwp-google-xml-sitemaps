<?php if (!defined('ABSPATH')) { exit; } ?>

<!--[if lte IE 9]>
<div class="bwp-table-fixed-headers-old-id-wrapper" style="width: 700px">
<!--<![endif]-->
	<table class="wp-list-table widefat striped bwp-table bwp-table-md bwp-table-inline bwp-table-fixed-headers bwp-table-valign-middle" style="width: 700px;">
		<thead>
			<tr>
				<th class="ordinal">#</th>
				<th style="min-width: 250px"><?php _e('Name', $this->domain); ?></th>
				<th style="width: 220px"><?php _e('Last generated on', $this->domain); ?></th>
				<th style="width: 200px"></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$count = 0;

			/* @var $item BWP_Sitemaps_Logger_Sitemap_LogItem */
			foreach ($data['items'] as $item) :
				$count++;
				/* translators: date format, see http://php.net/date */
				$generated_on = $item->get_formatted_datetime(__('M d, Y h:i:s A', $this->domain));
				$sitemap_url  = $this->get_sitemap_url($item->get_sitemap_slug());
?>
			<tr>
				<td class="ordinal"><?php echo $count; ?></td>
				<td style="min-width: 250px">
					<a target="_blank"
						href="<?php esc_attr_e($sitemap_url); ?>"><?php echo esc_html($item->get_sitemap_slug()); ?></a>
				</td>
				<td style="width: 220px"><?php echo esc_html($generated_on); ?></td>
				<td style="width: 200px">
					<a target="_blank" title="<?php _e('Click to view', $this->domain); ?>"
						href="<?php esc_attr_e($sitemap_url); ?>"
						class="button-secondary bwp-button">
						<span class="dashicons dashicons-visibility"></span>
					</a>
					<a target="_blank" title="<?php _e('Click to regenerate', $this->domain); ?>"
						href="<?php esc_attr_e(add_query_arg(array('generate' => 1, 't' => time()), $sitemap_url)); ?>"
						class="button-secondary bwp-button">
						<span class="dashicons dashicons-update"></span>
					</a>
				</td>
			</tr>
<?php
endforeach;
?>
		</tbody>
	</table>
<!--[if lte IE 9]>
</div>
<!--<![endif]-->
