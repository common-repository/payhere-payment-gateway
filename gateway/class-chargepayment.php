<?php
/**
 * Handle Merchant API call for charging and authentication call
 *
 * @link       https://www.payhere.lk
 * @since      2.0.0
 *
 * @package    PayHere
 * @subpackage PayHere/gateway
 */

/**
 * Call the PayHere Merchant API for charging a payment
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    PayHere
 * @subpackage PayHere/gateway
 * @author     Dilshan Jayasanka <dilshan@payhere.lk>
 */
class ChargePayment extends PayHereToken {


	/**
	 * Function for submit charge.
	 *
	 * @description  Call PayHere Merchant API for charge payment from saved card.
	 *
	 * @param string $token PayHere auth token aquired by auth token call.
	 * @param string $customer_token Tokenized Customer card return by pre-approve call.
	 * @param string $order_id Woocommerce order id.
	 * @param string $amount Charging amount in 0.00 format.
	 * @return Array Response body of PayHere charge call.
	 */
	private function submitCharge( $token, $customer_token, $order_id, $amount ) {
		$url = $this->get_payhere_chargin_api_url();

		$fields = array(
			'type'           => 'PAYMENT',
			'order_id'       => 'WC_' . $order_id,
			'items'          => 'Woocommerce  Order :' . $order_id,
			'currency'       => get_woocommerce_currency(),
			'amount'         => $amount,
			'customer_token' => $customer_token,
			'custom_1'       => '',
			'custom_2'       => '',
			'itemList'       => array(),
		);

		$args = array(
			'body'        => wp_json_encode( $fields ),
			'timeout'     => '10',
			'redirection' => '1',
			'httpversion' => '2.0',
			'blocking'    => true,
			'headers'     => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'cookies'     => array(),
			'data_format' => 'body',
		);

		$this->gateway_util->payhere_log( 'chargin_ARGS', $args );

		$res = wp_remote_post( $url, $args );

		if ( $res instanceof WP_Error ) {
			return false;
		}

		return $res['body'];
	}


	/**
	 * Function for validate request and call submitCharge()
	 *
	 * @description  Call get auth token and submitCharge() and update order accoring to PayHere response.
	 *
	 * @param WC_Order $order Woocommerce order.
	 * @param string   $token Tokenized Customer card return by pre-approve call.
	 * @return Array Generic Response for frontend with payment status.
	 */
	public function charge_payment( WC_Order $order, $token ) {
		$json = array();

		$_auth_token_data = $this->get_authorization_token();
		$auth_token_data  = json_decode( $_auth_token_data );
		$this->gateway_util->payhere_log( 'authorization_token', $_auth_token_data );

		if ( isset( $auth_token_data->access_token ) && ! empty( $auth_token_data->access_token ) ) {
			$this->gateway_util->payhere_log( 'ORDER', $order->get_id() );
			$_charge_response = $this->submitCharge(
				$auth_token_data->access_token,
				$token,
				$order->get_id(),
				$order->get_total()
			);

			$this->gateway_util->payhere_log( 'charge_response', $_charge_response );
			$charge_response = json_decode( $_charge_response );
			if ( '1' === $charge_response->status ) {

				if ( '2' === $charge_response->data->status_code ) {
					$order->payment_complete();
					$order->add_order_note( $charge_response->msg );
					$order->add_order_note( 'PayHere payment successful.<br/>PayHere Payment ID: ' . $charge_response->data->payment_id );

					$json['type']    = 'OK';
					$json['message'] = 'Payment Charged Successfully.';
				} else {
					$json['type']    = 'ERR';
					$json['message'] = 'Payment Un-Successful. Code : ' . $charge_response->data->status_code;
				}
			} else {
				$json['type']    = 'ERR';
				$json['message'] = 'Can\'t make the payment. Payment Charge Request Failed.<br/>' . $charge_response->msg;
			}
		} else {
			$json['type']    = 'ERR';
			$json['message'] = 'Can\'t make the payment. Can\'t Generate the Authorization Tokens.';
		}

		return $json;
	}
}
