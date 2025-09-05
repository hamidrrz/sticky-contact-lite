<?php
/**
 * Plugin Name: Sticky Contact Lite
 * Plugin URI: https://github.com/hamidrrz/sticky-contact-lite
 * Description: Floating call & WhatsApp buttons. Shortcode: [sticky_contact]
 * Version: 1.0.1
 * Author: Hamidreza Rezaei
 * Author URI: https://hrezaei.ir
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sticky-contact-lite
 * Domain Path: /languages
 * Requires at least: 5.2
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class StickyContactLite {
	const OPT       = 'scl_options';
	const MENU_SLUG = 'sticky-contact-lite';

	/** Ensure we only print markup once per page (footer/body_open). */
	private bool $printed = false;

	public function __construct() {
		// Admin UI & settings
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );

		// Shortcode
		add_shortcode( 'sticky_contact', array( $this, 'render' ) );

		// Auto inject (prints once even if both hooks fire)
		add_action( 'wp_body_open', array( $this, 'auto_inject' ), 999 );
		add_action( 'wp_footer', array( $this, 'auto_inject' ), 999 );

		// Quick "Settings" link on plugins list
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ) );
	}

	/** Allowed HTML for safe output (used by wp_kses). */
	private function allowed_html(): array {
		return array(
			'style' => array(),
			'div'   => array(
				'class'      => true,
				'aria-label' => true,
			),
			'a'     => array(
				'class'      => true,
				'href'       => true,
				'target'     => true,
				'rel'        => true,
				'aria-label' => true,
			),
		);
	}

	/** Add settings link on Plugins page. */
	public function settings_link( $links ) {
		$url      = admin_url( 'options-general.php?page=' . self::MENU_SLUG );
		$settings = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'sticky-contact-lite' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}

	/** Register settings page. */
	public function menu() {
		add_options_page(
			__( 'Sticky Contact Lite', 'sticky-contact-lite' ),
			__( 'Sticky Contact', 'sticky-contact-lite' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'page' )
		);
	}

	/** Register settings, sections, and fields. */
	public function settings() {
		register_setting( self::OPT, self::OPT, array( $this, 'sanitize' ) );
		add_settings_section( 'scl_main', '', '__return_false', self::OPT );

		add_settings_field( 'phone', __( 'Phone number (tel:)', 'sticky-contact-lite' ), array( $this, 'field_phone' ), self::OPT, 'scl_main' );
		add_settings_field( 'wa', __( 'WhatsApp (digits or wa.me/)', 'sticky-contact-lite' ), array( $this, 'field_wa' ), self::OPT, 'scl_main' );
		add_settings_field( 'mobile', __( 'Show on mobile only', 'sticky-contact-lite' ), array( $this, 'field_mobile' ), self::OPT, 'scl_main' );
		add_settings_field( 'auto', __( 'Auto-inject on all pages', 'sticky-contact-lite' ), array( $this, 'field_auto' ), self::OPT, 'scl_main' );
		add_settings_field( 'position', __( 'Icons position', 'sticky-contact-lite' ), array( $this, 'field_position' ), self::OPT, 'scl_main' );
	}

	/** Sanitize and normalize saved options. */
	public function sanitize( $v ) {
		$out = array();

		// phone: keep digits and optional leading +
		if ( isset( $v['phone'] ) ) {
			$phone = preg_replace( '/[^0-9+]/', '', (string) $v['phone'] );
			if ( strpos( $phone, '+' ) > 0 ) {
				$phone = ltrim( str_replace( '+', '', $phone ), '+' );
			}
			$out['phone'] = $phone;
		} else {
			$out['phone'] = '';
		}

		// wa: accept wa.me or api.whatsapp.com links, else digits only to build wa.me later
		if ( isset( $v['wa'] ) ) {
			$wa = trim( (string) $v['wa'] );
			if ( $wa !== '' ) {
				if ( strpos( $wa, 'wa.me/' ) !== false || strpos( $wa, 'api.whatsapp.com' ) !== false ) {
					if ( strpos( $wa, 'http' ) !== 0 ) $wa = 'https://' . ltrim( $wa, '/' );
					$out['wa'] = esc_url_raw( $wa );
				} else {
					$out['wa'] = preg_replace( '/[^0-9]/', '', $wa ); // digits only
				}
			} else {
				$out['wa'] = '';
			}
		} else {
			$out['wa'] = '';
		}

		$out['mobile']   = ! empty( $v['mobile'] ) ? '1' : '0';
		$out['auto']     = ! empty( $v['auto'] ) ? '1' : '0';
		$out['position'] = ( isset( $v['position'] ) && $v['position'] === 'left' ) ? 'left' : 'right';

		return $out;
	}

	/** Settings page. */
	public function page() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		echo '<div class="wrap"><h1>' . esc_html__( 'Sticky Contact Lite', 'sticky-contact-lite' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( self::OPT );
		do_settings_sections( self::OPT );
		submit_button();
		echo '</form>';
		echo '<p>' . esc_html__( 'Shortcode:', 'sticky-contact-lite' ) . ' <code>[sticky_contact]</code></p>';
		echo '</div>';
	}

	/** Get options with defaults. */
	private function get_opts() {
		$defaults = array(
			'phone'    => '',
			'wa'       => '',
			'mobile'   => '1',
			'auto'     => '1',
			'position' => 'right',
		);
		$saved = get_option( self::OPT, array() );
		if ( ! is_array( $saved ) ) $saved = array();
		return array_merge( $defaults, $saved );
	}

	/** Field: phone */
	public function field_phone() {
		$o = $this->get_opts(); ?>
		<input type="text" name="<?php echo esc_attr( self::OPT ); ?>[phone]" value="<?php echo esc_attr( $o['phone'] ); ?>" placeholder="+98..." class="regular-text">
		<p class="description"><?php esc_html_e( 'Example: +98912xxxxxxx', 'sticky-contact-lite' ); ?></p>
	<?php }

	/** Field: WhatsApp */
	public function field_wa() {
		$o = $this->get_opts(); ?>
		<input type="text" name="<?php echo esc_attr( self::OPT ); ?>[wa]" value="<?php echo esc_attr( $o['wa'] ); ?>" placeholder="98912xxxxxxx or wa.me/98912xxxxxxx" class="regular-text">
	<?php }

	/** Field: mobile only */
	public function field_mobile() {
		$o = $this->get_opts(); ?>
		<label><input type="checkbox" name="<?php echo esc_attr( self::OPT ); ?>[mobile]" <?php checked( $o['mobile'], '1' ); ?>> <?php esc_html_e( 'Yes', 'sticky-contact-lite' ); ?></label>
	<?php }

	/** Field: auto inject */
	public function field_auto() {
		$o = $this->get_opts(); ?>
		<label><input type="checkbox" name="<?php echo esc_attr( self::OPT ); ?>[auto]" <?php checked( $o['auto'], '1' ); ?>> <?php esc_html_e( 'Yes', 'sticky-contact-lite' ); ?></label>
	<?php }

	/** Field: position left/right */
	public function field_position() {
		$o = $this->get_opts(); ?>
		<select name="<?php echo esc_attr( self::OPT ); ?>[position]">
			<option value="right" <?php selected( $o['position'], 'right' ); ?>><?php esc_html_e( 'Right', 'sticky-contact-lite' ); ?></option>
			<option value="left"  <?php selected( $o['position'], 'left' );  ?>><?php esc_html_e( 'Left', 'sticky-contact-lite' );  ?></option>
		</select>
	<?php }

	/** Ensure single print, then output buttons if auto is enabled (escaped). */
	public function auto_inject() {
		if ( $this->printed ) return;

		$o = $this->get_opts();
		if ( $o['auto'] === '1' ) {
			$markup = $this->render( array(), '' );
			if ( $markup !== '' ) {
				echo wp_kses( $markup, $this->allowed_html() );
				$this->printed = true;
			}
		}
	}

	/** Shortcode renderer (returns safe HTML string). */
	public function render( $atts = array(), $content = '' ) {
		$o = $this->get_opts();
		if ( empty( $o['phone'] ) && empty( $o['wa'] ) ) return '';

		// Position CSS
		$pos = ( $o['position'] === 'left' ) ? 'left:16px; right:auto;' : 'right:16px; left:auto;';

		// tel: link
		$tel = '';
		if ( ! empty( $o['phone'] ) ) {
			$clean = preg_replace( '/[^0-9+]/', '', $o['phone'] );
			$tel   = 'tel:' . $clean;
		}

		// WhatsApp link
		$wa = '';
		if ( ! empty( $o['wa'] ) ) {
			if ( strpos( $o['wa'], 'wa.me/' ) !== false || strpos( $o['wa'], 'api.whatsapp.com' ) !== false ) {
				$wa = ( strpos( $o['wa'], 'http' ) === 0 ) ? $o['wa'] : 'https://' . ltrim( $o['wa'], '/' );
			} else {
				$digits = preg_replace( '/[^0-9]/', '', $o['wa'] );
				if ( $digits !== '' ) $wa = 'https://wa.me/' . $digits;
			}
		}

		// Hide on desktop if "mobile only"
		$hideDesktop = ( $o['mobile'] === '1' ) ? 'display:none' : '';

		// Styles & Markup
		$html  = '';
		$html .= '<style>
			.scl-wrap{position:fixed;' . $pos . 'bottom:16px;z-index:9999999;display:flex;gap:10px}
			.scl-btn{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:999px;
				background:#111;color:#fff;text-decoration:none;box-shadow:0 6px 20px rgba(0,0,0,.2);font-size:22px}
			.scl-btn:hover{opacity:.9}
			.scl-call{background:#111}
			.scl-wa{background:#25D366}
			@media (min-width:1025px){ .scl-wrap{' . $hideDesktop . '} }
		</style>';

		$html .= '<div class="scl-wrap" aria-label="' . esc_attr__( 'Sticky Contact', 'sticky-contact-lite' ) . '">';
		if ( ! empty( $tel ) ) {
			$html .= '<a class="scl-btn scl-call" href="' . esc_url( $tel ) . '" aria-label="' . esc_attr__( 'Call', 'sticky-contact-lite' ) . '">☎</a>';
		}
		if ( ! empty( $wa ) ) {
			$html .= '<a class="scl-btn scl-wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'WhatsApp', 'sticky-contact-lite' ) . '">✆</a>';
		}
		$html .= '</div>';

		return $html;
	}
}

// Cleanup on uninstall (remove saved options).
if ( function_exists( 'register_uninstall_hook' ) ) {
	register_uninstall_hook( __FILE__, 'sticky_contact_lite_uninstall' );
}
function sticky_contact_lite_uninstall() {
	delete_option( StickyContactLite::OPT );
}

// Boot
new StickyContactLite();
