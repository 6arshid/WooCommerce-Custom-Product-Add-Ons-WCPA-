<?php
/**
 * Plugin Name: WhitestudioTeam – WooCommerce Product Add‑Ons (WST WCPA)
 * Description: Product Add‑Ons for WooCommerce: text, textarea, select, radio, checkbox, file upload, number + flat/percent pricing. Per‑product and simple global add‑ons.
 * Version: 1.2.0
 * Author: WhitestudioTeam
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 * License: GPLv3 or later
 * Text Domain: whitestudioteam-wcpa
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) { exit; }

/**
 * Constants
 */
if (!defined('WHITESTUDIOTEAM_WCPA_META_KEY'))       define('WHITESTUDIOTEAM_WCPA_META_KEY', '_whitestudioteam_wcpa_addons');
if (!defined('WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL'))  define('WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL', 'whitestudioteam_wcpa_global_addons');
if (!defined('WHITESTUDIOTEAM_WCPA_NONCE'))          define('WHITESTUDIOTEAM_WCPA_NONCE', 'whitestudioteam_wcpa_nonce');
if (!defined('WHITESTUDIOTEAM_WCPA_MAX_UPLOAD'))     define('WHITESTUDIOTEAM_WCPA_MAX_UPLOAD', 5 * 1024 * 1024); // 5MB default

/**
 * i18n – base language English, translation‑ready
 */
