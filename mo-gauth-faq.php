<?php
    function mo_GAuth_faq( $mo_expand_value = null ) {
        ?>
        <div class="mo_gauth_table_layout" style="min-height:394px;">

            <br>
            <ul class="mo_gauth_faqs">
                <?php if ( current_user_can( 'manage_options' ) ) {
                    
                    $Faqs  = array(
                        "Invalid OTP ...Possible causes ",
                        "How to enable PHP cURL extension? (Pre-requisite)",
                        "Lockout Issues",
                        "My Users are not being prompted for 2-factor during login. Why?",
                        "I want to go back to default login with password",
                        "I have a custom / front-end login page on my site and I want the look and feel to remain the same when I add 2 factor ?",
                    );
                    
                    function answers($question){
                        switch($question){
                            case "Question0":
                                echo "<ol>
                                            <br>
                                            <li>You mis-typed the OTP, find the OTP again and type it.</li>
                                            <li>Your phone time is not in sync with miniOrange servers.</li>
                                            <li>How to sync?</li>
                                            <ul ><li>In the app, tap on Settings icon and then press Sync button.</li></ul>
                                       </ol>
                                        <br>";
                                break;
                                
                            case "Question1":
                                echo "cURL is enabled by default but in case you have disabled it, follow the below steps to enable it.
                                        <ol>
                                            <br>
                                            <li>Open php.ini(it's usually in /etc/ or in php folder on the server).</li>
                                            <li>Search for extension=php_curl.dll. Uncomment it by removing the semi-colon( ; ) in front of it.</li>
                                            <li>Restart the Apache Server.</li>
                                        </ol>
                                        <br>";
                                break;
                            case "Question2":
                                echo "You can obtain access to your website by one of the below options:
                                        <ol><br><li>If you have an additional administrator account whose Two Factor is not enabled yet, you can login with it.</li>
                                            
                                            <li>Rename the plugin from FTP - this disables the 2FA plugin and you will be able to login with your Wordpress username and password.</li>
                                            <li>Go to WordPress Database. Select wp_user_meta, search for mo2f_gauth_configured key and update its value to 0. Two Factor will get disabled.</li>
                                        </ol><br>";
                                break;
                            case "Question3":
                                echo " <ul><li>The free plugin provides the 2-factor functionality for one user(Administrator) forever. To enable 2FA for more users, please contact <a href='mailto:info@miniorange.com'>info@miniorange.com</a> or write a Query on Support form on right side of your screen.</li></ul>";
                                break;
                            case "Question4":
                                echo "<ul><li>You can disable Two Factor from Setup Two Factor Tab by unchecking <b>Enable Two Factor Authentication</b> checkbox.</li></ul>";
                                break;
                            case "Question5":
                                echo "Our plugin works with most of the custom login pages. However, we do not claim that it will work with all the customized login pages.<br> In such cases, You can submit a query to us from Support section to the right for more details.";
                                break;
                            
                            default:
                                echo "Please reload the page to see the answers.";
                                break;
                            
                        }
                    }
                    
                    $count = 0;
                    foreach ( $Faqs as $questions ) {
                        ?>
                        <h3><a data-toggle="collapse" href="#<?php echo esc_html( "Question" . $count ); ?>"
                               aria-expanded="false">
                                <li><?php echo esc_html( $questions ); ?></li>
                            </a></h3>
                        <div class="mo_gauth_collapse" id="<?php echo esc_html( "Question" . $count ); ?>">
                            <?php answers("Question" . $count); ?>
                        </div>
                        <hr>
                        <?php $count ++;
                    } ?>


                    <h3>
                        <a><?php echo esc_html( 'For any other query/problem/request, please feel free to submit a query in our support section on right hand side. We are happy to help you and will get back to you as soon as possible.' ) ?></a>
                    </h3>
                
                
                <?php } ?>


            </ul>


        </div>
    
    <?php } ?>