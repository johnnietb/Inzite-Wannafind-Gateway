<?php
/*
Plugin Name: WooCommerce Wannafind/Curanet Gateway
Description: Extends WooCommerce with an Wannafind/Curanet gateway.
Version: 1.1.2
Author: Johnnie Bertelsen - Inzite
Author URI: http://www.inzite.dk/
GitHub Plugin URI: https://github.com/johnnietb/Inzite-Wannafind-Gateway
*/

add_action('plugins_loaded', 'woocommerce_gateway_wannafind_init', 0);

wp_enqueue_script('wannafind', dirname( plugin_dir_url( __FILE__ ) ) .'/'. dirname( plugin_basename( __FILE__ ) ) .'/js/wannafind.js', array('jquery', 'jquery-cookie' ), 1.0);



function woocommerce_gateway_wannafind_init() {

	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
 	 * Localisation
	 */
	load_plugin_textdomain('wc-gateway-wannafind', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

	if ((isset($_COOKIE['payment_card_type']) && $_COOKIE['payment_card_type'] != "") && (isset($_COOKIE['payment_card_fee']) && $_COOKIE['payment_card_fee'] != "")) {
		add_action('woocommerce_cart_calculate_fees',
			function () {
				global $woocommerce;
				$woocommerce->cart->add_fee('Kort gebyr', number_format($_COOKIE['payment_card_fee'],4));
			}
		);
	}


	/**
 	 * Gateway class
 	*/
	class WC_Gateway_Wannafind extends WC_Payment_Gateway {

		public function __construct() {
			$this->id                 	= 'wannafind';
			//$this->icon               = apply_filters('woocommerce_cheque_icon', '');
			$this->has_fields         	= false;
			$this->method_title       	= __( 'Wannafind/Curanet', 'woocommerce' );
			$this->method_description 	= __( 'Adds Wannafind/Curanet gateway for payments through NETS', 'woocommerce' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        		= $this->get_option( 'title' );
			$this->order_button_text    = $this->get_option( 'order_button_text' );
			$this->description  		= $this->get_option( 'description' );
			$this->instructions 		= $this->get_option( 'instructions', $this->description );
			$this->shopid 				= $this->get_option( 'shopid');
			$this->currency 			= $this->get_option( 'currency', 208);
			$this->order_prefix 		= $this->get_option( 'order_prefix');

			$this->cardtype = '';
			$this->cardfee = 0;

			$this->card_details = get_option( 'woocommerce_card_fees',
			array(
				array(
					'card_name'   	=> $this->get_option( 'card_name' ),
					'card_code'      => $this->get_option( 'card_code' ),
					'card_min_fee'      => $this->get_option( 'card_min_fee' ),
					'card_percentage_fee'           => $this->get_option( 'card_percentage_fee' )
				)
			)
			);


			$this->wannafind_accept_url  = $this->get_option( 'wannafind_accept_url' );
			$this->wannafind_decline_url  = $this->get_option( 'wannafind_decline_url' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_card_fees' ) );

	    	add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'payment_page' ) );
	    	add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'receipt_page' ) );

	    	// Customer Emails
	    	// add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	    }

	    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	        if ( $this->instructions && ! $sent_to_admin && 'Wannafind' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}

	    public function init_form_fields() {
	    	$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Wannafind Gateway', 'woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( 'Betaling med kreditkort (DK/VISA/MC+)', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => __( 'Betaling med kreditkort.', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Betaling med kreditkort', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'order_button_text' => array(
					'title'       => __( 'Payment button', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Go to payment', 'woocommerce' ),
				),
				'shopid' => array(
					'title'       => __( 'Shop/Gateway ID', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Ex: 123456789 - can be retrieved from Curanet/Wannafind reseller', 'woocommerce' ),
				),
				'currency' => array(
					'title'       => __( 'Numeric currency code', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Ex: 208 = DKK', 'woocommerce' ),
				),
				'order_prefix' => array(
					'title'       => __( 'Order Prefix', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Ex: "WEB" (MAX LENGHT: 4 CHARS AND ONLY LETTERS.)', 'woocommerce' ),
				),
				'card_details' => array(
					'type'		=> 'card_details'

				)
			);
    	}

    /**
     * generate_card_details_html function.
     */
    public function generate_card_details_html() {
    	ob_start();
	    ?>
	    <tr valign="top">
            <th scope="row" class="titledesc"><?php _e( 'Kort typer', 'woocommerce' ); ?>:</th>
            <td class="forminp" id="card_fees">
			    <table class="widefat wc_input_table sortable" cellspacing="0">
		    		<thead>
		    			<tr>
		    				<th class="sort">&nbsp;</th>
		    				<th><?php _e( 'Kort type', 'woocommerce' ); ?></th>
			            	<th><?php _e( 'Kort code', 'woocommerce' ); ?></th>
			            	<th><?php _e( 'Min gebyr', 'woocommerce' ); ?></th>
			            	<th><?php _e( '% gebyr', 'woocommerce' ); ?></th>
		    			</tr>
		    		</thead>
		    		<tfoot>
		    			<tr>
		    				<th colspan="5"><a href="#" class="add button"><?php _e( '+ Tilføj kort', 'woocommerce' ); ?></a> <a href="#" class="remove_rows button"><?php _e( 'Fjern valgte kort', 'woocommerce' ); ?></a></th>
		    			</tr>
		    		</tfoot>
		    		<tbody class="cards">
		            	<?php
		            	$i = -1;
		            	if ( $this->card_details ) {
		            		foreach ( $this->card_details as $card ) {
		                		$i++;

		                		echo '<tr class="card">
		                			<td class="sort"></td>
		                			<td><input type="text" value="' . esc_attr( $card['card_name'] ) . '" name="card_name[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $card['card_code'] ) . '" name="card_code[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $card['card_min_fee'] ) . '" name="card_min_fee[' . $i . ']" /></td>
		                			<td><input type="text" value="' . esc_attr( $card['card_percentage_fee'] ) . '" name="card_percentage_fee[' . $i . ']" /></td>
			                    </tr>';
		            		}
		            	}
		            	?>
		        	</tbody>
		        </table>
		       	<script type="text/javascript">
					jQuery(function() {
						jQuery('#card_fees').on( 'click', 'a.add', function(){

							var size = jQuery('#card_fees tbody .card').size();

							jQuery('<tr class="card">\
		                			<td class="sort"></td>\
		                			<td><input type="text" name="card_name[' + size + ']" /></td>\
		                			<td><input type="text" name="card_code[' + size + ']" /></td>\
		                			<td><input type="text" name="card_min_fee[' + size + ']" /></td>\
		                			<td><input type="text" name="card_percentage_fee[' + size + ']" /></td>\
			                    </tr>').appendTo('#card_fees table tbody');

							return false;
						});
					});
				</script>
            </td>
	    </tr>
        <?php
        return ob_get_clean();
    }

    private function card_details( $order_id = '' ) {
    	if ( empty( $this->card_details ) ) {
    		return;
    	}

    	echo '<h2>' . __( 'Kort typer', 'woocommerce' ) . '</h2>' . PHP_EOL;

    	$card_fees = apply_filters( 'woocommerce_card_fees', $this->card_details );

    	if ( ! empty( $card_fees ) ) {
	    	foreach ( $card_fees as $card ) {
	    		echo '<ul class="order_details card_details">' . PHP_EOL;

	    		$card = (object) $card;

				$card_fields = apply_filters( 'woocommerce_card_fields', array(
					'card_name'=> array(
						'label' => __( 'Kort type', 'woocommerce' ),
						'value' => $card->card_name
					),
					'card_code'		=> array(
						'label' => __( 'Kort code', 'woocommerce' ),
						'value' => $card->card_code
					),
					'card_min_fee'			=> array(
						'label' => __( 'Min. gebyr', 'woocommerce' ),
						'value' => $card->card_min_fee
					),
					'card_percentage_fee'			=> array(
						'label' => __( '% gebyr', 'woocommerce' ),
						'value' => $card->card_percentage_fee
					)
				), $order_id );

				if ( $card->card_name ) {
					echo '<h3>' .  $card->card_name . '</h3>' . PHP_EOL;
				}

	    		foreach ( $card_fields as $field_key => $field ) {
				    if ( ! empty( $field['value'] ) ) {
				    	echo '<li class="' . esc_attr( $field_key ) . '">' . esc_attr( $field['label'] ) . ': <strong>' . wptexturize( $field['value'] ) . '</strong></li>' . PHP_EOL;
				    }
				}

	    		echo '</ul>';
	    	}
	    }
    }

    public function save_card_fees() {
    	$cards = array();

    	if ( isset( $_POST['card_name'] ) ) {

			$card_name   = array_map( 'wc_clean', $_POST['card_name'] );
			$card_code      = array_map( 'wc_clean', $_POST['card_code'] );
			$card_min_fee      = array_map( 'wc_clean', $_POST['card_min_fee'] );
			$card_percentage_fee           = array_map( 'wc_clean', $_POST['card_percentage_fee'] );

			foreach ( $card_name as $i => $name ) {
				if ( ! isset( $card_name[ $i ] ) ) {
					continue;
				}

	    		$cards[] = array(
	    			'card_name'   => $card_name[ $i ],
					'card_code'      => $card_code[ $i ],
					'card_min_fee'           => $card_min_fee[ $i ],
					'card_percentage_fee'            => $card_percentage_fee[ $i ]
	    		);
	    	}
    	}

    	update_option( 'woocommerce_card_fees', $cards );
    }

    	/**
		* Generate the payment form
		*
		* @access public
		* @return string
		*/
		public function payment_fields() {
			global $woocommerce;
			//$total_ex_fees = $woocommerce->cart->total;
			$total_ex_fees = number_format( $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total + $woocommerce->cart->shipping_tax_total + $woocommerce->cart->shipping_total - $woocommerce->cart->discount_total, 2, '.', '');
			$payment_currency = get_woocommerce_currency();
			$payment_fee = 0;
			echo $this->instructions;
			echo '<br>';
			$this->check_cookies();


			$i = 0;
		    if ( $this->card_details ) {
		        foreach ( $this->card_details as $card ) {
					$payment_min_fee = floatval(str_replace(",", ".", esc_attr( $card['card_min_fee'])));
					$payment_percent_fee = floatval(str_replace(",", ".", esc_attr( $card['card_percentage_fee'] )));
					$payment_card_type = esc_attr( $card['card_code'] );

					if ( ($total_ex_fees*$payment_percent_fee) < $payment_min_fee) {
						$payment_fee = $payment_min_fee;
					} else {
						$payment_fee = ($total_ex_fees*$payment_percent_fee);
					}

		            echo '<div><span><input type="radio" name="payment_card_type" value="'. $payment_card_type .'" data-minfee="'.$payment_min_fee.'" data-feepercent="'.$payment_percent_fee.'" data-fee="'.$payment_fee.'"';

		            if (($i == 0 && !isset($_COOKIE['payment_card_type'])) || $_COOKIE['payment_card_type'] == $payment_card_type) {
						echo ' checked';
					}
					echo ' /><img style="margin:0 6px;" src="'. dirname( plugin_dir_url( __FILE__ ) ) .'/'. dirname( plugin_basename( __FILE__ ) ) .'/cards/'.str_replace(",", "_", $payment_card_type).'.png" /> ' . esc_attr( $card['card_name'] ) . ' (+'. str_replace(".",",", str_replace(",","",number_format($payment_fee,2))) .' '.$payment_currency.')</span></div>';
					//echo 'test: ' . $_COOKIE['payment_card_type'] . ' : ' . $_POST['payment_card_type'];
			        $i++;
		        }
		        echo '<script language="javascript">payment_cards();</script>';
		    }


		}

		public function gateway_re_calculate() {
			global $woocommerce;
			$woocommerce->cart->add_fee('Kort gebyr', 500);
			//$woocommerce->cart->add_fee('Unique Transaction Code', $unique_transfer_code);

		}
		/**
		 * Generate the paypal button link
		 *
		 * @access public
		 * @param mixed $order_id
		 * @return string
		 */
		public function generate_payment_form( $order_id=0, $accepturl, $order ) {
				global $woocommerce;
				$payment_card_type = $_COOKIE['payment_card_type'];
				$payment_fee = 0;
				//$total_ex_fees = $woocommerce->cart->total;
				$total_w_fees = number_format($order->get_total(),2);

				$gateway_form = "
					<form name=\"wannafind_payment\" action=\"https://betaling.curanet.dk/paymentwindow/\" method=\"post\">
					  <input type=\"hidden\" name=\"shopid\" value=\"" . $this->shopid . "\" />
					  <input type=\"hidden\" name=\"amount\" value=\"" .  ( str_replace( ".","", str_replace( ",","", $total_w_fees ) ) ) . "\" />
				    <input type=\"hidden\" name=\"paytype\" value=\"creditcard\" />
				    <input type=\"hidden\" name=\"cardtype\" value=\"" . $payment_card_type . "\" />
				    <input type=\"hidden\" name=\"currency\" value=\"" . $this->currency . "\" />
				    <input type=\"hidden\" name=\"orderidprefix\" value=\"" . substr( $this->order_prefix , 0, 4) . "\" />
						<input type=\"hidden\" name=\"protocol\" value=\"1\" />
				    <input type=\"hidden\" name=\"directforward\" value=\"true\" />
				    <input type=\"hidden\" name=\"accepturl\" value=\"" . $accepturl . "\" />
				    <input type=\"hidden\" name=\"declineurl\" value=\"" . $woocommerce->cart->get_checkout_url() . "\" />
				    "; //$this->wannafind_decline_url
				    //<input type=\"hidden\" name=\"amount\" value=\"" .  ( str_replace( ".","", str_replace( ",","", preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_cart_total() )+$payment_fee  ) ) ) . "\" />
				    if ($order_id > 0) {
				    	$gateway_form .= "<input type=\"hidden\" name=\"orderid\" value=\"" . $order_id . "\" />";
				    }
				    $gateway_form .= "</form><script language=\"JavaScript\">document.wannafind_payment.submit();</script>";

				return $gateway_form;
		}


    	/**
		 * Output for the order received page.
		 *
		 * @access public
		 * @return void
		*/
		public function payment_page( $order_id ) {
			echo '<p>' . __( 'Thank you, continue to payment', 'woocommerce' ) . '</p>';
			$order      = wc_get_order( $order_id );
			echo $this->generate_payment_form( $order_id , $this->get_return_url($order), $order );
		}

		public function receipt_page( $order_id ) {
			//echo '<p>' . __( 'Thank you, continue to payment', 'woocommerce' ) . '</p>';
			//echo $this->generate_payment_form( $order_id , $this->get_return_url($order) );
	  		$order      = wc_get_order( $order_id );
	  		$order->update_status( 'on-hold', __( 'Check gateway', 'woocommerce' ) );
	  		// Mark order complete
	  		$order->payment_complete( $order_id );
		}

		/**
	     * Process the payment and return the result
	     *
	     * @param int $order_id
	     * @return array
	    */

		public function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );


			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);


		}

		public function check_cookies() {
			/** CHECK IF COOKIE EXISTS **/
			if ((isset($_COOKIE['payment_card_type']) && $_COOKIE['payment_card_type'] != "") && (isset($_COOKIE['payment_card_fee']) && $_COOKIE['payment_card_fee'] != "")) {
				/*add_action('woocommerce_cart_calculate_fees',
					function () {
						global $woocommerce;
						$woocommerce->cart->add_fee('Kort gebyr', number_format($_COOKIE['payment_card_fee']));
					}
				);
				echo "TEST " . $_COOKIE['payment_card_type'] . " :  " . $_COOKIE['payment_card_fee'];*/
			} else {
				if ( $this->card_details ) {
					global $woocommerce;
					$total_ex_fees = number_format( $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total + $woocommerce->cart->shipping_tax_total + $woocommerce->cart->shipping_total - $woocommerce->cart->discount_total, 2, '.', '');

				    foreach ( $this->card_details as $card ) {
						$payment_min_fee = floatval(str_replace(",", ".", esc_attr( $card['card_min_fee'])));
						$payment_percent_fee = floatval(str_replace(",", ".", esc_attr( $card['card_percentage_fee'] )));
						$payment_card_type = esc_attr( $card['card_code'] );
						if ( ($total_ex_fees*$payment_percent_fee) < $payment_min_fee) {
							$payment_fee = $payment_min_fee;
						} else {
							$payment_fee = ($total_ex_fees*$payment_percent_fee);
						}

						setcookie("payment_card_type", $payment_card_type, time()+3600, "/");
						setcookie("payment_card_fee", $payment_fee, time()+3600, "/");

						echo '<script language=\"JavaScript\">window.location.reload();</script>';
					    break;
				    }
				}
			}
		}
	}



	/**
 	* Add the Gateway to WooCommerce
 	**/
	function woocommerce_add_gateway_wannafind_gateway($methods) {
		$methods[] = 'WC_Gateway_Wannafind';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_wannafind_gateway' );
}
