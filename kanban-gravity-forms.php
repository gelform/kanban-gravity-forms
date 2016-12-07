<?php
/*
Contributors:		gelform
Plugin Name:		Kanban + Gravity Forms
Plugin URI:			https://kanbanwp.com/addons/gravityforms/
Description:		Use Gravity Forms forms to interact with your Kanban boards.
Requires at least:	4.0
Tested up to:		4.6.1
Version:			0.0.2
Release Date:		November 1, 2016
Author:				Gelform Inc
Author URI:			http://gelwp.com
License:			GPLv2 or later
License URI:		http://www.gnu.org/licenses/gpl-2.0.html
Text Domain:		kanban
Domain Path: 		/languages/
*/



// Kanban + Gravity Forms is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 2 of the License, or
// any later version.
//
// Kanban + Gravity Forms is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Kanban Shortcodes. If not, see {URI to Plugin License}.



// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



class Kanban_Gravity_Forms {
	static $slug = '';
	static $friendlyname = '';
	static $plugin_basename = '';



//	static $options = array(
//		'gravityforms' => array()
//	);



	static function init() {
		self::$slug            = basename( __FILE__, '.php' );
		self::$plugin_basename = plugin_basename( __FILE__ );
		self::$friendlyname    = 'Kanban + Gravity Forms';



		register_activation_hook( __FILE__, array( __CLASS__, 'check_for_core' ) );
		add_action( 'admin_init', array( __CLASS__, 'check_for_core' ) );

		// just in case
		if ( ! self::_is_parent_loaded() ) {
			return;
		}

//		if ( !class_exists( 'GFAPI' ) ) {
//			deactivate_plugins( plugin_basename( __FILE__ ) );
//			add_action( 'admin_notices', array( __CLASS__, 'gf_admin_notice' ) );
//
//			return;
//		}



		add_action( 'gform_post_submission', array( __CLASS__, 'on_post_submission' ), 10, 2 );


		add_action( 'init', array( __CLASS__, 'save_settings' ) );


		// add tab to settings page
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 10 );



