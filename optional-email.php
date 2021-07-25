<?php
/*
Plugin Name: Optional Email
Description: Makes email optional field for registration
Version: 1.3.3
Author: Nael Concescu
Author URI: https://cv.nael.pro/
Plugin URI: https://cv.nael.pro/
Text Domain: optional-email
Domain Path: /languages
*/

/**
 * Loads translation.
 */
function oe_load_textdomain() {
    load_plugin_textdomain( 'optional-email', false, plugin_basename( __DIR__ ) . '/languages' );
}
add_filter( 'plugins_loaded', 'oe_load_textdomain', 10 );

/*
Used for old versions @before 3.0.0
*/
add_filter( 'comment_form_default_fields', 'oe_comment_form' );
function oe_comment_form( $fields ) {
    unset( $fields['email'] );

    return $fields;
}

/*
 * Update profile page form
 * Removes empty email error
*/
add_filter( 'user_profile_update_errors', 'oe_profile_update_errors' );
function oe_profile_update_errors( $errors ) {
    unset( $errors->errors['empty_email'] );
}

// Skip random password if a password was entered in a form
add_filter( 'random_password', 'oe_reg_password' );
function oe_reg_password( $password ) {
    $pass = filter_input( INPUT_POST, 'user_pass' );
    if ( ! empty( $pass ) ) {
        $password = $pass;
    }

    return $password;
}

/**
 * Fields validation for register form
 */
function oe_registration_errors( $errors ) {
    // Removes empty email error
    unset( $errors->errors['empty_email'] );

    $pass1 = filter_input( INPUT_POST, 'user_pass' );
    $pass2 = filter_input( INPUT_POST, 'user_pass2' );
    // Check for blank password when adding a user.
    if ( empty( $pass1 ) ) {
        $errors->add( 'pass', __( '<strong>Error</strong>: Please enter a password.', 'optional-email' ) );
    }

    // Check for "\" in password.
    if ( false !== strpos( wp_unslash( $pass1 ), "\\" ) ) {
        $errors->add( 'pass', __( '<strong>Error</strong>: Passwords may not contain the character "\\".', 'optional-email' ) );
    }

    // Checking the password has been typed twice the same.
    if ( ! empty( $pass1 ) && $pass1 != $pass2 ) {
        $errors->add( 'pass', __( '<strong>Error</strong>: Passwords don\'t match. Please enter the same password in both password fields.', 'optional-email' ) );
    }

    return $errors;
}
add_filter( 'registration_errors', 'oe_registration_errors' );

/*
 * MU signup form & MU Admin add new user form
 * Fields validation
*/
add_filter( 'wpmu_validate_user_signup', 'oe_mu_signup_validate' );
function oe_mu_signup_validate( $results ) {
    // Skip if admin creates a new user
    if ( is_admin() ) {
        return $results;
    }

    $email = filter_input( INPUT_POST, 'user_email' );
    $pass1 = filter_input( INPUT_POST, 'user_pass' );
    $pass2 = filter_input( INPUT_POST, 'user_pass2' );
    if ( $results['errors']->errors['user_email'] && ! $email ) {
        unset( $results['errors']->errors['user_email'] );
    }

    // Check for blank password when adding a user.
    if ( empty( $pass1 ) ) {
        $results['errors']->add( 'user_pass', __( 'Please enter a password.', 'optional-email' ) );
    }

    // Check for "\" in password.
    if ( false !== strpos( wp_unslash( $pass1 ), "\\" ) ) {
        $results['errors']->add( 'user_pass', __( 'Passwords may not contain the character "\\".', 'optional-email' ) );
    }

    // Checking the password has been typed twice the same.
    if ( ! empty( $pass1 ) && $pass1 != $pass2 ) {
        $results['errors']->add( 'user_pass2', __( 'Please enter the same password in both password fields.', 'optional-email' ) );
    }

    return $results;
}

/*
 * Register form front-end
 * Adds password fields
*/
add_action( 'register_form', 'oe_regform_changes', 1 );
function oe_regform_changes() {
    $user_pass  = filter_input( INPUT_POST, 'user_pass' );
    $user_pass2 = filter_input( INPUT_POST, 'user_pass2' );
    ?>
    <p>
        <label for="user_pass"><?php _e( 'Password', 'optional-email' ) ?><br/>
            <input type="password" name="user_pass" id="user_pass" class="input" value="<?php echo esc_attr( stripslashes( $user_pass ) ); ?>" size="25" tabindex="20"/>
        </label>
    </p>
    <p>
        <label for="user_pass2"><?php _e( 'Confirm Password', 'optional-email' ) ?><br/>
            <input type="password" name="user_pass2" id="user_pass2" class="input" value="<?php echo esc_attr( stripslashes( $user_pass2 ) ); ?>" size="25" tabindex="20"/>
        </label>
    </p>
    <?php
}

