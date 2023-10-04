<?php

if(!class_exists('Wplms_TutorLms_Migration_Init')){
	class Wplms_TutorLms_Migration_Init{

		public static $instance;
		public static function init(){

	        if ( is_null( self::$instance ) )
	            self::$instance = new Wplms_TutorLms_Migration_Init();
	        return self::$instance;
	    }

	    public function __construct(){
	    	
	    }

	    
		

	} 
}

Wplms_TutorLms_Migration_Init::init();