<?php
/*
Plugin Name: wplms tutor lms migration
Plugin URI: http://www.vibethemes.com
Description: A simple WordPress plugin to modify wplms-tutorlms-migration
Version: 1.0
Author: VibeThemes
Author URI: http://www.vibethemes.com
License: GPL2
Text Domain: wplms-tutorlms-migration
*/
/*
Copyright 2014  VibeThemes  (email : vibethemes@gmail.com)

wplms_customizer program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

wplms_customizer program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with wplms_customizer program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


*/


include_once 'includes/class.init.php';






add_action('plugins_loaded','Wplms_TutorLms_Migration_translations');
function Wplms_TutorLms_Migration_translations(){
    $locale = apply_filters("plugin_locale", get_locale(), 'wplms-tutorlms-migration');
    $lang_dir = dirname( __FILE__ ) . '/languages/';
    $mofile        = sprintf( '%1$s-%2$s.mo', 'wplms-tutorlms-migration', $locale );
    $mofile_local  = $lang_dir . $mofile;
    $mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

    if ( file_exists( $mofile_global ) ) {
        load_textdomain( 'wplms-tutorlms-migration', $mofile_global );
    } else {
        load_textdomain('wplms-tutorlms-migration', $mofile_local );
    }  
}
add_filter( 'auto_update_plugin', '__return_false' );
add_filter( 'auto_update_theme', '__return_false' );

remove_action( 'load-update-core.php', 'wp_update_plugins' );

add_filter( 'pre_site_transient_update_plugins', function($x){
    return null;
} );
