<?php
// [cardlist] shortcode
function cards_list($atts){

	$a = shortcode_atts( array(
        'showfee' => 'false',
    ), $atts );

	$cards = get_option( 'woocommerce_card_fees' );

	ob_start();

		echo '<ul style="list-style: none; padding: 0 0 0 35px;">';
		foreach ($cards as $card) {
	    echo '<li style="position: relative;">';
			echo '<img style="position: absolute; left: -35px; top: 4px;" src="'. dirname( dirname(plugin_dir_url( __FILE__ )) ) .'/'. dirname(dirname( plugin_basename( __FILE__ ) )) .'/cards/'.str_replace(",", "_", $card['card_code']).'.png" />';
			echo $card['card_name'];
			if (boolval($a['showfee'])) {
				echo ' <span>(' . __('Gebyr', 'wannafind') . ': ' . floatval(str_replace(",", ".", esc_attr( $card['card_percentage_fee'] ))) * 100 . '%)</span>';
			}
			echo '</li>';
		}
		echo "</ul>";

	$card_list = ob_get_contents();

	ob_end_clean();

  return $card_list;

}
add_shortcode( 'cardlist', 'cards_list' );
?>
