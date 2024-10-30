<?php
	/*
	*Plugin Name: Login with TOTP (Google Authenticator, Microsoft Authenticator)
	*Plugin URI: http://miniorange.com/
	*Description: Login with TOTP (Google Authenticator, Microsoft Authenticator) secures your account and website with highly secure two-factor authentication methods as an additional layer of security for WordPress login.
	*Version: 1.1.1
	*Author: miniOrange
	*Author URI: http://miniorange.com/
	*/
	
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	
	
	require 'mo-gauth-class-customer.php';
	require 'mo-gauth-support-page.php';
	require 'mo_view_page.php';
	require 'mo-gauth-faq.php';
	require dirname( __FILE__ ) . '/feedback_form.php';
	require_once 'encryption.php';
	require 'mo-gauth-upgrade.php';
	require_once dirname( __FILE__ ) . '/database/database_functions.php';
	
	
	class miniorange_GAuth {
		protected $_codeLength = 6;
		
		function __construct() {
			add_action( 'admin_init', array( $this, 'mo_GAuth_save_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_style' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_script' ) );
			add_action( 'network_admin_menu', array( $this, 'miniorange_GAuth_menu' ) );
			register_activation_hook( __FILE__, array( $this, 'mo_auth_activate' ) );
			add_action( 'plugins_loaded', array( $this, 'mo_gauth_update_db_check' ) );
			add_action( 'admin_footer', array( $this, 'feedback_request' ) );
			
			if ( is_multisite() ) {
				remove_action( 'network_admin_notices', array( $this, 'mo_multi_auth_show_success_message' ) );
				remove_action( 'network_admin_notices', array( $this, 'mo_multi_auth_show_error_message' ) );
				add_action( 'network_admin_menu', array( $this, 'miniorange_GAuth_menu' ) );
			} else {
				remove_action( 'admin_notices', array( $this, 'mo_GAuth_success_message' ) );
				remove_action( 'admin_notices', array( $this, 'mo_GAuth_error_message' ) );
				add_action( 'admin_menu', array( $this, 'miniorange_GAuth_menu' ) );
			}
			//Google Authenticator
			$this->mo2f_GAuth_add_option( 'mo2f_gauth_issuer', $_SERVER['SERVER_NAME'] );
			add_action( 'login_form', array( $this, 'mo2f_GAuth_show_wp_login_form' ), 10 );
			add_filter( 'authenticate', array( $this, 'mo2f_GAuth_check_username_password' ), 99999, 4 );
			add_action( 'init', array( $this, 'miniorange_GAuth_redirect' ), 1 );
			$this->define_global();
			
		}
		
		public function mo2f_GAuth_add_option( $option, $value ) {
			
			if ( is_multisite() ) {
				add_site_option( $option, $value );
			} else {
				add_option( $option, $value );
			}
		}
		
		public function define_global() {
			global $Mo_gauthdbQueries;
			$Mo_gauthdbQueries = new mo_gauthDB();
		}
		
		public function mo_gauth_update_db_check() {
			
			global $Mo_gauthdbQueries;
			
			if ( ! $this->mo2f_GAuth_get_option( 'mo2f_gauth_existing_user_values_updated' ) ) {
				
				$user                        = get_user_by( 'email', $this->mo2f_GAuth_get_option( 'mo2f_gauth_user_email' ) );
				$user_id                     = $user->ID;
				$check_if_user_column_exists = false;
				
				if ( $user_id ) {
					$does_table_exist = $Mo_gauthdbQueries->check_if_table_exists();
					if ( $does_table_exist ) {
						$check_if_user_column_exists = $Mo_gauthdbQueries->check_if_user_column_exists( $user_id );
					}
					if ( ! $check_if_user_column_exists ) {
						$Mo_gauthdbQueries->generate_tables();
						$Mo_gauthdbQueries->insert_user( $user_id, array( 'user_id' => $user_id ) );
						$Mo_gauthdbQueries->update_user_details( $user_id,
							array(
								'mo2f_gauth_key'           => get_user_meta( $user_id, 'mo2f_gauth_key', true ),
								'mo2f_gauth_configured'    => get_user_meta( $user_id, 'mo2f_gauth_configured', true ),
								'mo2f_gauth_backup_codes'  => get_user_meta( $user_id, 'mo2f_gauth_backup_codes', true ),
								'mo2f_get_auth_rnd_string' => get_user_meta( $user_id, 'mo2f_get_auth_rnd_string', true ),
								'mo_gauth_user_email'      => $this->mo2f_GAuth_get_option( ( 'mo2f_gauth_user_email' ) )
							
							) );
						
						delete_user_meta( $user_id, 'mo2f_gauth_key' );
						delete_user_meta( $user_id, 'mo2f_gauth_configured' );
						delete_user_meta( $user_id, 'mo2f_get_auth_rnd_string' );
						delete_user_meta( $user_id, 'mo2f_gauth_backup_codes' );
						
						$this->mo2f_GAuth_update_option( 'mo2f_gauth_existing_user_values_updated', 1 );
						
					}
					
				}
				
			}
			
		}
		
		public static function mo2f_GAuth_get_option( $option, $val = null ) {
			if ( is_multisite() ) {
				
				$val = get_site_option( $option, $val );
				
			} else {
				$val = get_option( $option, $val );
			}
			
			return $val;
			
		}
		
		public function mo2f_GAuth_update_option( $option, $value ) {
			
			if ( is_multisite() ) {
				
				update_site_option( $option, $value );
				
			} else {
				update_option( $option, $value );
			}
			
		}
		
		public function feedback_request() {
			mo_gauth_display_feedback_form();
		}
		
		public function miniorange_GAuth_redirect() {
			
			if ( isset( $_POST['miniorange_soft_token_nonce'] ) ) {
				
				if ( ! wp_verify_nonce( sanitize_text_field( $_POST['miniorange_soft_token_nonce'] ), 'miniorange-gauth-soft-token-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.' ) );
					
					return $error;
				} else {
					$this->miniorange_GAuth_start_session();
					
					$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_url( $_POST['redirect_to'] ) : null;
					
					$otp_token = sanitize_text_field( $_POST['mo_gautha_softtoken'] );
					$user_id   = $_SESSION['mo2f_gauth_current_user'];
					
					if ( isset( $user_id ) ) {
						$user = get_user_by( 'id', $user_id );
						
						$secret = $this->mo_GAuth_get_secret( $user_id );
						
						
						$verify = $this->verifyCode( $secret, $otp_token, $user );
						
						if ( $verify == true ) {
							$this->mo2f_GAuth_login_user( $user );
						} else {
							//send again
							$login_message = "Invalid OTP. Please Try Again.";
							mo2f_GAuth_otp_prompt( $login_message, $redirect_to );
							exit;
						}
						
					} else {
						return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Please try again..' ) );
					}
				}
			}
			
		}
		
		public function miniorange_GAuth_start_session() {
			if ( ! session_id() || session_id() == '' || ! isset( $_SESSION ) ) {
				session_start();
			}
		}
		
		public function mo_GAuth_get_secret( $user_id ) {
			global $Mo_gauthdbQueries;
			$key    = $Mo_gauthdbQueries->get_user_detail( 'mo2f_get_auth_rnd_string', $user_id );
			$secret = $Mo_gauthdbQueries->get_user_detail( 'mo2f_gauth_key', $user_id );
			$secret = mo2f_GAuth_AESEncryption::decrypt_data( $secret, $key );
			
			return $secret;
		}
		
		public function verifyCode( $secret, $code, $user, $discrepancy = 1, $currentTimeSlice = null ) {
			
			global $Mo_gauthdbQueries;
			$backup_codes = json_decode( $Mo_gauthdbQueries->get_user_detail( 'mo2f_gauth_backup_codes', $user->ID ), true );
			
			if ( ! empty( $backup_codes ) ) {//check backup code there?
				
				
				$backup_validate = $this->mo2f_GAuth_backupcode_validation( $user, $code );
		
				if ( $backup_validate ) {
            
					return true;
				}
			}
			
			
			if ( $currentTimeSlice === null ) {
				$currentTimeSlice = floor( time() / 30 );
			}
			
			if ( strlen( $code ) != 6 ) {
				
				return false;
			}
			
			for ( $i = - $discrepancy; $i <= $discrepancy; ++ $i ) {
				$calculatedCode = $this->getCode( $secret, $currentTimeSlice + $i );
				if ( $this->timingSafeEquals( $calculatedCode, $code ) ) {
					return true;
				}
			}
			
			return false;
		}
		
		public function mo2f_GAuth_backupcode_validation( $user, $code ) {
			global $Mo_gauthdbQueries;
			
			$backup_codes = json_decode( $Mo_gauthdbQueries->get_user_detail( 'mo2f_gauth_backup_codes', $user->ID ), true );
			
			$mo2f_backup_code = md5( $code );
			
			if ( ! empty( $backup_codes ) ) {
				
				if ( in_array( $mo2f_backup_code, $backup_codes ) ) {
					
					foreach ( $backup_codes as $key => $value ) {
						
						if ( $value == $mo2f_backup_code ) {
							
							
							unset( $backup_codes[ $key ] );
							
							$backup_codes_encode = wp_json_encode( $backup_codes );
							$Mo_gauthdbQueries->update_user_details( $user->ID, array( 'mo2f_gauth_backup_codes' => $backup_codes_encode ) );
							
						}
						
						
					}
					
					return true;
					
				} else {
					
					return false;
					
				}
				
			} else {
				
				return false;
				
			}
			
		}
		
		public function getCode( $secret, $timeSlice = null ) {
			if ( $timeSlice === null ) {
				$timeSlice = floor( time() / 30 );
			}
			
			$secretkey = $this->_base32Decode( $secret );
			
			// Pack time into binary string
			$time = chr( 0 ) . chr( 0 ) . chr( 0 ) . chr( 0 ) . pack( 'N*', $timeSlice );
			// Hash it with users secret key
			$hm = hash_hmac( 'SHA1', $time, $secretkey, true );
			// Use last nipple of result as index/offset
			$offset = ord( substr( $hm, - 1 ) ) & 0x0F;
			
			// grab 4 bytes of the result
			$hashpart = substr( $hm, $offset, 4 );
			// Unpak binary value
			$value = unpack( 'N', $hashpart );
			$value = $value[1];
			// Only 32 bits
			$value  = $value & 0x7FFFFFFF;
			$modulo = pow( 10, $this->_codeLength );
			
			return str_pad( $value % $modulo, $this->_codeLength, '0', STR_PAD_LEFT );
		}
		
		protected function _base32Decode( $secret ) {
			if ( empty( $secret ) ) {
				return '';
			}
			$base32chars        = $this->_getBase32LookupTable();
			$base32charsFlipped = array_flip( $base32chars );
			
			$paddingCharCount = substr_count( $secret, $base32chars[32] );
			$allowedValues    = array( 6, 4, 3, 1, 0 );
			if ( ! in_array( $paddingCharCount, $allowedValues ) ) {
				return false;
			}
			
			
			for ( $i = 0; $i < 4; ++ $i ) {
				if ( $paddingCharCount == $allowedValues[ $i ] &&
				     substr( $secret, - ( $allowedValues[ $i ] ) ) != str_repeat( $base32chars[32], $allowedValues[ $i ] ) ) {
					return false;
				}
			}
			$secret        = str_replace( '=', '', $secret );
			$secret        = str_split( $secret );
			$binaryString  = '';
			$countOfSecret = count( $secret );
			for ( $i = 0; $i < $countOfSecret; $i = $i + 8 ) {
				$x = '';
				if ( ! in_array( $secret[ $i ], $base32chars ) ) {
					return false;
				}
				for ( $j = 0; $j < 8; ++ $j ) {
					
					$x .= str_pad( base_convert( @$base32charsFlipped[ @$secret[ $i + $j ] ], 10, 2 ), 5, '0', STR_PAD_LEFT );
				}
				$eightBits        = str_split( $x, 8 );
				$countOfeightBits = count( $eightBits );
				for ( $z = 0; $z < $countOfeightBits; ++ $z ) {
					$binaryString .= ( ( $y = chr( base_convert( $eightBits[ $z ], 2, 10 ) ) ) || ord( $y ) == 48 ) ? $y : '';
					
				}
			}
			
			return $binaryString;
		}
		
		protected function _getBase32LookupTable() {
			return array(
				'A',
				'B',
				'C',
				'D',
				'E',
				'F',
				'G',
				'H', //  7
				'I',
				'J',
				'K',
				'L',
				'M',
				'N',
				'O',
				'P', // 15
				'Q',
				'R',
				'S',
				'T',
				'U',
				'V',
				'W',
				'X', // 23
				'Y',
				'Z',
				'2',
				'3',
				'4',
				'5',
				'6',
				'7', // 31
				'=',  // padding char
			);
		}
		
		private function timingSafeEquals( $safeString, $userString ) {
			if ( function_exists( 'hash_equals' ) ) {
				return hash_equals( $safeString, $userString );
			}
			$safeLen = strlen( $safeString );
			$userLen = strlen( $userString );
			
			if ( $userLen != $safeLen ) {
				return false;
			}
			
			$result = 0;
			
			for ( $i = 0; $i < $userLen; ++ $i ) {
				$result |= ( ord( $safeString[ $i ] ) ^ ord( $userString[ $i ] ) );
			}
			
			// They are only identical strings if $result is exactly 0...
			return $result === 0;
		}
		
		public function mo2f_GAuth_login_user( $currentuser, $redirect_to = null ) {
			
			$user_id = $currentuser->ID;
			
			if ( $user_id ) {
				
				$currentuser = get_user_by( 'id', $user_id );
				wp_set_current_user( $user_id, $currentuser->user_login );
				unset( $_SESSION );
				wp_set_auth_cookie( $user_id, true );
				do_action( 'wp_login', $currentuser->user_login, $currentuser );
				$redirect_url = isset( $redirect_to ) && ! is_null( $redirect_to ) ? $redirect_to : get_admin_url();
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
		
		public function mo2f_GAuth_show_wp_login_form() {
			if ( $this->mo2f_GAuth_get_option( 'mo2f_gauth_login_page' ) ) {
				$nonce = wp_create_nonce( "mo2f-gauth-softtoken-nonce" );
				echo "\t<p>\n";
				echo "\t\t<label title=\"" . esc_html( 'If you don\'t have 2-factor authentication enabled for your WordPress account, leave this field empty.' ) . "\">" . esc_html( '2 Factor Authentication code*' ) . "<span id=\"google-auth-info\"></span><br />\n";
				echo "\t\t<input type=\"text\" name=\"mo2f_gauth_softtoken\" id=\"user_email\" pattern=\"[0-9]{4,8}\" class=\"input\" value=\"\" size=\"20\" style=\"ime-mode: inactive;\" />\n";
				echo "\t\t<input type=\"hidden\" name=\"mo2f_gauth_softtoken_nonce\" value=\"" . esc_html( $nonce ) . "\" size=\"20\" style=\"ime-mode: inactive;\" /></label>\n";
				echo "\t<p style='color:red; font-size:12px;padding:5px'>* Skip the authentication code if it doesn't apply.</p>\n";
				echo "\t</p>\n";
				echo " \r\n";
				echo " \r\n";
				echo "\n";
			}
		}
		
		public function mo_auth_activate() {
			$user = wp_get_current_user();
			
			$this->mo2f_GAuth_add_option( 'mo2f_gauth_user_email', $user->user_email );
			$this->mo2f_GAuth_update_option( 'mo_gauth_host_name', 'https://login.xecurify.com' );
			global $Mo_gauthdbQueries;
			$Mo_gauthdbQueries->mo_plugin_activate();
		}
		
		public function mo2f_GAuth_check_username_password( $user, $username, $password, $redirect_to = null ) {
			
			if ( is_a( $user, 'WP_Error' ) && ! empty( $user ) ) {
				return $user;
			}
			
			if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
				
				$currentuser = wp_authenticate_username_password( $user, $username, $password );
				if ( is_wp_error( $currentuser ) ) {
					$this->error = new IXR_Error( 403, __( 'Bad login/pass combination.' ) );
					
					return false;
				} else {
					return $currentuser;
				}
				
			} else {
				global $Mo_gauthdbQueries;
				$currentuser = wp_authenticate_username_password( $user, $username, $password );
				if ( is_wp_error( $currentuser ) ) {
					$currentuser->add( 'invalid_username_password', '<strong>' . mo2f_lt( 'ERROR' ) . '</strong>: ' . mo2f_lt( 'Invalid Username or password.' ) );
					
					return $currentuser;
				} else {
					
					$gauth_configured = $Mo_gauthdbQueries->get_user_detail( 'mo2f_gauth_configured', $currentuser->ID );
					$enable           = $this->mo2f_GAuth_get_option( 'mo2f_gauth_enable' );
					$otp_token        = '';
					if ( $enable ) {
						if ( empty( $gauth_configured ) ) {
							$this->mo2f_GAuth_login_user( $currentuser );
						}
						
						if ( $this->mo2f_GAuth_get_option( 'mo2f_gauth_login_page' ) ) {
							if ( ! wp_verify_nonce( $_POST['mo2f_gauth_softtoken_nonce'], 'mo2f-gauth-softtoken-nonce' ) ) {
								return;
							}
							
							if ( empty( $_POST['mo2f_gauth_softtoken'] ) ) { // Prevent PHP notices when using app password login
								return new WP_Error( 'one_time_passcode_empty', '<strong>ERROR</strong>: Please enter the One Time Passcode.' );
							} else {
								$otp_token = ( isset( $_POST['mo2f_gauth_softtoken'] ) && preg_match( "/\d{4,8}/", $_POST['mo2f_gauth_softtoken'] ) ) ? sanitize_text_field( trim( $_POST['mo2f_gauth_softtoken'] ) ) : '';
							}
						}
						
						$redirect_to = isset( $_REQUEST['redirect_to'] ) ? sanitize_url( $_REQUEST['redirect_to'] ) : home_url();
						$error       = $this->mo2f_GAuth_validate_2nd_factor( $currentuser, $otp_token, $redirect_to );
						
						if ( is_wp_error( $error ) ) {
							return $error;
						}
					} else {
						$this->mo2f_GAuth_login_user( $currentuser );
					}
					
				}
			}
			
		}
		
		public function mo2f_GAuth_validate_2nd_factor( $currentuser, $otp_token, $redirect_to = null ) {
			$user_id = $currentuser->ID;
			
			$secret = $this->mo_GAuth_get_secret( $user_id );
			
			$enabled_login_page = $this->mo2f_GAuth_get_option( 'mo2f_gauth_login_page' );
			
			if ( $enabled_login_page ) {
				
				
				$verify = $this->verifyCode( $secret, $otp_token, $currentuser );
				
				if ( $verify == true ) {
					$this->mo2f_GAuth_login_user( $currentuser );
				} else {
					return new WP_Error( 'one_time_passcode_invalid', '<strong>ERROR</strong>: Invalid One Time Passcode.' );
				}
			} else {
				$this->miniorange_GAuth_start_session();
				$_SESSION['mo2f_gauth_current_user'] = $user_id;
				$login_message                       = "Please Enter Your One Time Passcode";
				
				mo2f_GAuth_otp_prompt( $login_message, $redirect_to );
				exit;
			}
			
			
		}
		
		public function plugin_settings_style( $mo_gauth_hook_page ) {
			if ( 'toplevel_page_mo_view_page' != $mo_gauth_hook_page ) {
				return;
			}
			wp_enqueue_style( 'mo_gauth_admin_settings_style', plugins_url( 'includes/css/two_factor.css', __FILE__ ), array(), '1.0.8', 'all' );
			wp_enqueue_style( 'mo_gauth_admin_settings_phone_style', plugins_url( 'includes/css/phone.css', __FILE__ ), array(), '1.0.8', 'all' );
			wp_enqueue_style( 'bootstrap_style', plugins_url( 'includes/css/bootstrap.min.css', __FILE__ ), array(), '1.0.8', 'all' );
		}
		
		public function plugin_settings_script( $mo_gauth_hook_page ) {
			
			if ( 'toplevel_page_mo_view_page' != $mo_gauth_hook_page ) {
				return;
			}
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'mo_2_factor_admin_settings_phone_script', plugins_url( 'includes/js/phone.js', __FILE__ ), array(), '1.0.8', false );
			wp_enqueue_script( 'bootstrap_script', plugins_url( 'includes/js/bootstrap.min.js', __FILE__ ), array(), '1.0.8', false );
			wp_enqueue_script( 'mo2_gauth', plugins_url( '/includes/jquery-qrcode/jquery-qrcode.js', __FILE__ ), array(), '1.0.8', false );
		}
		
		public function mo_multi_auth_show_error_message() {
			remove_action( 'network_admin_notices', array( $this, 'mo_GAuth_error_message' ) );
			add_action( 'network_admin_notices', array( $this, 'mo_GAuth_success_message' ) );
		}
		
		public function mo_GAuth_error_message() {
			$message = $this->mo2f_GAuth_get_option( 'mo_gauth_msg' ); ?>
            <script>
                jQuery(document).ready(function () {
                    var message = "<?php echo esc_html( $message ); ?>";
                    jQuery('#messages').append("<div  style='padding:5px;'><div class='updated notice is-dismissible ' style='width:93%;margin:0px;'> <p class='mo_gauth_msgs'>" + message + "</p></div></div>");
                });
            </script>
			<?php
		}
		
		public function mo_GAuth_success_message() {
			
			$message = $this->mo2f_GAuth_get_option( 'mo_gauth_msg' ); ?>
            <script>
                jQuery(document).ready(function () {
                    var message = "<?php echo esc_html( $message ); ?>";
                    jQuery('#messages').append("<div  style='padding:5px;'><div class='error notice is-dismissible ' style='width:93%;margin:0px;'> <p class='mo_gauth_msgs'>" + message + "</p></div></div>");
                });
            </script>
			<?php
		}
		
		public function mo_login_view() {
			
			$mo_gauth_active_tab = isset( $_GET['mo_gauth_tab'] ) ? sanitize_text_field( $_GET['mo_gauth_tab'] ) : 'mo_setup';
			
			$user = wp_get_current_user();
			
			?>
            <div id="tab">
                <h2 class="nav-tab-wrapper">

                    <a href="admin.php?page=mo_view_page&mo_gauth_tab=mo_setup"
                       class="nav-tab <?php echo $mo_gauth_active_tab == 'mo_setup' ? 'nav-tab-active' : ''; ?>"
                       id="mo_gauth_tab1">
						<?php echo esc_html( 'Two Factor Setup' ); ?> </a>

                    <a href="admin.php?page=mo_view_page&mo_gauth_tab=mo_support"
                       class="nav-tab <?php echo $mo_gauth_active_tab == 'mo_support' ? 'nav-tab-active' : ''; ?>"
                       id="mo_gauth_tab2">
                        Feature Request</a>
                    <a href="admin.php?page=mo_view_page&mo_gauth_tab=mo_faq"
                       class="nav-tab <?php echo $mo_gauth_active_tab == 'mo_faq' ? 'nav-tab-active' : ''; ?>"
                       id="mo_gauth_tab3">
                        Help/FAQs</a>


                    <a href="admin.php?page=mo_view_page&mo_gauth_tab=mo_pricing"
                       class="nav-tab <?php echo $mo_gauth_active_tab == 'mo_pricing' ? 'nav-tab-active' : ''; ?>"
                       id="mo_gauth_tab4">
                        Pricing</a>


                </h2>
            </div>
            <div id="messages"></div>
            <table style="width:100%;padding:10px;">
            <tr>
                <td style="width:60%;vertical-align:top;">
					<?php
						if ( $mo_gauth_active_tab == 'mo_pricing' ) {
							//show pricing page
							mo_GAuth_upgrade( $user );
							
						} else if ( $mo_gauth_active_tab == 'mo_setup' ) {
							$this->mo_GAuth_get_details();
						} else if ( $mo_gauth_active_tab == 'mo_support' ) {
							mo_GAuth_support();
						} else if ( $mo_gauth_active_tab == 'mo_faq' ) {
							mo_GAuth_faq();
						}
					
					?></td>
                <td style="vertical-align:top;padding-left:1%;" id="mo_gauth_support_table">
					<?php
						if ( $mo_gauth_active_tab == 'mo_setup' ) {
							mo_Gauth_settings();
						}
						if ( $mo_gauth_active_tab != 'mo_support' ) {
							
							mo_GAuth_support();
						}
					
					?>
                </td>
            </tr>
            </table><?php
			
			
		}
		
		public function mo_GAuth_get_details() {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
			
			$secret = $this->mo_GAuth_get_secret( $user_id );
			
			if ( $secret == '' || $secret == false ) {
				
				$secret = $this->createSecret();
				$this->mo_GAuth_set_secret( $user_id, $secret );
			}
			
			$issuer = $this->mo2f_GAuth_get_option( 'mo2f_gauth_issuer' );
			
			$email = $this->mo2f_GAuth_get_option( 'mo_gauth_user_email', 'not_exits' );
			
			if ( $email == 'not_exits' ) {
				$email = $user->user_email;
			}
			
			$otpcode = $this->getCode( $secret );
			
			$url = $this->geturl( $secret, $issuer, $email );
			mo_GAuth_user_profile( $secret, $url, $otpcode );
			
		}
		
		public function createSecret( $secretLength = 16 ) {
			$validChars = $this->_getBase32LookupTable();
			
			// Valid secret lengths are 80 to 640 bits
			if ( $secretLength < 16 || $secretLength > 128 ) {
				throw new Exception( 'Bad secret length' );
			}
			$secret = '';
			$rnd    = false;
			if ( function_exists( 'random_bytes' ) ) {
				$rnd = random_bytes( $secretLength );
			} elseif ( function_exists( 'mcrypt_create_iv' ) ) {
				$rnd = mcrypt_create_iv( $secretLength, MCRYPT_DEV_URANDOM );
			} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
				$rnd = openssl_random_pseudo_bytes( $secretLength, $cryptoStrong );
				if ( ! $cryptoStrong ) {
					$rnd = false;
				}
			}
			if ( $rnd !== false ) {
				for ( $i = 0; $i < $secretLength; ++ $i ) {
					$secret .= $validChars[ ord( $rnd[ $i ] ) & 31 ];
				}
			} else {
				throw new Exception( 'No source of secure random' );
			}
			
			return $secret;
		}
		
		public function mo_GAuth_set_secret( $user_id, $secret ) {
			global $Mo_gauthdbQueries;
			$key = $this->random_str( 8 );
			$Mo_gauthdbQueries->update_user_details( $user_id, array( 'mo2f_get_auth_rnd_string' => $key ) );
			$secret = mo2f_GAuth_AESEncryption::encrypt_data( $secret, $key );
			
			$Mo_gauthdbQueries->update_user_details( $user_id, array( 'mo2f_gauth_key' => $secret ) );
		}
		
		public function random_str( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
			$randomString     = '';
			$charactersLength = strlen( $keyspace );
			for ( $i = 0; $i < $length; $i ++ ) {
				$randomString .= $keyspace[ rand( 0, $charactersLength - 1 ) ];
			}
			
			return $randomString;
			
		}
		
		private function geturl( $secret, $issuer, $email ) {
			// id can be email or name
			$url = "otpauth://totp/";
			
			$url .= $email . "?secret=" . $secret . "&issuer=" . $issuer;
			
			return $url;
			
			//aksjdbdzcaasd?secret=4RNWQWBQH4JDPABP&issuer=miniOrange/competits";
			
		}
		
		public function mo_GAuth_save_settings() {
			global $Mo_gauthdbQueries;
			if ( current_user_can( 'manage_options' ) ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
				if ( isset( $_POST['option'] ) && $_POST['option'] == "mo2f_gauth_test_otp" ) {
					
					if ( ! wp_verify_nonce( $_POST['mo2f_google_test_otp_nonce'], 'mo2f-gauth-test-otp-nonce' ) ) {
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid Request!' );
						$this->mo_GAuth_show_error_message();
						
						return;
					}
					$pattern = "/\d{4,8}/";
					$otp     = sanitize_text_field( $_POST['mo2f_gauth_otp'] );
					
					if ( $this->mo2f_check_number_length( $otp ) && preg_match( $pattern, $otp ) ) {
						
						if ( isset( $_POST['mo2f_gauth_save_secret'] ) ) {
							
							$secret = sanitize_text_field( $_POST['mo2f_gauth_save_secret'] );
							$verify = $this->verifyCode( $secret, $otp, $user );
							
							if ( $verify ) {
								$Mo_gauthdbQueries->insert_user( $user_id, array( 'user_id' => $user_id ) );
								$this->mo_GAuth_set_secret( $user->ID, $secret );
								$this->mo2f_GAuth_update_option( 'mo2f_gauth_enable', 1 );
								$Mo_gauthdbQueries->update_user_details( $user_id, array( 'mo2f_gauth_configured' => 1 ) );
								$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Two factor Authentication has been setup successfully.' );
								$this->mo_GAuth_show_success_message();
							} else {
								$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid OTP. Please Scan and try again.' );
								//Error message
								$this->mo_GAuth_show_error_message();
							}
						} else {
							$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid Secret. Please try again.' );
							//Error message
							$this->mo_GAuth_show_error_message();
						}
						
						
					} else {
						//give error message
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Please check the OTP and Enter again' );
						$this->mo_GAuth_show_error_message();
					}
					
				}
				
				
				if ( isset( $_POST['option'] ) && $_POST['option'] == "mo2f_gauth_appname" ) {
					
					$nonce = $_POST['mo2f_google_auth_nonce'];
					if ( ! wp_verify_nonce( $nonce, 'mo2f-gauth-app-name-change-nonce' ) ) {
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid Request!' );
						$this->mo_GAuth_show_error_message();
						
						return;
					}
					
					if ( isset( $_POST['mo2f_gauth_issuer'] ) && $_POST['mo2f_gauth_issuer'] != '' && preg_match( "/^[^±!£$%&*_§¡€#¢§¶•ªº«\\/<>;|=,{}]{1,30}$/", $_POST['mo2f_gauth_issuer'] ) ) {
						$this->mo2f_GAuth_update_option( 'mo2f_gauth_issuer', sanitize_text_field( $_POST['mo2f_gauth_issuer'] ) );
						$_SESSION['mo2f_gauth_issuer_set'] = "1";
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Your Settings are saved successfully. Please Scan the QR code Again' );
						$this->mo_GAuth_show_success_message();
					} else {
						$this->mo2f_GAuth_update_option( 'mo2f_gauth_issuer', $_SERVER['SERVER_NAME'] );
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Requested format did not match. It has been Reset to Default' );
						$this->mo_GAuth_show_error_message();
					}
				}
				
				
				if ( isset( $_POST['option'] ) && $_POST['option'] == "mo2f_gauth_backup_delete" ) {
					$nonce = $_POST['mo2f_google_auth_backup_code_delete_nonce'];
					if ( ! wp_verify_nonce( $nonce, 'mo2f-gauth-backup-code-delete-nonce' ) ) {
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid Request!' );
						$this->mo_GAuth_show_error_message();
						
						return;
					}
					$_SESSION['mo2f_gauth_backup_delete_set'] = "1";
					$Mo_gauthdbQueries->update_user_details( $user_id, array( 'mo2f_gauth_backup_codes' => null ) );
					$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Your codes are deleted. Please <b>Generate Codes</b> again.' );
					$this->mo_GAuth_show_success_message();
				}
				
				if ( isset( $_POST['option'] ) && $_POST['option'] == "mo2f_gauth_users_backup" ) {
					
					$nonce = $_POST['mo2f_google_auth_backup_code_nonce'];
					if ( ! wp_verify_nonce( $nonce, 'mo2f-gauth-backup_code-nonce' ) ) {
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid Request!' );
						$this->mo_GAuth_show_error_message();
						
						return;
					}
					$codes      = array();
					$codes_hash = array();
					header( 'Content-Disposition: attachment; filename=miniOrange-google-authenticator-BackupCodes.text' );
					echo "This is a backup codes file. You can use these backup codes instead of one time passcode during login. Each code can only be used once.
					r\n";
					echo "\r\n";
					for ( $x = 1; $x <= 3; $x ++ ) {
						$str = $this->random_str( 10 );
						array_push( $codes, $str );
						echo( "\t" . esc_html( $x ) . ". " . esc_html( $str ) . " \r\n" );
						array_push( $codes_hash, md5( $str ) );
						
					}
					$codes_hash_encode = wp_json_encode( $codes_hash );
					$Mo_gauthdbQueries->update_user_details( $user_id, array( 'mo2f_gauth_backup_codes' => $codes_hash_encode ) );
					exit;
					
				}
				if ( isset( $_POST['option'] ) && $_POST['option'] == "mo2f_gauth_save_twofactor" ) {
					$nonce = $_POST['mo2f_enable_two_factor_nonce'];
					if ( ! wp_verify_nonce( $nonce, 'mo2f-enable-two-factor-nonce' ) ) {
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid Request!' );
						$this->mo_GAuth_show_error_message();
						
						return;
					}
					if ( isset( $_POST['mo2f_gauth_enable'] ) && $_POST['mo2f_gauth_enable'] == '1' ) {
						$secret_configured = $Mo_gauthdbQueries->get_user_detail( 'mo2f_gauth_configured', $user->ID );
						if ( $secret_configured ) {
							$this->mo2f_GAuth_update_option( 'mo2f_gauth_enable', 1 );
							$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Your Settings are saved successfully' );
							$this->mo_GAuth_show_success_message();
						} else {
							$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Please Save Qr Code to Enable Two Factor.' );
							$this->mo_GAuth_show_error_message();
						}
					} else {
						$this->mo2f_GAuth_update_option( 'mo2f_gauth_enable', 0 );
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Your Settings are saved successfully' );
						$this->mo_GAuth_show_success_message();
					}
					
				}
				if ( isset( $_POST['option'] ) && $_POST['option'] == "mo2f_gauth_save_settings" ) {
					
					$val = isset( $_POST['mo2f_gauth_login_page'] ) ? sanitize_text_field( $_POST['mo2f_gauth_login_page'] ) : 0;
					$this->mo2f_GAuth_update_option( 'mo2f_gauth_login_page', $val );
					
					
				}
				if ( isset( $_POST['option'] ) && $_POST['option'] == 'mo_GAuth_skip_feedback' ) {
					$subject="Feedback [Feedback Skipped]: miniOrange Google Authenticator Plugin";
					$message='Plugin Deactivated:';
					
					$email = $Mo_gauthdbQueries->get_user_detail( 'mo_gauth_user_email', $user->ID );
					if ( $email == '' ) {
						$email = $user->user_email;
					}
					//only reason
					
					$contact_us = new CustomerGauth();
					$submited   = json_decode( $contact_us->send_email_alert( $email, $message,$subject ), true );
					if ( json_last_error() == JSON_ERROR_NONE ) {
						if ( is_array( $submited ) && array_key_exists( 'status', $submited ) && $submited['status'] == 'ERROR' ) {
							$this->mo2f_GAuth_update_option( 'mo_gauth_message', $submited['message'] );
							
						} else {
							if ( $submited == false ) {
								$this->mo2f_GAuth_update_option( 'mo_gauth_message', 'Your query could not be submitted. Please try again.' );
								
							} else {
								$this->mo2f_GAuth_update_option( 'mo_gauth_message', 'Thanks for getting in touch! We shall get back to you shortly.' );
								$this->mo_GAuth_show_success_message();
							}
						}
					}
					deactivate_plugins( '/miniorange-google-authenticator/mo_gauth_login.php' );
					
				}
				if ( isset( $_POST['mo_GAuth_feedback'] ) && $_POST['mo_GAuth_feedback'] == 'mo_GAuth_feedback' ) {
					$subject="Feedback: miniOrange Google Authenticator Plugin";
					$message = 'Plugin Deactivated:';
					if ( isset( $_POST['query_feedback'] ) ) {
						if ( $_POST['query_feedback'] == '' ) {
							// feedback add
							
							$email = $Mo_gauthdbQueries->get_user_detail( 'mo_gauth_user_email', $user->ID );
							if ( $email == '' ) {
								$email = $user->user_email;
							}
							//only reason
							
							$contact_us = new CustomerGauth();
							$submited   = json_decode( $contact_us->send_email_alert( $email, $message,$subject ), true );
							if ( json_last_error() == JSON_ERROR_NONE ) {
								if ( is_array( $submited ) && array_key_exists( 'status', $submited ) && $submited['status'] == 'ERROR' ) {
									$this->mo2f_GAuth_update_option( 'mo_gauth_message', $submited['message'] );
									
								} else {
									if ( $submited == false ) {
										$this->mo2f_GAuth_update_option( 'mo_gauth_message', 'Your query could not be submitted. Please try again.' );
										
									} else {
										$this->mo2f_GAuth_update_option( 'mo_gauth_message', 'Thanks for getting in touch! We shall get back to you shortly.' );
										$this->mo_GAuth_show_success_message();
									}
								}
							}
							deactivate_plugins( '/miniorange-google-authenticator/mo_gauth_login.php' );
							// $this->mo2f_GAuth_update_option( 'mo_gauth_message', 'Please let us know the reason for deactivation so that we improve the user experience.' );
						} else {
							
							if ( $_POST['query_feedback'] != '' ) {
								$message .= ':' . sanitize_text_field( $_POST['query_feedback'] );
							}
							$email = $Mo_gauthdbQueries->get_user_detail( 'mo_gauth_user_email', $user->ID );
							if ( $email == '' ) {
								$email = $user->user_email;
							}
							//only reason
							
							$contact_us = new CustomerGauth();
							$submited   = json_decode( $contact_us->send_email_alert( $email, $message,$subject ), true );
							if ( json_last_error() == JSON_ERROR_NONE ) {
								if ( is_array( $submited ) && array_key_exists( 'status', $submited ) && $submited['status'] == 'ERROR' ) {
									$this->mo2f_GAuth_update_option( 'mo_gauth_message', $submited['message'] );
									
								} else {
									if ( $submited == false ) {
										$this->mo2f_GAuth_update_option( 'mo_gauth_message', 'Your query could not be submitted. Please try again.' );
										
									} else {
										$this->mo2f_GAuth_update_option( 'mo_gauth_message', 'Thanks for getting in touch! We shall get back to you shortly.' );
										$this->mo_GAuth_show_success_message();
									}
								}
							}
							deactivate_plugins( '/miniorange-google-authenticator/mo_gauth_login.php' );
							
						}
						
					} else {
						
						$this->mo2f_GAuth_update_option( 'mo_gauth_message', 'Please Give reasons for deactivation' );
						
					}
					
				}
				
				if ( isset( $_POST['option'] ) && $_POST['option'] == "mo2f_gauth_reset_key" ) {
					
					$nonce = $_POST['mo2f_google_auth_reset_nonce'];
					if ( ! wp_verify_nonce( $nonce, 'mo2f-gauth-auth-reset-nonce' ) ) {
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid Request!' );
						$this->mo_GAuth_show_error_message();
						
						return;
					}
					$Mo_gauthdbQueries->update_user_details( $user_id, array( 'mo2f_gauth_key' => null ) );
					$Mo_gauthdbQueries->update_user_details( $user_id, array( 'mo2f_gauth_configured' => 0 ) );
					$this->mo2f_GAuth_update_option( 'mo2f_gauth_enable', 0 );
					$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'New QR Code generated and Two Factor has been disabled. Please scan and save the QR Code to enable Two Factor.' );
					$this->mo_GAuth_show_error_message();
				}
				
				
			}
			if ( isset( $_POST['option'] ) && $_POST['option'] == "mo_gauth_contact_us_query_option" ) {
				
				$nonce = $_POST['mo2f_GAuth_support_form_nonce'];
				
				if ( ! wp_verify_nonce( $nonce, 'mo2f-google-auth-support-form' ) ) {
					$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Invalid Request!' );
					$this->mo_GAuth_show_error_message();
					
					return;
				}
				if ( ! $this->mo_gauth_is_curl_installed() ) {
					$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'ERROR: PHP cURL extension is not installed or disabled. Query submit failed.' );
					$this->mo_GAuth_show_error_message();
					
					return;
				}
				
				// Contact Us query
				if ( filter_var( $_POST['mo_gauth_contact_us_email'], FILTER_VALIDATE_EMAIL ) &&
				     preg_match( '/[\+]\d{11,14}|[\+]\d{1,4}[\s]\d{8,10}/', $_POST['mo_gauth_contact_us_phone'] ) &&
				     preg_match( "/^[^±!£$%&*_§¡€#¢§¶•ªº«\\/<>;|=,{}]{1,30}$/", $_POST['mo_gauth_contact_us_query'] )
				) {
					$email = sanitize_email( $_POST['mo_gauth_contact_us_email'] );
					$phone = sanitize_text_field( $_POST['mo_gauth_contact_us_phone'] );
					$query = sanitize_text_field( $_POST['mo_gauth_contact_us_query'] );
				} else {
					$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Please match the requested format.' );
					$this->mo_GAuth_show_error_message();
					
					return;
				}
				
				$customer = new CustomerGauth();
				if ( $this->mo_GAuth_check_empty_or_null( $email ) || $this->mo_GAuth_check_empty_or_null( $query ) ) {
					$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Please fill up Email and Query fields to submit your query.' );
					$this->mo_GAuth_show_error_message();
				} else {
					$submited = $customer->submit_contact_us( $email, $phone, $query );
					if ( $submited == false ) {
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Your query could not be submitted. Please try again.' );
						$this->mo_GAuth_show_error_message();
					} else {
						$this->mo2f_GAuth_update_option( 'mo_gauth_msg', 'Thanks for getting in touch! We shall get back to you shortly.' );
						$this->mo_GAuth_show_success_message();
					}
				}
			}
			
			
		}
		
		public function mo_GAuth_show_error_message() {
			if ( is_multisite() ) {
				remove_action( 'network_admin_notices', array( $this, 'mo_GAuth_error_message' ) );
				add_action( 'network_admin_notices', array( $this, 'mo_GAuth_success_message' ) );
			} else {
				remove_action( 'admin_notices', array( $this, 'mo_GAuth_error_message' ) );
				add_action( 'admin_notices', array( $this, 'mo_GAuth_success_message' ) );
			}
		}
		
		public static function mo2f_check_number_length( $token ) {
			if ( is_numeric( $token ) ) {
				if ( strlen( $token ) >= 4 && strlen( $token ) <= 8 ) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		public function mo_GAuth_show_success_message() {
			
			if ( is_multisite() ) {
				remove_action( 'network_admin_notices', array( $this, 'mo_GAuth_success_message' ) );
				add_action( 'network_admin_notices', array( $this, 'mo_GAuth_error_message' ) );
			} else {
				remove_action( 'admin_notices', array( $this, 'mo_GAuth_success_message' ) );
				add_action( 'admin_notices', array( $this, 'mo_GAuth_error_message' ) );
			}
		}
		
		public function mo_gauth_is_curl_installed() {
			if ( in_array( 'curl', get_loaded_extensions() ) ) {
				return 1;
			} else {
				return 0;
			}
		}
		
		public function mo_GAuth_check_empty_or_null( $value ) {
			if ( ! isset( $value ) || empty( $value ) ) {
				return true;
			}
			
			return false;
		}
		
		public function miniorange_GAuth_menu() {
			//Add miniOrange gauth
			$user  = wp_get_current_user();
			$email = $user->user_email;
			if ( current_user_can( 'manage_options' ) && $this->mo2f_GAuth_get_option( 'mo2f_gauth_user_email' ) == $email ) {
				$page = add_menu_page( 'MO2F Google Authenticator', 'miniOrange Google Authenticator ', 'administrator', 'mo_view_page', array(
					$this,
					'mo_login_view'
				), plugin_dir_url( __FILE__ ) . 'includes/images/miniorange.png' );
			}
		}
		
		private function mo_multi_auth_show_success_message() {
			remove_action( 'network_admin_notices', array( $this, 'mo_GAuth_success_message' ) );
			add_action( 'network_admin_notices', array( $this, 'mo_GAuth_error_message' ) );
		}
		
		
	}
	
	new miniorange_GAuth;
?>