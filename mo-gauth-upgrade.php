<?php
    
    function mo_GAuth_upgrade( $user ) {
        
        $gauth_feature_set = array(
            "2FA on default Login Page",
            "Support wp forms, woocommerce",
            "Language Translation",
            "2FA on after login",
            "Backup codes",
            "Multisite Support",
            "Custom form Compatibility",
            "Inline Registration",
            "Rba Add-on",
            "Wordpress App password",
            "Unlimited Users",
            "Role based Two Factor",
            "Shortcodes for No Dashboard access",
            "Admin Disable Two Factor for users",
            "Custom Redirect based on roles",
            "UI customization"
        );
        
        $gauth_feature_set_with_plans_NC = array(
            
            "No. of Users"                       => array(
                "Site Based Pricing",
                "Site Based Pricing"
            ),
            "2FA on default Login Page"          => array( true, true ),
            "Language Translation"               => array( true, true ),
            "2FA on after login"                 => array( true, true ),
            "Multisite Support"                  => array( true, true ),
            "Backup codes"                       => array( true, true ),
            "Custom form Compatibility"          => array( true, true ),
            "Support wp forms, woocommerce"      => array( true, true ),
            "Inline Registration"                => array( false, true ),
            "Wordpress App password"             => array( false, true ),
            "Unlimited Users"                    => array( false, true ),
            "Role based Two Factor"              => array( false, true ),
            "Shortcodes for No Dashboard access" => array( false, true ),
            "Admin Disable Two Factor for users" => array( false, true ),
            "Custom Redirect based on roles"     => array( false, true ),
            "Rba Add-on"                         => array( false, true ),
            "UI customization"                   => array( false, true )
        );
        ?>
        <div class="gauth_licensing_plans">

            <table class="table gau_table-bordered table-center gau_table-striped">
                <thead>
                <tr class="gauth_licensing_plans_tr">
                    <th width="33%">
                        <h3><?php echo esc_html( 'Features \ Plans' ); ?></h3></th>
                    <th class="text-center" width="33%"><h3>Free</h3>

                        <p class="gauth_licensing_plans_plan_desc"><?php echo esc_html( 'Basic 2FA for Small Scale Web Businesses' ); ?></p>
                        <br></th>

                    <th class="text-center" width="33%"><h3><?php echo esc_html( 'Premium' ); ?></h3>

                        <p class="gauth_licensing_plans_plan_desc" style="margin:16px 0 26px 0   "><?php echo esc_html( 'Advanced and Intuitive
                        2FA for Large Scale Web businesses with enterprise-grade support' ); ?></p><span>
                    
                        
                        <h4 class="gauth_pricing_sub_header" style="padding-bottom:8px !important;"><a
                                    href="admin.php?page=mo_view_page&mo_gauth_tab=mo_support"
                                    class="button button-primary button-large"
                            ><?php echo esc_html( 'Contact Us' ); ?></a></h4>
                <br>
                </span></h3>
                    </th>

                </tr>
                </thead>
                <tbody class="gau_align-center gau-fa-icon">
                <?php
                    $countOfGauthFeatureSet = count( $gauth_feature_set );
                    for ( $i = 0; $i < $countOfGauthFeatureSet; $i ++ ) { ?>
                    <tr>
                        <td><?php
                                $feature_set = $gauth_feature_set[ $i ];
                                
                                echo esc_html( $feature_set );
                            ?></td>
                        
                        
                        <?php
                            $feature_set_plan = $gauth_feature_set_with_plans_NC[ $feature_set ]
                        ?>
                        <?php
                            if ( is_array( $feature_set_plan ) ) {
                                if ( $feature_set_plan[0] == true ) {
                                    ?>
                                    <td><img style="float: center;width: 25px;height: 21px"
                                             src="<?php echo esc_url( plugins_url( 'includes/images/tick.jpg"', __FILE__ ) ); ?>">
                                    </td> <?php
                                } else {
                                    ?>
                                    <td></td> <?php
                                }
                            } else {
                                echo "Not Array";
                            }
                            
                            if ( is_array( $feature_set_plan ) ) {
                                if ( $feature_set_plan[1] == true ) {
                                    ?>
                                    <td><img style="float: center;width: 25px;height: 21px"
                                             src="<?php echo esc_url( plugins_url( 'includes/images/tick.jpg"', __FILE__ ) ); ?>">
                                    </td> <?php
                                } else {
                                    ?>
                                    <td></td> <?php
                                }
                            } else {
                                echo "Not Array";
                            } ?>

                    </tr>
                <?php } ?>

                </tbody>
            </table>
            <br>
            <div style="padding:10px;">
                <br>
                <hr>
                <br>
                <div>
                    <h2>Refund Policy</h2>
                    <p class="gauth_licensing_plans_ol"><?php echo esc_html( 'At miniOrange, we want to ensure you are 100% happy with your purchase. If the premium plugin you purchased is not working as advertised and you\'ve attempted to resolve any issues with our support team, which couldn\'t get resolved then we will refund the whole amount within 10 days of the purchase.' ); ?>
                    </p>
                </div>
                <br>
                <hr>
                <br>
                <div>
                    <h2><?php echo esc_html( 'Contact Us' ); ?></h2>
                    <p class="gauth_licensing_plans_ol"><?php echo esc_html( 'If you have any doubts regarding the licensing plans, you can mail us at' ); ?>
                        <a href="mailto:info@miniorange.com"><i><?php echo esc_html( 'info@miniorange.com' ); ?></i></a> <?php echo esc_html( 'or submit a query using the support form.' ); ?>
                    </p>
                </div>
                <br>
                <hr>
                <br>

                <form class="mo_gauth_display_none_forms" id="gauth_loginform"
                      action="<?php echo esc_html( get_option( 'mo_gauth_host_name' ) . '/moas/login' ); ?>"
                      target="_blank" method="post">
                    <input type="email" name="username" value="<?php echo esc_html( get_option( 'mo2f_email' ) ); ?>"/>
                    <input type="text" name="redirectUrl"
                           value="<?php echo esc_html( get_option( 'mo_gauth_host_name' ) . '/moas/initializepayment' ); ?>"/>
                    <input type="text" name="requestOrigin" id="requestOrigin"/>
                </form>
                <script>
                    function gauth_upgradeform(planType) {
                        jQuery('#requestOrigin').val(planType);
                        jQuery('#mgauth_loginform').submit();
                    }
                    
                </script>

                <style>#gauth_support_table {
                        display: none;
                    }

                </style>
            </div>
        </div>
    
    <?php }
    
    function gauth_create_li( $gauth_array ) {
        $html_ol = '<ul>';
        foreach ( $gauth_array as $element ) {
            $html_ol .= "<li>" . $element . "</li>";
        }
        $html_ol .= '</ul>';
        
        return $html_ol;
    }
    
    function gauth_yearly_premium_pricing() {
        ?>
        <p class="gauth_pricing_text"
           id="gauth_yearly_sub"><?php echo esc_html__( 'Yearly Subscription Fees', 'miniorange-2-factor-authentication' ); ?>

            <select id="gauth_yearly" class="form-control" style="border-radius:5px;width:200px;">
                <option> <?php echo esc_html( '1 Site   - $19 per year' ); ?> </option>
                <option> <?php echo esc_html( '2 Sites  - $35 per year' ); ?> </option>
                <option> <?php echo esc_html( '3 Sites  - $50 per year' ); ?> </option>
                <option> <?php echo esc_html( '4 Sites  - $64 per year' ); ?> </option>
                <option> <?php echo esc_html( '5 Sites  - $77 per year' ); ?> </option>
                <option> <?php echo esc_html( '6 Sites  - $89 per year' ); ?> </option>
                <option> <?php echo esc_html( '7 Sites  - $99 per year' ); ?> </option>
                <option> <?php echo esc_html( '8 Sites  - $109 per year ' ); ?></option>
                <option> <?php echo esc_html( '9 Sites  - $119 per year' ); ?> </option>
                <option> <?php echo esc_html( '10 Sites - $129 per year' ); ?> </option>
            </select>
        </p>
        <?php
    }
    
    function gauth_get_binary_equivalent( $gauth_var ) {
        
        switch ( $gauth_var ) {
            case 1:
                return "<i class='fa fa-check'></i>";
            case 0:
                return "";
            default:
                return $gauth_var;
        }
    }

?>