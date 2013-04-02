<?php
######################################################################
# Base Config
######################################################################
global $avia_feedback_default_settings, $avia_feedback_html_settings;
$avia_feedback_default_settings = array('avia_posts_per_page'=>'8','votes_per_month'=>10,'vote_access'=>'everyone','post_status'=>'publish');
$avia_feedback_html_settings = array('a' => array('href' => array(),'title' => array()),'br' => array(),'em' => array(),'strong' => array());


//images allowed $avia_feedback_html_settings = array('img' => array('src'=>array(), 'alt'=>array()),'a' => array('href' => array(),'title' => array()),'br' => array(),'em' => array(),'strong' => array());


define('AVIA_FEEDBACK','Avia Feedback Box');
define('AVIA_FEEDBACK_BOX_URL',WP_PLUGIN_URL);
define('AVIA_FEEDBACK_BOX_URL_FOLDER', AVIA_FEEDBACK_BOX_URL."/avia_feedback_box/");
define('AVIA_FEEDBACK_BOX_URL_HELPER', AVIA_FEEDBACK_BOX_URL_FOLDER."avia_plugin_framework/");

######################################################################
# SHORTCODES
######################################################################

add_shortcode('avia_feedback_box', 'avia_feedback_box_shortcode');

function avia_feedback_box_shortcode($atts, $content=null, $shortcodename ="")
{	
	global $aviaFeedbackBox;
	$return = $aviaFeedbackBox->display();
	
	return $return;
}

function avia_feedback_box_display()
{
	global $aviaFeedbackBox;
	echo   $aviaFeedbackBox->display();
}

######################################################################
# POST METABOXES FOR BACKEND
######################################################################

$options = array();
$boxinfo = array('title' => 'Additional Options', 'id'=>'post_thumb_add', 'page'=>array('feedback'), 'context'=>'side', 'priority'=>'high', 'callback'=>'');


$options[] = array(	"name" => "<strong>Author Name</strong>",
			"desc" => "",
			"id" => "_avia_feedback_author",
			"std" => "",
			"size" => 31,
			"type" => "text");
			
$options[] = array(	"name" => "<strong>Author E-Mail</strong>",
			"desc" => "",
			"id" => "_avia_feedback_author_mail",
			"std" => "",
			"size" => 31,
			"type" => "text");				
$options[] =	array(	"name" => "<strong>Status</strong>",
	"desc" => "",
    "id" => "_avia_feedback_box_status",
    "type" => "dropdown",
    "std" => "open",
    "subtype" => array('Open'=>'open','Closed'=>'closed'));	
    
			
$options[] = array(	"name" => "<strong>Status Message</strong>",
			"desc" => "",
			"id" => "_avia_feedback_box_admincomment",
			"std" => "",
			"size" => 31,
			"type" => "textarea");
			
$options[] =	array(	"name" => "<strong>Status Message Icon</strong>",
	"desc" => "",
    "id" => "_avia_feedback_box_icon",
    "type" => "dropdown",
    "std" => "",
    "subtype" => array('None'=>'','Information'=>'info','In progress'=>'progress','Completed'=>'completed','Locked'=>'declined'));				
			

$new_box = new avia_meta_box_plugin_helper($options, $boxinfo);


######################################################################
# OPTIONS PAGE BACKEND
######################################################################


$pageinfo = array('full_name' => '"'.AVIA_FEEDBACK.'" General Options', 'optionname'=>'avia_feedback_box_settings', 'child'=>false, 'filename' => basename(__FILE__));
$subarray = array();
for($i = 1; $i < 21; $i++)
{
	$subarray[$i] = $i;
}



$options = array();
			
$options[] = array(	"type" => "open");
	
	
$options[] = array(	"name" => "'".AVIA_FEEDBACK."' - Skin",
			"desc" => "Please choose one of the ".AVIA_FEEDBACK." skins here",
            "id" => "skin",
            "type" => "dropdown",
            "std" => "",
            "subtype" => array(AVIA_FEEDBACK.' - Light'=>'light_skin',AVIA_FEEDBACK.' - Dark'=>'dark_skin',AVIA_FEEDBACK.' - Structure only'=>'no_skin'));



$options[] = array(	"name" => "Which page should contain the Feedback Box?",
			"desc" => "Select a page that displays the whole Avia Feedback Box.<br/><strong>Advanced users:</strong> You can also use the shortcode [avia_feedback_box] in your posts or the PHP function 'avia_feedback_box_display()' in your tempalte files instead.",
            "id" => "feedbackbox_page_id",
            "type" => "dropdown",
            "std" => "everyone",
            "subtype" => 'page');


$options[] = array(	"name" => "Vote and Submission access",
			"desc" => "Who is allowed to vote and submit new Feedback entries?",
            "id" => "vote_access",
            "type" => "dropdown",
            "std" => "everyone",
            "subtype" => array('Everyone'=>'everyone', 'Registered Members only'=>'member'));

            

$options[] = array(	"name" => "Votes to cast",
			"desc" => "How many Votes are users allowed to distribute each month?",
            "id" => "votes_per_month",
            "type" => "dropdown",
            "std" => 10,
            "subtype" => $subarray);

$options[] = array(	"name" => "Feedback publishing",
			"desc" => "Should new Feedback get published immediately or should the status be set to 'draft' so you can review it?",
            "id" => "post_status",
            "type" => "dropdown",
            "std" => 'publish',
            "subtype" => array('Draft'=>'draft','Published'=>'publish'));


$subarray["All Posts"] = '99999';
$options[] = array(	"name" => "Feedback Entries per Page",
			"desc" => "How many Feedback entries do you want to display per page?",
            "id" => "avia_posts_per_page",
            "type" => "dropdown",
            "std" => "8",
            "subtype" => $subarray);
            


							
$options[] = array(	"name" => "Info Text",
		"desc" => "This info text appears when someone hovers over the Question Mark Icon/information Button",
        "id" => "avia_feedback_info",
        "std" => "<p>The Feedbackbox is our feature request system. You can suggest new ideas, vote on existing ones, and track our progress.</p>

<p>Each month you get 10 votes to distribute. You can choose to use all your votes for one idea you really like, or spread them across between several ideas :)</p>

<p>Thanks for voting! </p>",
        "type" => "textarea");





$options[] = array(	"type" => "close");
	
          

$options_page = new avia_option_pages_plugin_helper($options, $pageinfo);
