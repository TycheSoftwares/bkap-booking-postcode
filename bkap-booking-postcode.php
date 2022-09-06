<?php 
/*
Plugin Name: Postcode Addon for Booking & Appointment Plugin for WooCommerce 
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin
Description: This plugin allow you set postcode for the weekdays of Booking & Appointment Plugin.
Version: 1.0
Author: Tyche Softwares
Author URI: http://www.tychesoftwares.com/
*/

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * bkap_booking_with_pos class
 **/
if (!class_exists('Bkap_Postcode')) {

    class Bkap_Postcode {

		public $display_booking_fields           = 'NO';
		
		public  $selected_week_days_for_post_code = array();
		
		public function __construct() {

        session_start();
		     
		    add_action( 'admin_enqueue_scripts',                   array( &$this, 'bkap_my_enqueue_scripts_css_for_postcode' ) );
		    add_action( 'bkap_add_global_settings_tab',            array( &$this, 'bkap_add_postcode_tab' ) );
		    add_action( 'wp_enqueue_scripts',                   	array( &$this, 'bkap_front_side_postcode_css' ) );
		    add_action( 'wp_enqueue_scripts',                   	array( &$this, 'bkap_front_side_postcode_js' ) );
		    
		    /* product page action & fileters */
		    add_action( 'woocommerce_before_single_product',       array( &$this, 'bkap_front_side_postcode_css' ) );
		    add_action( 'bkap_create_postcode_view',               array( &$this, 'bkap_create_postcode_view' ) );
			add_filter( 'bkap_postcode_display_booking_field',     array( &$this, 'bkap_postcode_display_booking_field' ) );
			add_action( 'bkap_create_postcode_modal',              array( &$this, 'bkap_create_postcode_modal' ) );
			add_action( 'bkap_create_postcode_field_before_field', array( &$this, 'bakp_create_postcode_field_before_field' ), 1 );
			add_filter( 'bkap_change_postcode_weekdays',           array( &$this, 'bakp_change_postcode_weekdays' ), 10, 3 );
			
			add_action( 'woocommerce_checkout_process',            array( &$this, 'bkap_validate_post_code' ) );
			
			add_action( 'woocommerce_after_checkout_billing_form', array( &$this, 'bkap_populate_postcode' ), 10 );
			add_action( 'init',                                    array( &$this, 'bkap_postcode_load_ajax' ) );


            add_filter( 'wcsatt_single_product_one_time_option_description', array( $this, 'bkap_one_time_purchase_label' ), 10, 3 );

            add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'bkap_remove_hooks' ), 8 );

            add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'bkap_display_weekdays' ), 12 );
            add_action( 'woocommerce_before_add_to_cart_button',         array( &$this, 'bkap_sub_range' ), 12);  
            add_action( 'woocommerce_before_add_to_cart_button' ,  array( &$this, 'bkap_add_sub_end_date' ), 13, 1 );  
			add_filter( 'woocommerce_checkout_create_subscription', array( &$this, 'bkap_modify_subscription_meta' ), 10, 2 );
			add_filter( 'woocommerce_add_cart_item_data', array( &$this, 'bkap_add_subscription_data' ), 10, 2 );
			add_filter( 'wcsatt_single_product_options', array( $this, 'bkap_single_product_options' ),10, 3 );

      add_action( 'woocommerce_before_cart', array( $this, 'bkap_create_postcode_view' ) );
      add_action( 'woocommerce_before_checkout_form', array( $this, 'bkap_create_postcode_view' ) );

      
			
		}

		function bkap_single_product_options( $options, $subscription_schemes, $product ) {

			foreach( $options as $key => &$value ) {
								
				switch( $value['value'] ) {
					case '0':
						$value['description'] = 'Single Delivery';
						break;
					case '1_week':
						$value['description'] = 'Weekly';
						break;
					case '2_week':
						$value['description'] = 'Fortnightly';
						break;
					case '3_week':
						$value['description'] = '3 Weekly';
						break;
					case '4_week':
						$value['description'] = '4 Weekly';
						break;
					default: 
						break;
				}
			}

			return $options;
		}


        function bkap_display_weekdays() {

            global $bkap_weekdays;

            $weekdays_for_postcode = $this->selected_week_days_for_post_code;

            $html = '<div id="weekday-block" style="display:none">';

            //$html .= '<label for="postcode_weekday"> Select a weekday</label>';

            $html .= '<select name="postcode_weekday">';
			$html .= '<option value="">Select a weekday</option>';

            foreach ($weekdays_for_postcode as $key => $value) {
              # code..
                $html .= '<option value="'. $key .'">'. $bkap_weekdays[$key] .'</option>';
            }

            $html .= '</select></div>';
          
        	echo $html;
		}
    


        function bkap_remove_hooks() {
          if ( class_exists( 'WCS_ATT_Display_Product' ) ) {
            remove_action( 'woocommerce_before_add_to_cart_button', array( 'WCS_ATT_Display_Product', 'show_subscription_options' ), 100 );
         add_action( 'bkap_subscription_hooks', array( 'WCS_ATT_Display_Product', 'show_subscription_options' ), 2 );  
          }
        }

        function bkap_one_time_purchase_label( $one_time_option_description, $product ) {
            $one_time_option_description = _x( 'Single Delivery', 'product subscription selection - negative response', 'woocommerce-subscribe-all-the-things' );
            
            return $one_time_option_description;
        }


        function bkap_sub_range( $booking_settings ) {
            echo '
                <div class="sub-period" style="display:none;">
                <input type = "radio" name="sub-range" value="Never" checked="checked">
                <label for="sub-range">No End Date</label>

                <input type = "radio" name="sub-range" value="date">
                <label for="sub-range">End Date</label>

                </div>
            ';
        }


        function bkap_add_sub_end_date( $booking_settings ) {
        ?>
            <div class="bkap_start_date" id="bkap_sub_end_date" style="display: none;">
				<input 
					type="text" 
					id="booking_calender_sub_end_date" 
					name="booking_calender_sub_end_date" 
					class="booking_calender" 
					style="cursor: text!important;" 
					readonly
				/>
            </div>

			<input type="hidden" id="set_end_date" name="set_end_date" value = "" >
        <?php
        }

		
		/*************************************************************
		 * This function include css files required for admin side.
		 ***********************************************************/
		function bkap_my_enqueue_scripts_css_for_postcode() {
		    if( (isset($_GET['page']) && $_GET['page'] == 'woocommerce_booking_page' ) ||
		        (isset($_GET['page']) && $_GET['page'] == 'woocommerce_history_page' ) ) {
		            wp_enqueue_style( 'bkap-data-postcode', plugins_url('/css/postcode-view.booking.style.css', __FILE__ ) , '', '', false);
		        	
				}
		}
		
		/*************************************************************
		 * This function include css files required for front side product page.
		 * It will create the css for the Postcode Modal.
		 ***********************************************************/
		function bkap_front_side_postcode_css (){

			global $post;
			if ( is_product() || is_page()) {
				wp_enqueue_style( 'bkap-postcode', plugins_url( '/css/postcode-modal.style.css', __FILE__ ) , '', '', false );
			}
		}
		
		function bkap_front_side_postcode_js() {
				wp_enqueue_script( 'bkap-postcode-js', plugins_url( '/js/bkap-postcode.js', __FILE__ ) , array('jquery'), '', false );
		}
		/*************************************************************
		 * This function will create admin menu.
		 ***********************************************************/
		function bkap_add_postcode_tab (){
		    
		    if ( isset( $_GET['action'] ) ) {
		        $action = $_GET['action'];
		    } else {
		        $action = '';
		    }
		    
		    if ( $action == 'post_code_settings' ) {
		        $post_code_settings = "nav-tab-active";
		    } else {
		        $post_code_settings = '';
		    }
		    ?>
		    
		    <a href="admin.php?page=woocommerce_booking_page&action=post_code_settings" class="nav-tab <?php echo $post_code_settings; ?>"> <?php _e( 'PostCode Settings', 'woocommerce-booking' );?> </a>
		    
		    <?php 
		    
		    if ( 'post_code_settings' == $action ){
		    
	        ?>
                <div id="content">
                    <form method="post" action="" id="wapbk_booking_post_code_settings">
                        <input type="hidden" name="wapbk_booking_post_code_frm" value="postcodesave">
                        
                        <div id="poststuff">
                            <div class="" >
                            <?php 
                            if ( isset( $_POST['wapbk_booking_post_code_frm'] ) && $_POST['wapbk_booking_post_code_frm'] == 'postcodesave' ) {
        
            		            if ( isset( $_POST [ 'booking_enable_post_code' ] ) && $_POST [ 'booking_enable_post_code' ] != '' ){
            		    
            		                update_option( 'booking_enable_post_code', $_POST[ 'booking_enable_post_code' ] );
            		            }else{
            		                update_option( 'booking_enable_post_code', '' );
            		            }
            		    
            		            $weekdays = bkap_get_book_arrays('bkap_weekdays');
            		            foreach( $weekdays as $key => $label ){
            		                /*
            		                 * If week day is selcted then Only update that week day is selcted
            		                 * Update the Post code of that weekday which IS selcted / On.
            		                 * Other wise Keep it balnk
            		                 */
            		                if ( isset( $_POST [ $key ] ) && $_POST [ $key ] != '' ){
            		                    $post_code_key = "post_code_" . $key ;
            		                    update_option( $key, $_POST[ $key ] );
            		                    $post_codes    =  preg_replace('/\s+/', ' ', $_POST[$post_code_key]); ;
            		                    update_option( $post_code_key, $post_codes );
            		                }else{
            		                    $post_code_key = "post_code_" . $key ;
            		                    update_option( $key, '' );
            		                    update_option( $post_code_key, '' );
            		                }
            		            }
            		    
            		            ?>
            	                <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
            	                    <p>
            	                        <strong><?php _e( 'Your settings have been saved.', 'woocommerce-booking' ); ?>
            	                        </strong>
            	                    </p>
            	                </div>
            	                <?php 
            	                }
            	                ?>
                               
                                <h2><?php _e( 'PostCode Settings', 'woocommerce-booking' ); ?></h2>
                                <div>
                                    <table class="form-table">
                                        <tr>
                                       		<th>
                                       			<label for="booking_enable_post_code"> <?php _e( 'Enable PostCode Setting:', 'woocommerce-booking' );?> </label>
                                       		</th>
                                       		<td>
                                               <?php 
                                               $enable_post_code_check = get_option ( 'booking_enable_post_code' );
                                               $enable_post_code = '';
                                               
                                               if ( isset( $enable_post_code_check ) && $enable_post_code_check == 'on' ) {
                                                   $enable_post_code = 'checked';
                                               }
                                               ?>
                                               <input type="checkbox" id="booking_enable_post_code" name="booking_enable_post_code" <?php echo $enable_post_code;?> >
                                               <img class="help_tip" width="16" height="16" style="margin-left:375px;" data-tip="<?php _e('Enable Post code setting\'s on Products Page', 'woocommerce-booking');?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                           </td>
                                       </tr>
                                        <tr>
                                           <th>
                                                <label for="booking_enable_weekday_dates"> <?php _e( 'Booking Days:', 'woocommerce-booking' );?> </label>
                                           </th>
                                           <td>
                                               <fieldset class="days-fieldset_post_code">
                                                   <legend><?php _e( 'Days:', 'woocommerce-booking' ); ?></legend>
                                                   <table>
                                                        
                                                           <?php 
                                                           $weekdays = bkap_get_book_arrays('bkap_weekdays');
                                                           foreach ( $weekdays as $n => $day_name) {
                                                                   
                                                             $post_code_label = "post_code_" . $n ;
                                                             $get_post_code   = get_option ( $post_code_label );
                                                             
                                                             $get_post_code = preg_replace('/\s+/', ' ', $get_post_code);
                                                             print('<tr><td> <input type="checkbox" name="'.$n.'" id="'.$n.'" value="checked" ' . get_option( $n ) . ' /> </td>
                                                                 <td><label for="'.$day_name.'">'.__( $day_name, 'woocommerce-booking' ).'</label> </td>
                                                                 <td> <textarea rows=2 cols=50 class="post_code" name="post_code_' . $n . '" id="post_code_' . $n . '" > '.$get_post_code.' </textarea></td>
                                                             </tr>');
                                                           }?>
                                                       
                                                   </table>
                                               </fieldset>
                                               <img class="help_tip" width="16" height="16" style="margin-left:225px;" data-tip="<?php _e( 'Select Weekdays', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                                           </td>
                                        </tr>
                                        
                                        <tr>
                                            <td>
                                                <input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'woocommerce-booking' ); ?>" />
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php 
	        }
		}    
		
		/*************************************************************
		 * This function will create the post code view on the front side product page.
		 * It will also create an array with the selected postcode of the user.
		 ***********************************************************/
		function bkap_create_postcode_view(){	

        if( session_id() === '' ){
            //session has not started
            session_start();
  	    }

        /*if( $_SERVER['REMOTE_ADDR'] == "27.4.40.65" ) {
      echo "<pre>"; print_r($_SESSION);  echo "</pre>";
    }*/
		    $check_post_code_enable  = '';
		    $explode_post_code_value = array();
			
		    ?>
    	    <script type="text/javascript">

    	    	jQuery( function(){
					  <?php 
                      if ( is_user_logged_in()
                        || ( isset( $_POST['wapbk_submit_post_code'] ) && $_POST['wapbk_submit_post_code'] != '' )
                        || ( isset( $_SESSION['bkap_validate_post_code'] ) && $_SESSION['bkap_validate_post_code'] !='' )
                      ) {

                        $check_post_code_enable = get_option ( 'booking_enable_post_code' );
                        if ( isset( $_POST['wapbk_submit_post_code'] ) && $_POST['wapbk_submit_post_code'] != '' ){
                            
                            $user_shipping_post_code = $_POST['wapbk_submit_post_code'];
                            $user_shipping_post_code = preg_replace('/\s+/', ' ', $user_shipping_post_code);
                        } else if ( isset( $_SESSION['bkap_validate_post_code'] ) && $_SESSION['bkap_validate_post_code'] != '' ){

                            $user_shipping_post_code = $_SESSION['bkap_validate_post_code'];
                            $user_shipping_post_code = preg_replace('/\s+/', ' ', $user_shipping_post_code);
                        
                        }else{
                            $user_id = get_current_user_id();
                            $key = 'shipping_postcode';
                            $single = true;
                            $user_shipping_post_code = get_user_meta( $user_id, $key, $single );
                        }
                        
                        if ( isset( $user_shipping_post_code ) && $user_shipping_post_code != '' ) {
                            
                            $weekdays = bkap_get_book_arrays('bkap_weekdays');
                            foreach ( $weekdays as $weekday_key => $day_name) {
                              $get_week_day_enabled = get_option ( $weekday_key );
                             
                              if ( isset( $get_week_day_enabled ) && 'checked' == $get_week_day_enabled ){

                                $post_code_label         = "post_code_" . $weekday_key ;
                                $get_post_code           = get_option ( $post_code_label );
                                $explode_post_code_value = explode( ",", $get_post_code);
                                
                                $explode_post_code_value = array_map( 'trim', $explode_post_code_value);
                                
                                if ( in_array( $user_shipping_post_code, $explode_post_code_value ) ){
                                    $this->display_booking_fields = 'YES';
                                    $this->selected_week_days_for_post_code [ $weekday_key ] = $weekday_key;
                                }
                              } 
    		                    }
    		              
        		              if ( empty ( $this->selected_week_days_for_post_code ) && ( isset( $_POST['wapbk_submit_post_code'] )  && $_POST['wapbk_submit_post_code'] != '' )  ){
        		              ?>	
    								                var confirm_value = confirm ("Shipping not available in your area!!");

                          	        if ( confirm_value ){
                          	        	var modal = document.getElementById('bkap_postcode_modal');
                            	        modal.style.display = "block";

                            	        var submit_post_code = document.getElementById("submit_post_code"); 

                            	        if ( submit_post_code !== null ){
                                            // When the user clicks the button, open the modal 
                                            submit_post_code.onclick = function( event ) {
                                        	  var client_entred_post_code = document.getElementById('bkap_post_code_of_user').value;
        
                                          	  var validate_post_code = isInt ( client_entred_post_code );
                                        	      if ( validate_post_code === true ){
                                      	    	     jQuery('#wapbk_submit_post_code').val(client_entred_post_code);
                                                  }else{
                                              	     alert ("Please enter valid PostCode!!");
                                               	     return false;
                                          	      }
                                              }
                            	        }
                                        
                                     }else{
                            	          
                          	        }
                            	   <?php
        		              }
        		            } else { 
    		            ?>
						        // Get the modal
                          var modal = document.getElementById('bkap_postcode_modal');

                          // Get the button that opens the modal
                          var btn = document.getElementById("enter_post_code");
                          var submit_post_code = document.getElementById("submit_post_code"); 

                          if ( submit_post_code !== null ){
                              // When the user clicks the button, open the modal 
                              submit_post_code.onclick = function( event ) {
                          	  var client_entred_post_code = document.getElementById('bkap_post_code_of_user').value;
    
                            	  var validate_post_code = isInt ( client_entred_post_code );
                          	      if ( validate_post_code === true ){
                                	  jQuery('#wapbk_submit_post_code').val(client_entred_post_code);
                              	    }else{
                                	  alert ("Please enter valid PostCode!!");
                            		return false;
                            	  }
                              }
                          }

						
                                  
                          if ( btn !== null ){
                              // When the user clicks the button, open the modal 
                              btn.onclick = function( event ) {
                                  modal.style.display = "block";
                                  return false;
                              }

                          }
                          var content = document.getElementById("content");
                          // When the user clicks anywhere outside of the modal, close it
                          window.onclick = function(event) {
                              if (event.target == content) {
                                  modal.style.display = "none";
                              }
                          }
                        <?php 
                      }
                    }else if ( !is_user_logged_in() || ( isset( $_POST['wapbk_submit_post_code'] )  && $_POST['wapbk_submit_post_code'] == '' )  ){
                        
                        $check_post_code_enable = get_option ( 'booking_enable_post_code' );
                        
                        if ( isset($check_post_code_enable) && 'on' != $check_post_code_enable){
                            //$display_booking_fields = 'YES';
							$this->display_booking_fields = 'YES';
                        }
                      ?>
					
                       // Get the modal
                      var modal = document.getElementById('bkap_postcode_modal');

                      // Get the button that opens the modal
                      var btn = document.getElementById("enter_post_code");

                      var submit_post_code = document.getElementById("submit_post_code"); 

                      if ( submit_post_code !== null ){
                          // When the user clicks the button, open the modal 
                          submit_post_code.onclick = function( event ) {
    
                        	  var client_entred_post_code = document.getElementById('bkap_post_code_of_user').value;
    
                        	  var validate_post_code = isInt ( client_entred_post_code );
                        	  
                      	      if ( validate_post_code === true ){
                            	  jQuery('#wapbk_submit_post_code').val(client_entred_post_code);
                            	  
                            	}else{
                           	      alert ("Please enter valid PostCode!!");
                        		  return false;
                        	  }
                          }
                      }

					  modal.style.display = "block";
                      if ( btn !== null ){
                          // When the user clicks the button, open the modal 
                          btn.onclick = function( event ) {
                              modal.style.display = "block";
                              return false;
                          }
                      }

                      var content = document.getElementById("content"); 
                      // When the user clicks anywhere outside of the modal, close it
                      window.onclick = function(event) {
                          
                          if (event.target == content) {
                              modal.style.display = "none";
                          }
                      }
                    <?php 
                    } 
                    ?>

                    /*
                    * This java script function will check if the Postcode enter is correct Integer value.
                    * It will disply alrt when post code have string.
                    */
                    function isInt(value) {
                  	  return !isNaN( value ) && 
                  	         parseInt( Number(value)) == value && 
                  	         !isNaN(parseInt(value, 10) );
                  	}
                });

    	    </script>
    	    <?php
    	    /*
    	     * When user had chnaged the post code on the checkout page. And we display the alert. 
    	     * If user select Cancel from that popup then it will allow to place order with chnaged postcode.
    	     */
			print( '<input type="hidden" id="wapbk_submit_post_code" class = "wapbk_submit_post_code" name="wapbk_submit_post_code" value="">' );
		}
		
		/*
		 * It will inform the booking plguin to disply booking fields on product page.
		 */
		function bkap_postcode_display_booking_field (){
		
			return $this->display_booking_fields;
		}
		
		/*
		 * This function will create the modal of the postcode on the product page.
		 */
		function bkap_create_postcode_modal(){
			
			print ('<label  class ="book_start_date_label" style="margin-top:5em;">'.__( get_option("book_date-label"),"woocommerce-booking").': </label>
				            <input type="text"  id="enter_post_code" style="cursor: text!important;" readonly/>
						    ');
				     
		    print  ('<div id="bkap_postcode_modal" class="bkap_modal">
		    
                      <!-- Modal content -->
                      <div class="bkap_modal-content">
                        <div class="bkap_modal-header" >
                          <b>Delivery Postcode</b>
                        </div>
		        
                        <div class="bkap_modal-body">
			                        <form method="post" action="" id="wapbk_booking_post_code_settings">
		        
                                		<table style = "height:112px; margin-bottom: 0px; margin-left: auto; margin-right: auto;">
                                    		<tr> 
                                    			<td> <input type="text" id = "bkap_post_code_of_user" class = "bkap_post_code_of_user" value="" > </td> 
                                    		</tr>
				                            <tr class ="bkap-postcode-button" >
				                                <td>
                                                    <button id = "submit_post_code" name="submit_post_code" class="button-primary submit_post_code">Ok</button>
                                                </td>
                                    		</tr>
                                		</table>
		                            
			                        </form>
                        </div>
                      </div>
                    </div>');
	

		}
	
		/*
		 * This function will disply the selected postcode of user before the booking field.
		 */
		function bakp_create_postcode_field_before_field (){

		    
		    if( session_id() === '' ){
		        //session has not started
		        session_start();
		    }
		    
		    $user_id = get_current_user_id();
		    $key = 'shipping_postcode';
		    $single = true;
		    $user_shipping_post_code = get_user_meta( $user_id, $key, $single );
		    
		    if ( is_user_logged_in() && isset( $user_shipping_post_code ) && $user_shipping_post_code != '' ){
		        $_SESSION['bkap_validate_post_code'] = $user_shipping_post_code;
		        print ('<label class ="bkap_entered_client_post_code" id="bkap_entered_client_post_code" > <strong> Delivery Postcode: </strong>'. $user_shipping_post_code . ' </label> <br> <br>');
		    
			}else if ( ( isset( $_POST['wapbk_submit_post_code'] ) && '' != $_POST['wapbk_submit_post_code'] ) || isset( $_SESSION['bkap_validate_post_code'] ) && '' != $_SESSION['bkap_validate_post_code'] ){
		        
		        $post_code = '';
		        if ( ( isset( $_POST['wapbk_submit_post_code'] ) && '' != $_POST['wapbk_submit_post_code']  ) ){
		             
		            if( session_id() === '' ){
		                //session has not started
		                session_start();
		            }
		            $post_code = $_POST['wapbk_submit_post_code'];
		            $_SESSION['bkap_validate_post_code'] = $_POST['wapbk_submit_post_code'];
		        }
		        else if ( ( isset( $_SESSION['bkap_validate_post_code'] ) && '' != $_SESSION['bkap_validate_post_code'] ) ){
		    
		            $post_code = $_SESSION['bkap_validate_post_code'];
		        }
		        print ('<button id = "reset_post_code"  class="button-primary reset_post_code"> <strong> Delivery Postcode: </strong>'. $post_code . ' </button> <br><br>');
		    }
        }

        /*
         * This function will disply only weekdays based on selected postcode of the user.
         * 
         */
		function bakp_change_postcode_weekdays ( $postcode_weekdays, $get_post_id, $default ){
		    $number_of_days = 0;
		    $check_post_code_enable = get_option ( 'booking_enable_post_code' );			

       //changed here 
			$booking_settings =   get_post_meta( $get_post_id, 'woocommerce_booking_settings', true );
      
		    if ( isset( $check_post_code_enable) && $check_post_code_enable == 'on' && count ( $this->selected_week_days_for_post_code ) > 0  ){
		        
				
		        foreach ( $booking_settings['booking_recurring'] as $wkey => $wval ) {
		             
		            if ( $default == "Y" ) {
					   $booking_settings[ 'booking_recurring' ][ $wkey ] = 'on';
		               //print('<input type="hidden" name="wapbk_'.$wkey.'" id="wapbk_'.$wkey.'" value="on">');
		           
				    } else {
		                if ( in_array( $wkey, $this->selected_week_days_for_post_code ) ){

		                    $booking_settings[ 'booking_recurring' ][ $wkey ] = $wval;
		                    //print('<input type="hidden" name="wapbk_'.$wkey.'" id="wapbk_'.$wkey.'" value="'.$wval.'">');
		                    
							if ( isset ( $wval ) && $wval == 'on' ) {
		                        $number_of_days++;
		                    }
		                } else {
		                    $booking_settings[ 'booking_recurring' ][ $wkey ] = '';							
		                    //print('<input type="hidden" name="wapbk_'.$wkey.'" id="wapbk_'.$wkey.'" value="">');
		                }
		            }
				}

		    }

			return $booking_settings[ 'booking_recurring' ];
			
		}
		
		/*
		 *
		 * This function will validate the postcode on the checkout page.
		 * If postcode is changed on the checkout page, the it will disply the warning & do not allow to place the order.
		 *
		 */
		public static function bkap_validate_post_code() {
		     
		    if( session_id() === '' ){
		        //session has not started
		        session_start();
		    }

        $check_delivery = false;

        foreach ( WC()->cart->cart_contents as $key => $value ) {
          if ( isset( $value['bkap_booking'] ) && !empty( $value['bkap_booking'] ) ){
            $check_delivery = true;
            break;
          }
        }

        if ( $check_delivery ) {
          
          if ( isset( $_POST['wapbk_do_not_validate_postcode'] ) && $_POST['wapbk_do_not_validate_postcode'] == 'YES' ){
            $validated = 'YES';
          }else{
            $validated = 'NO';
          }

          if ( isset( $_SESSION['bkap_validate_post_code'] ) && $_SESSION['bkap_validate_post_code'] != ''
              &&
              isset( $_POST['shipping_postcode'] ) && $_POST['shipping_postcode'] != ''
              &&
              'NO' == $validated
          ){

              $product_page_post_code  = $_SESSION['bkap_validate_post_code'];
              $checkout_page_post_code = $_POST['shipping_postcode'];
               
              if ( $product_page_post_code === $checkout_page_post_code ){
                  $validated = 'YES';
              }
          }
           
          if ( ( isset( $validated ) && 'NO' == $validated ) ){
               
              $message =  __('We have noticed your postcode has been changed in the checkout. Please
                                      remove all line items in your order and start again. If you would like to
                                      change your delivery address you will have to do it in the accounts area
                                      before adding your products. Alternatively just contact us and we will fix
                                      it for you.');
              wc_add_notice( $message, $notice_type = 'error' );
          }
        }
		}
		
		
		/*
		 * This function will populate the product page seleted post code into the checkout page billing and shipping post code field.
		 * It will also disply the popup window to users. If they Selct Ok from popup then it will empty the cart and redirect to shop page.
		 * If user select the cancel then it will allow to place order with chaned post code.
		 */
		public function bkap_populate_postcode() {
		     
		    if (  is_checkout() && !is_user_logged_in() ) {
		
    	        if( session_id() === '' ){
    	            //session has not started
    	            session_start();
    	        }

    	
    	        print ('<input type="hidden" id="wapbk_do_not_validate_postcode" class = "wapbk_do_not_validate_postcode" name="wapbk_do_not_validate_postcode" value="">');
    	        global $woocommerce;
    	        ?>
                 <script type="text/javascript">
    
                 jQuery(document).ajaxComplete( function( event, xhr, options ){
    	             
    	             var action = options.url.split( "=" );
    	             
                	    if( typeof action[ 1 ] !== "undefined" ) {
                        	var action_wc_ajax = action[ 0 ].split( "?" );
                            if( action_wc_ajax[ 1 ] == "wc-ajax" && action[ 1 ] == "update_order_review" ) {
                                <?php 
                                   if ( isset( $_SESSION ['bkap_validate_post_code'] ) && $_SESSION ['bkap_validate_post_code'] != ''){
                                    ?>
                                           document.getElementById("billing_postcode").value = <?php echo $_SESSION ['bkap_validate_post_code'] ; ?>;
                                           document.getElementById("shipping_postcode").value = <?php echo $_SESSION ['bkap_validate_post_code'] ; ?>;
                                    <?php 
                                   }
                                ?>
                            }
    
                            if( action_wc_ajax[ 1 ] == "wc-ajax" && action[ 1 ] == "checkout" ) {
                            	//console.log (xhr.responseText);
                            	var jsonResponse = JSON.parse(xhr.responseText);
                            	
                            	var error_message = jsonResponse.messages;
                                var post_code_error = 'We have noticed your postcode has been changed in the checkout';
                                if ( error_message.indexOf( post_code_error ) > -1 ){
                                	
                                	var user_selection = confirm ("We have noticed your postcode has been changed in the checkout. Please remove all line items in your order and start again. If you would like to change your delivery address you will have to do it in the accounts area before adding your products. Alternatively just contact us and we will fix it for you.");
                                	if ( user_selection ){
    
                                	    <?php  $admin_url = get_admin_url(); ?>
                                	    var data = {
                              	    		          action: "bkap_empty_cart_wrong_postcode"
            									   };
            			
            							jQuery.post( '<?php echo $admin_url; ?>' + 'admin-ajax.php', data, function( response )
            							{
            								window.location.replace( response );
            								
            							});
                                	    
                                	}else{
    
                                		jQuery('#wapbk_do_not_validate_postcode').val('YES');
                                	}
                                }
                            }
                        }     
                 } );
                 </script>
            <?php  
         }
         if (  is_checkout() && is_user_logged_in() ) {
             global $woocommerce;
             print ('<input type="hidden" id="wapbk_do_not_validate_postcode" class = "wapbk_do_not_validate_postcode" name="wapbk_do_not_validate_postcode" value="" >');
             ?>
             <script type="text/javascript">
             
                 jQuery(document).ajaxComplete( function( event, xhr, options ){
    	             
    	             var action = options.url.split( "=" );
    	                 if( typeof action[ 1 ] !== "undefined" ) {
                    	    
                        	var action_wc_ajax = action[ 0 ].split( "?" );
                            if( action_wc_ajax[ 1 ] == "wc-ajax" && action[ 1 ] == "checkout" ) {
                            	
                            	var jsonResponse = JSON.parse(xhr.responseText);
                            	
                            	var error_message = jsonResponse.messages;
                                var post_code_error = 'We have noticed your postcode has been changed in the checkout';
                                if ( error_message.indexOf( post_code_error ) > -1 ){
                                	
                                	var user_selection = confirm ("We have noticed your postcode has been changed in the checkout. Please remove all line items in your order and start again. If you would like to change your delivery address you will have to do it in the accounts area before adding your products. Alternatively just contact us and we will fix it for you.");
                                	if ( user_selection ){
    
                                	    <?php  $admin_url = get_admin_url(); ?>
                                	    var data = {
                                	    		   action: "bkap_empty_cart_wrong_postcode"
     									};
             			
             							jQuery.post( '<?php echo $admin_url; ?>' + 'admin-ajax.php', data, function( response )
             							{
             								window.location.replace( response );
             								
             							});
                                	    
                                	}else{
                                	    jQuery('#wapbk_do_not_validate_postcode').val('YES');
                                	}
                                }
                            }
                        }     
                 } );
             </script>
            <?php
            }    
        }
        
        /**
         * This is ajax function for empty the cart.
         */
        function bkap_postcode_load_ajax() {
        
            if ( !is_user_logged_in() ){
                add_action('wp_ajax_nopriv_bkap_empty_cart_wrong_postcode', array( &$this,'bkap_empty_cart_wrong_postcode') ) ;
            } else{
                add_action( 'wp_ajax_bkap_empty_cart_wrong_postcode',       array( &$this, 'bkap_empty_cart_wrong_postcode' ) );
            }
        }
        
        function bkap_empty_cart_wrong_postcode (){
            global $woocommerce;
            $shop_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
            $woocommerce->cart->empty_cart();
            echo $shop_url ;
            die;
        }

		function bkap_add_subscription_data( $cart_item_meta, $product_id ) {
			if( isset( $_POST['set_end_date'] ) && '' !== $_POST['set_end_date'] ) {
				$cart_item_meta['sub_end_date'] = $_POST['set_end_date'];
			}

			return $cart_item_meta;
		}

		function bkap_modify_subscription_meta( $subscription, $posted_data ) {
			global $woocommerce;

			foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) { 
				if ( isset( $values['sub_end_date'] ) ) {
					$sub_end_date = $values['sub_end_date'];
				}

				$date =  $values['bkap_booking'][0]['date'];
				$hidden_date =  $values['bkap_booking'][0]['hidden_date'];
				
				$booking_date = date( 'Y-m-d', strtotime($hidden_date));

				$period = $subscription->get_billing_period();
				$interval = $subscription->get_billing_interval();
				$next_date = bkap_subscriptions_common_class::bkap_sub_add_time($interval, $period, strtotime($booking_date) );

				$subscription->update_dates( array(
					'trial_end'    => '',
					'next_payment' => '',
					'end'          => $sub_end_date,
					'next_delivery'=> date('Y-m-d H:i:s', $next_date ),
				) );
			}
			
		}

	}	
}
$bkap_postcode = new Bkap_Postcode();

