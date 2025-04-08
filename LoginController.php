<?php
/**
 * User login controller I wrote for a corporate website.
 *
 * @package CLIENT\Core\Controller\Theme
 */

namespace CLIENT\Core\Controller;

use CLIENT\Core\Controller\Controller;

/**
 * Controller responsible for login functionality on the front-end.
 */
class LoginController extends Controller {

	/**
	 * Customer portal page slug.
	 *
	 * @var int|null
	 */
	public $customer_portal_page_id;

	/**
	 * Boot the controller.
	 *
	 * @return void
	 */
	public function set_up() {
		$this->customer_portal_page_id = get_option( 'options_portal_homepage' ) ?? 0;

		if ( $this->customer_portal_page_id && get_post_status( $this->customer_portal_page_id ) ) {
			add_action( 'init', [ $this, 'restrict_admin' ] );
			add_action( 'template_redirect', [ $this, 'control_login_page' ] );
		}

		// Disabling Woocommerce's admin prevention filter; we have our own with restrict_admin()
		add_filter( 'woocommerce_prevent_admin_access', '__return_false' );
	}

	/**
	 * Restrict admin access from non-admin users.
	 *
	 * @return void
	 */
	public function restrict_admin() {
		if ( is_admin() && is_user_logged_in() && ! current_user_can( 'wpsl_store_locator_manager' ) && ! current_user_can( 'editor' ) && ! current_user_can( 'administrator' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) { // phpcs:ignore
			$this->redirect_to_customer_portal();
		}
	}

	/**
	 * Control the login page.
	 *
	 * @return void
	 */
	public function control_login_page() {
		if ( is_page_template( 'page-login.php' ) && is_user_logged_in() ) {
			$this->redirect_to_customer_portal();
		}
	}

	/**
	 * Redirect user to the customer portal homepage.
	 *
	 * @return void
	 */
	protected function redirect_to_customer_portal() {
		$redirect_url = home_url();

		if ( $this->customer_portal_page_id ) {
			$redirect_url = get_permalink( $this->customer_portal_page_id );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
