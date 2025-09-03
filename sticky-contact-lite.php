<?php

 /**
 * Plugin Name: Sticky Contact Lite
 * Plugin URI: https://github.com/hamidrrz/sticky-contact-lite
 * Description: Floating call & WhatsApp buttons. Shortcode: [sticky_contact]
 * Version: 1.0
 * Author: Hamidreza Rezaei
 * Author URI: https://hrezaei.ir
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sticky-contact-lite
 * Domain Path: /languages
 * Requires at least: 5.2
 * Tested up to: 6.6
 * Requires PHP: 5.6
 */

if (!defined('ABSPATH')) exit;

class StickyContactLite {
  const OPT = 'scl_options';
  const TD  = 'sticky-contact-lite';

  public function __construct() {
    // Load translations
    add_action('plugins_loaded', array($this, 'i18n'));
    // Admin UI & settings
    add_action('admin_menu', array($this, 'menu'));
    add_action('admin_init', array($this, 'settings'));
    // Shortcode
    add_shortcode('sticky_contact', array($this, 'render'));
    // Auto inject in theme footer/body
    add_action('wp_footer', array($this, 'auto_inject'), 999);
    add_action('wp_body_open', array($this, 'auto_inject'), 999);
  }

  /** Load plugin text domain for translations (.mo files) */
  public function i18n() {
    load_plugin_textdomain(self::TD, false, dirname(plugin_basename(__FILE__)) . '/languages/');
  }

  /** Register settings page */
  public function menu() {
    add_options_page(
      __('Sticky Contact Lite', self::TD),
      __('Sticky Contact', self::TD),
      'manage_options',
      'sticky-contact-lite',
      array($this, 'page')
    );
  }

  /** Register settings, sections, and fields */
  public function settings() {
    register_setting(self::OPT, self::OPT, array($this, 'sanitize'));
    add_settings_section('scl_main', '', '__return_false', self::OPT);

    add_settings_field('phone', __('Phone number (tel:)', self::TD), array($this,'field_phone'), self::OPT, 'scl_main');
    add_settings_field('wa', __('WhatsApp (number or wa.me/)', self::TD), array($this,'field_wa'), self::OPT, 'scl_main');
    add_settings_field('mobile', __('Show on mobile only', self::TD), array($this,'field_mobile'), self::OPT, 'scl_main');
    add_settings_field('auto', __('Auto-inject on all pages', self::TD), array($this,'field_auto'), self::OPT, 'scl_main');
    add_settings_field('position', __('Icons position', self::TD), array($this,'field_position'), self::OPT, 'scl_main');
  }

  /** Sanitize and normalize saved options */
  public function sanitize($v){
    $out = array();
    $out['phone']    = isset($v['phone']) ? sanitize_text_field($v['phone']) : '';
    $out['wa']       = isset($v['wa']) ? sanitize_text_field($v['wa']) : '';
    $out['mobile']   = !empty($v['mobile']) ? '1' : '0';
    $out['auto']     = !empty($v['auto']) ? '1' : '0';
    $out['position'] = (isset($v['position']) && $v['position']=='left') ? 'left' : 'right';
    return $out;
  }

  /** Settings page markup */
  public function page() { ?>
    <div class="wrap"><h1><?php _e('Sticky Contact Lite', self::TD); ?></h1>
      <form method="post" action="options.php">
        <?php settings_fields(self::OPT); do_settings_sections(self::OPT); submit_button(); ?>
      </form>
      <p><?php _e('Shortcode:', self::TD); ?> <code>[sticky_contact]</code></p>
    </div>
  <?php }

  /** Get options with defaults */
  private function get_opts() {
    $defaults = array(
      'phone'    => '',
      'wa'       => '',
      'mobile'   => '1',
      'auto'     => '1',
      'position' => 'right',
    );
    $saved = get_option(self::OPT, array());
    if (!is_array($saved)) $saved = array();
    return array_merge($defaults, $saved);
  }

