<?php

class DataProcessor {

	function __construct($parent) {

		add_action('admin_menu', array($this, 'add_menu_page'));

		add_action('wp_ajax_dp_iteration', array($this, 'dp_iteration_callback'));
		add_action('wp_ajax_nopriv_dp_iteration', array($this, 'dp_iteration_callback'));

		add_filter( 'heartbeat_settings', array($this, 'heartbeat_frequency') );
		add_action( 'admin_enqueue_scripts', array($this, 'heartbeat_enqueue'));
		add_filter( 'heartbeat_send', array($this, 'heartbeat_send_callback'), 10, 2 );

		$this->user_class_instance = $parent;
	}

	
  	function heartbeat_frequency( $settings ) {
		
		$settings['interval'] = '15';
		return $settings;
		
	}
		

	function dp_iteration_callback() {
		
		set_transient( 'dp_iteration_start_time', current_time('H:i:s'),30); 
		
		if(isset($_REQUEST['task'])) $task = $_REQUEST['task'];
		set_transient('current_task', $task , 600);
		
		
		if ($task == 'start') {
			set_transient( 'dp_log','',600 );
			set_transient( 'dp_start_time', current_time('mysql'),30); 
		}

		if ($task == 'finish') {
			delete_transient( 'dp_start_time' );
			delete_transient( 'dp_iteration_start_time' );
			wp_send_json_success($task);
		} 
		else {
			$next_task = $this->user_class_instance->$task();

			$iter_count = (int)get_transient('test_count_'.$task, 600);
			set_transient('test_count_'.$task, $iter_count+1 , 600);

			
			$url = admin_url('admin-ajax.php?action=dp_iteration&task='.$next_task);
			wp_remote_get($url);
		}
		
	}


	function add_menu_page(){
		
		add_submenu_page(
			$parent_slug = 'tools.php',
			$page_title = 'Обработка данных',
			$menu_title = 'Обработка данных',
			$capability = 'manage_options',
			$menu_slug = 'dp',
			$function = array($this, 'dp_tools_callback')
		);
		
	}


	function dp_tools_callback(){
		
		$working = get_transient('dp_iteration_start_time');
		
		if($working) {
			?>
			<div id="dp-wrapper" class="wrap">
				<div>В процессе: <span class="test-result"><?php echo get_transient('current_task'); ?></span></div>
				<div id="test"></div>
			</div>
			<?php
		}
		else {
			?>
			<div id="dp-wrapper" class="wrap">
				<button id="dp-product-import" class="button button-small">Запустить</button>
				<div id="test"></div>

				<script type="text/javascript">
					jQuery(document).ready(function($) {
					  $('#dp-wrapper button').click(function () {
						
						$(this).detach();
						 $('#dp-wrapper').append('<p>Приступаем к работе</p>');
						var data = {
							action: 'dp_iteration',
							task: 'start',
						};
						$.getJSON(ajaxurl, data);
					  });
					});
				</script>

			</div>
			<?php
		}
	}
  

	function heartbeat_send_callback($data, $screen_id){
		
		if('tools_page_dp' != $screen_id) return $data;

		$data['current_task'] = get_transient('current_task');
		$data['current_task_iteration'] = get_transient('test_count_'.$data['current_task']);
		$data['dp_iteration_start_time'] = get_transient('dp_iteration_start_time');
		$data['current_time'] = current_time('H:i:s');
		$data['text'] = '<p>'.$data['current_time'].': '.$data['current_task'].' '.$data['current_task_iteration'].' итерация началась '.$data['dp_iteration_start_time'].'</p>';
		
		return $data;
		
	}

	
	function heartbeat_footer_js(){

		$cs = get_current_screen();
		if('tools_page_dp' != $cs->id) return;
		?>
        <script type="text/javascript" id="dp-hearbeat">
        (function($){
            $(document).on( 'heartbeat-tick', function(e, data) {
			   $('#dp-wrapper').append(data['text']);
            });
        }(jQuery));
        </script>
		<?php
		
	}

	
	function heartbeat_enqueue() {
		
		wp_enqueue_script( 'heartbeat' );
		add_action( 'admin_print_footer_scripts', array($this, 'heartbeat_footer_js'));
		
	}
	
}

	
