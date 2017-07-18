<div class="wrap" id="kanban_gravityforms">
	<h1>
		Kanban + Gravity Forms
	</h1>

	<?php if ( !class_exists( 'GFAPI' ) ) : ?>
		<div class="notice notice-error">
			<p>
				<?php echo sprintf(
					__('Please install and activate %s.', 'kanban'),
					'<a href="http://gravityforms.com" target="_blank">Gravity Forms</a>'
				)
				?>
			</p>
		</div>
	<?php endif ?>

	<?php if ( isset($_GET['message']) ) : ?>
	<div class="updated">
		<p><?php echo $_GET['message'] ?></p>
	</div>
	<?php endif // notice ?>

	<?php if ( isset($form) && isset($board) ) : ?>
	<?php include __DIR__ . '/inc/map-fields.php' ?>
	<?php elseif ( isset($form) ) : ?>
		<?php include __DIR__ . '/inc/choose-board.php' ?>
	<?php else : ?>
		<?php include __DIR__ . '/inc/choose-form.php' ?>
	<?php endif ?>

</div><!-- wrap -->
