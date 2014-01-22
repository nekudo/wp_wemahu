<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form method="post" action="options.php">
		<?php
		settings_fields('wemahu');
		do_settings_sections('wemahu_settings_page');
		submit_button();
		?>
	</form>
</div>