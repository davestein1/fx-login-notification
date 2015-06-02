<?php
/* do not access this file directly */
if ( ! defined( 'WPINC' ) ) { die; }

/**
 * Login Notification Settings
 * Create settings page in "Settings > Login Notification"
 * @since 0.1.0
 */

/**
 * Create Settings Page
 * @since 0.1.0
 */
class fx_Login_Nf_Settings{

	/**
	 * Settings Page Slug
	 * @since 0.1.0
	 */
	public $settings_slug = 'fx-login-nf';

	/**
	 * Settings Page Hook Name
	 * @since 0.1.0
	 */
	public $settings_id = 'settings_page_fx-login-nf';

	/**
	 * Options Group
	 * @since 0.1.0
	 */
	public $options_group = 'fx-login-nf';

	/**
	 * Option Name
	 * @since 0.1.0
	 */
	public $option_name = 'fx-login-nf-admin';

	/**
	 * Start
	 * @since 0.1.0
	 */
	public function __construct(){

		/* Create Settings Page */
		add_action( 'admin_menu', array( $this, 'create_settings_page' ) );

		/* Register Settings and Fields */
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Create Settings Page
	 * @since 0.1.0
	 */
	public function create_settings_page(){

		/* Create Settings Sub-Menu */
		add_submenu_page( 
			'options-general.php', // parent slug
			_x( 'Login Notification Settings', 'settings page', 'fx-login-notification' ), // page title
			_x( 'Login Notification', 'settings page', 'fx-login-notification' ), // menu title
			'manage_options',  // capability
			$this->settings_slug, // page slug
			array( $this, 'settings_page' ) // callback functions
		);

	}

	/**
	 * Settings Page Output
	 * @since 0.1.0
	 */
	public function settings_page(){
	?>
		<div class="wrap">

			<h2><?php _ex( 'Login Notification Settings', 'settings page', 'fx-login-notification' ); ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields( $this->options_group ); ?>
				<?php do_settings_sections( $this->settings_slug ); ?>
				<?php submit_button(); ?>
			</form>

		</div><!-- wrap -->
	<?php
	}

	/**
	 * Sanitize Options
	 * @since 0.1.0
	 */
	public function sanitize( $data ){

		/* New Data */
		$new_data = array();

		/* Enable/Disable Admin Notification */
		$new_data['enable'] = isset( $data['enable'] ) ? true : false ;

		/* User Roles */
		$new_data['exclude_roles'] = isset( $data['exclude_roles'] ) ? true : false ;

		/* If no role data is set, set as empty array */
		if( isset( $data['roles'] ) && is_array( $data['roles'] ) ){
			$roles = array();
			foreach( $data['roles'] as $roles_data ){
				if( array_key_exists( $roles_data, fx_login_nf_user_roles() ) ){
					$roles[] = $roles_data;
				}
			}
			$new_data['roles'] = $roles;
		}
		else{
			$new_data['roles'] = array();
		}

		/* E-mail Title Template */
		if( isset( $data['email_subject'] ) ){
			$new_data['email_subject'] = sanitize_text_field( esc_attr( $data['email_subject'] ) );
		}

		/* E-mail Content Template */
		if( isset( $data['email_content'] ) ){
			$new_data['email_content'] = fx_login_nf_email_content_sanitize( $data['email_content'] );
		}

		return $new_data;
	}

	/**
	 * Register Settings
	 * @since 0.1.0
	 */
	public function register_settings(){

		/* Register settings */
		register_setting(
			$this->options_group, // options group
			$this->option_name, // option name/database
			array( $this, 'sanitize' ) // sanitize callback function
		);

		/* Create settings section */
		add_settings_section(
			'fx_login_nf_admin_section', // section ID
			_x( 'Site Administrator Notification', 'settings page', 'fx-login-notification' ), // section title
			function(){

				printf( _x( 'Notification will be sent to admin e-mail address. You can change admin e-mail address in <a href="%s">General Settings</a>.', 'settings page', 'fx-login-notification' ), esc_url( admin_url( 'options-general.php' ) ) );

			}, // section callback function
			$this->settings_slug // settings page slug
		);

		/* Create Setting Field: Enable */
		add_settings_field(
			'fx_login_nf_admin_enable', // field ID
			_x( 'Admin Notification', 'settings page', 'fx-login-notification' ), // field title 
			function(){
			?>
				<label for="fx_login_nf_admin_enable"><input type="checkbox" value="1" id="fx_login_nf_admin_enable" name="<?php echo esc_attr( $this->option_name . '[enable]' );?>" <?php checked( fx_login_nf_get_option( 'enable', true ) ); ?>> <?php _ex( 'Enable site admin notification.', 'settings page', 'fx-login-notification' );?></label>
			<?php
			}, // field callback function
			$this->settings_slug, // settings page slug
			'fx_login_nf_admin_section' // section ID
		);

		/* Create Setting Field: User Roles */
		add_settings_field(
			'fx_login_nf_admin_user_roles', // field ID
			_x( 'Exclude User Roles', 'settings page', 'fx-login-notification' ), // field title 
			function(){
				?>

				<label for="fx_login_nf_admin_exclude_roles"><input type="checkbox" value="1" id="fx_login_nf_admin_exclude_roles" name="<?php echo esc_attr( $this->option_name . '[exclude_roles]' );?>" <?php checked( fx_login_nf_get_option( 'exclude_roles', true ) ); ?>> <?php _ex( 'Enable exclude user roles feature.', 'settings page', 'fx-login-notification' );?></label><br/>

				<p class="description"><?php _ex( 'If enabled, e-mail notification will be sent to administrator only if user log-in without selected role.<br />If disabled, e-mail notification will be sent to administrator for for all user log-in.', 'settings page', 'fx-login-notification' );?></p><br/>

				<p><?php _ex( "Select user roles to exclude:", 'settings page', 'fx-login-notification' );?></p>
				<?php
				/* For each roles, create option */
				$roles = fx_login_nf_user_roles();
				foreach( $roles as $role_id => $role_name ){
					$option_name = $this->option_name . '[roles][]';
					$id = 'fx_login_nf_admin_user_roles_' . $role_id;
					$roles_selected = fx_login_nf_get_option( 'roles',  array( 'subscriber' ) );
					$checked = false;
					if( is_array( $roles_selected ) && in_array( $role_id, $roles_selected ) ){
						$checked = true;
					}
				?>
					<label for="<?php echo esc_attr( $id ); ?>"><input type="checkbox" value="<?php echo esc_attr( $role_id );?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $option_name ); ?>" <?php checked( $checked ); ?>> <?php echo $role_name; ?></label><br />
				<?php
				}
			?>
			<?php
			}, // field callback function
			$this->settings_slug, // settings page slug
			'fx_login_nf_admin_section' // section ID
		);

		/* Create Setting Field: Email Template */
		add_settings_field(
			'fx_login_nf_admin_email_template', // field ID
			_x( 'E-mail Template', 'settings page', 'fx-login-notification' ), // field title 
			function(){
				$option_name = $this->option_name;
			?>
				<label for="admin_email_subject_template"><?php _ex( 'E-mail Subject', 'settings page', 'fx-login-notification' );?></label><br/>
				<input style="max-width:100%;width:500px;" type="text" class="ltr" value="<?php echo esc_attr( fx_login_nf_get_option( 'email_subject', fx_login_nf_email_subject_default() ) ); ?>" id="admin_email_subject_template" name="<?php echo esc_attr( $option_name . '[email_subject]' ); ?>"><br/><br/>

				<label for="admin_email_content_template"><?php _ex( 'E-mail Content', 'settings page', 'fx-login-notification' );?></label><br/>
				<textarea id="admin_email_content_template" style="max-width:100%;width:500px;" cols="30" rows="12" name="<?php echo esc_attr( $option_name . '[email_content]' ); ?>"><?php echo esc_textarea( fx_login_nf_email_content_sanitize( fx_login_nf_get_option( 'email_content', fx_login_nf_email_content_default() ) ) );?></textarea>

				<p class="description">
					<?php $desc = _x(
						'You can use tags below in e-mail subject and content:<br />' .
						'<code>%site_name%</code> to display Website Name.<br />' .
						'<code>%site_url%</code> to display Website URL.<br />' .
						'<code>%current_time%</code> to display Current Date and Time.<br />' .
						'<code>%http_user_agent%</code> to display HTTP User Agent.<br />' .
						'<code>%http_referer%</code> to display HTTP Referer.<br />' .
						'<code>%ip_address%</code> to display IP Address.<br />' .
						'<code>%user_id%</code> to display User ID.<br />' .
						'<code>%user_login%</code> to display User Login Name.<br />' .
						'<code>%user_email%</code> to display User E-mail Address.<br />' .
						'<code>%display_name%</code> to display User Display Name.<br />' .
						'<code>%user_roles%</code> to display User Roles.<br />',
						'email template setting description',
						'fx-login-notification'
					);?>
					<?php print $desc; ?>
				</p>
			<?php
			}, // field callback function
			$this->settings_slug, // settings page slug
			'fx_login_nf_admin_section' // section ID
		);
	}
}
