<?php
/*
Plugin Name: Optional Email
Description: Makes email optional field for registration
Version: 1.3.11
Author: Nael Concescu
Author URI: https://cv.nael.pro/
Plugin URI: https://cv.nael.pro/
*/

require_once plugin_dir_path( __FILE__ ) . 'inc/woocommerce.php';

/**
 * Loads translation.
 */
function oe_load_textdomain() {
    load_plugin_textdomain( 'optional-email', false, plugin_basename( __DIR__ ) . '/languages' );
}
add_filter( 'plugins_loaded', 'oe_load_textdomain', 10 );

/**
 * Used for old versions @before 3.0.0
 */
function oe_comment_form( $fields ) {
    unset( $fields['email'] );

    return $fields;
}
add_filter( 'comment_form_default_fields', 'oe_comment_form' );

/**
 * Update profile page form.
 * Removes empty email error.
 */
function oe_profile_update_errors( $errors ) {
    unset( $errors->errors['empty_email'] );
}
add_filter( 'user_profile_update_errors', 'oe_profile_update_errors' );

/**
 * Skips random password value if a password was entered in a form
 */
function oe_reg_password( $password ) {
    $pass = filter_input( INPUT_POST, 'user_pass' );
    if ( ! empty( $pass ) ) {
        $password = $pass;
    }

    return $password;
}
add_filter( 'random_password', 'oe_reg_password' );

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

/**
 * MU signup form & MU Admin add new user form
 * Fields validation
 */
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
add_filter( 'wpmu_validate_user_signup', 'oe_mu_signup_validate' );

/**
 * Register form front-end
 * Adds password fields
 */
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
add_action( 'register_form', 'oe_regform_changes', 1 );

/**
 * MU front-end register form.
 * Adds password fields.
 * Fires at the end of the new user account registration form.
 *
 * @param WP_Error $errors A WP_Error object containing 'user_name' or 'user_email' errors.
 */
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
add_action( 'signup_extra_fields', 'oe_mu_signup_extrafields', 1 );

/**
 * Admin javascript
 */
function oe_admin_footer() {
    // Skip if multisite is enabled
    if ( is_multisite() ) {
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery('label[for="email"] > span.description').hide();
        jQuery('#createuser input[name=email]').closest('tr').removeClass('form-required');
    </script>
    <?php
}
add_action( 'admin_footer', 'oe_admin_footer', 1 );

/**
 * Front-end javascript
 */
function oe_login_footer() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('#reg_passmail').hide();
            let email_fld = $('label[for=user_email], label[for=reg_email]');
            let text = email_fld.html();

            // Define an array of search and replace pairs.
            let replacements = [
                {search: '<?php echo esc_html( __( 'Email&nbsp;Address:', 'default' ) ); ?>', replace: '<?php echo esc_html( __( 'Email Address: (optional)', 'optional-email' ) ); ?>'},
                {search: '<?php echo esc_html( __( 'Email Address:', 'default' ) ); ?>', replace: '<?php echo esc_html( __( 'Email Address: (optional)', 'optional-email' ) ); ?>'},
                {search: '<?php echo esc_html( __( 'Email address', 'woocommerce' ) ); ?>', replace: '<?php echo esc_html( __( 'Email Address (optional)', 'optional-email' ) ); ?>'},
                {search: '<?php echo esc_html( __( 'Email', 'default' ) ); ?>', replace: '<?php echo esc_html( __( 'Email (optional)', 'optional-email' ) ); ?>'},
                {search: '<?php echo esc_html( __( 'E-mail', 'default' ) ); ?>', replace: '<?php echo esc_html( __( 'E-mail (optional)', 'optional-email' ) ); ?>'}
            ];

            if (text && text.length) {
                // Iterate through the array and perform replacements
                for (let i = 0; i < replacements.length; i++) {
                    let pattern = new RegExp(replacements[i].search, 'gi');

                    // Check if there are matches
                    if (text.match(pattern)) {
                        // Make the replacement
                        text = text.replace(pattern, replacements[i].replace);
                        // Break out of the loop since a replacement has been made
                        break;
                    }
                }

                // Replace email field text with the new optional.
                email_fld.html(text);
            }

            // Another text to replace
            text = jQuery('#setupform').html();
            if (text && text.length) {
                text = text.replace("<?php esc_attr_e( 'We send your registration email to this address. (Double-check your email address before continuing.)', 'default' ) ?>", '');
                jQuery('#setupform').html(text);
            }
        });
    </script>
    <?php
}
add_action( 'login_footer', 'oe_login_footer', 1 ); // For register page.
add_action( 'after_signup_form', 'oe_login_footer', 1 ); // For MU signup page.
add_action( 'woocommerce_register_form_end', 'oe_login_footer', 1 ); // Runs at the end of the WooCommerce register form.

function oe_login_scripts() {
    wp_enqueue_script( 'jquery' );
}
add_action( 'login_enqueue_scripts', 'oe_login_scripts' );

/**
 * Automatically logs in user to skip confirmation page.
 * Fires before the Site Signup page is loaded.
 * For MU.
 */
function oe_signup() {
    // Make a wp nonce to load page if there is an error
    $id                      = mt_rand();
    $_POST['signup_form_id'] = $id;
    $_POST['_signup_form']   = wp_create_nonce( 'signup_form_' . $id );

    // Pre-check fields. Just to create user if all is correct. Because it will throw headers error if skip it to native action position.
    $result     = validate_user_form();
    $user_name  = $result['user_name'];
    $user_email = $result['user_email'];
    $user_pass  = filter_input( INPUT_POST, 'user_pass' );
    $errors     = $result['errors'];

    // Just return
    if ( $errors->get_error_code() ) {
        return;
    }

    // Create user in db
    $user_id = wpmu_create_user( $user_name, $user_pass, $user_email );
    if ( ! $user_id ) {
        return;
    }

    // Logs in user.
    $user                         = get_userdata( $user_id );
    $credentials['user_login']    = $user->user_login;
    $credentials['user_password'] = $user_pass;
    wp_signon( $credentials );

    // Redirect to home page after login
    wp_redirect( home_url() );
    die;
}
add_action( 'before_signup_header', 'oe_signup', 1 );

/**
 * Auto logs in created user.
 * Changes some default settings for created user.
 * Fires after a new user registration has been recorded.
 *
 * @param int $user_id ID of the newly registered user.
 *
 * @return void
 */
function oe_register_new_user( $user_id ) {
    global $wpdb;
    $user_pass = filter_input( INPUT_POST, 'user_pass' );

    if ( ! $user_pass ) {
        return;
    }

    $wpdb->update( $wpdb->users, array( 'user_activation_key' => '' ), array( 'ID' => $user_id ) );
    delete_user_option( $user_id, 'default_password_nag', true );

    $user                         = get_userdata( $user_id );
    $credentials['user_login']    = $user->user_login;
    $credentials['user_password'] = $user_pass;
    // Auto logs in created user.
    wp_signon( $credentials );

    // Redirect to home page after login.
    wp_redirect( home_url() );
    die;
}
add_action( 'register_new_user', 'oe_register_new_user' );
