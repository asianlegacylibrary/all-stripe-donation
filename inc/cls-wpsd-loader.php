<?php
/**
 * General action, hooks loader
*/
class Wpsd_Loader
{
	protected $wpsd_actions;
	protected $wpsd_filters;

	function __construct(){
		$this->wpsd_actions = array();
		$this->wpsd_filters = array();
	}

	function add_action( $hook, $component, $callback ){
		$this->wpsd_actions = $this->add( $this->wpsd_actions, $hook, $component, $callback );
	}

	function add_filter( $hook, $component, $callback ){
		$this->wpsd_filters = $this->add( $this->wpsd_filters, $hook, $component, $callback );
	}

	private function add( $hooks, $hook, $component, $callback ){
		$hooks[] = array( 'hook' => $hook, 'component' => $component, 'callback' => $callback );
		return $hooks;
	}

	function wpsd_run(){
		 foreach ( $this->wpsd_filters as $hook ) {
			 add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
		 }
		 foreach ( $this->wpsd_actions as $hook ) {
			 add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
		 }
	}	
}
?>