		add_filter( 'gform_pre_render', array( __CLASS__, 'populate_form_selects' ) );
		add_filter( 'gform_pre_validation', array( __CLASS__, 'populate_form_selects' ) );
		add_filter( 'gform_pre_submission_filter', array( __CLASS__, 'populate_form_selects' ) );
		add_filter( 'gform_admin_pre_render', array( __CLASS__, 'populate_form_selects' ) );
	}



	static function admin_menu() {
		add_submenu_page(
			Kanban::get_instance()->settings->basename,
			'Kanban Gravity Forms',
			'Gravity Forms',
			'manage_options',
			'kanban_gravityforms',
			array( __CLASS__, 'add_admin_page' )
		);
	}



	static function add_admin_page() {

		$forms = array();

		if ( class_exists( 'GFAPI' ) ) {
			$forms = GFAPI::get_forms();
		}

		$boards = Kanban_Board::get_all();

		foreach ( $boards as $board_id => &$board ) {
			$board->projects  = Kanban_Project::get_all( $board_id );
			$board->statuses  = Kanban_Status::get_all( $board_id );
			$board->estimates = Kanban_Estimate::get_all( $board_id );
			$board->users     = Kanban_User::get_allowed_users( $board_id );
		}



		$table_columns = array(
			'title'            => 'Title',
			'user_id_author'   => 'Task author',
			'user_id_assigned' => 'Assigned to user',
			'status_id'        => 'Status',
			'estimate_id'      => 'Estimate',
			'project_id'       => 'Project'
		);



		// Previously saved data.
		$saved = Kanban_Option::get_option( self::$slug );

		include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
	}



	static function save_settings() {

		if ( ! is_admin() || $_SERVER[ 'REQUEST_METHOD' ] != 'POST' || ! isset( $_POST[ self::$slug . '-nonce' ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST[ self::$slug . '-nonce' ], self::$slug ) ) {
			return;
		}

		Kanban_Option::update_option( self::$slug, $_POST[ 'forms' ] );

		wp_redirect(
			add_query_arg(
				array(
					'notice' => __( 'Saved!', 'kanban' )
				),
				sanitize_text_field( wp_unslash( $_POST[ '_wp_http_referer' ] ) )
			)
		);
		exit;
	}



	static function on_post_submission( $entry, $form ) {

		$saved = Kanban_Option::get_option( self::$slug );

		$form_id = $entry[ 'form_id' ];

		if ( ! isset( $saved[ $form_id ] ) ) {
			return false;
		}

		$table_columns = Kanban_Task::table_columns();
		$task_data     = array_fill_keys( array_keys( $table_columns ), '' );


		$board_id = $saved[ $form_id ][ 'board' ];

		$task_data[ 'created_dt_gmt' ]   = Kanban_Utils::mysql_now_gmt();
		$task_data[ 'modified_dt_gmt' ]  = Kanban_Utils::mysql_now_gmt();
		$task_data[ 'modified_user_id' ] = 0; // get_current_user_id();
		$task_data[ 'user_id_author' ]   = get_current_user_id();
		$task_data[ 'is_active' ]        = 1;
		$task_data[ 'board_id' ]         = $board_id;



		foreach ( $saved[ $form_id ] as $field_id => $task_field ) {

			// get the board id and move on
			if ( $field_id == 'board' ) {
				continue;
			}

			$task_data[ $task_field[ 'table_column' ] ] = $entry[ $field_id ];
		}

		//Set to the first status if empty.
		if ( empty( $task_data[ 'status_id' ] ) ) {
			$statuses = Kanban_Status::get_all( $board_id );

			$status = reset( $statuses );

			$task_data[ 'status_id' ] = $status->id;
		}

		Kanban_Task::replace( $task_data );
	}



	/**
	 *
	 * @link https://www.gravityhelp.com/documentation/article/dynamically-populating-drop-down-fields/
	 *
	 * @param $form
	 *
	 * @return object
	 */
	static function populate_form_selects( $form ) {

		$saved = Kanban_Option::get_option( self::$slug );

		$form_id = $form[ 'id' ];


		if ( ! isset( $saved[ $form_id ] ) ) {
			return $form;
		}

		$board_id = $saved[ $form_id ][ 'board' ];

		$estimates = array();
		$statuses  = array();
		$users     = array();

		foreach ( $saved[ $form_id ] as $field_id => $task_field ) {

			if ( $field_id == 'board' ) {
				continue;
			}

			if ( ! isset( $task_field[ 'defaultValue' ] ) ) {
				$task_field[ 'defaultValue' ] = null;
			}

			switch ( $task_field[ 'table_column' ] ) {
				case 'estimate_id':

					if ( empty( $estimates ) ) {
						$estimates = Kanban_Estimate::get_all( $board_id );
					}

					foreach ( $form[ 'fields' ] as &$field ) {
						if ( $field->id != $field_id ) {
							continue;
						}

						switch ( $field->type ) {
							case 'hidden':
								$field->defaultValue = $task_field[ 'defaultValue' ];

								break;

							case 'select':

								$choices = array();
								foreach ( $estimates as $estimate ) {
									$choices[] = array( 'text' => $estimate->title, 'value' => $estimate->id );
								}

								$field->choices = $choices;

								break;

						}
					}

					break;

				case 'status_id':

					if ( empty( $statuses ) ) {
						$statuses = Kanban_Status::get_all( $board_id );
					}

					foreach ( $form[ 'fields' ] as &$field ) {
						if ( $field->id != $field_id ) {
							continue;
						}

						switch ( $field->type ) {
							case 'hidden':
								$field->defaultValue = $task_field[ 'defaultValue' ];

								break;

							case 'select':

								$choices = array();
								foreach ( $statuses as $status ) {
									$choices[] = array( 'text' => $status->title, 'value' => $status->id );
								}

								$field->choices = $choices;

								break;

						}
					}

					break;

				case 'project_id':

					if ( empty( $projects ) ) {
						$projects = Kanban_Project::get_all( $board_id );
					}

					foreach ( $form[ 'fields' ] as &$field ) {
						if ( $field->id != $field_id ) {
							continue;
						}

						switch ( $field->type ) {
							case 'hidden':
								$field->defaultValue = $task_field[ 'defaultValue' ];

								break;

							case 'select':

								$choices = array();
								foreach ( $projects as $project ) {
									$choices[] = array( 'text' => $project->title, 'value' => $project->id );
								}

								$field->choices = $choices;

								break;

						}
					}

					break;

				case 'user_id_author':
				case 'user_id_assigned':

					if ( empty( $users ) ) {
						$users = Kanban_User::get_allowed_users( $board_id );
					}

					foreach ( $form[ 'fields' ] as &$field ) {
						if ( $field->id != $field_id ) {
							continue;
						}

						switch ( $field->type ) {
							case 'hidden':
								$field->defaultValue = $task_field[ 'defaultValue' ];

								break;

							case 'select':

								$choices = array();
								foreach ( $users as $user ) {
									$choices[] = array( 'text' => $user->long_name_email, 'value' => $user->ID );
								}

								$field->choices = $choices;

								break;

						}
					}

					break;
			}
		}

		return $form;
	}



//	static function add_options_defaults( $defaults ) {
//		return array_merge( $defaults, self::$options );
//	}



	static function notify_php_version() {
		if ( ! is_admin() ) {
			return;
		}
		?>
		<div class="error below-h2">
			<p>
				<?php
				echo sprintf(
					Kanban::get_instance()->settings->admin_notice,
					Kanban::get_instance()->settings->pretty_name,
					PHP_VERSION
				);
				?>
			</p>
		</div>
		<?php
	}



	static function check_for_core() {
		if ( ! self::_is_parent_loaded() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );

			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );

		}
	}



	static function admin_notice() {
		if ( ! is_admin() ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo sprintf(
					__( 'Whoops! This plugin %s requires the Kanban for WordPress plugin.
	            		Please download it here: <a href="https://wordpress.org/plugins/kanban/" target="_blank">https://wordpress.org/plugins/kanban/</a>.'
					),
					self::$friendlyname
				);
				?>
			</p>
		</div>
		<?php
	}



	static function gf_admin_notice() {
		if ( ! is_admin() ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo sprintf(
					__( 'Whoops! This plugin %s requires the Gravity Forms plugin.
	            		Please make sure it is installed and activated.'
					),
					self::$friendlyname
				);
				?>
			</p>
		</div>
		<?php
	}



	static function _is_parent_loaded() {
		return class_exists( 'Kanban' );
	}



	static function _is_parent_activated() {
		$active_plugins_basenames = get_option( 'active_plugins' );
		foreach ( $active_plugins_basenames as $plugin_basename ) {
			if ( false !== strpos( $plugin_basename, '/kanban.php' ) ) {
				return true;
			}
		}

		return false;
	}


}



function kanban_gravityforms_addon() {
	Kanban_Gravity_Forms::init();
}



if ( Kanban_Gravity_Forms::_is_parent_loaded() ) {
	// If parent plugin already included, init add-on.
	kanban_gravityforms_addon();
} else if ( Kanban_Gravity_Forms::_is_parent_activated() ) {
	// Init add-on only after the parent plugins is loaded.
	add_action( 'kanban_loaded', 'kanban_gravityforms_addon' );
} else {
	// Even though the parent plugin is not activated, execute add-on for activation / uninstall hooks.
	kanban_gravityforms_addon();
}
