<?php
    
    function mo2f_GAuth_otp_prompt( $login_message, $redirect_to ) {
        ?>

        <html>
        <head>
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <?php
                mo2f_gauth_echo_js_css_files();
            ?>
        </head>
        <body>
        <div class="mo_gauth_modal" tabindex="-1" role="dialog">
            <div class="mo_gauth-modal-backdrop"></div>
            <div style="margin-left:30% !important;margin-top:5% !important;"
                 class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
                <div class="mo_customer_validation-modal-content">
                    <div class="mo_gauth_modal-header">
                        <h4 class="mo_gauth_modal-title">
                            <button type="button" class="mo_gauth_close" data-dismiss="modal" aria-label="Close"
                                    title="<?php echo esc_html( 'Back to login' ); ?>"
                                    onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
                            <?php echo esc_html( 'Validate OTP' ); ?>
                        </h4>
                    </div>
                    <div class="mo_gauth_modal-body center">
                        <?php if ( isset( $login_message ) && ! empty( $login_message ) ) { ?>
                            <div id="otpMessage">
                                <p><?php echo esc_html( $login_message ); ?></p>
                            </div>
                        <?php } ?>
                        <br>
                        <div id="showOTP">
                            <div class="mo_gauth-login-container">
                                <form name="f" id="mo_gauth_submitotp_loginform" method="post">
                                    <center>
                                        <input type="text" name="mo_gautha_softtoken" style="height:28px !important;"
                                               placeholder="<?php echo esc_html( 'Enter code' ); ?>"
                                               id="mo_gautha_softtoken" required="true" class="mo_otp_token"
                                               autofocus="true"
                                               
                                               title="<?php echo esc_html( 'Only digits within range 4-8 are allowed.' ); ?>"/>
                                    </center>
                                    <br>
                                    <input type="submit" name="miniorange_otp_token_submit"
                                           id="miniorange_otp_token_submit"
                                           class="miniorange_otp_token_submit"
                                           value="<?php echo esc_html( 'Validate' ); ?>"/>

                                    <input type="hidden" name="miniorange_soft_token_nonce"
                                           value="<?php echo esc_html( wp_create_nonce( 'miniorange-gauth-soft-token-nonce' ) ); ?>"/>
                                    <input type="hidden" name="redirect_to"
                                           value="<?php echo esc_html( $redirect_to ); ?>"/>
                                </form>
                                <br>

                            </div>
                        </div>
                        </center>
                        <?php mo_gauth_customize_logo(); ?>
                    </div>
                </div>
            </div>
        </div>
        <form name="f" id="mo_gauth_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>"
              class="mo_gauth_display_none_forms">
            <input type="hidden" name="miniorange_mobile_validation_failed_nonce"
                   value="<?php echo esc_html( wp_create_nonce( 'miniorange-gauth-mobile-validation-failed-nonce' ) ); ?>"/>
        </form>


        <script>

            function mologinback() {
                jQuery('#mo_gauth_backto_mo_loginform').submit();
            }

            function mologinforgotphone() {
                jQuery('#mo_gauth_show_forgotphone_loginform').submit();
            }
        </script>
        </body>
        </html>
        <?php
    }
    
    
    function mo_GAuth_user_profile( $secret, $url, $otpcode ) {
        $google = new miniorange_GAuth();
        ?>

        <div class="mo_gauth_table_layout">

            <h3><span style="
                <?php
                    if ( miniorange_GAuth::mo2f_GAuth_get_option( 'mo2f_gauth_enable' ) ) {
                        echo 'color:Green';
                    } else {
                        echo 'color:RED;';
                    }
                ?>
                        "><b style="font-weight:bold;"><span
                                style="color:black;">TWO FACTOR - </span>
                                <?php
                                    if ( miniorange_GAuth::mo2f_GAuth_get_option( 'mo2f_gauth_enable' ) ) {
                                        echo 'ENABLED';
                                    } else {
                                        echo 'DISABLED';
                                    }
                                ?>
                        </b></span><label class="switch" style="float:right;">
                    <form name="f" id="login_settings_form" method="post" action="">

                        <input type="checkbox" id="mo2f_gauth_enable" name="mo2f_gauth_enable"
                               value="1" <?php checked( $google->mo2f_GAuth_get_option( 'mo2f_gauth_enable' ) == 1 ); ?>
                               onclick="mo2f_enable_2fa();">
                        <span class="slider round" style="margin-bottom:2%;"></span>
                </label>
                <input type="hidden" name="mo2f_enable_two_factor_nonce"
                       value="<?php echo esc_html( wp_create_nonce( 'mo2f-enable-two-factor-nonce' ) ); ?>"/>
                <input type="hidden" name="option" value="mo2f_gauth_save_twofactor"/>
                </form>
            </h3>
            <hr>


            <h3>Step 1. Install Google Authenticator App.</h3>
            <ol style="margin-left:6%">
                <li><?php echo esc_html( 'Navigate to App Store in your phone.' ); ?>
                </li>
                <li><?php echo esc_html( 'Search for Google Authenticator.' ); ?>
                    <b>Android</b>: <a
                            href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"
                            target="_blank"><?php echo esc_html( 'Play Store ' ); ?></a>&nbsp; <b>iPhone</b>: <a
                            href="http://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8"
                            target="_blank"><?php echo esc_html( 'App Store' ); ?></a>.
                </li>

            </ol>

            <hr>

            <h4 style="font-size:17px;margin-bottom:1%;"><span style="color:black;">Step 2. Open Google Authenticator App and Scan Qr code.</span><label
                        style="float:right;margin-top:-1%;">
                    <form name="f" id="mo2f_gauth_reset_form" method="post" action="">
                        <input type="hidden" name="option" value="mo2f_gauth_reset_key"/>
                        <input type="hidden" name="mo2f_google_auth_reset_nonce"
                               value="<?php echo esc_html( wp_create_nonce( 'mo2f-gauth-auth-reset-nonce' ) ); ?>"/>
                        <input type="submit" name="submit" value="Generate New QR code" style="float:right;"
                               class="button button-primary button-large"/>
                    </form>
                </label>
            </h4>


            <div style="margin-left:4%;">

                <br>

                <div class="mo2f_gauth_column mo2f_gauth_left">
                    <div class="mo2f_gauth"
                         data-qrcode='<?php echo esc_html( $url ); ?>'
                         style="float:left;margin-left:30%;">
                    </div>


                </div>


                <div>

                    <a class="btn btn-link" data-toggle="collapse" style="font-size:15px;" href="#preview"
                       aria-expanded="false"><?php echo esc_html( 'Can\'t scan QR code?' ); ?></a>

                    <div class="mo_gauth_collapse" id="preview" style="height:200px;">

                        <ol class="mo2f_ol">
                            <li><?php echo esc_html( 'Tap on Menu and select' ); ?>
                                <b> <?php echo esc_html( ' Set up account ' ); ?></b>.
                            </li>
                            <li><?php echo esc_html( 'Select' ); ?>
                                <b> <?php echo esc_html( ' Enter provided key ' ); ?></b>.
                            </li>
                            <li><?php echo esc_html( 'For the' ); ?>
                                <b> <?php echo esc_html( ' Enter account name ' ); ?></b>
                                <?php echo esc_html( 'field, type your preferred account name' ); ?>.
                            </li>
                            <li><?php echo esc_html( 'For the' ); ?>
                                <b> <?php echo esc_html( ' Enter your key ' ); ?></b>
                                <?php echo esc_html( 'field, type the below secret key' ); ?>:
                            </li>

                            <div class="mo_gauth_google_authy_secret_outer_div">
                                <div class="mo_gauth_google_authy_secret_inner_div">
                                    <?php echo esc_html( $secret ); ?>
                                </div>

                            </div>
                            <li><?php echo esc_html( 'Key type: make sure' ); ?>
                                <b> <?php echo esc_html( ' Time-based ' ); ?></b>
                                <?php echo esc_html( ' is selected' ); ?>.
                            </li>

                            <li><?php echo esc_html( 'Tap Add.' ); ?></li>
                        </ol>


                    </div>
                </div>
            </div>
            <br>
            <hr>
            <h4 style="font-size:17px;margin-bottom:1%;">Step 3. Enter the OTP and Save Two Factor</h4>
            <div style="margin-left:3%;">
                <?php echo esc_html( 'Now an account will be added displaying' ); ?>
                <b><?php echo esc_html( 'Time Based OTP' ); ?></b>. <?php echo esc_html( 'Please Enter the Time Passcode and Save.' ); ?>

                <br><br>

                <form name="f" id="mo2f_gauth_test_otp" method="post" action="">
                    <input type="text" class="mo_gauth_table_textbox" style="width:18% !important;"
                           name="mo2f_gauth_otp" pattern="[0-9]{4,8}" placeholder="Enter the Code"/>
                    <input type="hidden" name="mo2f_google_test_otp_nonce"
                           value="<?php echo esc_html( wp_create_nonce( 'mo2f-gauth-test-otp-nonce' ) ); ?>"/>
                    <input type="hidden" name="mo2f_gauth_save_secret" value="<?php echo esc_html( $secret ); ?>"/>&nbsp;&nbsp;
                    <input type="hidden" name="option" value="mo2f_gauth_test_otp"/>&nbsp;&nbsp;
                    <input type="submit" name="submit" value="Save" class="button button-primary button-large"/>

                </form>
            </div>
            <br>
            
            
            <?php
                wp_register_script( 'jquery-qrcode', esc_url( plugins_url( '/includes/jquery-qrcode/jquery-qrcode.js', __FILE__ ) ), array(), '1.0.8', false );
                wp_register_script( 'jquery-qrcode-min', esc_url( plugins_url( '/includes/jquery-qrcode/jquery-qrcode.min.js', __FILE__ ) ), array(), '1.0.8', false );
                
                echo '<head>';
                wp_print_scripts( 'jquery-qrcode' );
                wp_print_scripts( 'jquery-qrcode-min' );
                echo '</head>';
                echo '<script>';
                echo 'jQuery(document).ready(function() {';
                echo "jQuery('.mo2f_gauth').qrcode({
								'render': 'image',
								size: 235,
								'text': jQuery('.mo2f_gauth').data('qrcode')
							});";
                echo '});';
                echo '</script>';
            
            ?>
            <br>
        </div>

        <script>
            function mo2f_enable_2fa() {

                jQuery("#login_settings_form").submit();

            }
        </script>
        <?php
        
        
    }
    
    function mo_Gauth_settings() {
        $google = new miniorange_GAuth();
        if ( current_user_can( 'manage_options' ) ) {
            
            echo '<div class="mo_gauth_table_layout" style="width:93%;min-height:200px;">
                <h3>' . esc_html( 'Advanced Settings' ) . '</h3>
                <hr>

                <form name="f" method="post" id="mo2f_gauth_login_page_form" action="">
                    <input type="hidden" name="option" value="mo2f_gauth_save_settings"/>

                    <h4 style="font-size:15px;">' . esc_html( 'Show Second Factor' ) . '</h4>

                    <div style="margin-left:2%;">
                        <input type="radio" name="mo2f_gauth_login_page" value="1"
                            ' . checked( ($google->mo2f_GAuth_get_option( 'mo2f_gauth_login_page' ) == 1), true, false ) . '/>
            ' . esc_html( 'Ask Second Factor on login Page' ) . '
                        <a class="btn btn-link"
                           data-toggle="collapse"
                           href="#preview2"
                           aria-expanded="false">(See preview)</a><br>
                        <div class="mo_gauth_collapse" id="preview2" style="height:300px;">
                            <center><br>
                                <img style="height:300px;"
                                     src="' . esc_url( plugins_url( 'includes/images/Default_login_page_with_Two_factor.png"', __FILE__ ) ) . '">
                            </center>
                        </div>
                        <br>
                        <input type="radio" name="mo2f_gauth_login_page" value="0"
            ' . checked( ($google->mo2f_GAuth_get_option( 'mo2f_gauth_login_page' ) == 0), true, false ) . ' />
            ' . esc_html( 'Ask Second Factor on Next Page' ) . '
                        <a class="btn btn-link"
                           data-toggle="collapse"
                           href="#preview3"
                           aria-expanded="false">(See preview)</a><br>
                        <div class="mo_gauth_collapse" id="preview3" style="height:175px;">
                            <center><br>
                                <img style="height:175px;width:100%;"
                                     src="' . esc_url( plugins_url( 'includes/images/Two_factor_new_page.png"', __FILE__ ) ) . '">
                            </center>
                        </div>

                    </div>
                </form>


                <h4 style="font-size:15px;">Set the account name</h4>
                <hr>';
            
            if ( isset( $_SESSION['mo2f_gauth_issuer_set'] ) ) {
                echo '<div style=" padding: 16px;background-color:rgba(1, 145, 191, 0.117647);color: black;">
                        <span style=" margin-left: 15px;color: black;font-weight: bold;float: right;font-size: 22px;line-height: 20px;cursor: pointer;font-family: Arial;transition: 0.3s"></span>Please
                        Scan the Qr code Again.
                    </div>    <br>';
            }
            echo esc_html( 'Change the account name in your Authenticator App.' ) . '<a
                        class="btn btn-link"
                        data-toggle="collapse"
                        href="#preview1"
                        aria-expanded="false">' . esc_html( '(See preview)' ) . '</a>.

                <div class="mo_gauth_collapse" id="preview1" style="height:300px;">
                    <center><br>
                        <img style="height:300px;"
                             src="' . esc_url( plugins_url( 'includes/images/miniOrangeAuth_appname.jpg"', __FILE__ ) ) . '">

                    </center>
                </div>
                <br><br>
                <form name="f" id="login_settings_appname_form" method="post" action="">
                    <input type="hidden" name="option" value="mo2f_gauth_appname"/>
                    <input type="hidden" name="mo2f_google_auth_nonce"
                           value="' . esc_html( wp_create_nonce( 'mo2f-gauth-app-name-change-nonce' ) ) . '"/>
            <input type="text" class="mo_gauth_table_textbox" style="width:27% !important;"
                   name="mo2f_gauth_issuer" placeholder="Enter the app name"
                   pattern="^[^±!£$%^&*_+§¡€#¢§¶•ªº«\\/<>?:;|=,{}]{1,30}$"
                   value="' . esc_html( $google->mo2f_GAuth_get_option( 'mo2f_gauth_issuer' ) ) . '"/>&nbsp;&nbsp;&nbsp;

            <input type="submit" name="submit" value="Change App Name"
                   class="button button-primary button-large"/>

            <br>
            </form>


            </form>';
            
            global $Mo_gauthdbQueries;
            $user = wp_get_current_user();
            
            $backup_codes = json_decode( $Mo_gauthdbQueries->get_user_detail( 'mo2f_gauth_backup_codes', $user->ID ), true );
            $count        = 0;
            if ( ! is_null( $backup_codes ) ) {
                $count = count( $backup_codes );
            }
            if ( $count > 0 && ! empty( $backup_codes ) ) {
                $str = 'Codes Remaining: ' . $count;
            } else {
                $str = 'No Backup Code';
            }
            
            
            echo '<h3>' . esc_html( 'Backup Codes' ) . '
                    <div class="gauth_tooltip">&#9432;<span
                                class="gauth_tooltiptext">' . esc_html( 'When can you use this codes?<br>You can use these codes if your phone is lost. Enter the code in place of OTP during login in case of any issue.' ) . '</span>
                    </div>
                    <label style="float:right;color:red;cursor:default;">' . esc_html( $str ) . '
                    </label>
                </h3>
                <hr>';
            if ( isset( $_SESSION['mo2f_gauth_backup_delete_set'] ) ) {
                echo '<div id="backup_code_delete_msg"
                         style=" padding: 16px;background-color:rgba(1, 145, 191, 0.117647);color: black;">
                        <span style=" margin-left: 15px;color: black;font-weight: bold;float: right;font-size: 22px;line-height: 20px;cursor: pointer;font-family: Arial;transition: 0.3s"></span>Your
                        codes are deleted. Please <b>Generate Codes</b> again.
                    </div>';
            }
            echo '<div id="backup_code_msg"
                 style=" padding: 16px;background-color:rgba(1, 145, 191, 0.117647);color: black;" hidden>
                <span style=" margin-left: 15px;color: black;font-weight: bold;float: right;font-size: 22px;line-height: 20px;cursor: pointer;font-family: Arial;transition: 0.3s"></span>' . esc_html( 'You can use these codes as a backup method to login to your site, in case your phone is not with you.' ) . '
            </div>
            <p>' . esc_html( 'Each code can be used only once. Just enter them in place of the Google Authenticator code to gain access.' ) . '</p>

            <div>

                <form name="f" method="post" id="mo2f_gauth_users_backup" action="">

                    <input type="hidden" name="option" value="mo2f_gauth_users_backup"/>
                    <input type="hidden" name="mo2f_google_auth_backup_code_nonce"
                           value="' . esc_html( wp_create_nonce( 'mo2f-gauth-backup_code-nonce' ) ) . '"/>
                    <input type="button" name="back" id="back_btn" style="margin-left:10px;float:left;"
                           class="button button-primary button-large" onclick="backup_code_generation();"
                           value="Generate Codes"/>
                </form>

                <form name="f" method="post" id="mo2f_gauth_backup_delete" action="">

                    <input type="hidden" name="option" value="mo2f_gauth_backup_delete"/>
                    <input type="hidden" name="mo2f_google_auth_backup_code_delete_nonce"
                           value="' . esc_html( wp_create_nonce( 'mo2f-gauth-backup-code-delete-nonce' ) ) . '"/>
                    <input type="submit" name="Delete Codes" id="mo2f_delete_codes"
                           style="margin-left:10px;float:left;" class="button button-primary button-large"
                           value="' . esc_html( 'Delete Codes' ) . '"><br>

                </form>

            </div>
            <br/>
            </div><br>
            
            <script>
                jQuery("input[type=radio][name=mo2f_gauth_login_page]").change(function () {
                    jQuery("#mo2f_gauth_login_page_form").submit();
                });

                function backup_code_generation() {
                    jQuery("#backup_code_msg").show();
                    jQuery("#backup_code_delete_msg").hide();
                    jQuery("#mo2f_gauth_users_backup").submit();
                }


            </script>';
      
            if ( isset( $_SESSION['mo2f_gauth_backup_delete_set'] ) ) {
                unset( $_SESSION['mo2f_gauth_backup_delete_set'] );
            }
            
            if ( isset( $_SESSION['mo2f_gauth_issuer_set'] ) ) {
                unset( $_SESSION['mo2f_gauth_issuer_set'] );
            }
        }
        
    }
   
    function mo2f_gauth_echo_js_css_files() {
        wp_register_script( 'bootstrap-min-js', esc_url( plugins_url( '/includes/js/bootstrap.min.js', __FILE__ ) ), array(), '4.3.1', true );

        wp_register_style( 'bootstrap-min', esc_url( plugins_url( 'includes/css/bootstrap.min.css', __FILE__ ) ), array(), '1.0.8', 'all' );
        wp_register_style( 'front-end-login', esc_url( plugins_url( 'includes/css/front_end_login.css', __FILE__ ) ), array(), '1.0.8', 'all' );
        wp_register_style( 'two-factor', esc_url( plugins_url( 'includes/css/two_factor.css', __FILE__ ) ), array(), '1.0.8', 'all' );
        wp_register_style( 'hide-login', esc_url( plugins_url( 'includes/css/hide-login.css', __FILE__ ) ), array(), '1.0.8', 'all' );
	
	    wp_enqueue_script('jquery');
        wp_print_scripts('bootstrap-min-js');
        
        wp_print_styles( 'bootstrap-min' );
        wp_print_styles( 'front-end-login' );
        wp_print_styles( 'two-factor' );
        wp_print_styles( 'hide-login' );

    }
    
    function mo_gauth_customize_logo() {
        ?>
        <div style="float:right;"><a target="_blank" href="http://miniorange.com/2-factor-authentication"><img
                        alt="logo"
                        src="<?php echo esc_url( plugins_url( '/includes/images/miniOrange2.png', __FILE__ ) ); ?>"/></a>
        </div>
        
        <?php
    }

?>
