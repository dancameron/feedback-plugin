<?php
############################################################################################
# ATTENTION: Don't remove any CSS classnames or ids, that could break the javascript
############################################################################################

if(!function_exists('avia_feedback_box_html_output'))
{
	/**
 	* Function that displays the Feedback Entries and controlls in your frontend. 
 	* The function is separated from the main aviaFeedbackBox class so that less seasoned developers have an easier time to edit it
 	*
 	* @param obj $feedback 	The $feedback var contains the result of a wordpress query. It holds all data like title, content, author, rating and other custom name values
 	* @param obj $user 		The $user var contains data about the currently viewing user. Based on that data the output will change
 	* @param obj $message 	The $message var contains an error or success message if the user submitted an entry
 	* @return string 		The frontend HTML is returned
 	*/
 	function avia_feedback_box_html_output($feedback, $user, $message='')
	{	
		
		//navigation links
		$feedbackHTML  = "	<div id='avia_feedback_box' class='avia_".$user->settings['skin']."'>\n";
		$feedbackHTML .= "		<div class='avia_ajax_loader'></div>\n";
		$feedbackHTML .= "		<div class='avia_feedback_navigation'>\n";
		$feedbackHTML .= "			<a href='".avia_feedback_url_builder('feedback_sort=popular')."' title='' class='avia_feedback_popular avia_ajaxed'>Popular</a>\n";
		$feedbackHTML .= "			<a href='".avia_feedback_url_builder('feedback_sort=newest')."' title='' class='avia_feedback_newest avia_ajaxed'>Newest</a>\n";
		$feedbackHTML .= "			<a href='".avia_feedback_url_builder('feedback_sort=progress')."' title='' class='avia_feedback_progress avia_ajaxed'>In Progress</a>\n";
		$feedbackHTML .= "			<a href='".avia_feedback_url_builder('feedback_sort=completed')."' title='' class='avia_feedback_finished avia_ajaxed'>Completed</a>\n";
		if($user->settings['avia_feedback_info']) $feedbackHTML .= "		<div class='avia_feedback_info'>Info<div class='avia_info_hover avia_info_hover_".$user->settings['skin']."'>".html_entity_decode($user->settings['avia_feedback_info'])."</div></div>\n";
		if($user->voting_allowed ) $feedbackHTML .= "			<a href='?addentry' title='' class='avia_feedback_add'>Add Entry</a>\n";
		$feedbackHTML .= "		</div>\n\n";
		
		//feedback form (hidden by default)
		$feedbackHTML .= avia_feedback_commentform($user);		
		
		$feedbackHTML .= '		<div id="avia_feedback_ajaxed">';
		//post display loop starts here
		$feedbackHTML .= avia_feedback_display_posts($feedback, $user, $message);
		
		//pagination
		$feedbackHTML.= aviaFeedbackBox::pagination($feedback->max_num_pages);
		$feedbackHTML .= '		</div>';
		
		//hidden input field with ajax url value
		$feedbackHTML .= "		<input type='hidden' name='avia_box_ajax_url' value='".AVIA_FEEDBACK_BOX_URL_FOLDER."' />\n";

		$feedbackHTML .= "	</div>\n\n";
		wp_reset_query();
		return $feedbackHTML;
		
	}
}