function whitestudioteam_load_textdomain() {
	load_plugin_textdomain('whitestudioteam-wcpa', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'whitestudioteam_load_textdomain');

/**
 * Admin: meta box
 */
function whitestudioteam_register_meta_box() {
	add_meta_box(
		'whitestudioteam_wcpa_meta',
		__('Product Add‑Ons', 'whitestudioteam-wcpa'),
		'whitestudioteam_meta_box_html',
		'product',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'whitestudioteam_register_meta_box');

function whitestudioteam_admin_assets($hook) {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (in_array($hook, ['post.php', 'post-new.php'], true) && $screen && $screen->post_type === 'product') {
		wp_enqueue_style('whitestudioteam-wcpa-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', [], '1.2.0');
		wp_enqueue_script('whitestudioteam-wcpa-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], '1.2.0', true);
	}
}
add_action('admin_enqueue_scripts', 'whitestudioteam_admin_assets');

function whitestudioteam_meta_box_html($post) {
	wp_nonce_field(WHITESTUDIOTEAM_WCPA_NONCE, WHITESTUDIOTEAM_WCPA_NONCE);
	$addons = get_post_meta($post->ID, WHITESTUDIOTEAM_WCPA_META_KEY, true);
	if (!is_array($addons)) { $addons = []; }
	?>
	<div id="whitestudioteam-wcpa-wrap" class="wcpa-wrap">
		<p class="description"><?php _e('Add custom fields shown on the product page. Each field can add a price (flat or %).', 'whitestudioteam-wcpa'); ?></p>
		<table class="widefat wcpa-table" id="whitestudioteam-wcpa-table">
			<thead>
				<tr>
					<th><?php _e('Label', 'whitestudioteam-wcpa'); ?></th>
					<th><?php _e('Type', 'whitestudioteam-wcpa'); ?></th>
					<th><?php _e('Required', 'whitestudioteam-wcpa'); ?></th>
					<th><?php _e('Options (comma‑separated for select/radio/checkbox)', 'whitestudioteam-wcpa'); ?></th>
					<th><?php _e('Price Type', 'whitestudioteam-wcpa'); ?></th>
					<th><?php _e('Price Value', 'whitestudioteam-wcpa'); ?></th>
					<th><?php _e('Max Length', 'whitestudioteam-wcpa'); ?></th>
					<th><?php _e('Actions', 'whitestudioteam-wcpa'); ?></th>
				</tr>
			</thead>
			<tbody id="whitestudioteam-wcpa-rows">
				<?php foreach ($addons as $index => $field) : ?>
					<?php whitestudioteam_row_html($index, $field); ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" id="whitestudioteam-wcpa-add-row"><?php _e('Add Field', 'whitestudioteam-wcpa'); ?></button></p>
	</div>
	<script type="text/html" id="tmpl-whitestudioteam-wcpa-row">
		<?php whitestudioteam_row_html('{{INDEX}}', []); ?>
	</script>
	<?php
}

function whitestudioteam_wcpa_allowed_types() {
	return ['text','textarea','select','radio','checkbox','file','number'];
}

function whitestudioteam_wcpa_whitelist_type($type) {
	$type = sanitize_key($type);
	return in_array($type, whitestudioteam_wcpa_allowed_types(), true) ? $type : 'text';
}

function whitestudioteam_row_html($index, $field) {
	$defaults = [
		'label' => '',
		'type' => 'text', // text|textarea|select|radio|checkbox|file|number
		'required' => 0,
		'options' => '',
		'price_type' => 'none', // none|flat|percent
		'price_value' => '',
		'max_len' => '',
	];
	$field = wp_parse_args($field, $defaults);
	$field['type'] = whitestudioteam_wcpa_whitelist_type($field['type']);
	?>
	<tr class="wcpa-row">
		<td><input type="text" name="wcpa[<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($field['label']); ?>" class="widefat" /></td>
		<td>
			<select name="wcpa[<?php echo esc_attr($index); ?>][type]">
				<?php foreach (whitestudioteam_wcpa_allowed_types() as $t): ?>
					<option value="<?php echo esc_attr($t); ?>" <?php selected($field['type'], $t); ?>><?php echo esc_html(ucfirst($t)); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td><input type="checkbox" name="wcpa[<?php echo esc_attr($index); ?>][required]" value="1" <?php checked($field['required'], 1); ?> /></td>
		<td><input type="text" name="wcpa[<?php echo esc_attr($index); ?>][options]" value="<?php echo esc_attr($field['options']); ?>" placeholder="Red|+5, Blue|+0, Green|+10" class="widefat" /></td>
		<td>
			<select name="wcpa[<?php echo esc_attr($index); ?>][price_type]">
				<option value="none" <?php selected($field['price_type'], 'none'); ?>><?php _e('None', 'whitestudioteam-wcpa'); ?></option>
				<option value="flat" <?php selected($field['price_type'], 'flat'); ?>><?php _e('Flat', 'whitestudioteam-wcpa'); ?></option>
				<option value="percent" <?php selected($field['price_type'], 'percent'); ?>><?php _e('Percent', 'whitestudioteam-wcpa'); ?></option>
			</select>
		</td>
		<td><input type="number" step="0.01" name="wcpa[<?php echo esc_attr($index); ?>][price_value]" value="<?php echo esc_attr($field['price_value']); ?>" /></td>
		<td><input type="number" name="wcpa[<?php echo esc_attr($index); ?>][max_len]" value="<?php echo esc_attr($field['max_len']); ?>" /></td>
		<td><button type="button" class="button whitestudioteam-wcpa-remove">&times;</button></td>
	</tr>
	<?php
}

function whitestudioteam_save_product_addons($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!isset($_POST[WHITESTUDIOTEAM_WCPA_NONCE]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[WHITESTUDIOTEAM_WCPA_NONCE])), WHITESTUDIOTEAM_WCPA_NONCE)) return;
	if (!current_user_can('edit_post', $post_id)) return;

	if (isset($_POST['wcpa']) && is_array($_POST['wcpa'])) {
		$clean = [];
		foreach ($_POST['wcpa'] as $i => $field) {
			$type = whitestudioteam_wcpa_whitelist_type($field['type'] ?? 'text');
			$clean[] = [
				'label' => sanitize_text_field($field['label'] ?? ''),
				'type' => $type,
				'required' => isset($field['required']) ? 1 : 0,
				'options' => sanitize_text_field($field['options'] ?? ''),
				'price_type' => in_array(($field['price_type'] ?? 'none'), ['none','flat','percent'], true) ? $field['price_type'] : 'none',
				'price_value' => is_numeric($field['price_value'] ?? '') ? (float)$field['price_value'] : '',
				'max_len' => is_numeric($field['max_len'] ?? '') ? (int)$field['max_len'] : '',
			];
		}
		update_post_meta($post_id, WHITESTUDIOTEAM_WCPA_META_KEY, $clean);
	} else {
		delete_post_meta($post_id, WHITESTUDIOTEAM_WCPA_META_KEY);
	}
}
add_action('save_post_product', 'whitestudioteam_save_product_addons');

/**
 * Global options store
 */
function whitestudioteam_maybe_initialize_global_option() {
	if (get_option(WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL) === false) {
		add_option(WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL, []);
	}
}
add_action('admin_init', 'whitestudioteam_maybe_initialize_global_option');

