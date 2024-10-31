<?php
/**
 * Template for generating PayHere hidden checkout form for save card hold on card
 *
 * @link       https://payhere.lk
 * @since      2.0.0
 *
 * @package    PayHere
 * @subpackage PayHere/public
 */

if ( ! $onsite_checkout_enabled ) {
	?>
	<form action="<?php echo esc_url( $authorize_url ); ?>" method="post" id="payhere_payment_form">
		<?php
		foreach ( $payhere_args as $key => $value ) {
			?>
			<input type="hidden" value="<?php echo esc_html( $value ); ?>" name="<?php echo esc_html( $key ); ?>" />
			<?php
		}
		?>
	</form>
	<?php
}
?>


<div class="pay-button-wrapper">
	<button type="button" class="payhere-button" id="show_payhere_payment_onsite" onclick="payhere_submit_trigger()">
		<?php echo esc_html__( 'Pay via Payhere Auth', 'woo_payhere' ); ?>
	</button>
</div>

