<?php function mo_gauth_display_feedback_form() {
    if ( 'plugins.php' != basename( $_SERVER['PHP_SELF'] ) ) {
        return;
    }
    $gauth            = new miniorange_GAuth();
    $mo_gauth_message = $gauth->mo2f_GAuth_get_option( 'mo_gauth_message' );
    wp_enqueue_style( 'wp-pointer' );
    wp_enqueue_script( 'wp-pointer' );
    wp_enqueue_script( 'utils' );
    
    ?>

    </head>
    <style>
        .mo_GAuth_modal {
            display: none;
            position: fixed;
            z-index: 1;
            padding-top: 100px;
            left: 100px;
            top: 0;
            margin-left: 220px;
            width: 50%;
            height: 100%;

        }


        .mo_GAuth_modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 55%;
        }

        .mo_GAuth_close {
            color: #aaaaaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .mo_GAuth_close:hover,
        .mo_GAuth_close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .alert {
            padding: 5px;
            margin-bottom: 10px;
            border: 1px solid transparent;
            border-radius: 4px
        }

        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1
        }
        .mo_GAuth_modal-content h3{
               text-align:center;
        }
    </style>
    <body>


    <!-- The Modal -->
    <div id="myModal" class="mo_GAuth_modal" style="display:none">

        <!-- Modal content -->
        <div class="mo_GAuth_modal-content">
            <span class="mo_GAuth_close">&times;</span>
            <h3>What Happened? </h3>
            <?php if ( $mo_gauth_message != '' ) { ?>
                <div style="padding:10px;">
                    <div class="alert alert-info" style="padding:10px;">
                        <p style="font-size:15px"><?php echo esc_html( $mo_gauth_message ); ?></p>
                    </div>
                </div>
            <?php } ?>

            <form name="f" method="post" action="" id="mo_GAuth_feedback">
                <input type="hidden" name="mo_GAuth_feedback" value="mo_GAuth_feedback"/>

                <div>
                    <p style="margin-left:2%">
                        <br>
                        <textarea id="query_feedback" name="query_feedback" rows="5" style="width: 100%"
                                  placeholder="Tell us what happended!"></textarea>
                        <br><br>
                    <div class="mo_modal-footer" style="text-align: center">
                        <input type="submit" name="miniorange_feedback_submit"
                               class="button button-primary button-large" value="Submit"/>
                        <input type="button" name="miniorange_skip_feedback"
                               class="button button-primary button-large" value="Skip and Deactivate"
                               onclick="document.getElementById('mo_GAuth_feedback_form_close').submit();"/>
                        </p>
                    </div>

            </form>
            <form name="f" method="post" action="" id="mo_GAuth_feedback_form_close">
                <input type="hidden" name="option" value="mo_GAuth_skip_feedback"/>
            </form>

        </div>

    </div>
      
    <script>
      
        jQuery('a[aria-label="Deactivate Login with TOTP (Google Authenticator, Microsoft Authenticator)"]').click(function () {
// Get the mo_GAuth_modal

            

            var mo_GAuth_modal = document.getElementById('myModal');

// Get the button that opens the mo_GAuth_modal
            var btn = document.getElementById("myBtn");

// Get the <span> element that closes the mo_GAuth_modal
            var span = document.getElementsByClassName("mo_GAuth_close")[0];

// When the user clicks the button, open the mo_GAuth_modal 

            mo_GAuth_modal.style.display = "block";

            // jQuery('#myModal').mo_GAuth_modal('mo_GAuth_toggle');
            // When the user clicks on <span> (x), mo_GAuth_close the mo_GAuth_modal
            span.onclick = function () {
                mo_GAuth_modal.style.display = "none";
                // jQuery('#mo_GAuth_feedback_form_close').submit();
            }

            // When the user clicks anywhere outside of the mo_GAuth_modal, mo_GAuth_close it
            window.onclick = function (event) {
                if (event.target == mo_GAuth_modal) {
                    mo_GAuth_modal.style.display = "none";
                }
            }
           
             return false;
        });
    </script><?php
}

?>