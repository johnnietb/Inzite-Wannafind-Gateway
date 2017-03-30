jQuery.noConflict();

jQuery(window).load(function() {

  payment_cards();

  // Fill out the checkout form from a cookie
  fill_checkout_form();

});

function get_checkout_form() {
  // Get checkout form data
  var $form = jQuery('form[name="checkout"]'),
      sData = $form.serialize();
  // Store checkout form data in cookie
  jQuery.cookie('checkout_form_data',sData, {path: "/"});
};

function fill_checkout_form() {
  if ( jQuery.cookie('checkout_form_data') ) {

    // Get form data from cookie and create object
    var data = jQuery.cookie('checkout_form_data').split("&");
    var obj={};
    for(var key in data) {
        obj[data[key].split("=")[0]] = data[key].split("=")[1];
    }

    // reset the form
  	jQuery('form[name="checkout"]').get(0).reset();

  	//reset form values from json object
  	jQuery.each(obj, function(name, val) {
  		var $el = jQuery('[name="' + name + '"]'),
  			  type = $el.attr('type');
  		switch (type) {
  			case 'checkbox':
  				$el.attr('checked', 'checked');
  				break;
  			case 'radio':
  				$el.filter('[value="' + val + '"]').attr('checked', 'checked');
  				break;
  			default:
  				$el.val(decodeURIComponent(val.replace(/\+/g, '%20')));
  		}
  	});
  }
}

function payment_cards() {
  if ( jQuery.cookie('payment_method') != "" ) {
      jQuery("#" + jQuery.cookie('payment_method')).prop('checked', true);
  }

  jQuery("input[type=radio][name=payment_method]").each( function() {
    jQuery(this).change( function() {

      var id = jQuery(this).attr('id');
      jQuery.cookie('payment_method', id , {path: "/"});
      if (jQuery(this).val() != "Wannafind" ) {
        jQuery.cookie('payment_card_type', 0, {path: "/"});
        jQuery.cookie('payment_card_fee', 0, {path: "/"});

        // Save the checkout form to a cookie
        get_checkout_form();

        window.location.reload();
      } else {
        jQuery("input[type=radio][name=payment_card_type]").first().prop('checked', true).change();
      }

    });

  });

  jQuery("input[type=radio][name=payment_card_type]").each( function() {

    jQuery(this).change( function(e) {

      var selected = jQuery(this).val();
      var fee = jQuery(this).attr("data-fee");
      jQuery.cookie('payment_card_type',selected, {path: "/"});
      jQuery.cookie('payment_card_fee',fee, {path: "/"});

      // Save the checkout form to a cookie
      get_checkout_form();

      window.location.reload();

    });

  });
}
