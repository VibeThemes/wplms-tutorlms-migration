<?php
/**
 * Initialization functions for WPLMS TutorLMS MIGRATION
 * @author      Anshuman Sahu
 * @category    Admin
 * @package     Initialization
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Wplms_TutorLms_Migration_Init{

    public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new Wplms_TutorLms_Migration_Init();

        return self::$instance;
    }

    private function __construct(){
    	if ( in_array( 'tutor/tutor.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || (function_exists('is_plugin_active') && is_plugin_active( 'tutor/tutor.php'))) {
			add_action( 'admin_notices',array($this,'migration_notice' ));
			add_action('wp_ajax_migration_wp_tl_courses',array($this,'migration_wp_tl_courses'));

			add_action('wp_ajax_migration_wp_tl_course_to_wplms',array($this,'migration_wp_tl_course_to_wplms'));
            add_action('wp_ajax_dismiss_message',array($this,'dismiss_message'));
		}
    }

    function migration_notice(){
    	$this->migration_status = get_option('wplms_tutorlms_migration');
        if(!empty($this->migration_status)){
            ?>
            <div id="migration_tutorlms_courses_revert" class="update-nag notice ">
               <p id="revert_message"><?php printf( __('TutorLMS Courses migrated to WPLMS: Want to revert changes %s ', 'wplms-ldm' ),'</a><a id="dismiss_message" href=""><i class="fa fa-times-circle-o"></i>Dismiss</a>'); ?>
               </p>
            </div>
           <?php
           return;
        }        
        
        $check = 1;
        if(!function_exists('woocommerce')){
            $check = 0;
            ?>
            <div class="welcome-panel" id="welcome_ld_panel" style="padding-bottom:20px;width:96%">
                <h1><?php echo __('Please note the following before starting migration:','wplms-lp'); ?></h1>
                <ol>
                    <li><?php echo __('Woocommerce must be activated if using paid courses.','wplms-lp'); ?></li>
                    <li><?php echo __('WPLMS vibe custom types plugin must be activated.','wplms-lp'); ?></li>
                    <li><?php echo __('WPLMS vibe course module plugin must be activated.','wplms-lp'); ?></li>
                </ol>
                <p><?php echo __('If all the above plugins are activated then please click on the button below to proceed to migration proccess','wplms-lp'); ?></p>
                <form method="POST">
                    <input name="click" type="submit" value="<?php echo __('Click Here','wplms-lp'); ?>" class="button">
                </form>
            </div>
            <?php
        }
        if(isset($_POST['click'])){
            $check = 1;
            ?> <style> #welcome_ld_panel{display:none;} </style> <?php
        }

    	if(empty($this->migration_status) && $check){
    		?>
    		<div id="migration_tutorlms_courses" class="error notice ">
		       <p id="ldm_message"><?php printf( __('Migrate Learndash coruses to WPLMS %s Begin Migration Now %s', 'wplms-ldm' ),'<a id="begin_wplms_tutorlms_migration" class="button primary">','</a>'); ?>
		       	
		       </p>
		   <?php wp_nonce_field('security','security'); ?>
		        <style>.wplms_tutorlms_progress .bar{-webkit-transition: width 0.5s ease-in-out;
    -moz-transition: width 1s ease-in-out;-o-transition: width 1s ease-in-out;transition: width 1s ease-in-out;}</style>
		        <script>
		        	jQuery(document).ready(function($){
		        		$('#begin_wplms_tutorlms_migration').on('click',function(){
			        		$.ajax({
			                    type: "POST",
			                    dataType: 'json',
			                    url: ajaxurl,
			                    data: { action: 'migration_wp_tl_courses', 
			                              security: $('#security').val(),
			                            },
			                    cache: false,
			                    success: function (json) {

			                    	$('#migration_tutorlms_courses').append('<div class="wplms_tutorlms_progress" style="width:100%;margin-bottom:20px;height:10px;background:#fafafa;border-radius:10px;overflow:hidden;"><div class="bar" style="padding:0 1px;background:#37cc0f;height:100%;width:0;"></div></div>');

			                    	var x = 0;
			                    	var width = 100*1/json.length;
			                    	var number = 0;
									var loopArray = function(arr) {
									    wpld_ajaxcall(arr[x],function(){
									        x++;
									        if(x < arr.length) {
									         	loopArray(arr);   
									        }
									    }); 
									}
									
									// start 'loop'
									loopArray(json);

									function wpld_ajaxcall(obj,callback) {
										
				                    	$.ajax({
				                    		type: "POST",
						                    dataType: 'json',
						                    url: ajaxurl,
						                    data: {
						                    	action:'migration_wp_tl_course_to_wplms', 
						                        security: $('#security').val(),
						                        id:obj.id,
						                    },
						                    cache: false,
						                    success: function (html) {
						                    	number = number + width;
						                    	$('.wplms_tutorlms_progress .bar').css('width',number+'%');
						                    	if(number >= 100){
                                                    $('#migration_tutorlms_courses').removeClass('error');
                                                    $('#migration_tutorlms_courses').addClass('updated');
                                                    $('#ldm_message').html('<strong>'+x+' '+'<?php _e('Courses successfully migrated from Learndash to WPLMS','wplms-ldm'); ?>'+'</strong>');
										        }
						                    }
				                    	});
									    // do callback when ready
									    callback();
									}
			                    }
			                });
		        		});
		        	});
		        </script>
		    </div>
		    <?php
    	}
    }

    function migration_wp_tl_courses(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
         	_e('Security check Failed. Contact Administrator.','wplms-ldm');
         	die();
      	}

      	global $wpdb;
		$courses = $wpdb->get_results("SELECT id,post_title FROM {$wpdb->posts} where post_type='courses'");
		$json=array();
		foreach($courses as $course){
			$json[]=array('id'=>$course->id,'title'=>$course->post_title);
		}
		update_option('wplms_tutorlms_migration',1);
		
		$this->migrate_posts();

		print_r(json_encode($json));
		die();
    }

    function dismiss_message(){
        if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-ldm');
            die();
        }
        update_option('wplms_tutorlms_migration_reverted',1);
        die();
    }

    function migrate_posts(){

    	global $wpdb;
      
    	


    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'course' WHERE post_type = 'courses'");
    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'unit' WHERE post_type = 'lesson'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'quiz' WHERE post_type = 'tutor_quiz'");

    }

    function migration_wp_tl_course_to_wplms(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
         	_e('Security check Failed. Contact Administrator.','wplms-ldm');
         	die();
      	}

    	global $wpdb;
		$this->migrate_course_settings($_POST['id']);
		
    }

    function migrate_course_settings($course_id){
    	$basic_settings = get_post_meta($course_id,'_tutor_course_settings',true);
    	if(!empty($basic_settings)){
    		if(!empty($basic_settings['maximum_students'])){
    			update_post_meta($course_id,'vibe_max_students',$basic_settings['maximum_students']);
    		}
    	}
    	$duration = get_post_meta($course_id,'_course_duration',true);
    	if(!empty($duration)){
    		
			$seconds = 0;
			if(!empty($duration['hours'])){
				$seconds = intval($duration['hours'])*3600;
			}
			if(!empty($duration['minutes'])){
				$seconds = intval($duration['minutes'])*60;
			}
			if(!empty($duration['seconds'])){
				$seconds = intval($duration['seconds'])*60;
			}
			update_post_meta($course_id,'vibe_duration',floor($seconds/3600));
			update_post_meta($course_id,'vibe_duration_parameter',3600);
    		
    	}
    	$content_meta_array = ['_tutor_course_benefits','_tutor_course_requirements','_tutor_course_target_audience','_tutor_course_material_includes'];
    	$content = '';
    	foreach ($content_meta_array as $key => $ca) {
    		$con = get_post_meta($course_id,$ca,true);
    		if(!empty($con)){
    			$heading = str_replace('_tutor_', '', $ca);
    			$heading = str_replace('_', ' ', $heading);
    			$heading = ucfirst($heading);

    			$content .= '<br/><h3>'.$heading.'</h3><br/>'.$con;
    		}
    	}
    	if(!empty($content)){
    		$my_post['ID'] = $course_id;
	        $my_post['post_content'] = get_post_field('post_content',$course_id).$content;
	        wp_update_post( $my_post );
    	}
    	$video = get_post_meta($course_id,'_video',true);
    	if(!empty($video) && !empty($video['source'])){
    		$new_video = [];
    		if($video['source']=='html5'){

    		}
    		if($video['source']=='youtube' || $video['source']=='vimeo'){

    		}
    		if($video['source']=='embedded' ){
    			
    		}
    	}
    	

    	$this->course_id = $course_id;
       
    	
		$this->build_curriculum($course_id);
    }

    function build_curriculum($course_id){
    	global $wpdb;
    	$orderby = 'menu_order';
    	

    	$order = 'DESC';
    	if(!empty($this->unit_order)){
    		$order = $this->unit_order;
    	}
    	$this->unit_order_by; $this->unit_order;

    	$lessons_topics_quizzes = $wpdb->get_results("SELECT DISTINCT m.post_id as id,p.post_type as type,p.post_title as title, p.$orderby FROM {$wpdb->postmeta} as m LEFT JOIN {$wpdb->posts} as p ON p.id = m.post_id WHERE m.meta_value = $course_id AND m.meta_key = 'course_id' ORDER BY p.$orderby $order");

    	if(!empty($lessons_topics_quizzes)){
    		foreach($lessons_topics_quizzes as $unit){
    			switch($unit->type){
    				case 'unit':
                        $this->migrate_unit_settings($unit->id);
    					$after_unit = get_post_meta($unit->id,'lesson_id',true);
                        if(!empty($after_unit)){
                            //Course TOPIC UNIT 
                            $unit_key = array_search($after_unit,$curriculum);
                            if($unit_key !== false){
                                array_splice( $curriculum, ($unit_key+1), 0, $unit->id );
                            }else{
                                if(empty($this->store_units[$after_unit])){
                                    $this->store_units[$after_unit] = array($unit->id);    
                                }else{
                                    $this->store_units[$after_unit][] = $unit->id; 
                                }
                            }
                            
                        }else{
                            /*
                            LESSON UNIT ID; unit ID is LESSON ID
                            */
                            $curriculum[] = $unit->title;
                            $curriculum[] = $unit->id;
                        }
    				break;
    				case 'quiz':
                    /* $unit->id = $quiz_id */
                        $after_unit = get_post_meta($unit->id,'lesson_id',true);
                        if(!empty($after_unit)){
                            $quiz_key = array_search($after_unit,$curriculum);
                            if($quiz_key !== false){
                                array_splice( $curriculum, ($quiz_key+1), 0, $unit->id );
                            }else{
                                if(empty($this->store_quiz[$after_unit])){
                                    $this->store_quiz[$after_unit] = array($unit->id);    
                                }else{
                                    $this->store_quiz[$after_unit][] = $unit->id; 
                                }
                            }
                            
                        }else{
                            $curriculum[] = $unit->id;
                        }
                        
                        $this->migrate_quiz_settings($unit->id);
                        $this->migrate_questions($unit->id);
                        
    				break;
    			}
    		}

            if(!empty($this->store_units)){
                foreach($this->store_units as $parent_unit_id => $unit_ids){
                    if(!empty($unit_ids)){
                        $parent_unit_key = array_search($parent_unit_id,$curriculum);
                        array_splice( $curriculum, ($parent_unit_key+1), 0, $unit_ids );
                    }
                }
            }
            if(!empty($this->store_quiz)){
                foreach($this->store_quiz as $parent_quiz_id => $quiz_ids){
                    if(!empty($quiz_ids)){
                        $parent_quiz_key = array_search($parent_quiz_id,$curriculum);
                        array_splice( $curriculum, ($parent_quiz_key+1), 0, $quiz_ids );
                    }
                }
            }

    	}
    	update_post_meta($course_id,'vibe_course_curriculum',$curriculum);
    	//we have to add topics as sectionand its description as unit next to section
    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'unit' WHERE post_type = 'topics'");
    }
    
    function migrate_unit_settings($unit_id){
        $settings = get_post_meta($unit_id,'_sfwd-topic',true);
        if(!empty($settings)){
            if(!empty($settings['sfwd-topic_forced_lesson_time'])){
                update_post_meta($unit_id,'vibe_duration',$settings['sfwd-topic_forced_lesson_time']);
            }
        }

        $settings = get_post_meta($unit_id,'_sfwd-lessons',true);
        if(!empty($settings)){
            if(!empty($settings['sfwd-lessons_forced_lesson_time'])){
                update_post_meta($unit_id,'vibe_duration',$settings['sfwd-lessons_forced_lesson_time']);
            }
            if(!empty($settings['sfwd-lessons_visible_after_specific_date'])){
                update_post_meta($unit_id,'vibe_access_date',$settings['sfwd-lessons_visible_after_specific_date']);
            }
        }
    }

    function migrate_quiz_settings($quiz_id){
        global $wpdb;
        $settings = get_post_meta($quiz_id,'_sfwd-quiz',true);
        if(!empty($settings)){
            if(!empty($settings['sfwd-quiz_course'])){
                update_post_meta($quiz_id,'vibe_quiz_course',$settings['sfwd-quiz_course']);
            }

            if(!empty($settings['sfwd-quiz_repeats'])){
                update_post_meta($quiz_id,'vibe_quiz_retakes',$settings['sfwd-quiz_repeats']);
            }

            if(!empty($settings['sfwd-quiz_quiz_pro'])){
                $new_quiz_id = $settings['sfwd-quiz_quiz_pro'];
                $quizzes = $wpdb->get_results("SELECT result_text, time_limit, question_random FROM {$wpdb->prefix}wp_pro_quiz_master WHERE id = $new_quiz_id");

                if(!empty($quizzes)){
                    foreach($quizzes as $quiz){
                        if(!empty($quiz->result_text)){
                            update_post_meta($quiz_id,'vibe_quiz_message',$quiz->result_text);
                        }
                        if(!empty($quiz->time_limit)){
                            update_post_meta($quiz_id,'vibe_duration',$quiz->time_limit);
                        }else{
                            update_post_meta($quiz_id,'vibe_duration',9999);
                        }
                        if(!empty($quiz->question_random)){
                            update_post_meta($quiz_id,'vibe_quiz_random','S');
                        }
                    }
                }
            }
        }
    }

    function migrate_questions($quiz_id){
        global $wpdb;
        $settings = get_post_meta($quiz_id,'_sfwd-quiz',true);
        if(!empty($settings)){
            if(!empty($settings['sfwd-quiz_quiz_pro'])){
                $ld_quiz_id = $settings['sfwd-quiz_quiz_pro'];

                $questions = $wpdb->get_results("SELECT title, points, question, correct_msg, tip_enabled, tip_msg, answer_type, answer_data FROM {$wpdb->prefix}wp_pro_quiz_question WHERE quiz_id = $ld_quiz_id");
                $quiz_questions = array('ques'=>array(),'marks'=>array());
                if(!empty($questions)){
                    foreach($questions as $question){
                        $args = array(
                            'post_type'=>'question',
                            'post_status'=>'publish',
                            'post_title'=>$question->title,
                            'post_content'=>$question->question
                        );
                        $question_id = wp_insert_post($args);
                        $quiz_questions['ques'][]=$question_id;
                        $quiz_questions['marks'][]=$question->points;

                        if($question->tip_enabled){
                            if(!empty($question->tip_msg))
                                update_post_meta($question_id,'vibe_question_hint',$question->question_answer_hint);
                        }

                        if(!empty($question->correct_msg))
                            update_post_meta($question_id,'vibe_question_explaination',$question->correct_msg);

                        if($question->answer_type == 'free_answer')
                            $question->answer_type = 'largetext';
                        if($question->answer_type == 'sort_answer')
                            $question->answer_type = 'sort';
                        if($question->answer_type == 'matrix_sort_answer')
                            $question->answer_type = 'match';
                        if($question->answer_type == 'cloze_answer')
                            $question->answer_type = 'fillblank';
                        if($question->answer_type == 'assessment_answer')
                            $question->answer_type = 'assessment';
                        
                        if($question->answer_type != 'largetext' && $question->answer_type != 'assessment' && $question->answer_type != 'fillblank'){
                            $ans_data = unserialize($question->answer_data);

                            if($question->answer_type == 'sort'){

                                $opt_arr = Array();
                                $ans_arr = Array();
                                foreach($ans_data as $and => $data) {
                                    $options = $this->accessProtected($data, '_answer');
                                    $opt_arr[] =  $options;
                                    $ans_arr[] =  $and + 1;
                                }
                                $correct_answer = implode(',', $ans_arr);
                                update_post_meta($question_id,'vibe_question_options',$opt_arr);
                                update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                            }

                            if($question->answer_type == 'match'){
                                $opt_arr = Array();
                                $ans_arr = Array();
                                $ld_que_post_content = get_post_field('post_content',$question_id);
                                update_post_meta($question_id,'ld_que_post_content',$ld_que_post_content);
                                $content = $ld_que_post_content;

                                $match_list = '<br />[match]<ul>';
                                foreach($ans_data as $and => $data) {
                                    $match = $this->accessProtected($data, '_answer');
                                    $match_list .='<li>'.$match.'</li>';
                                    $matched_ans = $this->accessProtected($data, '_sortString');
                                    $opt_arr[] =  $matched_ans;
                                    $ans_arr[] =  $and + 1;
                                }
                                $match_list .= '</ul>[/match]';
                                $content .= $match_list;
                                $post = array('ID' => $question_id,'post_content' => $content );
                                wp_update_post($post,true);

                                $correct_answer = implode(',', $ans_arr);
                                update_post_meta($question_id,'vibe_question_options',$opt_arr);
                                update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                            }

                            if($question->answer_type == 'single' || $question->answer_type == 'multiple'){

                                $opt_arr = Array();
                                $ans_arr = Array();
                                $ans_data = unserialize($question->answer_data);
                                foreach($ans_data as $and => $data) {
                                    $options = $this->accessProtected($data, '_answer');
                                    $opt_arr[] =  $options;
                                    $ans = $this->accessProtected($data, '_correct');
                                    if($ans == 1) {
                                        $ans_arr[] =  $and + 1;
                                    }
                                }
                                $correct_answer = implode(',', $ans_arr);
                                update_post_meta($question_id,'vibe_question_options',$opt_arr);
                                update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                            }
                        }

                        if($question->answer_type == 'fillblank'){
                            $opt_arr = Array();
                            $ans_arr = Array();
                            $ans_data = unserialize($question->answer_data);
                            foreach($ans_data as $and => $data) {
                                $que_content = $this->accessProtected($data, '_answer');
                                preg_match_all('/{(.*)+}/', $que_content, $out);
                                foreach ($out[0] as $key => $answer) {
                                    $ans_arr[] = $answer;
                                }
                                $correct_answer = implode('|', $ans_arr);
                                update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                                $q_content = preg_replace('/{(.*)+}/', '[fillblank]', $que_content);
                                $ld_que_post_content = get_post_field('post_content',$question_id);
                                update_post_meta($question_id,'ld_que_post_content',$ld_que_post_content);
                                $content = $ld_que_post_content;

                                $fill_blank = '<br />';
                                $fill_blank .= $q_content;
                                $content .= $fill_blank;
                                $post = array('ID' => $question_id,'post_content' => $content );
                                wp_update_post($post,true);
                            }
                        }
                        update_post_meta($question_id,'vibe_question_type',$question->answer_type);
                    }
                    update_post_meta($quiz_id,'vibe_quiz_questions',$quiz_questions);
                }
            }
        }
    }

    function accessProtected($obj, $prop) {
        if(class_exists('ReflectionClass')) {
            $reflection = new ReflectionClass($obj);
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            return $property->getValue($obj);
        }
    }


    function course_pricing($settings,$course_id){

        if(!empty($settings['sfwd-courses_course_price'])){

            $post_args=array('post_type' => 'product','post_status'=>'publish','post_title'=>get_the_title($course_id));
            $product_id = wp_insert_post($post_args);
            update_post_meta($product_id,'vibe_subscription','H');

            update_post_meta($product_id,'_price',$settings['sfwd-courses_course_price']);

            wp_set_object_terms($product_id, 'simple', 'product_type');
            update_post_meta($product_id,'_visibility','visible');
            update_post_meta($product_id,'_virtual','yes');
            update_post_meta($product_id,'_downloadable','yes');
            update_post_meta($product_id,'_sold_individually','yes');

            $max_seats = get_post_meta($course_id,'vibe_max_students',true);
            if(!empty($max_seats) && $max_seats < 9999){
                update_post_meta($product_id,'_manage_stock','yes');
                update_post_meta($product_id,'_stock',$max_seats);
            }
            
            $courses = array($course_id);
            update_post_meta($product_id,'vibe_courses',$courses);
            update_post_meta($course_id,'vibe_product',$product_id);

            $thumbnail_id = get_post_thumbnail_id($course_id);
            if(!empty($thumbnail_id))
                set_post_thumbnail($product_id,$thumbnail_id);
        }
    }
}

Wplms_TutorLms_Migration_Init::init();