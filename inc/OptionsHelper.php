<?php
class OptionsHelper{
	protected $general;
	protected $template;
	public function __construct() {
		$this->general = stripslashes_deep( unserialize( get_option('wpsd_general_settings') ) );
		$this->template = stripslashes_deep( unserialize( get_option('wpsd_temp_settings') ) );
	}
	
	public function get_value($type, $key, $default, $translate = true){
		$get_value = function ($key, $arr){
			if(is_array($arr) && array_key_exists($key, $arr)) {
				if (is_string($arr[$key])) {
					if (!empty($arr[$key])) {
						return $arr[$key];
					}
					else {
						// return null on empty string
						return null;
					}
				}
				elseif (is_int($arr[$key])) {
					return $arr[$key];
				}
				return $arr[$key];
			}
			return null;
		};
		$value = null;
		switch ($type) {
			case "general":
				$value = $get_value($key, $this->general);
				if ($value && $translate && is_string($value)) {
					$value = esc_html__($value, 'wp-stripe-donation');
				}
				break;
			case "template":
				$value = $get_value($key, $this->template);
				if ($value && $translate && is_string($value)) {
					$value = esc_html__($value, 'wp-stripe-donation');
				}
				break;
		}
		
		if ($value || is_bool($value)) {
			return $value;
		}
		if ($translate) {
			return esc_html__($default, 'wp-stripe-donation');
		}
		return $default;
	}
}