function whitestudioteam_settings_tab($settings, $current_section) {
	$custom = [];
	$custom[] = [
		'name' => __('Global Product Add‑Ons', 'whitestudioteam-wcpa'),
		'type' => 'title',
		'id'   => 'whitestudioteam_wcpa_global_title',
		'desc' => __('These fields appear on all products unless you override them per‑product.', 'whitestudioteam-wcpa')
	];
	$global = get_option(WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL, []);
	$global_json = !empty($global) ? wp_json_encode($global, JSON_PRETTY_PRINT) : '';
	$custom[] = [
		'name' => __('Global Add‑Ons JSON', 'whitestudioteam-wcpa'),
		'type' => 'textarea',
		'id'   => WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL,
		'css'  => 'min-height:220px;',
		'css_class' => 'large-text code',
		'desc_tip' => true,
		'desc' => __('Enter an array of field objects. Example: [{"label":"Gift Wrap","type":"checkbox","price_type":"flat","price_value":5}]', 'whitestudioteam-wcpa'),
		'default' => $global_json,
	];
	$custom[] = [ 'type' => 'sectionend', 'id' => 'whitestudioteam_wcpa_global_title' ];
	return array_merge($settings, $custom);
}
add_filter('woocommerce_get_settings_products', 'whitestudioteam_settings_tab', 10, 2);

function whitestudioteam_update_global_options() {
	if (!current_user_can('manage_woocommerce')) return;
	// WooCommerce settings page nonce
	if (function_exists('check_admin_referer')) { @check_admin_referer('woocommerce-settings'); }
	if (isset($_POST[WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL])) {
		$raw = (string) wp_unslash($_POST[WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL]);
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) {
			// sanitize structure
			$san = [];
			foreach ($decoded as $field) {
				$san[] = [
					'label' => sanitize_text_field($field['label'] ?? ''),
					'type' => whitestudioteam_wcpa_whitelist_type($field['type'] ?? 'text'),
					'required' => !empty($field['required']) ? 1 : 0,
					'options' => sanitize_text_field($field['options'] ?? ''),
					'price_type' => in_array(($field['price_type'] ?? 'none'), ['none','flat','percent'], true) ? $field['price_type'] : 'none',
					'price_value' => is_numeric($field['price_value'] ?? '') ? (float) $field['price_value'] : '',
					'max_len' => is_numeric($field['max_len'] ?? '') ? (int) $field['max_len'] : '',
				];
			}
			update_option(WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL, array_values($san));
		}
	}
}
add_action('woocommerce_update_options_products', 'whitestudioteam_update_global_options');

/**
 * Frontend render helpers
 */
function whitestudioteam_get_all_fields_for_product($product_id) {
	$per = get_post_meta($product_id, WHITESTUDIOTEAM_WCPA_META_KEY, true);
	if (!is_array($per)) { $per = []; }
	$global = get_option(WHITESTUDIOTEAM_WCPA_OPTION_GLOBAL, []);
	if (!is_array($global)) { $global = []; }
	$merged = array_merge($global, $per);
	// enforce whitelist
	$out = [];
	foreach ($merged as $f) {
		if (empty($f['label']) || empty($f['type'])) continue;
		$f['type'] = whitestudioteam_wcpa_whitelist_type($f['type']);
		$out[] = $f;
	}
	return array_values($out);
}

function whitestudioteam_render_product_fields() {
	global $product;
	if (!$product) { return; }
	$fields = whitestudioteam_get_all_fields_for_product($product->get_id());
	if (empty($fields)) { return; }
	wp_nonce_field(WHITESTUDIOTEAM_WCPA_NONCE, WHITESTUDIOTEAM_WCPA_NONCE);
	echo '<div class="wcpa-frontend-fields">';
	echo '<h4 class="wcpa-title">' . esc_html__('Product Options', 'whitestudioteam-wcpa') . '</h4>';
	foreach ($fields as $i => $f) {
		whitestudioteam_render_field($f, $i);
	}
	echo '</div>';
}
add_action('woocommerce_before_add_to_cart_button', 'whitestudioteam_render_product_fields');

