<?php
/*
Plugin Name: WooCommerce Saman Gateway - درگاه بانک سامان ووکامرس
Version: 1.1
Description:  افزونه درگاه بانکی سامان برای ووکامرس. تسخه تر و تمیز مرتبی که کار میکنه و رایگانه!
Plugin URI: http://sadoo.me/?s=افزونه
Author: Sadoo
Author URI: http://sadoo.me/

*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function WC_Load_Saman_Gateway() {
	if ( class_exists( 'WC_Payment_Gateway' ) && ! class_exists( 'WC_Saman_Gateway' ) ) {
		add_filter( 'woocommerce_payment_gateways', 'WC_Add_Saman_Gateway' );
		function WC_Add_Saman_Gateway( $methods ) {
			$methods[] = 'WC_Saman_Gateway';
			return $methods;
		}
	}


	class WC_Saman_Gateway extends WC_Payment_Gateway {
		public function __construct() {
			$this->id                 = 'WC_Saman_Gateway';
			$this->method_title       = 'درگاه بانک سامان';
			$this->method_description = 'تنظیمات درگاه پرداخت بانک سامان برای افزونه فروشگاه ساز ووکامرس';
			$this->icon               = WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/assets/images/logo.png';

			$this->title = ' 3درگاه بانک سامان';

            $this->init_form_fields();
			$this->init_settings();

			$this->description        = $this->get_option('description');
            $this->title              = $this->get_option('title');

            $this->merchantcode       = $this->get_option('merchantcode');

			$this->success_massage    = $this->get_option('success_massage');
			$this->failed_massage     = $this->get_option('failed_massage');
			$this->has_fields         = false;

			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
					array( $this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
			}
			add_action( 'woocommerce_receipt_' . $this->id . '',
				array( $this, 'marco_go_to_saman_and_bring_us_things' ) );
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ) . '',
				array( $this, 'marco_what_did_you_bring_us_from_saman' ) );

		}

		public function init_form_fields() {
			$this->form_fields = [
				'base_confing'    => array(
					'title'       => 'تنظیمات پایه',
					'type'        => 'title',
					'description' => '',
				),
				'enabled'         => array(
					'title'       => 'فعالسازی/غیرفعالسازی',
					'type'        => 'checkbox',
					'label'       => 'فعالسازی درگاه بانک سامان',
					'description' => 'برای فعالسازی درگاه پرداخت بانک سامان باید چک باکس را تیک بزنید',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'title'           => array(
					'title'       => 'عنوان درگاه',
					'type'        => 'text',
					'description' => 'عنوان درگاه که در طی خرید به مشتری نمایش داده میشود',
					'default'     => 'بانک سامان',
					'desc_tip'    => true,
				),
				'description'     => array(
					'title'       => 'توضیحات درگاه',
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => 'توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد',
					'default'     => 'پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه بانک سامان',

				),
				'account_confing' => array(
					'title'       => 'تنظیمات حساب بانک سامان',
					'type'        => 'title',
					'description' => '',
				),
				'merchantcode'    => array(
					'title'       => 'مرچنت آیدی',
					'type'        => 'text',
					'description' => 'مرچنت آیدی ای که از بانک سامان دریافت نموده اید',
					'default'     => '',
					'desc_tip'    => true
				),
				'payment_confing' => array(
					'title'       => 'تنظیمات عملیات پرداخت',
					'type'        => 'title',
					'description' => '',
				),
				'success_massage' => array(
					'title'       => 'پیام پرداخت موفق',
					'type'        => 'textarea',
					'description' => 'متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید. همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (توکن) بانک سامان استفاده نمایید .',
					'default'     => 'با تشکر از شما. سفارش شما با موفقیت پرداخت شد.',
				),
				'failed_massage'  => array(
					'title'       => 'پیام پرداخت ناموفق',
					'type'        => 'textarea',
					'description' => 'متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید. همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید. این دلیل خطا از سایت سامان ارسال میگردد.',
					'default'     => 'پرداخت شما ناموفق بوده است. لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید.',
				),
			];
		}


		public function admin_options() {
			parent::admin_options();
		}

		public function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );

			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);
		}

		public function marco_go_to_saman_and_bring_us_things( $order_id ) {
			global $woocommerce;
			$woocommerce->session->saman_order_id = $order_id;
			$order                                = new WC_Order( $order_id );
			$currency                             = $order->get_order_currency();

			$total = intval( $order->order_total );

			if (
				strtolower( $currency ) == strtolower( 'IRT' )
				|| strtolower( $currency ) == strtolower( 'TOMAN' )
				|| strtolower( $currency ) == strtolower( 'Iran TOMAN' )
				|| strtolower( $currency ) == strtolower( 'Iranian TOMAN' )
				|| strtolower( $currency ) == strtolower( 'Iran-TOMAN' )
				|| strtolower( $currency ) == strtolower( 'Iranian-TOMAN' )
				|| strtolower( $currency ) == strtolower( 'Iran_TOMAN' )
				|| strtolower( $currency ) == strtolower( 'Iranian_TOMAN' )
				|| strtolower( $currency ) == strtolower( 'تومان' )
				|| strtolower( $currency ) == strtolower( 'تومان ایران' )
			) {
				$total = $total * 10;
			} else if ( strtolower( $currency ) == strtolower( 'IRHT' ) ) {
				$total = $total * 10000;
			} else if ( strtolower( $currency ) == strtolower( 'IRHR' ) ) {
				$total = $total * 1000;
			} else if ( strtolower( $currency ) == strtolower( 'IRR' ) ) {
				$total = $total;
			}

			$MerchantCode = $this->merchantcode;
			$CallbackUrl  = add_query_arg( 'wc_order', $order_id, WC()->api_request_url( 'WC_Saman_Gateway' ) );
			$ResNumber    = $order_id;
			$Payment_URL  = 'https://sep.shaparak.ir/Payment.aspx';

			echo 'در حال انتقال به درگاه بانکی، لطفا منتظر باشید...';
			echo '
	        <form method="POST" action="' . $Payment_URL . '" id="pay_form" style="display:none">
		        <input type="text" name="Amount" value="' . $total . '">
		        <input type="text" name="MID" value="' . $MerchantCode . '">
		        <input type="text" name="ResNum" value="' . $ResNumber . '">
		        <input type="text" name="RedirectURL" value="' . $CallbackUrl . '">
		        <input type="submit" value="submit">
	        </form>';
			echo '<script type="text/javascript"> document.getElementById("pay_form").submit(); </script>';
		}

		public function marco_what_did_you_bring_us_from_saman() {
			global $woocommerce;
			$State  = isset( $_POST['State'] ) ? $_POST['State'] : '';
			$ResNum = isset( $_POST['ResNum'] ) ? $_POST['ResNum'] : '';
			$RefNum = isset( $_POST['RefNum'] ) ? $_POST['RefNum'] : '';

			# Get order ID one way or another

			if ( isset( $_GET['wc_order'] ) ) {
				$order_id = $_GET['wc_order'];
			} else if ( $ResNum ) {
				$order_id = $ResNum;
			} else {
				$order_id = $woocommerce->session->saman_order_id;
				unset( $woocommerce->session->saman_order_id );
			}

			# We got such order right?

			if ( $order_id && get_post_type( $order_id ) == 'shop_order' ) {
				$order    = new WC_Order( $order_id );
				$currency = $order->get_order_currency();
				$err      = 0;
				if ( $order->status != 'completed' ) {
					if ( $State != '' && $ResNum != '' && $RefNum != '' ) {
						$MerchantCode = $this->merchantcode;
						$client       = new SoapClient( 'https://acquirer.samanepay.com/payments/referencepayment.asmx?WSDL',
							array( 'encoding' => 'UTF-8' ) );
						$result       = $client->VerifyTransaction( $RefNum, $MerchantCode );
						if ( $result <= 0 ) {
							$Status  = 'failed';
							$Message = "تراکنش با شناسه بانکی {$RefNum} با شکست مواجه شد و مبلغ پرداختی به صورت خودکار بازگشت داده خواهد شد. ";
							$err     = $result;
						} else {
							$Status = 'done';
						}
					} else {
						$Status  = 'failed';
						$Fault   = - 300;
						$Message = "یک یا چند پارامتر ارسال نشده است.";
					}
					if ( $Status == 'done' ) {
						update_post_meta( $order_id, '_transaction_id', $RefNum );
						$order->payment_complete( $RefNum );
						$woocommerce->cart->empty_cart();
						$Note = sprintf( 'پرداخت موفقیت آمیز بود .<br/> کد رهگیری : %s',
							$RefNum );
						$order->add_order_note( $Note, 1 );
						$Notice = wpautop( wptexturize( $this->success_massage ) );
						$Notice = str_replace( "{transaction_id}", $RefNum, $Notice );
						if ( $Notice ) {
							wc_add_notice( $Notice, 'success' );
						}
						wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
						exit;
					} else {
						# The status is not "done"
						$tr_id = ( isset( $result ) ) ? ( '<br/>شماره خطا: ' . $result ) : '';
						$Note  = sprintf( 'خطا در هنگام بازگشت از بانک : %s %s', $Message, $tr_id );
						if ( $Note ) {
							$order->add_order_note( $Note, 1 );
						}
						$Notice = wpautop( wptexturize( $this->failed_massage ) );
						$Notice = str_replace( "{transaction_id}", $RefNum, $Notice );
						$Notice = str_replace( "{fault}", $Message, $Notice );
						if ( $Notice ) {
							wc_add_notice( $Notice, 'error' );
						}
						wp_redirect( $woocommerce->cart->get_checkout_url() );
						exit;
					}
				} else {
					$Transaction_ID = get_post_meta( $order_id, '_transaction_id', true );
					$Notice         = wpautop( wptexturize( $this->success_massage ) );
					$Notice         = str_replace( "{transaction_id}", $Transaction_ID, $Notice );
					if ( $Notice ) {
						wc_add_notice( $Notice, 'success' );
					}
					wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
					exit;
				}
			} else {
				$Fault  = 'شماره سفارش وجود ندارد .';
				$Notice = wpautop( wptexturize( $this->failed_massage ) );
				$Notice = str_replace( "{fault}", $Fault, $Notice );
				if ( $Notice ) {
					wc_add_notice( $Notice, 'error' );
				}
				wp_redirect( $woocommerce->cart->get_checkout_url() );
				exit;
			}
		}
	}

}

add_action( 'plugins_loaded', 'WC_Load_Saman_Gateway', 0 );
