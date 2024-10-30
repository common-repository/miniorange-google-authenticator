<?php
    
    class CustomerGauth {
        public $email;
        public $phone;
        
        public function submit_contact_us( $email, $phone, $query ) {
            $current_user = wp_get_current_user();
			$server_name = sanitize_url($_SERVER ['SERVER_NAME']);
            $query        = '[Wordpress miniOrange Google Authenticator] ' . $query;
            $fields       = array(
                'firstName' => $current_user->user_firstname,
                'lastName'  => $current_user->user_lastname,
                'company'   => $server_name,
                'email'     => $email,
                'ccEmail'   => '2fasupport@xecurify.com',
                'phone'     => $phone,
                'query'     => $query
            );
            $field_string = wp_json_encode( $fields );
            $gauth        = new miniorange_GAuth();
            $url          = $gauth->mo2f_GAuth_get_option( 'mo_gauth_host_name' ) . '/moas/rest/customer/contact-us';
	
	        $response     = self::callAPI( $url, $field_string );
			
			return $response;

        }
       
        public function send_email_alert( $email, $message,$subject ) {
            $gauth = new miniorange_GAuth();
	        $server_name = sanitize_url($_SERVER ['SERVER_NAME']);
            $url   = $gauth->mo2f_GAuth_get_option( 'mo_gauth_host_name' ) . '/moas/api/notify/send';
            $customerKey = "16555";
            $apiKey      = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
            $currentTimeInMillis = self::get_timestamp();
            $stringToHash        = $customerKey . $currentTimeInMillis . $apiKey;
            $hashValue           = hash( "sha512", $stringToHash );
            $fromEmail           = $email;
            
            // $subject             = "Feedback: miniOrange Google Authenticator Plugin";
            
            global $user;
            $user = wp_get_current_user();
            
            
            $query = '[miniOrange Google Authenticator ]: ' . $message;
            
            
            $content = '<div >Hello, <br><br>First Name :' . esc_html( $user->user_firstname ) . '<br><br>Last  Name :' . esc_html( $user->user_lastname ) . '   <br><br>Company :<a href="' . esc_url($server_name) . '" target="_blank" >' . esc_url($server_name) . '</a><br><br>Email :<a href="mailto:' . esc_html( $fromEmail ) . '" target="_blank">' . esc_html( $fromEmail ) . '</a><br><br>Query :' . esc_html( $query ) . '</div>';
            
            $fields       = array(
                'customerKey' => $customerKey,
                'sendEmail'   => true,
                'email'       => array(
                'customerKey' => $customerKey,
                'fromEmail'   => $fromEmail,
                'bccEmail'    => '2fasupport@xecurify.com',
                'fromName'    => 'miniOrange',
                'toEmail'     => '2fasupport@xecurify.com',
                'toName'      => '2fasupport@xecurify.com',
                'subject'     => $subject,
                'content'     => $content
                ),
            );
            $field_string = wp_json_encode( $fields );
            
            $headers = [
                "Content-Type" => "application/json",
                "Customer-Key" => $customerKey,
                "Timestamp"    => $currentTimeInMillis,
                "Authorization"=> $hashValue,
            ];
            
            $response = self::callAPI( $url, $field_string, $headers);
            return $response;
            
        }
        
        public function get_timestamp() {
	        $currentTimestampInMillis = round(microtime(true) * 1000);
            $currentTimestampInMillis = number_format($currentTimestampInMillis, 0, '', '');
			
			return $currentTimestampInMillis;
        }
	
	    private static function callAPI(
		    $url, $json_string, $headers = array(
		    "Content-Type"  => "application/json",
		    "charset"       => "UTF-8",
		    "Authorization" => "Basic"
	    )
	    ) {
		    $response  = wp_remote_post( $url, array(
				    'method'      => 'POST',
				    'timeout'     => 45,
				    'redirection' => 5,
				    'httpversion' => '1.0',
				    'blocking'    => true,
				    'headers'     => $headers,
				    'body'        => $json_string,
				    'cookies'     => array()
			    )
		    );
		
		    if(!is_wp_error($response)){
			    return $response['body'];
	        } else {
			    $message = 'Please enable curl extension. <a href="admin.php?page=mo_2fa_troubleshooting">Click here</a> for the steps to enable curl.';
	            return json_encode( array( "status" => 'ERROR', "message" => $message ) );
	        }
	    }
    }

?>