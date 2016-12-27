<?php
/*
Plugin Name: Plugin Uses Data Processor
*/

require_once('data-processor.php');


class SmallCsvParser {
	
	function __construct() {
		
		add_action( 'init', array($this,'register_post_types') );

	}
	
	
	function register_post_types() {

		register_post_type( 'test', array(
			'labels'       => array(
				"name" => 'тестовые записи',
				"singular_name" => 'тестовая запись',

			),
			'show_in_menu' => true,
			'show_ui'      => true,
			'public'       => true,
			'hierarchical' => false,
			'has_archive'  => false,
			'supports'     => array( 'title','editor','thumbmnail'),
			'rewrite'      => false,

		) );
		
	}

	
	function start() {
		
		return 'parse_file';
		
	}
	
	
	//step 1
	function parse_file() {
		
		$filename = plugin_dir_url( __FILE__ ).'data.csv';
		$data = explode("\n", file_get_contents($filename));

		$number = 1;
		update_option('current_entry',$number);

		foreach ($data as $entry) {
			set_transient('entry_'.$number,$entry,0);
			$number += 1;
		}
		
		return 'parse_entry';
		
	}
	

	//step 2 - loop
	function parse_entry() {
		$number = get_option('current_entry',1);
		update_option('current_entry',$number+1);
		
		$entry = get_transient('entry_'.$number);
		
		if ( $entry ) {
		
			$entry_data = str_getcsv($entry, ",", "\""); 

			if ( !is_null($entry_data[0]) ) {

					$args = array();
					$args['slug'] = $entry_data[0];
					$args['title'] = $entry_data[1];
							
					$post_id = insert_test_post($args);

			}
			
			
			delete_transient('entry_'.$number,$entry);
			
			return 'parse_entry';
		
		}
		else {
			
			return 'finish';
			
		}
		
	}

}


function insert_test_post($args) {
	
	$my_post = array(
		'post_title'    => $args['title'],
		'post_name'     => $args['slug'],
		'post_content'  => '',
		'post_status'   => 'publish',
		'post_type'     => 'test',
	);
	
	$post_id = wp_insert_post( $my_post);

	return $post_id;
	
}


$parser = new SmallCsvParser();
$data_processor = new DataProcessor($parser);