function whitestudioteam_render_field($f, $i) {
	$label = esc_html($f['label']);
	$type  = whitestudioteam_wcpa_whitelist_type($f['type']);
	$req   = !empty($f['required']);
	$name  = 'wcpa_' . $i;
	$max   = isset($f['max_len']) && $f['max_len'] !== '' ? (int)$f['max_len'] : '';
	echo '<p class="form-row form-row-wide wcpa-field wcpa-type-' . esc_attr($type) . '">';
	echo '<label>' . $label . ($req ? ' <span class="required">*</span>' : '') . '</label>';
	switch ($type) {
		case 'textarea':
			echo '<textarea name="' . esc_attr($name) . '" ' . ($max ? 'maxlength="' . (int)$max . '"' : '') . '></textarea>';
			break;
		case 'select':
			echo '<select name="' . esc_attr($name) . '">';
			foreach (whitestudioteam_parse_options($f) as $opt) {
				echo '<option value="' . esc_attr($opt['value']) . '">' . esc_html($opt['label']) . '</option>';
			}
			echo '</select>';
			break;
		case 'radio':
			foreach (whitestudioteam_parse_options($f) as $idx => $opt) {
				echo '<label class="wcpa-inline"><input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($opt['value']) . '" ' . ($idx===0 ? 'checked' : '') . '> ' . esc_html($opt['label']) . '</label>';
			}
			break;
		case 'checkbox':
			foreach (whitestudioteam_parse_options($f) as $opt) {
				echo '<label class="wcpa-inline"><input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($opt['value']) . '"> ' . esc_html($opt['label']) . '</label>';
			}
			break;
		case 'file':
			echo '<input type="file" name="' . esc_attr($name) . '" />';
			break;
		case 'number':
			echo '<input type="number" name="' . esc_attr($name) . '" />';
			break;
		case 'text':
		default:
			echo '<input type="text" name="' . esc_attr($name) . '" ' . ($max ? 'maxlength="' . (int)$max . '"' : '') . ' />';
	}
	echo '</p>';
}

function whitestudioteam_parse_options($f) {
	$opts = [];
	$raw = isset($f['options']) ? (string) $f['options'] : '';
	if (!$raw) { return $opts; }
	$parts = array_map('trim', explode(',', $raw));
	foreach ($parts as $p) {
		// format: Label|+5  or Label|0  or just Label
		list($label, $extra) = array_pad(array_map('trim', explode('|', $p)), 2, '');
		$price_adj = 0;
		if ($extra !== '') {
			$price_adj = (float) str_replace(['+','%'], '', $extra);
		}
		$label = sanitize_text_field($label);
		$opts[] = [
			'label' => $label,
			'value' => $label,
			'price_adj' => $price_adj,
		];
	}
	return $opts;
}

/**
 * Ensure add-to-cart form supports file uploads
 */
function whitestudioteam_force_form_multipart() {
	if (!is_product()) { return; }
	echo "
<script>(function(){var f=document.querySelector('form.cart'); if(f){f.setAttribute('enctype','multipart/form-data');}})();</script>
"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action('wp_footer', 'whitestudioteam_force_form_multipart');

/**
 * Secure upload helpers
 */
function whitestudioteam_wcpa_allowed_mimes() {
	$default = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif',
		'pdf' => 'application/pdf',
	];
	return apply_filters('whitestudioteam_wcpa_allowed_mimes', $default);
}

function whitestudioteam_wcpa_unique_filename($dir, $name, $ext) {
	return 'wcpa-' . wp_generate_password(12, false, false) . $ext;
}

/**
 * Cart capture (validation + sanitization + whitelist checks)
 */
