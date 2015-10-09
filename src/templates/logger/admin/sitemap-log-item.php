<!--[if lte IE 9]>
<div class="bwp-table-fixed-headers-old-id-wrapper" style="width: 700px>
<!--<![endif]-->
	<table class="wp-list-table widefat striped bwp-table bwp-table-medium-wide bwp-table-inline bwp-table-fixed-headers" style="width: 700px;">
		<thead>
			<tr>
				<th class="ordinal">#</th>
				<th style="min-width: 370px"><?php _e('Sitemap name (click to view/generate)', $this->domain); ?></th>
				<th style="width: 300px"><?php _e('Last generated on', $this->domain); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$count = 0;

			/* @var $item BWP_Sitemaps_Logger_Sitemap_LogItem */
			foreach ($data['items'] as $item) :
				$count++;
				/* translators: date format, see http://php.net/date */
				$generated_on = $item->get_formatted_datetime(__('M j, Y : H:i:s', $this->domain));
?>
			<tr>
				<td class="ordinal"><?php echo $count; ?></td>
				<td style="min-width: 370px">
					<a href="<?php esc_attr_e(sprintf($data['sitemap_url_struct'], $item->get_sitemap_slug())); ?>"
						target="_blank"><?php echo esc_html($item->get_sitemap_slug()); ?></a>
				</td>
				<td style="width: 300px"><?php echo esc_html($generated_on); ?></td>
			</tr>
<?php
endforeach;
?>
		</tbody>
	</table>
<!--[if lte IE 9]>
</div>
<!--<![endif]-->
