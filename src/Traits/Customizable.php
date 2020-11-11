<?php

namespace Truust\Traits;

use Truust\Activator;

defined('ABSPATH') || exit;

trait Customizable
{
	public function init_form_fields()
	{
		$form_fields = [
			// ---------- api credentials ---------- //

			'credentials_section_heading' => [
				'title' => __('Truust API Credentials', config('text-domain')),
				'type' => 'title',
				'link_url' => 'https://docs.truust.io/getting-started',
			],
			'api_key' => [
				'title' => __('Secret Key', config('text-domain')),
				'type' => "api_key"
			],

			// ---------- display settings ---------- //

			'display_section_heading' => [
				'title' => __('Display Options', config('text-domain')),
				'type' => 'title',
			],
			'description' => [
				'title' => __('Description', 'woocommerce'),
				'type' => 'textarea',
				'default' => __('You will be redirect to Truust payment page after you submit order', config('text-domain')),
				'description' => 'The customer will see this description when making a purchase',
				'css' => 'width: 400px;',
			],

			// ---------- seller information ---------- //

			'seller_section_heading' => [
				'title' => __('Seller Information', config('text-domain')),
				'type' => 'title',
				'contact' => true
			],
			'email' => [
				'title' => __('Email', config('text-domain')),
				'type' => 'text',
				'input_type' => 'email',
				'disabled' => !$this->valid_key,
			],
			'phone' => [
				'title' => __('Phone', config('text-domain')),
				'type' => 'text',
				'input_type' => 'text',
				'disabled' => !$this->valid_key,
			],
			'seller_confirmed_url' => [
				'title' => __('Seller Confirmed URL', config('text-domain')),
				'type' => 'text',
				'input_type' => 'text',
				'disabled' => !$this->valid_key,
			],
			'seller_denied_url' => [
				'title' => __('Seller Denied URL', config('text-domain')),
				'type' => 'text',
				'input_type' => 'text',
				'disabled' => !$this->valid_key,
			],
		];

		if ($this->allow_shipping) {
			$form_fields = array_merge($form_fields, [
				//  ---------- shipping information ---------- //

				'shipping_section_heading' => [
					'title' => __('Shipping Information', config('text-domain')),
					'type' => 'title',
				],
				'origin_name' => [
					'title' => __('Origin Name', config('text-domain')),
					'type' => 'text',
				],
				'origin_address' => [
					'title' => __('Origin Address Line 1', config('text-domain')),
					'type' => 'textarea',
					'css' => 'width: 400px;',
				],
				'origin_address_2' => [
					'title' => __('Origin Address Line 2', config('text-domain')),
					'type' => 'textarea',
					'css' => 'width: 400px;',
				],
				'origin_city' => [
					'title' => __('Origin City', config('text-domain')),
					'type' => 'text',
				],
				'origin_state' => [
					'title' => __('Origin State', config('text-domain')),
					'type' => 'text',
				],
				'origin_zip_code' => [
					'title' => __('Origin Zip Code', config('text-domain')),
					'type' => 'text',
				],
				'origin_country' => [
					'title' => __('Origin Country', config('text-domain')),
					'type' => 'countries',
				],
			]);
		}

		$this->form_fields = $form_fields;
	}

	// ---------- overrides ---------- //

	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		$old = determine_env($this->get_option('api_key'));
		$new = determine_env($post_data['woocommerce_truust_api_key']);

		if ($old != $new) {
			Activator::reset_truust_customers();
		}

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch (\Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}

	// ---------- generators ---------- //

	public function generate_title_html($key, $data)
	{
		$field_key = $this->get_field_key($key);
		$defaults = array(
			'title' => '',
			'class' => '',
		);

		$data = wp_parse_args($data, $defaults);

		ob_start();

		?>
			</table>
				<h3 class="wc-settings-sub-title <?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></h3>
				<?php if (!empty($data['description'])) : ?>
					<p><?php echo wp_kses_post($data['description']); ?></p>
				<?php endif; ?>
				<?php echo $this->get_link_html($data); ?>
				<?php if (isset($data['contact']) && $data['contact']) : ?>
					<p>If you want have more than one seller, contact us at: <a href="mailto:hello@truust.io">hello@truust.io</a></p>
				<?php endif; ?>
			<table class="form-table">
		<?php

		return ob_get_clean();
	}