function whitestudioteam_capture_cart_item_data($cart_item_data, $product_id) {
	if (!isset($_POST[WHITESTUDIOTEAM_WCPA_NONCE]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[WHITESTUDIOTEAM_WCPA_NONCE])), WHITESTUDIOTEAM_WCPA_NONCE)) {
		return $cart_item_data;
	}
	$fields = whitestudioteam_get_all_fields_for_product($product_id);
	if (empty($fields)) { return $cart_item_data; }

	$collected = [];
	$uploads_to_process = [];

	foreach ($fields as $i => $f) {
		$name = 'wcpa_' . $i;
		$type = whitestudioteam_wcpa_whitelist_type($f['type']);
		$required = !empty($f['required']);
		$value = null;

		if ($type === 'file' && isset($_FILES[$name]) && !empty($_FILES[$name]['name'])) {
			$uploads_to_process[$i] = $_FILES[$name];
			$value = sanitize_file_name($_FILES[$name]['name']);
		} else {
			if ($type === 'checkbox') {
				$raw = isset($_POST[$name]) ? (array) $_POST[$name] : [];
				$value = array_map('sanitize_text_field', array_map('wp_unslash', $raw));
			} else {
				$value = isset($_POST[$name]) ? sanitize_text_field(wp_unslash($_POST[$name])) : '';
			}
		}

		// Required & length validation (server-side)
		$max = isset($f['max_len']) && $f['max_len'] !== '' ? (int)$f['max_len'] : 0;
		if ($required) {
			$empty = ($type==='checkbox') ? empty($value) : ($value==='');
			if ($empty) {
				wc_add_notice(sprintf(__('Please complete: %s', 'whitestudioteam-wcpa'), esc_html($f['label'])), 'error');
				return $cart_item_data;
			}
		}
		if ($max && in_array($type, ['text','textarea'], true) && is_string($value) && (mb_strlen($value) > $max)) {
			wc_add_notice(sprintf(__('Maximum length exceeded for: %s', 'whitestudioteam-wcpa'), esc_html($f['label'])), 'error');
			return $cart_item_data;
		}

		// Whitelist option values to prevent tampering
		if (in_array($type, ['select','radio','checkbox'], true)) {
			$opts = whitestudioteam_parse_options($f);
			$allowed_values = array_map(function($o){ return $o['value']; }, $opts);
			if ($type === 'checkbox') {
				foreach ((array)$value as $val) {
					if (!in_array($val, $allowed_values, true)) {
						wc_add_notice(__('Invalid option selected.', 'whitestudioteam-wcpa'), 'error');
						return $cart_item_data;
					}
				}
			} else {
				if ($value !== '' && !in_array($value, $allowed_values, true)) {
					wc_add_notice(__('Invalid option selected.', 'whitestudioteam-wcpa'), 'error');
					return $cart_item_data;
				}
			}
		}

		$collected[] = [
			'label' => sanitize_text_field($f['label']),
			'type' => $type,
			'value' => $value,
			'price_type' => $f['price_type'] ?? 'none',
			'price_value' => isset($f['price_value']) && $f['price_value'] !== '' ? (float)$f['price_value'] : 0,
			'options' => isset($opts) ? $opts : whitestudioteam_parse_options($f),
		];
	}

	// Handle uploads now (type whitelist + size limit + safe name)
	if (!empty($uploads_to_process)) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$allowed_mimes = whitestudioteam_wcpa_allowed_mimes();
		$max_bytes = (int) apply_filters('whitestudioteam_wcpa_max_upload_bytes', WHITESTUDIOTEAM_WCPA_MAX_UPLOAD);
		$overrides = [
			'test_form' => false,
			'mimes' => $allowed_mimes,
			'unique_filename_callback' => 'whitestudioteam_wcpa_unique_filename',
		];
		foreach ($uploads_to_process as $idx => $file) {
			if ((int)$file['size'] > $max_bytes) {
				wc_add_notice(__('Uploaded file is too large.', 'whitestudioteam-wcpa'), 'error');
				return $cart_item_data;
			}
			$check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
			if (empty($check['ext']) || empty($check['type'])) {
				wc_add_notice(__('File type not allowed.', 'whitestudioteam-wcpa'), 'error');
				return $cart_item_data;
			}
			$move = wp_handle_upload($file, $overrides);
			if (isset($move['url'])) {
				$collected[$idx]['value'] = esc_url_raw($move['url']);
			} else {
				wc_add_notice(__('File upload failed.', 'whitestudioteam-wcpa'), 'error');
				return $cart_item_data;
			}
		}
	}

	if (!empty($collected)) {
		$cart_item_data['whitestudioteam_wcpa'] = $collected;
	}
	return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'whitestudioteam_capture_cart_item_data', 10, 2);

