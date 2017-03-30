  $(window).load(function() {

    payment_cards();

    // Fill out the checkout form from a cookie
    fill_checkout_form();

  });

  function get_checkout_form() {
    // Get checkout form data
    var $form = $('form[name="checkout"]'),
        sData = $form.serialize();
    // Store checkout form data in cookie
    $.cookie('checkout_form_data',sData, {path: "/"});
  };

  function fill_checkout_form() {
    if ( $.cookie('checkout_form_data') ) {

      // Get form data from cookie and create object
      var data = $.cookie('checkout_form_data').split("&");
      var obj={};
      for(var key in data) {
          obj[data[key].split("=")[0]] = data[key].split("=")[1];
      }

      // reset the form
    	$('form[name="checkout"]').get(0).reset();

    	//reset form values from json object
    	$.each(obj, function(name, val) {
    		var $el = $('[name="' + name + '"]'),
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
    if ( $.cookie('payment_method') != "" ) {
        $("#" + $.cookie('payment_method')).prop('checked', true);
    }

    $("input[type=radio][name=payment_method]").each( function() {
      $(this).change( function() {

        var id = $(this).attr('id');
        $.cookie('payment_method', id , {path: "/"});
        if ($(this).val() != "Wannafind" ) {
          $.cookie('payment_card_type', 0, {path: "/"});
          $.cookie('payment_card_fee', 0, {path: "/"});

          // Save the checkout form to a cookie
          get_checkout_form();

          window.location.reload();
        } else {
          $("input[type=radio][name=payment_card_type]").first().prop('checked', true).change();
        }

      });

    });

    $("input[type=radio][name=payment_card_type]").each( function() {

      $(this).change( function(e) {

        var selected = $(this).val();
        var fee = $(this).attr("data-fee");
        $.cookie('payment_card_type',selected, {path: "/"});
        $.cookie('payment_card_fee',fee, {path: "/"});

        // Save the checkout form to a cookie
        get_checkout_form();

        window.location.reload();

      });

    });
  }
