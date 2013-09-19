<?php

class SeoPilot_Widget extends WP_Widget {

	public $seopilot;

	function __construct() {

		parent::__construct(
			'seopilot',			// Base ID
			'SeoPilot.pl',		// Name
			array( 'description' => __( 'SeoPilot.pl Widget', 'seopilot' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {

		require_once( plugin_dir_path( __FILE__ ).'../SeoPilotClient.php' );

		$this->seopilot = new SeoPilotClient( array(
			'is_test'	=> get_option('SEOPILOT_TEST') == 1 ? true : false,
			'charset'	=> get_option('SEOPILOT_CHARSET')
		));

		echo $this->seopilot->build_links();
	}

	public function form( $instance ) {}
	public function update( $new_instance, $old_instance ) {}
}

?>