if(!function_exists('avia_feedback_display_posts'))
{ 
	/**
 	* Function that displays the Feedback Entries
 	* The function is separated from the main aviaFeedbackBox class so that less seasoned developers have an easier time to edit it
 	*
	* @param obj $feedback 	The $feedback var contains the result of a wordpress query. It holds all data like title, content, author, rating and other custom name values
 	* @param obj $user 		The $user var contains data about the currently viewing user and the backend settings. Based on that data the output will change
 	* @return string 		The Post HTML is returned
 	*/
	function avia_feedback_display_posts($feedback, $user, $message = '')
	{	
	
		$feedbackHTML = $v_class = "";
		
		//success or error message upon post/comment sending
		if($message) $feedbackHTML.= $message;
		
		if(is_object($feedback) && $feedback->have_posts()) : while ($feedback->have_posts()) : $feedback->the_post();
			global $post;
			//gather necessary variables and data
			$id = get_the_ID();
			$votes = $post->menu_order; 
			$author = get_post_meta($id, "_avia_feedback_author", true); 
			$status = get_post_meta($id, "_avia_feedback_box_status", true); 
			$icon = get_post_meta($id, "_avia_feedback_box_icon", true); 
			$admincomment = get_post_meta($id, "_avia_feedback_box_admincomment", true); 
			$v_count = $user->_avia_feedback_box_user_votes; 
			$castedVotes = aviaFeedbackBox::get_casted_votes($user->unified_id, $id);
			$unixTimeStamp = strtotime(get_the_time('Y:m:d H:i:s'));
			$timestamp = human_time_diff( $unixTimeStamp, time());
			$temp_allowed = $user->voting_allowed;
			$v_class = "";
			$content =  apply_filters('the_content',get_the_content());

			
			if($status == 'closed') $temp_allowed = false;
			
			$castedVotesClass = $castedVotes != "" ?  'avia_visible' : "";
			if(!$votes) $votes = "0";
			if(!$temp_allowed) $v_class = 'avia_inactive';
			if(!$user->voting_allowed) $v_count = '0';
			
			if($admincomment != "" && $icon == "")
			{
				$icon = 'info';
			}
			
			$votetext = "<strong>$v_count</strong> Votes left ";
			if(!$temp_allowed && $status == 'closed') $votetext = "Voting closed";
			
			
			//html rendering of entries
			$feedbackHTML .= "	<div class='avia_feedback_entry'>\n";
			$feedbackHTML .= "		<div class='avia_feedback_entry_display_vote'>\n";
			$feedbackHTML .= "		<span class='avia_feedback_entry_vote_count'><strong>$votes</strong> Votes</span>\n";
 			$feedbackHTML .= "		<span class='avia_feedback_casted_votes $castedVotesClass'>$castedVotes</span>\n";
			$feedbackHTML .= "		</div>\n";
			$feedbackHTML .= "		<a class='uservotes $v_class' id='avia_addvote-$id' href='?addVote-$id'>$votetext</a>\n";
			$feedbackHTML .= "		<div class='avia_feedback_entry_content'>\n";
			$feedbackHTML .= "			<h4 class='avia_feedback_title'>".get_the_title()."</h4>\n";
			if($admincomment||$status) $feedbackHTML .= "		<div class='avia_feedback_status avia_feedback_status_$icon'>?<div class='avia_info_hover avia_info_hover_".$user->settings['skin']."'>".html_entity_decode($admincomment)."</div></div>\n";
			$feedbackHTML .= "			<div class='avia_feedback_text'>".$content."</div>\n";
			$feedbackHTML .= "			<a id='avia_feedback_comments-$id' class='avia_feedback_comment' href='?comments-$id'><span>".get_comments_number($id)."</span> Comments</a>\n";
			$feedbackHTML .= "			<span id='avia_feedback_comments-meta-$id' class='avia_feedback_comment_meta'><span>suggested by ".$author.", ".$timestamp." ago</span></span>\n";
			$feedbackHTML .= "			<div class='avia_feedback_box_comments avia_hidden'>\n";
			$feedbackHTML .= "			</div>\n";
			$feedbackHTML .= "		</div>\n";
			$feedbackHTML .= "	</div>\n\n";
			
		endwhile; endif;
	


		return $feedbackHTML;
	}



}


if(!function_exists('avia_feedback_commentform'))
{

	/**
 	* Function that displays the Feedback Comment Form, both for new entries as well as for comments
 	* The function is separated from the main aviaFeedbackBox class so that less seasoned developers have an easier time to edit it
 	*
 	* @param obj $user 		The $user var contains data about the currently viewing user. Based on that data the output will change
 	* @param string $context 	either feedback or comment: slightly different form rendering depending on the context
 	* @param int $post_id 	The $post_id var contains the id of the current post (optional, depending on context)
 	* @return string 		The frontend HTML is returned
 	*/
	function avia_feedback_commentform($user, $context = 'feedback', $post_id='')
	{	
		$action = "";
		$status = 'open';
		if($context == 'feedback') { $action =  get_pagenum_link(1); } else { $status = get_post_meta($post_id, "_avia_feedback_box_status", true);  }
		
		
		if($user->commenting_allowed && $status == 'open')
		{
			$feedbackHTML   = "		<div class='avia_clear'></div>\n";
			$feedbackHTML  .= "		<form class='avia_feedback_form' method='post' action='".$action."'><div class='avia_feedback_inner_form'>\n";
			
			if($user->vote_access == 'everyone')
			{
				$feedbackHTML .= "			<p>";	
				$feedbackHTML .= "			<label for='avia_".$context."_name'>Your Name</label>";
				$feedbackHTML .= "			<input type='text' value='' class='avia_text_input' name='avia_".$context."_name' id='avia_".$context."_name' />";
				$feedbackHTML .= "			</p>";
				
				$feedbackHTML .= "			<p>";
				$feedbackHTML .= "			<label for='avia_".$context."_mail'>Your E-Mail (won't be displayed)</label>";
				$feedbackHTML .= "			<input type='text' value='' class='avia_text_input' name='avia_".$context."_mail' id='avia_".$context."_mail' />";
				$feedbackHTML .= "			</p>";			
			}

			if($context == 'feedback')
			{
				$feedbackHTML .= "			<p>";
				$feedbackHTML .= "			<label for='avia_".$context."_titel'>Subject</label>";
				$feedbackHTML .= "			<input type='text' value='' class='avia_text_input' name='avia_".$context."_titel' id='avia_".$context."_titel' />";
				$feedbackHTML .= "			</p>";
			}
			
			/*this field will never be displayed and is only used to fool bots that try to automatically fill in form: if "website" contains any value the form wont be sumbitted*/
			$feedbackHTML .= "			<p class='avia_hidden'><label class='avia_hidden' for='avia_".$context."_website'>If you are human dont fill in this field:</label>";
			$feedbackHTML .= "			<input type='text' value='' class='avia_text_input avia_hidden' name='avia_".$context."_website' id='avia_".$context."_website' /></p>";
			/* * * */
			
			$feedbackHTML .= "			<p>";
			$feedbackHTML .= "			<label for='avia_".$context."_message'>Your Message</label>";
			$feedbackHTML .= "			<textarea class='avia_text_area' name='avia_".$context."_message' id='avia_".$context."_message' rows='4' cols='30'></textarea>";
			$feedbackHTML .= "			</p>";
			
			
			$feedbackHTML .= "			<input type='submit' value='Submit' class='avia_text_button' name='avia_".$context."_new_send' id='avia_".$context."_new_send' />";
			$feedbackHTML .= "			<input type='hidden' value='".$post_id."' name='avia_".$context."_post_id' class='avia_send_by_js' />";

			
			$feedbackHTML .= "		</div></form>\n\n";
			
			return $feedbackHTML;
		}
	}
}



