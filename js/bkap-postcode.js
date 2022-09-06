jQuery(document).ready(function() {
    let price = 0;
    let qty = 1;
    jQuery(".wcsatt-options-product select option[value='0']").prop("selected", true);

	jQuery(".wcsatt-options-product select").on("change", function() {

        //var custom_data = jQuery(".wcsatt-options-product select option:selected").data('custom_data'); 
        console.log("custom date");     
        console.log(jQuery(this).val()); 
		/*if( jQuery(this).val() !== '0' ) {
            console.log("HERE1");
            price = custom_data['subscription_scheme'].price;
            jQuery( "#weekday-block select option[value='']" ).prop("selected", true);
			jQuery( "#bkap-booking-form" ).slideUp();
			jQuery( "#weekday-block" ).slideDown();
            jQuery( ".sub-period" ).slideDown();
           // jQuery( "#bkap_price" ).html( price );
            
            jQuery( "#bkap_price_charged" ).val( price );
            jQuery( "#total_price_calculated" ).val( price );
           // jQuery( "#bkap_price" ).html( bkap_functions.bkap_format_money( woocommerce_params, price ) );

           jQuery( ".single_add_to_cart_button" ).show();
            jQuery( '.quantity input[name="quantity"]' ).show();
            
            
		} else {
            console.log("HERE2");
			jQuery( "#bkap-booking-form" ).slideDown();	
			jQuery( "#weekday-block" ).slideUp();
			jQuery( ".sub-period" ).slideUp();
            jQuery( "#bkap_sub_end_date" ).slideUp();
            jQuery( "#bkap_price_charged" ).val("");     
            jQuery( "#total_price_calculated" ).val("");                   
            jQuery( "#wapbk_hidden_date" ).val( "" );            
            jQuery( "#booking_calender" ).datepicker( "setDate", "" );
            console.log("HERE");
		}*/
        console.log("TEST");
    });
  
	if ( typeof( bkap_process_params ) !== 'undefined' ) {
		var settings = JSON.parse( bkap_process_params.additional_data );
		var bkap_settings = JSON.parse( bkap_process_params.bkap_settings );
		var global_settings = JSON.parse( bkap_process_params.global_settings );
                
		var datepicker_options = {
			dateFormat: global_settings.booking_date_format,
			numberOfMonths: parseInt( global_settings.booking_months ),
            firstDay: parseInt( global_settings.booking_calendar_day ),
            onSelect: setEndDate
        }
        

		jQuery('input[name="sub-range"]').on( "change", function() {
	
			if( jQuery(this).val() === 'date' ) {
				jQuery("#bkap_sub_end_date").slideDown();
				jQuery("#booking_calender_sub_end_date").datepicker(datepicker_options);
			}else {
				jQuery("#bkap_sub_end_date").slideUp();
				
			}
        });

        jQuery( "#weekday-block select").on( "change", function(){

            var current_date = new Date() ;
            var current_weekday = current_date.getDay();
            var weekday = jQuery(this).val().split('_');
            var selected_weekday = weekday[2];

            var diff = selected_weekday - current_weekday;
            if( diff <= 0 ) {
                diff += 7;
            } 
    
            current_date.setDate( current_date.getDate() + diff );
            
            var monthValue = current_date.getMonth() + 1;
            var dayValue = current_date.getDate();
            var yearValue = current_date.getFullYear();
    
            var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
    
            jQuery( "#wapbk_hidden_date" ).val( current_dt );
            jQuery( "#booking_calender" ).datepicker( "setDate", current_date );          
       
        });
    }
});



function setEndDate( date, inst ) {
    var monthValue = ( inst.selectedMonth ) < 10 ? "0" + (inst.selectedMonth+1) : inst.selectedMonth+1 ;
	var dayValue = ( inst.selectedDay ) < 10 ? "0"+inst.selectedDay : inst.selectedDay;
	var yearValue = inst.selectedYear;

    var current_dt = yearValue + "-" + monthValue + "-" + dayValue + " " + "00:00:00";
    jQuery("#set_end_date").val( current_dt );
}
