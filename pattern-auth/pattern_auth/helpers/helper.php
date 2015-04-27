<?php
    

class pattern_auth_helper {
    
    /*
     * Send pattern by email
     */
    public function send_pattern( $user ){
        
        // If email somehow empty
        if ( !empty( $user->user_email ) ) {
            return false;
        }
        
        $email_recepients = $user->user_email;
        
        //Sanitize and set all fields for wp_mail
        $name = get_bloginfo('name');
        $email = 'noreplay@pattern_auth.com';
        $subject = __('Your login pattern: ', 'pattern_auth');
        $message = sanitize_text_field( get_the_author_meta( 'pattern_auth', $user->ID ) );

        //Set translation terms
        $pre_name = __('From', 'pattern_auth');
        $pre_email = __('Do not replay', 'pattern_auth');
        $pre_message = __('Message', 'pattern_auth');

        //Build wp_mail parameters
        $subject = get_bloginfo('name').' / ' . $subject;
        $body = "$pre_name: $name \n\n$pre_email: $email \n\n$pre_message: $message";
        $headers = __('From: ', 'pattern_auth') . $name . ' <' . $email . '>' . "\r\n" . __('Reply-To: ', 'pattern_auth') . $email;

        //Send mail and get boolean back
        $mail_sent = wp_mail( $email_recepients, $subject, $body, $headers );

        if ( $mail_sent ) {
            return true;
        } else {
            return false;
        }
 
    }
    
}
?>