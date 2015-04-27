<?php
/*
Plugin Name: Pattern authentificator
Plugin URI: https://github.com/AleksandarPredic/pattern-auth
Description: Add pattern authentification in login form as an extra security
Version: 1.0.0
Author: Aleksandar Predic
Author URI: http://www.acapredic.com
License: GPLv2 or later

Documentation: https://github.com/s-yadav/patternLock
*/

// don't load directly
if (!defined('ABSPATH')) die('-1');

require_once dirname( __FILE__ ) . '/helpers/helper.php';

class pattern_auth_plugin {
    
    private $helper;
    
    // Define meta key for user saved pattern
    const META_KEY = 'pattern_auth';


    public function __construct() {
        
        // Init helper class
        $this->helper = new pattern_auth_helper();
        
        // Load plugin text domain
        load_plugin_textdomain( 'pattern_auth', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
        
        // Add pattern auth to user screen
        add_action( 'show_user_profile', array( $this, 'add_user_pattern_auth' ) );
        add_action( 'edit_user_profile', array( $this, 'add_user_pattern_auth' ) );
        
        // Save pattern auth to user meta
        add_action( 'personal_options_update', array( $this, 'save_user_pattern_auth' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_pattern_auth' ) );
        
        // Add new login field
        add_action('login_form', array( $this, 'pattern_auth_added_login_field' ));
        
        // Enqueue styles on login screen
        add_action( 'login_enqueue_scripts', array( $this, 'pattern_auth_enqueue_login_style' ), 10 );
        add_action( 'login_enqueue_scripts', array( $this, 'pattern_auth_enqueue_login_scripts' ), 1 );
        
        // Enqueue styles on user screen
        add_action( 'admin_enqueue_scripts', array( $this, 'pattern_auth_enqueue_admin_scripts' ) );
        
        // Add new authentification
        add_filter('wp_authenticate_user', array( $this, 'pattern_auth_login' ),10,2);
        
    }
    
    /*
     * Enqueue style on login page
     */
    public function pattern_auth_enqueue_login_style() {
        wp_enqueue_style( 'plugin-auth', plugins_url( '/css/pattern-lock.css', __FILE__ ), false ); 
        wp_enqueue_style( 'plugin-auth-custom', plugins_url( '/css/pattern-lock-custom.css', __FILE__ ), false ); 
    }
    
    /*
     * Enqueue scripts on login page
     */
    public function pattern_auth_enqueue_login_scripts() {
        
        wp_enqueue_script('jquery');
        wp_enqueue_script( 'plugin-auth', plugins_url( '/js/pattern-lock.min.js', __FILE__ ), array('jquery'), '0.4.1', true );
        wp_enqueue_script( 'plugin-auth-init', plugins_url( '/js/pattern-lock-init.js', __FILE__ ), array('plugin-auth'), '1.0.0', true );
    }
    
    /*
     * Enqueue scripts on profile and edit user page in admin
     */
    public function pattern_auth_enqueue_admin_scripts( $hook ) {
        
        if ( 'user-edit.php' === $hook || 'profile.php' === $hook) {
            $this->pattern_auth_enqueue_login_style();
            wp_enqueue_script( 'plugin-auth', plugins_url( '/js/pattern-lock.min.js', __FILE__ ), array('jquery'), '0.4.1', true );
        }
        
    }
   
    /*
     * Add pattern fields on login screen
     */
    public function pattern_auth_added_login_field(){
    ?>
        <div>
            <label for="my_extra_field"><?php _e( 'Please provide lock pattern', 'pattern_auth' ); ?></label>
            <input type="text" tabindex="20" size="20" value="" class="input" id="pattern_auth" name="pattern_auth">
            <div id="patternHolder"></div>
            
        </div>
        <div class="forget-pattern">
            <label><?php _e( 'Forget pattern?', 'pattern_auth' ); ?></label>
            <input type="checkbox" name="retrive_pattern" value="send" />
        </div>
    <?php
    }
    
    /*
     * Validate pattern after sending login form
     */
    public function pattern_auth_login ($user, $password) {

        // Check if user entered correct pattern
        $user_pattern = get_user_meta($user->ID, self::META_KEY, true);

        // If user did not entered his own pattern then accept any
        if( !isset( $user_pattern ) || empty( $user_pattern ) ) {
            return $user;
        }
        
        $retrieve_pattern = isset( $_POST['retrive_pattern'] ) && !empty( $_POST['retrive_pattern'] ) ? $_POST['retrive_pattern'] : 'nothing';
        
         if ( $_POST['pattern_auth'] === $user_pattern ) {
             
             return $user;
             
         } elseif( $retrieve_pattern === 'send' ) {
             
             // Check if password is provided and only then send pattern
             if( !wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
                 $lost_password_link = ' <a href="' . wp_lostpassword_url() . '">' . __( 'Lost your password?', 'pattern_auth' ) . '</a>';
                 $error = new WP_Error( 
                         'denied', 
                         __("<strong>ERROR</strong>: Provided password is invalid, please enter correct password to receive pattern by email.", "pattern_auth") . $lost_password_link );
                 return $error;
             }
             
             // Send email
             $mail_sent = $this->helper->send_pattern( $user );
             if ( $mail_sent ) {
                 $error = new WP_Error( 'mail_sent', __("<strong>MAIL SENT</strong>: Email with login pattern have been sent to your email.", "pattern_auth") );
             } else {
                 $error = new WP_Error( 'mail_error', __("<strong>MAIL NOT SENT</strong>: There was a problem sending email. Please contact site administrator.", "pattern_auth") );
             }
             
             return $error;
             
         } else {
             
             $error = new WP_Error( 'denied', __("<strong>ERROR</strong>: Provided pattern for authentification was invalid.", "pattern_auth") );
             return $error;
         }
    }

    /*
     * Add pattern fields on profile and edit user page in admin
     */
    public function add_user_pattern_auth( $user ) {
        ?>  
            <div class="pattern_auth_user">
                <h3><?php _e( 'Pattern authentificator', 'pattern_auth' ); ?></h3>
                <p><?php _e( 'Please set your login pattern here and then click "save" button.', 'pattern_auth' ); ?></p>
                <div>
                    <p class="inline-block">
                        <?php _e( 'Your pattern code is: ', 'pattern_auth' ); ?>
                    </p>
                    <p class="inline-block">
                        <input type="text" id="pattern_auth" name="pattern_auth" value="<?php echo esc_attr( get_user_meta($user->ID, self::META_KEY, true) ); ?>" class="regular-text" />
                    </p>
                    <div id="patternHolder"></div>
                </div>
            </div>
        <?php
        
        wp_enqueue_script( 'plugin-auth--user-init', plugins_url( '/js/pattern-lock-user-init.js', __FILE__ ), array('plugin-auth'), '1.0.0', true );
        $localization_array = array (
            'pattern_set' => get_user_meta($user->ID, self::META_KEY, true),
        );
        wp_localize_script( 'plugin-auth--user-init', 'pattern_auth_data', $localization_array );
    }

    /*
     * Save pattern field for profile and edit user page in admin
     */
    function save_user_pattern_auth( $user_id )
    {
        update_user_meta( $user_id, self::META_KEY, sanitize_text_field( $_POST['pattern_auth'] ) );
    }

    
}
// Finally initialize code
new pattern_auth_plugin();





