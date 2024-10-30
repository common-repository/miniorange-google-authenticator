<?php function mo_GAuth_support() {
    
    $google = new miniorange_GAuth();
    $nonce  = wp_create_nonce( "mo2f-google-auth-support-form" );
    
    echo '<div class="mo_gauth_support_layout" >
		<div>
			<h3>Support</h3>
			<p>Need any help? We can help you with configuration or looking for a feature. Just send us a query and we will get back to you soon.</p>
			<form method="post" action="">
				<input type="hidden" name="option" value="mo_gauth_contact_us_query_option" />
				<input type="hidden" value="' . esc_html( $nonce ) . '" name="mo2f_GAuth_support_form_nonce"
               />
				<table class="mo_gauth_settings_table">
					<tr>
						<td><input style="width:95%" type="email" class="mo_gauth_table_textbox" required name="mo_gauth_contact_us_email" value="' . esc_html( $google->mo2f_GAuth_get_option( "mo_gauth_admin_email" ) ) . '" placeholder="Enter your email"></td>
					</tr>
					<tr>
						<td><input type="tel" style="width:95%" id="contact_us_phone" pattern="[\+]\d{11,14}|[\+]\d{1,4}[\s]\d{9,10}" class="mo_gauth_table_textbox" name="mo_gauth_contact_us_phone" value="' . esc_html( $google->mo2f_GAuth_get_option( "mo_gauth_admin_phone" ) ) . '" placeholder="Enter your phone"></td>
					</tr>
					<tr>
						<td><textarea id="query" name="mo_gauth_contact_us_query" style="resize: vertical;border-radius:4px;width:95.5%;height:143px;" onkeyup="mo_gauth_valid_query(this)" onblur="mo_gauth_valid_query(this)" onkeypress="mo_gauth_valid_query(this)" placeholder="Write your query here"></textarea></td>
						</tr>
				
				<tr>
				<td><input type="submit" name="submit" style="width:120px;" class="button button-primary button-large" value="Submit"/></td>
				</tr>
				</table>
				<br><br>
				<div>
				</div>
			</form>
		</div>
	</div>
	<script>
		jQuery("#contact_us_phone").intlTelInput();
		function mo_gauth_valid_query(f) {
			!(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(
					/[^a-zA-Z?,.\(\)\/@ 0-9]/, "") : null;
		}
		
	</script>';
}

