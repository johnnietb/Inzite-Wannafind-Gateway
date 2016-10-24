  $(window).load(function() {

    payment_cards();

  });

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
        window.location.reload();

      });

    });
  }