/*
 * Multisite register form front-end
 * Adds password fields
*/
add_action( 'signup_extra_fields', 'oe_mu_signup_extrafields', 1 );
function oe_mu_signup_extrafields( $errors ) {
    $errmsg     = $errors->get_error_message( 'user_pass' );
    $errmsg2    = $errors->get_error_message( 'user_pass2' );
    $user_pass  = filter_input( INPUT_POST, 'user_pass' );
    $user_pass2 = filter_input( INPUT_POST, 'user_pass2' );
    ?>
    <label for="user_pass"><?php _e( 'Password', 'optional-email' ) ?></label>
    <?php if ( $errmsg ) : ?>
        <p class="error"><?php echo $errmsg ?></p>
    <?php endif; ?>
    <input type="password" name="user_pass" id="user_pass" value="<?php echo esc_attr( stripslashes( $user_pass ) ); ?>" size="25" tabindex="20"/><br/>

    <label for="user_pass2"><?php _e( 'Confirm Password', 'optional-email' ) ?></label>
    <?php if ( $errmsg2 ) : ?>
        <p class="error"><?php echo $errmsg2 ?></p>
    <?php endif; ?>
    <input type="password" name="user_pass2" id="user_pass2" value="<?php echo esc_attr( stripslashes( $user_pass2 ) ); ?>" size="25" tabindex="20"/><br/>
    <?php
}

// Admin javascript
add_action( 'admin_footer', 'oe_admin_footer', 1 );
function oe_admin_footer() {
    // Skip if multisite is enabled
    if ( is_multisite() ) {
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery( 'label[for="email"] > span.description' ).hide();
        jQuery( '#createuser input[name=email]' ).closest( 'tr' ).removeClass( 'form-required' );
    </script>
    <?php
}

// For register page
add_action( 'login_footer', 'oe_login_footer', 1 );
// For MU signup page
add_action( 'after_signup_form', 'oe_login_footer', 1 );
/*
 * Front-end javascript
 */
function oe_login_footer() {
    ?>
    <script type="text/javascript">
        jQuery( '#reg_passmail' ).hide();
        var text = jQuery( 'label[for=user_email]' ).html();
        if ( text && text.length ) {
            if ( text.includes( "<?php _e( 'Email&nbsp;Address:' ) ?>" ) ) {
                text = text.replace( "<?php _e( 'Email&nbsp;Address:' ) ?>", "<?php echo __( 'Email Address: (optional)', 'optional-email' ) ?>" );
            } else if ( text.includes( "<?php _e( 'Email Address:' ) ?>" ) ) {
                text = text.replace( "<?php _e( 'Email Address:' ) ?>", "<?php echo __( 'Email Address: (optional)', 'optional-email' ) ?>" );
            } else {
                text = text.replace( "<?php _e( 'Email' ) ?>", "<?php echo __( 'Email (optional)', 'optional-email' ) ?>" );
                text = text.replace( "<?php _e( 'E-mail' ) ?>", "<?php echo __( 'E-mail (optional)', 'optional-email' ) ?>" );
            }
            jQuery( 'label[for=user_email]' ).html( text );
        }

        // Another text to replace
        text = jQuery( '#setupform' ).html();
        if ( text && text.length ) {
            text = text.replace( "<?php esc_attr_e( 'We send your registration email to this address. (Double-check your email address before continuing.)' ) ?>", '' );
            jQuery( '#setupform' ).html( text );
        }
    </script>
    <?php
}

add_action( 'login_enqueue_scripts', 'oe_login_scripts' );
function oe_login_scripts() {
    wp_enqueue_script( 'jquery' );
}

/*
 * Automatically login user to skip confirmation page
 * 
 * For MU
 */
add_action( 'before_signup_header', 'oe_signup', 1 );
function oe_signup() {
    // Make a wp nonce to load page if there is an error
    $id                      = mt_rand();
    $_POST['signup_form_id'] = $id;
    $_POST['_signup_form']   = wp_create_nonce( 'signup_form_' . $id );

    // Precheck fields. Just to create user if all is correct. Because it will throw headers error if skip it to native action position
    $result     = validate_user_form();
    $user_name  = $result['user_name'];
    $user_email = $result['user_email'];
    $user_pass  = filter_input( INPUT_POST, 'user_pass' );
    $errors     = $result['errors'];

    // Just return
    if ( $errors->get_error_code() ) {
        return false;
    }

    // Create user in db
    $user_id = wpmu_create_user( $user_name, $user_pass, $user_email );
    if ( ! $user_id ) {
        return false;
    }

    // Login user
    $user                         = get_userdata( $user_id );
    $credentials['user_login']    = $user->user_login;
    $credentials['user_password'] = $user_pass;
    wp_signon( $credentials );

    // Redirect to home page after login
    wp_redirect( home_url() );
    die;
}

/*
// Autologin created user
add_action('user_register', 'oe_autologin');
function oe_autologin($user_id){
	$user_pass = filter_input(INPUT_POST, 'user_pass');
	if( ! $user_pass )
			return false;
	
	$user = get_userdata($user_id);
	$credentials['user_login'] = $user->user_login;
	$credentials['user_password'] = $user_pass;
	wp_signon($credentials);
	
	echo $user_id; die;
	// Redirect to home page after login
	wp_redirect(home_url());
	die;
}*/

/*
 * After new user creation hook.
 * Changes some default settings for created user
 * Autologin created user
 */
// @since (4.5) or maybe earlier
add_action( 'register_new_user', 'oe_register_new_user' );
function oe_register_new_user( $user_id ) {
    global $wpdb;
    $user_pass = filter_input( INPUT_POST, 'user_pass' );
    if ( ! $user_pass ) {
        return false;
    }
    $wpdb->update( $wpdb->users, array( 'user_activation_key' => '' ), array( 'ID' => $user_id ) );
    delete_user_option( $user_id, 'default_password_nag', true );

    $user                         = get_userdata( $user_id );
    $credentials['user_login']    = $user->user_login;
    $credentials['user_password'] = $user_pass;
    wp_signon( $credentials );

    // Redirect to home page after login
    wp_redirect( home_url() );
    die;
}

