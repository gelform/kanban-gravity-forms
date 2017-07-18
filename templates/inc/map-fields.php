<form action="" method="post" class="gform" id="gform-<?php echo $form['id'] ?>" xxstyle="display: none;">


	<ol>
		<li>
			<?php echo __( 'Form', 'kanban' ) ?>:
			<b><?php echo $form['title'] ?></b>
			<input name="form" type="hidden" value="<?php echo $form['id'] ?>">
		</li>

		<li>
			<?php echo __( 'Board', 'kanban' ) ?>:
			<b><?php echo $board->title ?></b>
			<input name="board" type="hidden" value="<?php echo $board->id ?>">
		</li>


		<?php wp_nonce_field( self::$slug, self::$slug . '-nonce' ) ?>

		<li>
			<?php echo __( 'Map fields:', 'kanban' ) ?>
			<br><br>


			<table style="background: white; min-width: 50%;">
				<tbody>
				<?php $i = 0;
				foreach ( $form['fields'] as $field ) : ?>
					<tr class="<?php echo $i % 2 == 0 ? '' : 'alternate' ?>" title="Field id <?php echo $field->id ?>">
						<td>
							<?php echo $field->label ?>
						</td>
						<td>
							&rarr;
						</td>
						<td>
							<select name="<?php echo $field->id ?>[table_column]" class="table_column"
							        autocomplete="off">
								<option value="">
									-- <?php echo __( 'Map to', 'kanban' ) ?> --
								</option>
								<?php foreach ( $board->table_columns as $id => $label ) : ?>
									<option value="<?php echo $id ?>" <?php echo isset( $saved[ $field->id ] ) && $saved[ $field->id ]['table_column'] == $id ? 'selected' : '' ?>>
										<?php echo esc_html( stripslashes( $label ) ) ?>
									</option>
								<?php endforeach; // $table_columns ?>
							</select>

							<?php // endforeach; // $table_columns ?>
						</td>
						<td>

							<?php if ( $field['type'] == 'hidden' ) : ?>
								<select name="<?php echo $field->id ?>[defaultValue]" autocomplete="off"
								        class="default_value" disabled>
									<option value="">
										-- <?php echo __( 'Choose a default', 'kanban' ) ?> --
									</option>
									<?php foreach ( $board->projects as $project ) : ?>
										<option value="<?php echo $project->id ?>" class="project_id"
											<?php echo isset( $saved[ $field->id ] ) && $saved[ $field->id ]['table_column'] == 'project_id' && isset( $saved[ $field->id ]['defaultValue'] ) && $saved[ $field->id ]['defaultValue'] == $project->id ? 'selected' : '' ?>
										>
											<?php echo $project->title ?>
										</option>
									<?php endforeach; ?>
									<?php foreach ( $board->statuses as $status ) : ?>
										<option value="<?php echo $status->id ?>" class="status_id"
											<?php echo isset( $saved[ $field->id ] ) && $saved[ $field->id ]['table_column'] == 'status_id' && isset( $saved[ $field->id ]['defaultValue'] ) && $saved[ $field->id ]['defaultValue'] == $status->id ? 'selected' : '' ?>
										>
											<?php echo $status->title ?>
										</option>
									<?php endforeach; ?>
									<?php foreach ( $board->estimates as $estimate ) : ?>
										<option value="<?php echo $estimate->id ?>" class="estimate_id"
											<?php echo isset( $saved[ $field->id ] ) && $saved[ $field->id ]['table_column'] == 'estimate_id' && isset( $saved[ $field->id ]['defaultValue'] ) && $saved[ $field->id ]['defaultValue'] == $estimate->id ? 'selected' : '' ?>
										>
											<?php echo $estimate->title ?>
										</option>
									<?php endforeach; ?>
									<?php foreach ( $board->users as $user_id => $user ) : ?>
										<option value="<?php echo $user_id ?>" class="user_id_assigned"
											<?php echo isset( $saved[ $field->id ] ) && $saved[ $field->id ]['table_column'] == 'user_id_assigned' && isset( $saved[ $field->id ]['defaultValue'] ) && $saved[ $field->id ]['defaultValue'] == $user->ID ? 'selected' : '' ?>
										>
											<?php echo $user ?>
										</option>
									<?php endforeach; ?>
									<?php foreach ( $board->users as $user_id => $user ) : ?>
										<option value="<?php echo $user_id ?>" class="user_id_author"
											<?php echo isset( $saved[ $field->id ] ) && $saved[ $field->id ]['table_column'] == 'user_id_author' && isset( $saved[ $field->id ]['defaultValue'] ) && $saved[ $field->id ]['defaultValue'] == $user->ID ? 'selected' : '' ?>
										>
											<?php echo $user ?>
										</option>
									<?php endforeach; ?>
									<?php foreach ( $board->fields as $field_id => $board_field ) : ?>
										<?php foreach ( $board_field as $value ) : if ( empty( $value ) ) {
											continue;
										} ?>
											<option value="<?php echo $value ?>" class="<?php echo $field_id ?>"
												<?php echo isset( $saved[ $field->id ] ) && $saved[ $field->id ]['table_column'] == $field_id && isset( $saved[ $field->id ]['defaultValue'] ) && $saved[ $field->id ]['defaultValue'] == $value ? 'selected' : '' ?>
											>
												<?php echo esc_html( stripslashes( $value ) ) ?>
											</option>
										<?php endforeach; ?>
									<?php endforeach; ?>
								</select>

								<input type="text" name="<?php echo $field->id ?>[defaultValue]" class="default_value"
								       placeholder="<?php echo __( 'Enter a default value', 'kanban' ) ?>"
								       value="<?php echo isset( $saved[ $field->id ] ) && isset( $saved[ $field->id ]['defaultValue'] ) ? $saved[ $field->id ]['defaultValue'] : '' ?>"
								       style="display: none;" disabled>
							<?php endif ?>
						</td>
					</tr>
					<?php $i ++; endforeach; // $field ?>
				</tbody>
			</table>

			<?php if ( class_exists( 'GFAPI' ) ) : ?>
				<?php submit_button( __( 'Save your settings', 'kanban' ) ); ?>
			<?php endif ?>

