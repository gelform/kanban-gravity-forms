<?php if ( class_exists( 'GFAPI' ) ) : ?>
	<form action="<?php echo add_query_arg( array() ) ?>" method="get">

		<ol>
			<li>
				<?php echo __( 'Form', 'kanban' ) ?>:
				<b><?php echo $form['title'] ?></b>
				<input name="form" type="hidden" value="<?php echo $form['id'] ?>">
			</li>


			<li>
				<?php echo __( 'Choose a board to sync with', 'kanban' ) ?>:
				<?php if ( count( $boards ) > 1 ) : ?>
					<select name="board" autocomplete="off">
						<option value="">-- <?php echo __( 'Choose a board', 'kanban' ) ?> --</option>
						<?php foreach ( $boards as $key => $b ) : ?>
							<option
									value="<?php echo $b->id ?>" <?php echo isset( $saved[ $form['id'] ]['board'] ) && $saved[ $form['id'] ]['board'] == $b->id ? 'selected' : '' ?>>
								<?php echo $b->title ?>
							</option>
						<?php endforeach; // $forms ?>
					</select>
				<?php else : $board = reset( $boards ) ?>
					<input name="board" type="hidden" value="<?php echo $board->id ?>">
					<input type="text" value="<?php echo esc_attr( $board->title ) ?>" readonly>
				<?php endif ?>

				<?php submit_button(
					__( 'Continue', 'kanban' ),
					'primary',
					'',
					''
				); ?>
				<input type="hidden" name="page" value="<?php echo self::$slug ?>">
			</li>

			<li>
				<?php echo __( 'Map fields.', 'kanban' ) ?>
			</li>
		</ol>
	</form>
<?php endif ?>