if(!function_exists('avia_feedback_comments'))
{

	/**
 	* Function that displays the Feedback Comments
 	* The function is separated from the main aviaFeedbackBox class so that less seasoned developers have an easier time to edit it
 	*
 	* @param obj $user	 		The $user var contains the data for the current user 
 	* @param obj $comments 		The $comments var contains the result of a wordpress "get_comments" query. It holds all data like comment-title, comment-content, comment-author etc
 	* @param string $ajaxClass 	A class to add if its an ajax request (needs to be hidden to make slideUp possible)
  	* @return string 			The comment HTML output is returned
 	*/
	function avia_feedback_comments($user, $comments)
	{			
		$commentsHTML = "";
		foreach($comments as $comment) :
			
			//gather necessary variables and data
			$dateformat = get_option('date_format') == "" ? 'd.m.Y' : get_option('date_format');
			
			$unixTimeStamp = strtotime($comment->comment_date_gmt);
			$date = human_time_diff( $unixTimeStamp, time());
			// uncomment the following line if you want a "real" date
			// $date = date($dateformat, $unixTimeStamp);
			
			//html rendering
			$commentsHTML .= "<div class='avia_feedback_box_comment'>\n";
			$commentsHTML .= "  <div class='avia_feedback_box_comment_meta'>\n";
			$commentsHTML .= "  <div class='avia_feedback_box_comment_gravatar'>\n";
			$commentsHTML .= 	get_avatar( $comment->comment_author_email, $size = '32', $default = '' ); 
			$commentsHTML .= "  </div>\n";
			$commentsHTML .= "  <span class='avia_feedback_box_comment_author'>".$comment->comment_author . " said </span>";
			$commentsHTML .= "  <span class='avia_feedback_box_comment_date'>".$date." ago</span>";
			$commentsHTML .= "  </div>\n";
			$commentsHTML .= "  <div class='avia_feedback_box_comment_text'>\n";
			$commentsHTML .=    $comment->comment_content;
			$commentsHTML .= "  </div>\n";
			$commentsHTML .= "</div>\n";
		endforeach;
		
		return $commentsHTML;
	}
}




if(!function_exists('avia_feedback_message'))
{

	/**
 	* Function that displays a message based on the value passed
 	* The function is separated from the main aviaFeedbackBox class so that less seasoned developers have an easier time to edit it
 	*
 	* @return string 			The message returned
 	*/
	function avia_feedback_message($selector, $settings = '')
	{
		$msg = $class = "";
		
		switch($selector)
		{	
			case 'new_comment_entry':
				$class = 'avia_success';
				$msg =  "<strong>Thanks for your comment!</strong><br/> We appreciate your effort ;)";
			break;
			
			case 'failed_comment_submit':
				$class = 'avia_error';
				$msg =  "<strong>Couldn't add your comment.</strong><br/> Please make sure to fill in all form fields.";
			break;
		
			case 'new_feedback_entry' :
				
				$class = 'avia_success';
				
				if($settings['post_status'] == 'draft')
				{
					$msg =  "<strong>Thanks for your feedback!</strong><br/> Once your entry was reviewed it will be displayed for everyone to comment.";
				}
				else
				{
					$msg =  "<strong>Thanks for your feedback!</strong><br/> People can now vote on your idea!";
				}
				
			break;
			
			case 'failed_submit' :
				
				$class = 'avia_error';
				$msg =  "<strong>Sorry, we couldn't add your submission.</strong><br/> Please make sure to fill in all form fields.";
				
			break;
		}
		
		if($msg) return "<div class='avia_notification ".$class."'><span class='avia_notification_icon avia_icon_".$class."'></span>".$msg."</div>";
	}
}


if(!function_exists('avia_feedback_url_builder'))
{

	/**
 	* Function that creates the links for the main navigation based on the current url
 	*
 	* @param string $append		get parameters to append to the url
 	* @return string 			The link returned
 	*/
	function avia_feedback_url_builder($append)
	{	
		$link = get_permalink();
		$connector = "?";
		
		if(preg_match("#\?#",$link))
		{
			$connector = "&amp;";
		}
		
		if(!$append) $connector = "";
		
		return $link.$connector.$append;
	}
}


function avia_pagination_helper($page, $current = "")
{
	if($current != "") return $current;
	
	return get_pagenum_link($page);
}



