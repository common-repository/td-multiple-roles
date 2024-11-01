<?php
/**
 * Plugin Name:	TD Multiple Roles
 * Plugin URI:	https://wordpress.org/plugins/td-multiple-roles/
 * Description:	This TD Multiple Roles plugin allows you to assign multiple user roles to a user profile.
 * Version:		1.1
 * Author:		TD Plugin
 * Author URI:	http://www.tdeveloper.in/
 * License:		GPL-2.0+
 * License URI:	http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('TDMR_PATH', plugin_dir_path(__FILE__));
define('TDMR_ASSETS', plugins_url('/assets/', __FILE__));
define('TDMR_SLUG', plugin_basename(__FILE__));
define('TDMR_PRFX', 'tdmr_');
define('TDMR_CLS_PRFX', 'cls-tdmr-');
define('TDMR_TXT_DOMAIN', 'td-multiple-roles');
define('TDMR_VERSION', '1.1');

add_action( 'plugins_loaded', 'tdmr_plugin_init' );
function tdmr_plugin_init() {
	load_plugin_textdomain( TDMR_TXT_DOMAIN, false, TDMR_PATH . '/languages/' );
}

add_action( 'user_new_form', 'tdmr_add_multiple_roles_ui', 0 );
add_action( 'show_user_profile', 'tdmr_add_multiple_roles_ui', 0 );
add_action( 'edit_user_profile', 'tdmr_add_multiple_roles_ui', 0 );
function tdmr_admin_enqueue_scripts( $handle ) {
		
	if ( 'user-edit.php' == $handle || 'user-new.php' == $handle || 'options-general.php' == $handle ) {
		wp_enqueue_style('tdmr-admin', TDMR_ASSETS . 'tdmr-admin.css', array(), TDMR_VERSION, FALSE );
		
		wp_enqueue_script( 'jquery' );
		
		wp_enqueue_script('tdmr-admin', TDMR_ASSETS . 'tdmr-admin.js', array('jquery'), TDMR_VERSION, TRUE);
	}
}
add_action( 'admin_enqueue_scripts', 'tdmr_admin_enqueue_scripts', 10 );


function tdmr_add_multiple_roles_ui( $user ) {

	$roles = get_editable_roles();

	$user_roles = ! empty( $user->roles ) ? array_intersect( array_values( $user->roles ), array_keys( $roles ) ) : array();
	?>
	<div class="tdmr-roles-container">
		<table class="form-table">
			<tr>
				<th>
					<label><?php _e('Roles', TDMR_TXT_DOMAIN); ?></label>
				</th>
				<td>
					<?php
						foreach ( $roles as $role_id => $role_data ) {

							if ( current_user_can( 'promote_users', get_current_user_id() ) ) {
								?>
								<label for="user_role_<?php echo esc_attr( $role_id ); ?>">
									<input type="checkbox" id="user_role_<?php esc_attr_e( $role_id ); ?>" value="<?php esc_attr_e( $role_id ); ?>" name="tdmr_user_roles[]" <?php echo ( ! empty( $user_roles ) && in_array( $role_id, $user_roles ) ) ? ' checked="checked"' : ''; ?> />
									<?php esc_html_e( translate_user_role( $role_data['name'] ) ); ?>
								</label>
								<br />
								<?php
							} else {
								
								if ( ! empty( $user_roles ) && in_array( $role_id, $user_roles ) ) {
									echo translate_user_role( $role_data['name'] ) . ', ';
								}
							}
						} 
					?>
					<?php wp_nonce_field( 'tdmr_set_roles', '_tdmr_roles_nonce' ); ?>
					<br>
					<a href="<?php echo esc_url( 'https://paypal.me/tdeveloper?country.x=IN&locale.x=en_GB' ); ?>" target="_blank" style="color:orange; font-weight: bold; margin-top: 10px;">
						<?php esc_html_e( 'Help us to keep this plugin alive. Buy us a coffee!',TDMR_TXT_DOMAIN ) ?>
					</a>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

add_action('personal_options_update', 'tdmr_save_multiple_user_roles');
add_action('edit_user_profile_update', 'tdmr_save_multiple_user_roles');
add_action('user_register', 'tdmr_save_multiple_user_roles');
function tdmr_save_multiple_user_roles( $user_id ) {

	if ( ! current_user_can( 'promote_users', $user_id ) || ! wp_verify_nonce( $_POST['_tdmr_roles_nonce'], 'tdmr_set_roles' ) ) {
		return;
	}
	
	$user = new WP_User( $user_id );

	$roles = get_editable_roles();
	
	if ( ! empty( $_POST['tdmr_user_roles'] ) ) {

		$new_roles = array_map( 'sanitize_text_field', wp_unslash( $_POST['tdmr_user_roles'] ) );

		$new_roles = array_intersect( $new_roles, array_keys( $roles ) );

		$roles_to_remove = array();

		$user_roles = array_intersect( array_values( $user->roles ), array_keys( $roles ) );

		if ( ! $new_roles ) {
			$roles_to_remove = $user_roles;

		} else {

			$roles_to_remove = array_diff( $user_roles, $new_roles );
		}

		foreach ( $roles_to_remove as $_role ) {

			$user->remove_role( $_role );

		}

		if ( $new_roles ) {

			$_new_roles = array_diff( $new_roles, array_intersect( array_values( $user->roles ), array_keys( $roles ) ) );

			foreach ( $_new_roles as $_role ) {

				$user->add_role( $_role );

			}
		}
	}
}
