<div class="wrap">
	<div class="image-sizes-acf-container">
		<div class="image-sizes-acf-info">
			<h1><?php _e('ACF Image Sizes', 'image-sizes-acf') ?></h1>
			<p>
				<?php _e('This plugin gives you the option of selecting which image sizes should be generated when you create an ACF image or gallery field.', 'image-sizes-acf') ?>
			</p>
			<p>
				<?php _e('If you have installed this plugin on a site that already has images, or you have changed field settings after uploading images; use the bulk optimise button below to scan all ACF image and gallery fields, and remove any images that are no longer required, or create any that are missing!', 'image-sizes-acf') ?>
			</p>
		</div>
		<div class="image-sizes-acf-options">
			<h3><?php _e('Optimise Existing Images', 'image-sizes-acf') ?></h3>
			<p><label><input type="checkbox" id="image-sizes-acf-delete" value="1" checked> <?php _e('Delete unused image sizes.', 'image-sizes-acf') ?></label></p>
			<p><label><input type="checkbox" id="image-sizes-acf-create" value="1" checked> <?php _e('Create required image sizes.', 'image-sizes-acf') ?></label></p>
			<p><button class="button" type="button" id="image-sizes-acf-cleanup"><?php _e('Bulk Optimise', 'image-sizes-acf') ?></button></p>
		</div>
		<div id="optimisation-progress-container">
			<h3><?php _e('Optimisation Progress', 'image-sizes-acf') ?></h3>
			<table class="image-sizes-acf-progress-table">

				<tr>
					<td>
						<strong><?php _e('Status', 'image-sizes-acf') ?>:</strong> <span id="image-sizes-acf-status"><?php _e('Standing By', 'image-sizes-acf') ?></span>
					</td>
					<td>
						<strong><?php _e('Fields Found', 'image-sizes-acf') ?>:</strong> <span id="image-sizes-acf-fields-found">0</span>
					</td>
					<td>
						<strong><?php _e('Images Found', 'image-sizes-acf') ?>:</strong> <span id="image-size-acf-images-found">0</span>
					</td>
				</tr>

				<tr>
					<td colspan="3">
						<div class="progress-bar-container">
							<span id="progress-bar-perc" class="progress-bar-inner"></span>
						</div>
						<p class="image-sizes-acf-percent"><span id="image-sizes-acf-percent-number">0</span>% <?php _e('Complete', 'image-sizes-acf') ?></p>
					</td>
				</tr>

				<tr>
					<td colspan="3">
						<p><strong><?php _e('Current Batch', 'image-sizes-acf') ?>:</strong> <span id="image-sizes-acf-current-batch">0</span> / <span id="image-sizes-acf-total-batches">0</span></p>
						<div id="image-sizes-acf-image-list"></div>
					</td>
				</tr>

				<tr>
					<td>
						<strong><span id="image-sizes-acf-space-text"><?php _e('Space Saved', 'image-sizes-acf') ?></span>:</strong> <span id="image-sizes-acf-space-saved">0 Bytes</span>
					</td>
					<td>
						<strong><?php _e('Images Deleted', 'image-sizes-acf') ?>:</strong> <span id="image-sizes-acf-images-deleted">0</span>
					</td>
					<td>
						<strong><?php _e('Images Added', 'image-sizes-acf') ?>:</strong> <span id="image-sizes-acf-images-created">0</span>
					</td>
				</tr>

			</table>
			<button class="button" type="button" id="image-sizes-acf-cancel" disabled><?php _e('Cancel', 'image-sizes-acf') ?></button>
		</div>
	</div>

	<div class="error" id="image-sizes-acf-error-text-container"><p id="image-sizes-acf-error-text"></p></div>
</div>