</form><!-- form -->


<style>
	table select,
	table input {
		width: 100%;
	}
</style>

<script>
	jQuery(function ($) {

		$('.table_column').on(
			'change',
			function (e, val_defaultValue) {

				var $select = $(this);
				var $tr = $select.closest('tr');
				var $defaultValueSelect = $('select.default_value', $tr);
				var $defaultValueInput = $('input.default_value', $tr);

				// If not a hidden field, skip it.
				if ($defaultValueSelect.length == 0) {
					return;
				}

				// Get the table column.
				var val_select = $select.val();

				// If no table column, reset back to disabled.
				if ('' == val_select) {
					$defaultValueSelect.prop('disabled', true).show();
					$defaultValueInput.hide();
					return;
				}

				// Get the default value options for the selected table column.
				var $options = $('.' + val_select, $defaultValueSelect).show();

				// If no options, let them enter text.
				if ($options.length == 0) {
					$defaultValueSelect.prop('disabled', true).hide();
					$defaultValueInput.removeProp('disabled').show(); // .focus()
				}
				else {
					// Otherwise, hide text input.
					$defaultValueInput.prop('disabled', true).hide();

					// Hide the non-related options.
					$('option:not(:first)', $defaultValueSelect).not($options).hide();

					// Reset to first option.
					$defaultValueSelect.show().removeProp('disabled').val($("option:first", $defaultValueSelect).val());
				}

				if ('undefined' !== typeof val_defaultValue && '' != val_defaultValue) {
					var $option = $('.' + val_select + '[value="' + val_defaultValue + '"]', $defaultValueSelect).prop('selected', true);
				}
			} // function
		); // on

		$('.table_column').each(function () {
			var $select = $(this);
			var $tr = $select.closest('tr');
			var $defaultValueSelect = $('select.default_value', $tr);
			var $defaultValueInput = $('input.default_value', $tr);

			if ($defaultValueSelect.length == 0) {
				return;
			}

			var val_select = $select.val();

			if (val_select == '') {
				return;
			}

			var val_defaultValue = $defaultValueSelect.val();

			// Send default value, even if empty.
			$select.trigger('change', [val_defaultValue]);
		});


	});
</script>



