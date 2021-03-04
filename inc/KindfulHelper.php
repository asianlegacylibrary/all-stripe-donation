<?php


trait KindfulHelper {
	private function getKindfulFunds(){
		$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
		$kindfulApi = $wpsdKeySettings["wpsd_kindful_url"];
		$token = $wpsdKeySettings['wpsd_kindful_token'];
		$args = array(
			'headers' => array(
				'Authorization' => 'Token token="' . $token . '"',
			)
		);
		$url = $kindfulApi . "/api/v1/funds";
		$result = wp_remote_get($url, $args);
		$kindfulFunds = array();
		if ($result['response']['code'] === 200) {
			$result = json_decode($result['body'], true);
			foreach ( $result as $item ) {
				$kindfulFunds[] = array(
					'id' => $item['id'],
					'name' => $item['name'],
				);
			}
		}
		if(count($kindfulFunds)){
			return $kindfulFunds;
		}
		return [
			[
				'id' => 'General',
				'name' => 'General',
			]
		];
	}
	
	private function getKindfulCampaigns(){
		$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
		$kindfulApi = $wpsdKeySettings["wpsd_kindful_url"];
		$token = $wpsdKeySettings['wpsd_kindful_token'];
		$args = array(
			'headers' => array(
				'Authorization' => 'Token token="' . $token . '"',
			)
		);
		$url = $kindfulApi . "/api/v1/campaigns";
		$result = wp_remote_get($url, $args);
		$kindfulCampaigns = array();
		if ($result['response']['code'] === 200) {
			$result = json_decode($result['body'], true);
			foreach ( $result as $item ) {
				$kindfulFunds[] = array(
					'id' => $item['id'],
					'name' => $item['name'],
				);
			}
		}
		if(count($kindfulCampaigns)){
			return $kindfulCampaigns;
		}
		return [
			[
				'id' => 'General',
				'name' => 'General',
			]
		];
	}
}