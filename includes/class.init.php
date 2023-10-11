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
            add_action('wp_ajax_revert_migrated_courses',array($this,'revert_migrated_courses'));
            add_action('wp_ajax_endTutorLmsMigration',[$this,'endTutorLmsMigration']);
		}
    }

    function migration_notice(){
    	$this->migration_status = get_option('wplms_tutorlms_migration');
        $this->revert_status = get_option('wplms_wp_tl_migration_reverted');
        if(!empty($this->migration_status) && empty($this->revert_status)){
            ?>
            <div id="migration_tutorlms_courses_revert" class="update-nag notice ">
               <p id="revert_message"><?php printf( __('Tutorlms Courses migrated to WPLMS: Want to revert changes %s Revert Changes Now %s Otherwise dismiss this notice.', 'wplms-ldm' ),'<a id="begin_revert_migration" class="button primary">','</a><a id="dismiss_message" href=""><i class="fa fa-times-circle-o"></i>Dismiss</a>'); ?>
               </p>
            </div>
            <style>
                #migration_tutorlms_courses_revert{width:97%;} 
                #dismiss_message {float:right;padding:5px 10px 10px 10px;color:#e00000;}
                #dismiss_message i {padding-right:3px;}
            </style>
            <?php wp_nonce_field('security','security'); ?>
            <script>
                jQuery(document).ready(function($){
                    $('#begin_revert_migration').on('click',function(){
                        $.ajax({
                            type: "POST",
                            url: ajaxurl,
                            data: { action: 'revert_migrated_courses', 
                                      security: $('#security').val(),
                                    },
                            cache: false,
                            success: function () {
                                $('#migration_tutorlms_courses_revert').removeClass('update-nag');
                                $('#migration_tutorlms_courses_revert').addClass('updated');
                                $('#migration_tutorlms_courses_revert').html('<p id="revert_message">'+'<?php _e('WPLMS - Tutorlms MIGRATION : Migrated courses Reverted !', 'wplms-ldm' ); ?>'+'</p>');
                            }
                        });
                    });
                    $('#dismiss_message').on('click',function(){
                        $.ajax({
                            type: "POST",
                            url: ajaxurl,
                            data: { action: 'dismiss_message', 
                                      security: $('#security').val(),
                                    },
                            cache: false,
                            success: function () {
                                
                            }
                        });
                    });
                });
            </script>
            <?php
            return;
        }        
        
        $check = 1;
        if(!function_exists('woocommerce')){
            $check = 0;
            ?>
            <div class="welcome-panel" id="welcome_ld_panel" style="padding-bottom:20px;width:96%;background:#ffffff !important;padding:1rem">
                <h1><?php echo __('Please note the following before starting migration:','wplms-lp'); ?></h1>
                <ol>
                    <li><?php echo __('Woocommerce must be activated if using paid courses.','wplms-lp'); ?></li>
                    <li><?php echo __('Vibebp must be activated.','wplms-lp'); ?></li>
                    <li><?php echo __('WPLMS plugin must be activated.','wplms-lp'); ?></li>
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
		       <p id="ldm_message"><?php printf( __('Migrate tutorlms coruses to WPLMS %s Begin Migration Now %s', 'wplms-tutorlms-migration' ),'<a id="begin_wplms_tutorlms_migration" class="button primary">','</a>'); ?>
		       	
		       </p>
		   <?php wp_nonce_field('security','security'); ?>
		        <style>.wplms_tutorlms_progress .bar{-webkit-transition: width 0.5s ease-in-out;
                -moz-transition: width 1s ease-in-out;-o-transition: width 1s ease-in-out;transition: width 1s ease-in-out;}</style>
		        <script>


		        	jQuery(document).ready(function($){
                        function call_callback(obj){
                            return new Promise((resolve,reject)=>{
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
                                        resolve();
                                    }
                                });
                            });
                        }
		        		$('#begin_wplms_tutorlms_migration').on('click',function(){
			        		$.ajax({
			                    type: "POST",
			                    dataType: 'json',
			                    url: ajaxurl,
			                    data: { action: 'migration_wp_tl_courses', 
			                              security: $('#security').val(),
			                            },
			                    cache: false,
			                    success: async function (json) {

			                    	$('#migration_tutorlms_courses').append('<div class="wplms_tutorlms_progress" style="width:100%;margin-bottom:20px;height:10px;background:#fafafa;border-radius:10px;overflow:hidden;"><div class="bar" style="padding:0 1px;background:#37cc0f;height:100%;width:0;"></div></div>');
                                    endCall = ()=>{
                                        $.ajax({
                                            type: "POST",
                                            dataType: 'json',
                                            url: ajaxurl,
                                            data: {
                                                action:'endTutorLmsMigration', 
                                                security: $('#security').val(),
                                            },
                                            cache: false,
                                            success: function (html) {

                                            }
                                        });
                                        
                                    }
			                    	var x = 0;
			                    	var width = 100*1/json.length;
			                    	var number = 0;
									for (var i = 0; i < json.length; i++) {
                                    
                                        await call_callback(json[i]);
                                        number = number + width;
                                        $('.wplms_tutorlms_progress .bar').css('width',number+'%');
                                        if(number >= 100){
                                            $('#migration_tutorlms_courses').removeClass('error');
                                            $('#migration_tutorlms_courses').addClass('updated');
                                            $('#ldm_message').html('<strong>'+x+' '+'<?php _e('Courses successfully migrated from tutorlms to WPLMS','wplms-tutorlms-migration'); ?>'+'</strong>');
                                        }
                                        if(i==(json.length-1)){
                                            endCall();
                                        }
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
    function revert_migrated_courses(){
        if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-ldm');
            die();
        }
        update_option('wplms_wp_tl_migration_reverted',1);
        $this->revert_migrated_posts();
        die();
    }

    function revert_migrated_posts(){
       global $wpdb;
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'courses' WHERE post_type = 'course'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'lesson' WHERE post_type = 'unit'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'tutor_quiz' WHERE post_type = 'quiz'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'tutor_assignments' WHERE post_type = 'wplms-assignment'");
        
        $wpdb->query("UPDATE {$wpdb->term_taxonomy} SET taxonomy = 'course-category' WHERE taxonomy = 'course-cat'");
    }

    function migration_wp_tl_courses(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
         	_e('Security check Failed. Contact Administrator.','wplms-tutorlms-migration');
         	die();
      	}

      	global $wpdb;
		$courses = $wpdb->get_results("SELECT id,post_title FROM {$wpdb->posts} where post_type='courses' AND post_status!='auto-draft'");
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
            _e('Security check Failed. Contact Administrator.','wplms-tutorlms-migration');
            die();
        }
        delete_option('wplms_tutorlms_migration');
        update_option('wplms_tutorlms_migration_reverted',1);
        die();
    }

    function migrate_posts(){

    	global $wpdb;
      
    	


    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'course' WHERE post_type = 'courses'");
    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'unit' WHERE post_type = 'lesson'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'quiz' WHERE post_type = 'tutor_quiz'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'wplms-assignment' WHERE post_type = 'tutor_assignments'");
        
        $wpdb->query("UPDATE {$wpdb->term_taxonomy} SET taxonomy = 'course-cat' WHERE taxonomy = 'course-category'");
    }

    function migration_wp_tl_course_to_wplms(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
         	_e('Security check Failed. Contact Administrator.','wplms-tutorlms-migration');
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
    		
			$seconds = $this->get_seconds_from_duration($duration);
            if(!empty($seconds)){
                update_post_meta($course_id,'vibe_duration',floor($seconds/3600));
                update_post_meta($course_id,'vibe_course_duration_parameter',3600);
            }
			
    		
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
    		if($video['source']=='html5' && !empty($video['source_video_id'])){
                $new_video= ['type'=>'video','url'=>wp_get_attachment_url($video['source_video_id']),'id'=>$video['source_video_id'],'name'=>'video1'];
    		}
    		if($video['source']=='youtube' || $video['source']=='vimeo' && !empty($video['source_'.$video['source']])){
                $new_video= ['type'=>$video['source'],'url'=>$video['source_'.$video['source']]];
    		}
            if($video['source']=='external_url' && !empty($video['source_external_url'])){
                $new_video= ['type'=>'video','url'=>$video['source_external_url']];
            }
            update_post_meta($course_id,'post_video',$new_video);
    	}
        $level = get_post_meta($course_id,'_tutor_course_level',true);
    	if(!empty($level)){
            wp_set_object_terms( $course_id, $level, 'level' );
        }
        $precourses = get_post_meta($course_id,'_tutor_course_prerequisites_ids',true);
        if(!empty($precourses)){
            
            update_post_meta($course_id,'vibe_pre_course',$precourses);
        }

        $paid = get_post_meta($course_id,'_tutor_course_price_type',true);
        $pid = get_post_meta($course_id,'_tutor_course_product_id',true);
        if(!empty($paid) && $paid =='paid' && !empty($pid)){
            
            update_post_meta($course_id,'vibe_product',$pid);
            update_post_meta($pid,'vibe_courses',[$course_id]);
            $thumbnail_id = get_post_thumbnail_id($course_id);
            if(!empty($thumbnail_id))
                set_post_thumbnail($product_id,$thumbnail_id);
        }
        if($paid=='free'){
            update_post_meta($course_id,'vibe_course_free','S');
        }
    	$this->course_id = $course_id;
        //drip feed left
    	
		$this->build_curriculum($course_id);
    }

    function get_seconds_from_duration($duration){
        $seconds = 0;
        if(!empty($duration['hours'])){
            $seconds += intval($duration['hours'])*3600;
        }
        if(!empty($duration['minutes'])){
            $seconds += intval($duration['minutes'])*60;
        }
        if(!empty($duration['seconds'])){
            $seconds += intval($duration['seconds']);
        }
        return $seconds;
    }

    function build_curriculum($course_id){
    	global $wpdb;
    	$topics = $wpdb->get_results("SELECT post_title as title,ID as id, post_type as type FROM {$wpdb->posts} WHERE post_parent={$course_id} AND post_type='topics' ORDER BY menu_order ASC");
        $curriculum=[];
        foreach ($topics as $key => $topic) {
            $curriculum[] = $topic->title;
            /*$topic_desc =get_post_field('post_content',$topic->id);
            if(!empty($topic_desc)){
                $new_topic_description = [
                    'post_title'=>sprintf(_x('%s description','','wplms-tutorlms-migration'),$topic->title),
                    'post_content'=>$topic_desc,
                    'post_status'=>'publish'
                ];

                $topic_unit_id = wp_insert_post($new_topic_description);

                $curriculum[] = $topic_unit_id;
            }*/
            
            $topid_id= $topic->id;

            $lessons_topics_quizzes = $wpdb->get_results("SELECT post_title as title,ID as id, post_type as type FROM {$wpdb->posts} WHERE post_parent = {$topid_id} ORDER BY menu_order ASC");

            if(!empty($lessons_topics_quizzes)){
                foreach($lessons_topics_quizzes as $unit){
                    switch($unit->type){
                        
                        case 'quiz':
                        
                            $curriculum[] = $unit->id;
                            $this->migrate_questions($unit->id,$course_id);
                            
                            $this->migrate_quiz_settings($unit->id,$course_id);
                            
                            
                        break;
                        case 'wplms-assignment':
                            $curriculum[] = $unit->id;
                            $this->migrate_assignment_settings($unit->id,$course_id);
                        break;
                        
                        case 'unit':
                        default:
                            $this->migrate_unit_settings($unit->id);
                            
                               
                            $curriculum[] = $unit->id;
                            
                        break;
                    }
                }
            }
            
        }
    	update_post_meta($course_id,'vibe_course_curriculum',$curriculum);
    	
    }
    
    function migrate_unit_settings($unit_id){
        $attachments = get_post_meta($unit_id,'_tutor_attachments',true);
        if(!empty($attachments)){
            $new_attachments= [];
            foreach ($attachments as $key => $at) {
                $new_attachments[] = [
                    'id'=>$at,
                    'url'=>wp_get_attachment_url($at),
                    'type'=>$this->get_attachment_type($at),
                    'name'=>get_the_title($at),
                ];
            }
            update_post_meta($unit_id,'vibe_unit_attachments',$new_attachments);
        }
        $video = get_post_meta($unit_id,'_video',true);
        if(!empty($video) && !empty($video['source'])){
            $new_video = [];
            if($video['source']=='html5' && !empty($video['source_video_id'])){
                $new_video= ['type'=>'video','url'=>wp_get_attachment_url($video['source_video_id']),'id'=>$video['source_video_id']];
            }
            if($video['source']=='youtube' || $video['source']=='vimeo' && !empty($video['source_'.$video['source']])){
                $new_video= ['type'=>$video['source'],'url'=>$video['source_'.$video['source']]];
            }
            if($video['source']=='external_url' && !empty($video['source_external_url'])){
                $new_video= ['type'=>'video','url'=>$video['source_external_url']];
            }
            if($video['source']=='shortcode' && !empty($video['source_shortcode'])){
               
                $my_post['ID'] = $unit_id;
                $my_post['post_content'] = get_post_field('post_content',$unit_id).$video['source_shortcode'];
                wp_update_post( $my_post );
            }
            if(!empty($video['runtime'])){
                $seconds = $this->get_seconds_from_duration($video['runtime']);
                if(!empty($seconds)){
                    update_post_meta($unit_id,'vibe_duration',floor($seconds/60));
                    update_post_meta($unit_id,'vibe_unit_duration_parameter',60);
                }
            }
            update_post_meta($unit_id,'vibe_post_video',$new_video);
            update_post_meta($unit_id,'vibe_type','video');
        }
    }

    function get_attachment_type($post_id){

          $type = get_post_mime_type($post_id);
          switch ($type) {
            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
              return "image"; break;
            case 'video/mpeg':
            case 'video/mp4': 
            case 'video/quicktime':
              return "video"; break;
            case 'text/csv':
            case 'text/plain': 
            case 'text/xml':
              return "text"; break;
            default:
              return  "file";
          }
        
    }

    function migrate_quiz_settings($quiz_id,$course_id){
        global $wpdb;
        update_post_meta($quiz_id,'vibe_quiz_course',$course_id);
        update_post_meta($quiz_id,'vibe_quiz_auto_evaluate','S');
        update_post_meta($quiz_id,'vibe_question_number_react',1);
        update_post_meta($quiz_id,'vibe_type','static');
        
        $settings = get_post_meta($quiz_id,'tutor_quiz_option',true);
        if(!empty($settings)){
            if(!empty($settings['time_limit']) && !empty($settings['time_limit']['time_value'])){
                update_post_meta($quiz_id,'vibe_duration',intval($settings['time_limit']['time_value']));
                update_post_meta($quiz_id,'vibe_quiz_duration_parameter',$this->time_duration_string_to_int($settings['time_limit']['time_type'],$quiz_id));
            }
            if(!empty($settings['attempts_allowed']) && !empty($settings['feedback_mode']) && $settings['feedback_mode']=='retry'){
                update_post_meta($quiz_id,'vibe_quiz_retakes',intval($settings['attempts_allowed']));
            }
            if(!empty($settings['passing_grade'])){
                //proccessed after setting questions
                $question_Details=get_post_meta($quiz_id,'vibe_quiz_questions',true);
                if(!empty($question_Details) && !empty($question_Details['marks'])){
                    $total = array_sum($question_Details['marks']);
                    if (!empty($total)) {
                        $passing_marks = floor(($settings['passing_grade']*$total)/100);
                        update_post_meta($quiz_id,'vibe_quiz_passing_score',true);
                    }
                    
                }
            }
            
        }
        
    }

    function time_duration_string_to_int($duration_parameter_string){
        switch($duration_parameter_string){
            case 'days':
                $duration_parameter = 86400;
            break;
            case 'years':
                $duration_parameter = (365*86400);
            break;
            case 'months':
                $duration_parameter =(30*86400);
            break;
            case 'weeks':
                $duration_parameter = (7*86400);
            break;
            case 'minutes':
                $duration_parameter = 60;
            break;
            case 'seconds':
                $duration_parameter = 1;
            break;
            default :
                $duration_parameter = 1;
            break;
       }
       return $duration_parameter;
    }

    function migrate_questions($quiz_id,$course_id){
        global $wpdb;
        $table = $wpdb->prefix.'tutor_quiz_questions';
        $questions = $wpdb->get_results("SELECT * FROM {$table} WHERE quiz_id = $quiz_id ORDER BY question_order ASC");
        $quiz_questions = array('ques'=>array(),'marks'=>array());
        if(!empty($questions)){
            foreach($questions as $question){
                $args = array(
                    'post_type'=>'question',
                    'post_status'=>'publish',
                    'post_title'=>$question->question_title,
                    'post_author'=>get_current_user_id(),
                    'post_content'=>$question->question_description
                );
                $question_id = wp_insert_post($args);
                if(empty($question_id)){
                    continue;
                }

                $quiz_questions['ques'][]=$question_id;
                $quiz_questions['marks'][]=intval($question->question_mark);

                if(!empty($question->answer_explanation)){
                    update_post_meta($question_id,'vibe_question_explaination',$question->answer_explanation);
                }
                $type = '';
                switch ($question->question_type) {
                    case 'true_false':
                        $type = 'truefalse';
                        $qid =$question->question_id;
                        $table = $wpdb->prefix.'tutor_quiz_question_answers';
                        $correct = $wpdb->get_var("SELECT answer_two_gap_match FROM {$table} WHERE belongs_question_id = {$qid} AND is_correct=1 AND belongs_question_type='true_false'");
                        if(!empty($correct) && $correct=='true'){
                            update_post_meta($question_id,'vibe_question_answer',1);
                        }else{
                            update_post_meta($question_id,'vibe_question_answer',0);
                        }
                        break;
                    case 'single_choice':
                        $type = 'single';
                        $qid =$question->question_id;
                        $table = $wpdb->prefix.'tutor_quiz_question_answers';
                        $options = $wpdb->get_results("SELECT * FROM {$table} WHERE belongs_question_id = {$qid} AND belongs_question_type='single_choice' ORDER BY answer_order ASC");
                        $new_options= [];
                        $correct = 1;
                        if(!empty($options) ){
                            foreach ($options as $key => $option) {
                                $answer_option = $option->answer_title;

                                if(!empty($option->answer_view_format) && $option->answer_view_format=='text_image' && !empty($option->image_id)){
                                    $url = wp_get_attachment_url($option->image_id);
                                    if(!empty($url)){
                                        $answer_option .= '<br/><img src="'.$url.'"/>';
                                    }
                                    
                                }
                                $new_options[] =   $answer_option;
                                if(!empty($option->is_correct)){
                                    $correct = $key+1;
                                }
                                
                            }
                        }
                        update_post_meta($question_id,'vibe_question_options',$new_options);
                        update_post_meta($question_id,'vibe_question_answer',$correct);
                        break;
                    case 'multiple_choice':
                        $type = 'multiple';
                        $qid =$question->question_id;
                        $table = $wpdb->prefix.'tutor_quiz_question_answers';
                        $options = $wpdb->get_results("SELECT * FROM {$table} WHERE belongs_question_id = {$qid} AND belongs_question_type='multiple_choice' ORDER BY answer_order ASC");
                        $new_options= [];
                        $correct = [];
                        if(!empty($options) ){
                            foreach ($options as $key => $option) {
                                $answer_option = $option->answer_title;

                                if(!empty($option->answer_view_format) && $option->answer_view_format=='text_image' && !empty($option->image_id)){
                                    $url = wp_get_attachment_url($option->image_id);
                                    if(!empty($url)){
                                        $answer_option .= '<br/><img src="'.$url.'"/>';
                                    }
                                    
                                }
                                $new_options[] =   $answer_option;
                                if(!empty($option->is_correct)){
                                    $correct[] = $key+1;
                                }
                                
                            }
                        }
                        update_post_meta($question_id,'vibe_question_options',$new_options);
                        update_post_meta($question_id,'vibe_question_answer',implode(',', $correct));
                        break;
                    case 'open_ended':
                    case 'short_answer':
                        $type = 'largetext';
                        break;
                    case 'fill_in_the_blank':
                        
                        $type = 'fillblank';
                        $qid =$question->question_id;
                        $table = $wpdb->prefix.'tutor_quiz_question_answers';
                        $options = $wpdb->get_results("SELECT * FROM {$table} WHERE belongs_question_id = {$qid} AND belongs_question_type='fill_in_the_blank' ORDER BY answer_order ASC");
                        $new_statment= '';
                        $correct = [];
                        if(!empty($options) ){
                            foreach ($options as $key => $option) {
                                $answer_option = str_replace('{dash}', '[fillblank]',$option->answer_title );
                                $answer_option = '<br/>'.$answer_option;
                                
                                $new_statment .=   $answer_option;
                                $correct = $option->answer_two_gap_match;
                                
                            }
                        }


                        $my_post['ID'] = $question_id;

                        $my_post['post_content'] = get_post_field('post_content',$question_id).$new_statment;
                        wp_update_post( $my_post );
                        update_post_meta($question_id,'vibe_question_answer',$correct);
                        break;
                    
                    case 'matching':
                        $match_content= '';
                        $type = 'match';
                        $table = $wpdb->prefix.'tutor_quiz_question_answers';
                        $qid =$question->question_id;
                        $options = $wpdb->get_results("SELECT * FROM {$table} WHERE belongs_question_id = {$qid} AND belongs_question_type='matching' ORDER BY answer_order ASC");
                        $new_options = [];
                        $correct = [];
                        $content_li = [];
                        //options answer_two_gap_match
                        //description li answer_title

                        if(!empty($options) ){
                            foreach ($options as $key => $option) {
                                $answer_option = $option->answer_two_gap_match;

                                if(!empty($option->answer_view_format) && $option->answer_view_format=='text_image' && !empty($option->image_id)){
                                    $url = wp_get_attachment_url($option->image_id);
                                    if(!empty($url)){
                                        $answer_option .= '<br/><img src="'.$url.'"/>';
                                    }
                                    
                                }
                                $new_options[] =   $answer_option;
                                
                                $content_li[] = $option->answer_title;
                                $correct[]=$key+1;
                                
                            }
                        }
                        update_post_meta($question_id,'vibe_question_options',$new_options);
                        update_post_meta($question_id,'vibe_question_answer',implode(',', $correct));
                        if(!empty($content_li)){
                            $match_content = '<br/>[match]
                            <ul>';
                            
                            foreach ($content_li as $key => $li) {
                                $match_content .= ' <li>'.$li.'</li>';
                            }
                            $match_content .='</ul>
                            [/match]';
                        }
                        $my_post['ID'] = $question_id;

                        $my_post['post_content'] = get_post_field('post_content',$question_id).$match_content;
                        wp_update_post( $my_post );

                        break;
                    case 'image_matching':
                        $match_content= '';
                        $type = 'match';
                        $table = $wpdb->prefix.'tutor_quiz_question_answers';
                        $qid =$question->question_id;
                        $options = $wpdb->get_results("SELECT * FROM {$table} WHERE belongs_question_id = {$qid} AND belongs_question_type='image_matching' ORDER BY answer_order ASC");
                        $new_options = [];
                        $correct = [];
                        $content_li = [];
                        //options answer_title
                        //description li image_id

                        if(!empty($options) ){
                            foreach ($options as $key => $option) {
                                $new_options[] = $option->answer_title;

                                if(!empty($option->image_id)){
                                    $url = wp_get_attachment_url($option->image_id);
                                    if(!empty($url)){
                                        $content_li[] = '<img src="'.$url.'"/>';
                                    }
                                    
                                }
                                
                                $correct[]=$key+1;
                                
                            }
                        }
                        update_post_meta($question_id,'vibe_question_options',$new_options);
                        update_post_meta($question_id,'vibe_question_answer',implode(',', $correct));
                        if(!empty($content_li)){
                            $match_content = '<br/>[match]
                            <ul>';
                            
                            foreach ($content_li as $key => $li) {
                                $match_content .= ' <li>'.$li.'</li>';
                            }
                            $match_content .='</ul>
                            [/match]';
                        }
                        $my_post['ID'] = $question_id;

                        $my_post['post_content'] = get_post_field('post_content',$question_id).$match_content;
                        wp_update_post( $my_post );
                        break;
                    case 'image_answering':
                        $type = 'smalltext';
                        $table = $wpdb->prefix.'tutor_quiz_question_answers';
                        $qid =$question->question_id;
                        $options = $wpdb->get_results("SELECT * FROM {$table} WHERE belongs_question_id = {$qid} AND belongs_question_type='image_answering' ORDER BY answer_order ASC");
                        $new_statment= '';
                        $correct = [];
                        if(!empty($options) ){
                            foreach ($options as $key => $option) {
                                if(!empty($option->image_id)){
                                    $url = wp_get_attachment_url($option->image_id);
                                    if(!empty($url)){
                                        $new_statment .= '<img src="'.$url.'"/>';
                                    }
                                }
                                
                                $correct = $option->answer_title;
                                
                            }
                        }


                        $my_post['ID'] = $question_id;

                        $my_post['post_content'] = get_post_field('post_content',$question_id).$new_statment;
                        wp_update_post( $my_post );
                        update_post_meta($question_id,'vibe_question_answer',$correct);
                        break;
                    case 'ordering':
                        
                        $type = 'sort';
                        $table = $wpdb->prefix.'tutor_quiz_question_answers';
                        $qid =$question->question_id;
                        $options = $wpdb->get_results("SELECT * FROM {$table} WHERE belongs_question_id = {$qid} AND belongs_question_type='ordering' ORDER BY answer_id DESC");
                        $new_options = [];
                        $correct = [];
                        //options answer_title
                        //description li image_id

                        if(!empty($options) ){
                            foreach ($options as $key => $option) {
                                $new_options[] = $option->answer_title;

                                if(!empty($option->image_id)){
                                    $url = wp_get_attachment_url($option->image_id);
                                    if(!empty($url)){
                                        $content_li[] = '<img src="'.$url.'"/>';
                                    }
                                    
                                }
                                
                                $correct[]=$option->answer_order;
                                
                            }
                        }
                        update_post_meta($question_id,'vibe_question_options',$new_options);
                        update_post_meta($question_id,'vibe_question_answer',implode(',', $correct));
                        


                        break;
                }
                
                update_post_meta($question_id,'vibe_question_type',$type);
            }
            update_post_meta($quiz_id,'vibe_quiz_questions',$quiz_questions);
        }
            
        
    }

    function migrate_assignment_settings($unit_id,$course_id){
        global $wpdb;
        update_post_meta($unit_id,'vibe_assignment_course',$course_id);
        $settings = get_post_meta($unit_id,'assignment_option',true);
        if(!empty($settings)){
            if(!empty($settings['time_duration']) && !empty($settings['time_duration']['value'])){
                update_post_meta($unit_id,'vibe_assignment_duration',intval($settings['time_duration']['value']));
                update_post_meta($unit_id,'vibe_assignment_duration_parameter',$this->time_duration_string_to_int($settings['time_duration']['time']));
            }
            if(!empty($settings['total_mark'])){
                update_post_meta($unit_id,'vibe_assignment_marks',$settings['total_mark']);
            }
            if(!empty($settings['upload_file_size_limit'])){
                update_post_meta($unit_id,'vibe_attachment_size',$settings['upload_file_size_limit']);
            }
            
            update_post_meta($unit_id,'vibe_attachment_type',array (
              'JPG',
              'GIF',
               'PNG',
              'PDF',
              'DOCX',
              'DOC',
            ));
            update_post_meta($unit_id,'vibe_assignment_submission_type','upload');
            
            $atts = get_post_meta($unit_id,'_tutor_assignment_attachments',true);
            if(!empty($atts)){
                $atts_urls = [];
                foreach ($atts as $key => $at) {
                   
                    $atts_urls[]= '<br/><a href="'.wp_get_attachment_url($at).'">'._x('Attachment','','wplms-tutorlms-migration').' '.($key+1).' </a>';
                    
                }
                $atts_urls = implode(',', $atts_urls);
                $my_post['ID'] = $unit_id;

                $my_post['post_content'] = get_post_field('post_content',$unit_id).$atts_urls;
                wp_update_post( $my_post );
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

    function endTutorLmsMigration(){
        if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-tutorlms-migration');
            die();
        }
        global $wpdb;
        //we have to add topics as sectionand its description as unit next to section
        //$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'unit' WHERE post_type = 'topics'");
    }
}

Wplms_TutorLms_Migration_Init::init();