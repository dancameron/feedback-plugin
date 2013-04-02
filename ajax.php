<?php

    $paths = array(
        "../..",
        "../../..",
        "../../../..",
        "../../../../..",
        "../../../../../..",
        "../../../../../../..",
        "../../../../../../../.."
    );
   
   	#include wordpress, make sure its available in one of the higher folders
    foreach ($paths as $path) 
    {
       if(@include_once($path.'/wp-load.php')) break;
    }
    
# end request if no action was specified or user is not logged in
if ( ! isset( $_REQUEST['action'] ))  die('Error - no such Action');


$action = $_REQUEST['action'];

@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
send_nosniff_header();


//check if we got a valid post id
if(isset($_REQUEST['id']) && !ctype_digit($_REQUEST['id'] )) {
	if($_REQUEST['id'] != "")
	{
		echo "Error - invalid";
		return false;
	}
}

$post_ID = $_REQUEST['id'];
global $aviaFeedbackBox;

	
switch ($action)
{
	
	//show the comments of a feedback entry
	case 'showComments':
		
		$output = "";
		//get the comments object by querying the database and then pass that object to the avia_feedback_comments function located in html_output for rendering
		$comments = get_comments( array('post_id' => $post_ID, 'status' => 'approve', 'order' => 'ASC') );
		
		$output .= avia_feedback_comments($aviaFeedbackBox->user, $comments);
		$output .= avia_feedback_commentform($aviaFeedbackBox->user, 'comment', $post_ID);
		
		echo $output;
		
	break;
	
	
	//cast a vote
	case 'vote':
		global $wpdb, $avia_table_name, $current_user;
		$unifiedQueries = false;

		if($aviaFeedbackBox->user->_avia_feedback_box_user_votes > 0)
		{
			
			$voteValue = get_post($post_ID); 
			$voteValue = $voteValue->menu_order;
			
			//case membervote
			if($aviaFeedbackBox->user->vote_access == 'member' && function_exists('is_user_logged_in') && is_user_logged_in())
			{	
				//save the vote to the item and decrease the users votecount
				$unifiedQueries = true;
	      		get_currentuserinfo();
	      		
	      		update_user_meta($current_user->ID, '_avia_feedback_box_user_votes', $current_user->_avia_feedback_box_user_votes -1 );
			}
			//case everyone vote
			else if($aviaFeedbackBox->user->vote_access == 'everyone')
			{
 				$unifiedQueries = true;
 				
 				if($aviaFeedbackBox->user->first_vote === true)
 				{
 					$wpdb->insert( $avia_table_name['user_table'], array( 'user_ip' => $aviaFeedbackBox->user->ip_adress, 'user_votes_left' => $aviaFeedbackBox->user->_avia_feedback_box_user_votes -1 ) );
 				}
 				else
 				{
					$wpdb->update( $avia_table_name['user_table'], array( 'user_ip' => $aviaFeedbackBox->user->ip_adress, 'user_votes_left' => $aviaFeedbackBox->user->_avia_feedback_box_user_votes -1), array('user_ip'=>$aviaFeedbackBox->user->ip_adress), array( '%s','%d' ), array( '%s' ) );
				}
			}
			
			//run queries that need to be sent in both cases
			if($unifiedQueries)
			{
				
				$castedVotes = aviaFeedbackBox::get_casted_votes($aviaFeedbackBox->user->unified_id, $post_ID);
				if($castedVotes)
				{
					$wpdb->update( $avia_table_name['vote_table'], array( 'user' => $aviaFeedbackBox->user->unified_id, 'post_id' => $post_ID, 'votes'=>$castedVotes + 1), array('user'=>$aviaFeedbackBox->user->unified_id,'post_id' => $post_ID), array( '%s','%d','%d' ), array( '%s','%d' ) );
				}
				else
				{
					$wpdb->insert( $avia_table_name['vote_table'], array( 'user' => $aviaFeedbackBox->user->unified_id, 'post_id' => $post_ID, 'votes'=> 1));
				}
				
				
				
				wp_update_post( array('ID'=>$post_ID, 'menu_order'=> $voteValue + 1) );
	
			}
			
		}
		break;
		
		
		case 'submitFeedback':
			$feedback = $aviaFeedbackBox->query_feedback($aviaFeedbackBox->addedFeddbackID);
			
			//if setting is: submit drafts remove the $feedback from the output
			if($aviaFeedbackBox->user->settings['post_status'] == 'draft') $feedback = "";
			
			echo avia_feedback_display_posts($feedback, $aviaFeedbackBox->user, $aviaFeedbackBox->message);
			
			
		break;
		
		case 'submitComment':
			global $wpdb;
			$comments = $wpdb->get_results( "SELECT * FROM $wpdb->comments WHERE comment_ID = '".$aviaFeedbackBox->addedCommentID."'" );
			echo avia_feedback_comments($aviaFeedbackBox->user, $comments);
		break;
		
		
		case 'switchContent':
			global $paged;
			$currentpage = $_REQUEST['feedback_sort'];
			$paged = $_REQUEST['get_page'];
			$feedback = $aviaFeedbackBox->query_feedback();
			$feedbackHTML = avia_feedback_display_posts($feedback, $aviaFeedbackBox->user);
			$feedbackHTML.= aviaFeedbackBox::pagination($feedback->max_num_pages, '?feedback_sort='.$currentpage);
			
			echo $feedbackHTML;
		break;
}