function whitestudioteam_show_item_data($item_data, $cart_item) {
        if (isset($cart_item['whitestudioteam_wcpa']) && is_array($cart_item['whitestudioteam_wcpa'])) {
                foreach ($cart_item['whitestudioteam_wcpa'] as $f) {
                        $val = $f['value'];
                        if (is_array($val)) { $val = implode(', ', array_map('sanitize_text_field', $val)); }

                        $display_val = ($f['type'] === 'file'
                                ? wp_kses_post(sprintf('<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url($val), esc_html__('View file','whitestudioteam-wcpa')))
                                : esc_html($val));

                        if (!empty($f['price_type']) && $f['price_type'] !== 'none' && $f['price_value'] !== '') {
                                $p = (float) $f['price_value'];
                                if ($f['price_type'] === 'percent') {
                                        $display_val .= ' (+' . wc_format_decimal($p) . '%)';
                                } else {
                                        $display_val .= ' (+' . wc_price($p) . ')';
                                }
                        }

                        $item_data[] = [
                                'key' => esc_html($f['label']),
                                'value' => wp_kses_post($display_val),
                                'display' => '',
                        ];
                }
        }
        return $item_data;
}
add_filter('woocommerce_get_item_data', 'whitestudioteam_show_item_data', 10, 2);

function whitestudioteam_add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['whitestudioteam_wcpa'])) {
                foreach ($values['whitestudioteam_wcpa'] as $f) {
                        $val = $f['value'];
                        if (is_array($val)) { $val = implode(', ', array_map('sanitize_text_field', $val)); }
                        if (!empty($f['price_type']) && $f['price_type'] !== 'none' && $f['price_value'] !== '') {
                                $p = (float) $f['price_value'];
                                if ($f['price_type'] === 'percent') {
                                        $val .= ' (+' . wc_format_decimal($p) . '%)';
                                } else {
                                        $val .= ' (+' . strip_tags(wc_price($p)) . ')';
                                }
                        }
                        $item->add_meta_data(sanitize_text_field($f['label']), $val, true);
                }
        }
}
add_action('woocommerce_checkout_create_order_line_item', 'whitestudioteam_add_order_item_meta', 10, 4);

function whitestudioteam_apply_price_adjustments($cart) {
	if (is_admin() && !defined('DOING_AJAX')) { return; }
	if (did_action('woocommerce_before_calculate_totals') >= 2) { return; }
	foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
		if (empty($cart_item['whitestudioteam_wcpa'])) { continue; }
		$product = $cart_item['data'];
		if (!$product instanceof WC_Product) { continue; }
		$base_price = (float) $product->get_price();
		$extra = 0.0;
		foreach ($cart_item['whitestudioteam_wcpa'] as $f) {
			$ptype = $f['price_type'] ?? 'none';
			$pval  = isset($f['price_value']) ? (float)$f['price_value'] : 0;
			if ($ptype === 'flat') {
				$extra += $pval;
			} elseif ($ptype === 'percent') {
				$extra += ($base_price * ($pval / 100));
			}
			// Option-level price adj from options string (for select/radio/checkbox)
			if (in_array($f['type'], ['select','radio','checkbox'], true)) {
				$selected = $f['value'];
				$opts = $f['options'];
				if (is_array($selected)) {
					foreach ($selected as $sv) {
						foreach ($opts as $o) { if ($o['value'] === $sv) { $extra += (float)$o['price_adj']; } }
					}
				} else {
					foreach ($opts as $o) { if ($o['value'] === $selected) { $extra += (float)$o['price_adj']; } }
				}
			}
		}
		if ($extra !== 0) {
			$new_price = max(0, $base_price + $extra);
			$product->set_price(wc_format_decimal($new_price));
		}
	}
}
add_action('woocommerce_before_calculate_totals', 'whitestudioteam_apply_price_adjustments');

/**
 * Boot only if WooCommerce is active
 */
function whitestudioteam_wcpa_bootcheck() {
	if (!class_exists('WooCommerce')) { return; }
}
add_action('plugins_loaded', 'whitestudioteam_wcpa_bootcheck');

/* -------------------------- Assets: admin.css -------------------------- */
/*
Create file: assets/admin.css

.wcpa-wrap .wcpa-table th, .wcpa-wrap .wcpa-table td { vertical-align: middle; }
.wcpa-wrap .wcpa-inline { display: inline-block; margin-right: 12px; }
.wcpa-frontend-fields { margin: 1em 0; }
.wcpa-title { margin-bottom: .5em; }
*/

/* -------------------------- Assets: admin.js --------------------------- */
/*
Create file: assets/admin.js

jQuery(function($){
	function renumber(){
		$('#whitestudioteam-wcpa-rows tr').each(function(i){
			$(this).find('input, select, textarea').each(function(){
				this.name = this.name.replace(/wcpa\[[^\]]+\]/, 'wcpa['+i+']');
			});
		});
	}
	$('#whitestudioteam-wcpa-add-row').on('click', function(){
		var html = $('#tmpl-whitestudioteam-wcpa-row').html().replace(/\{\{INDEX\}\}/g, $('#whitestudioteam-wcpa-rows tr').length);
		$('#whitestudioteam-wcpa-rows').append(html);
	});
	$(document).on('click', '.whitestudioteam-wcpa-remove', function(){
		$(this).closest('tr').remove();
		renumber();
	});
});
*/