	public function generate_api_key_html($key, $data)
	{
		$field_key = $this->get_field_key($key);
		$defaults = [
			'title' => '',
		];

		$data = wp_parse_args($data, $defaults);

		if ($this->valid_key) {
			$message = 'Valid key for use in the <strong>' . determine_env($this->get_option('api_key')) . '</strong> environment';
			$icon = '<span class="dashicons dashicons-yes-alt valid"></span>';
		} else {
			$message = 'Invalid or missing API Key';
			$icon = '<span class="dashicons dashicons-dismiss invalid"></span>';
		}

		ob_start();

		?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php echo wp_kses_post( $data['title'] ); ?></span>
						</legend>
						<input class="input-text regular-input" type="text" name="<?php echo esc_attr($field_key); ?>" id="truust-api-key" value="<?php echo esc_attr($this->get_option($key)); ?>" placeholder="Secret key">
						<button id="truust-validator-btn" class="button-primary" type="button" name="validate">
							<span class="validator-text"><?php esc_attr_e('Validate'); ?></span>
							<span class="truust-spinner"></span>
						</button>
					</fieldset>
					<fieldset>
						<legend class="screen-reader-text"><span>Validity</span></legend>
						<p class="valid_key">
							<?php echo $message; ?>
							<?php echo $icon; ?>
						</p>
					</fieldset>
				</td>
			</tr>
		<?php

		return ob_get_clean();
	}

	public function generate_countries_html($key, $data)
	{
		$field_key = $this->get_field_key($key);
		$defaults = array(
			'title' => '',
			'class' => '',
		);

		$data = wp_parse_args($data, $defaults);

		$code = $this->get_option($key);

		ob_start();

		?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
						<select class="select <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>">
							<?php foreach (countries() as $key => $value) : ?>
								<?php if ($key === 'ES') : ?>
									<option value="ES" selected="selected">Spain</option>
								<?php else : ?>
									<option value="<?php echo $key; ?>" disabled="true"><?php echo $value; ?></option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
					</fieldset>
				</td>
			</tr>
		<?php

		return ob_get_clean();
	}

	// ---------- validators ---------- //

	public function validate_origin_name_field($key, $value)
	{
		if ($value === '') {
			add_flash_notice(__($this->title_case($key) . ' is required', config('text-domain')), 'error', true);
		} else if (strlen($value) > 40) {
			add_flash_notice(__($this->title_case($key) . ' must be 40 characters or less', config('text-domain')), 'error', true);
		} else {
			return $value;
		}
	}

	public function validate_origin_address_field($key, $value)
	{
		if ($value === '') {
			add_flash_notice(__($this->title_case($key) . ' is required', config('text-domain')), 'error', true);
		} else if (strlen($value) > 40) {
			add_flash_notice(__($this->title_case($key) . ' must be 40 characters or less', config('text-domain')), 'error', true);
		} else {
			return $value;
		}
	}

	public function validate_origin_address_2_field($key, $value)
	{
		if (strlen($value) > 30) {
			add_flash_notice(__($this->title_case($key) . ' must be 30 characters or less', config('text-domain')), 'error', true);
		} else {
			return $value;
		}
	}

	public function validate_origin_city_field($key, $value)
	{
		if ($value === '') {
			add_flash_notice(__($this->title_case($key) . ' is required', config('text-domain')), 'error', true);
		} else if (strlen($value) > 40) {
			add_flash_notice(__($this->title_case($key) . ' must be 255 characters or less', config('text-domain')), 'error', true);
		} else {
			return $value;
		}
	}

	public function validate_origin_state_field($key, $value)
	{
		if ($value === '') {
			add_flash_notice(__($this->title_case($key) . ' is required', config('text-domain')), 'error', true);
		} else if (strlen($value) > 40) {
			add_flash_notice(__($this->title_case($key) . ' must be 255 characters or less', config('text-domain')), 'error', true);
		} else {
			return $value;
		}
	}

	public function validate_origin_zip_code_field($key, $value)
	{
		if ($value === '') {
			add_flash_notice(__($this->title_case($key) . ' is required', config('text-domain')), 'error', true);
		} else if (strlen($value) != 5) {
			add_flash_notice(__($this->title_case($key) . ' must be 5 digits', config('text-domain')), 'error', true);
		} else {
			return $value;
		}
	}

	// ---------- utilities ---------- //

	public function get_link_html($data)
	{
		return !empty($data['link_url']) ? '<p><a href="'. $data['link_url'] .'" target="_blank">' .  wp_kses_post(!empty($data['link_text']) ? $data['link_text'] : $data['link_url']) . '</a></p>' . "\n" : '';
	}

	public function title_case($str)
	{
		return ucwords(str_replace('_', ' ', $str));
	}
}