  /** Field: phone */
  public function field_phone(){ $o=$this->get_opts(); ?>
    <input type="text" name="<?php echo self::OPT; ?>[phone]" value="<?php echo esc_attr($o['phone']); ?>" placeholder="+98..." class="regular-text">
    <p class="description"><?php _e('Example: +98912xxxxxxx', self::TD); ?></p>
  <?php }

  /** Field: WhatsApp */
  public function field_wa(){ $o=$this->get_opts(); ?>
    <input type="text" name="<?php echo self::OPT; ?>[wa]" value="<?php echo esc_attr($o['wa']); ?>" placeholder="98912xxxxxxx or wa.me/98912xxxxxxx" class="regular-text">
  <?php }

  /** Field: mobile only */
  public function field_mobile(){ $o=$this->get_opts(); ?>
    <label><input type="checkbox" name="<?php echo self::OPT; ?>[mobile]" <?php checked($o['mobile'],'1'); ?>> <?php _e('Yes', self::TD); ?></label>
  <?php }

  /** Field: auto inject */
  public function field_auto(){ $o=$this->get_opts(); ?>
    <label><input type="checkbox" name="<?php echo self::OPT; ?>[auto]" <?php checked($o['auto'],'1'); ?>> <?php _e('Yes', self::TD); ?></label>
  <?php }

  /** Field: position left/right */
  public function field_position(){ $o=$this->get_opts(); ?>
    <select name="<?php echo self::OPT; ?>[position]">
      <option value="right" <?php selected($o['position'],'right'); ?>><?php _e('Right', self::TD); ?></option>
      <option value="left"  <?php selected($o['position'],'left');  ?>><?php _e('Left', self::TD); ?></option>
    </select>
  <?php }

  /** Auto inject buttons if enabled */
  public function auto_inject() {
    $o = $this->get_opts();
    if ($o['auto'] === '1') echo $this->render(array(), '');
  }

  /** Shortcode renderer */
  public function render($atts = array(), $content = '') {
    $o = $this->get_opts();
    if (empty($o['phone']) && empty($o['wa'])) return '';

    // Position CSS snippet
    $pos = ($o['position']=='left') ? 'left:16px; right:auto;' : 'right:16px; left:auto;';

    // Build tel: link
    $tel = '';
    if (!empty($o['phone'])) {
      $clean = preg_replace('/\s+/', '', $o['phone']);
      $tel = 'tel:' . $clean;
    }

    // Build WhatsApp link
    $wa = isset($o['wa']) ? $o['wa'] : '';
    if (!empty($wa) && strpos($wa, 'wa.me/') === false) {
      $wa = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $wa);
    }

    // Hide on desktop if "mobile only" is on
    $hideDesktop = ($o['mobile'] === '1') ? 'display:none' : '';

    // Styles & Markup
    $html  = '';
    $html .= '<style>
      .scl-wrap{position:fixed;'.$pos.'bottom:16px;z-index:99999;display:flex;gap:10px;direction:rtl}
      .scl-btn{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:999px;
        background:#111;color:#fff;text-decoration:none;box-shadow:0 6px 20px rgba(0,0,0,.2);font-size:22px}
      .scl-btn:hover{opacity:.9}
      .scl-call{background:#111}
      .scl-wa{background:#25D366}
      @media (min-width:1025px){ .scl-wrap{'.$hideDesktop.'} }
    </style>';

    $html .= '<div class="scl-wrap" aria-label="Sticky Contact">';
    if (!empty($tel)) $html .= '<a class="scl-btn scl-call" href="'.esc_url($tel).'" aria-label="'.esc_attr__('Call', self::TD).'">☎</a>';
    if (!empty($wa))  $html .= '<a class="scl-btn scl-wa" href="'.esc_url($wa).'" target="_blank" rel="noopener" aria-label="'.esc_attr__('WhatsApp', self::TD).'">✆</a>';
    $html .= '</div>';

    return $html;
  }
}
new StickyContactLite();
