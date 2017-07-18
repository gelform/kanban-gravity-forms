<?php if ( class_exists( 'GFAPI' ) ) : ?>
	<form action="<?php echo add_query_arg( array() ) ?>" method="get">
		<ol>
			<li>
				<label for="form"><?php echo __( 'Choose a Gravity Form form to edit', 'kanban' ) ?>:
					<select name="form" id="form" autocomplete="off">
						<option value="">-- <?php echo __( 'Choose a form', 'kanban' ) ?> --</option>
						<?php foreach ( $forms as $form ) : ?>
							<option value="<?php echo $form['id'] ?>">
								<?php echo $form['title'] ?>
							</option>
						<?php endforeach; // $forms ?>
					</select>
				</label>

				<?php submit_button(
					__( 'Continue', 'kanban' ),
					'primary',
					'',
					''
				); ?>
				<input type="hidden" name="page" value="<?php echo self::$slug ?>">
			</li>

			<li>
				<?php echo __( 'Choose a board.', 'kanban' ) ?>
			</li>

			<li>
				<?php echo __( 'Map fields.', 'kanban' ) ?>
			</li>
		</ol>
	</form>
<?php endif ?>

