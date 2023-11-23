<?php

/**
 * Front-end javascript for WooCommerce register and edit account forms.
 */
function oe_wc_user_form() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            let $email_fld_label = $('label[for="reg_email"], label[for="account_email"]');
            // Remove "*" symbol from the email field
            $email_fld_label.find($('.required')).remove();
        });
    </script>
    <?php
}
add_action( 'woocommerce_register_form_end', 'oe_wc_user_form', 1 ); // Runs at the end of the register form.
add_action( 'woocommerce_edit_account_form_end', 'oe_wc_user_form', 1 ); // Runs at the end of the edit account form.

/**
 * Check if WooCommerce is installed and active, then update "generate username" option.
 * Forces WooCommerce to show username field on the registration form.
 */
function oe_wc_update_registration_option() {
    // Check if WooCommerce is active.
    if ( class_exists( 'WooCommerce' ) && is_admin() ) {
        // Update the `generate_username` option on front-end registration form. This option enables showing username field.
        update_option( 'woocommerce_registration_generate_username', 'no' );
    }
}
add_action( 'init', 'oe_wc_update_registration_option' );

/**
 * WooCommerce does not allow to register a user without email address. And there is no hook to override this behavior.
 * This function is a workaround to make email field optional.
 * It sets a temporary value for email field to pass the validation.
 *
 * @return void
 */
function oe_wc_pre_process_registration() {
    if ( ! isset( $_POST['register'] ) ) {
        return;
    }
    // Check if email value was set by user. If any value is set, then skip.
    if ( $_POST['email'] !== '' ) {
        return;
    }

    // Set a temporary value for email field to pass the validation.
    $_POST['email'] = 'oe-temp-' . time() . '@google.com';
    // Sets a flag to reset email field value after passing registration validation.
    $_POST['oe_wc_temporary_email'] = 1;
}
add_action( 'wp_loaded', 'oe_wc_pre_process_registration', 1 );

/**
 * Resets email field value to empty if it was set by the oe_wc_pre_process_registration function.
 * This is required to prevent displaying temporary email in registration form.
 *
 * @return void
 */
function oe_wc_post_process_registration() {
    if ( isset( $_POST['oe_wc_temporary_email'] ) ) {
        $_POST['email'] = '';
    }
}
add_action( 'wp_loaded', 'oe_wc_post_process_registration', 30 );

/**
 * Removes email field value from the new user data if it was set by the oe_wc_pre_process_registration function.
 * This is required to prevent saving the temporary email value to the database.
 *
 * @param array $args
 *
 * @return array
 */
function oe_wc_new_user( $args ) {
    if ( isset( $_POST['oe_wc_temporary_email'] ) ) {
        $args['user_email'] = '';
    }

    return $args;
}
add_filter( 'woocommerce_new_customer_data', 'oe_wc_new_user' );

/**
 * Removes email field from list of required fields after submitting the form.
 *
 * @param array $fields Array with required fields.
 *
 * @return array
 */
function oe_wc_account_required_fields( $fields ) {
    unset( $fields['account_email'] );

    return $fields;
}
add_filter( 'woocommerce_save_account_details_required_fields', 'oe_wc_account_required_fields' );
