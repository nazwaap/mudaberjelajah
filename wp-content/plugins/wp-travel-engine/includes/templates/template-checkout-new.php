<?php
/**
 * @var $checkout \WPTravelEngine\Core\Controllers\Checkout
 */

global $wte_cart;

use WPTravelEngine\Core\Cart\Item;
use WPTravelEngine\Core\Controllers\Checkout;


$cart_items              = $wte_cart->getItems();
$tripid                  = $wte_cart->get_cart_trip_ids();
$cart_totals             = $wte_cart->get_total( false );
$trip_total              = $wte_cart->get_total();
$wte_settings            = get_option( 'wp_travel_engine_settings' );
$cart_discounts          = $wte_cart->get_discounts();
//$cart_data               = wptravelengine_get_newcost( $cart_discounts, $trip_total, $cart_totals );
$global_settings         = wp_travel_engine_get_settings();
$default_payment_gateway = isset( $global_settings[ 'default_gateway' ] ) && ! empty( $global_settings[ 'default_gateway' ] ) ? $global_settings[ 'default_gateway' ] : 'booking_only';

$cart_items = wptravelengine_cart()->getItems( true );
/**
 * Get first item from cart items since we are only supporting single trip booking.
 *
 * @var Item $cart_item
 * @since 5.7.4
 */
$cart_item = reset( $cart_items );
?>
<div class="wpte-bf-outer wpte-bf-checkout">
	<div class="wpte-bf-booking-steps">
		<?php
		$show_header_steps_checkout = apply_filters( 'wp_travel_engine_show_checkout_header_steps', true );

		if ( $show_header_steps_checkout ) {
			/**
			 * Action hook for header steps.
			 */
			do_action( 'wp_travel_engine_checkout_header_steps' );
		}

		$options = get_option( 'wp_travel_engine_settings', array() );
		?>
		<div class="wpte-bf-step-content-wrap">
			<?php if ( $checkout->has_form_fields() ) : ?>
				<div class="wpte-bf-checkout-form">
					<?php do_action( 'wp_travel_engine_before_billing_form' ); ?>
					<div
						class="wpte-bf-title"><?php echo esc_html( apply_filters( 'wpte_billings_details_title', esc_html__( 'Billing Details', 'wp-travel-engine' ) ) ); ?></div>
					<form id="wp-travel-engine-new-checkout-form" method="POST"
						  name="wp_travel_engine_new_checkout_form" action="" enctype="multipart/form-data"
						  class="">
						<input type="hidden" name="action" value="wp_travel_engine_new_booking_process_action">
						<?php
						// Create booking process action nonce for security.
						wp_nonce_field( 'wp_travel_engine_new_booking_process_nonce_action', 'wp_travel_engine_new_booking_process_nonce' );
						$checkout->render_form_fields();

						if ( wp_travel_engine_is_cart_partially_payable() ) :
							?>
							<div class="wpte-bf-field wpte-bf-radio wpte-bf_downpayment-options">
								<label for="" class="wpte-bf-label">
									<?php
									//									$partial_payment_label = apply_filters( 'wte_checkout_partial_pay_heading', __( 'Down payment options', 'wp-travel-engine' ) );
									$down_payment_label = apply_filters( 'wte_checkout_down_pay_label', __( 'Down payment(%s)', 'wp-travel-engine' ) );
									$full_payment_label = apply_filters( 'wte_checkout_full_pay_label', __( 'Full payment(%s)', 'wp-travel-engine' ) );
									echo apply_filters( 'wte_checkout_partial_pay_heading', __( 'Down payment options', 'wp-travel-engine' ) );
									?>
								</label>
								<div class="wpte-bf-radio-wrap">
									<input type="radio" name="wp_travel_engine_payment_mode" value="partial"
										   id="wp_travel_engine_payment_mode-partial" checked>
									<label for="wp_travel_engine_payment_mode-partial">
										<?php
										$down_payment_settings = $cart_item->down_payment_settings();
										echo esc_html( $checkout->down_payment_label( $down_payment_settings ) );
										?>
									</label>
								</div>
								<?php
								/**
								 * Condition added to hide full payment option.
								 *
								 * @since 5.7.1
								 */
								if ( $down_payment_settings[ 'trip_full_payment' ] && $down_payment_settings[ 'global_full_payment' ] ) :
									?>
									<div class="wpte-bf-radio-wrap">
										<input type="radio" name="wp_travel_engine_payment_mode" value="full_payment"
											   id="wp_travel_engine_payment_mode-full">
										<label for="wp_travel_engine_payment_mode-full">
											<?php
											$down_payment_settings = ( $down_payment_settings[ 'type' ] ?? '' ) === 'amount' ? wptravelengine_the_price( $wte_cart->get_total()[ 'total' ], false ) : '100%';
											echo sprintf( $checkout->full_payment_label(), $down_payment_settings );
											?>
										</label>
									</div>
								<?php endif; ?>
							</div>
						<?php
						endif;
						// Get active payment gateways to display publicly.
						$active_payment_methods = $checkout->get_active_payments();
						if ( ! empty( $active_payment_methods ) ) :
							?>
							<div class="wpte-bf-field wpte-bf-radio wpte-bf_payment-methods">
								<label for="" class="wpte-bf-label">
									<?php esc_html_e( 'Payment Method', 'wp-travel-engine' ); ?>
								</label>
								<?php
								$first_payment_option = true;
								foreach ( $active_payment_methods as $key => $payment_method ) :
									$default_gateway = $default_payment_gateway === $key ? true : $first_payment_option;
									?>
									<div class="wpte-bf-radio-wrap wpte-bf_payment-method">
										<input
											data-target-info="wpte__checkout-info--<?php echo esc_attr( $key ); ?>" <?php checked( $default_gateway, true ); ?>
											type="radio" name="wpte_checkout_paymnet_method"
											value="<?php echo esc_attr( $key ); ?>"
											id="wpte-checkout-paymnet-method-<?php echo esc_attr( $key ); ?>">
										<label for="wpte-checkout-paymnet-method-<?php echo esc_attr( $key ); ?>">
											<?php
											if ( ! empty( $payment_method[ 'icon_url' ] ) ) :
												?>
												<img src="<?php echo esc_url( $payment_method[ 'icon_url' ] ); ?>"
													 alt="<?php echo esc_attr( $payment_method[ 'label' ] ); ?>">
											<?php
											else :
												echo esc_html( $payment_method[ 'label' ] );
											endif;
											?>
										</label>
										<?php
										if ( ! empty( $payment_method[ 'info_text' ] ) ) :
											?>
											<div id="wpte__checkout-info--<?php echo esc_attr( $key ); ?>"
												 class="wpte-checkout-payment-info<?php echo esc_attr( $first_payment_option ? '' : ' hidden' ); ?>"><?php echo esc_html( $payment_method[ 'info_text' ] ); ?></div>
										<?php
										endif;
										?>
									</div>
									<?php
									$first_payment_option = false;
								endforeach;
								?>
							</div>
						<?php
						endif;

						$checkout->render_privacy_form_fields();

						$checkout->submit_button();
						?>
					</form>
					<?php do_action( 'wte_booking_after_checkout_form_close' ); ?>
				</div><!-- .wpte-bf-checkout-form -->
			<?php endif; ?>
			<div class="wpte-bf-book-summary">
				<?php $checkout->template_mini_cart(); ?>
			</div><!-- .wpte-bf-book-summary -->
		</div><!-- .wpte-bf-step-content-wrap -->
	</div><!-- .wpte-bf-booking-steps -->
</div><!-- .wpte-bf-outer -->
