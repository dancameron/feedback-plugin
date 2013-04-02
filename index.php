<?php
/*
Plugin Name: Avia Feedback Box
Plugin URI: http://aviathemes/plugins/feedbackbox/
Description: A feedback tool for customers -  <br/> Update notifications available on twitter and facebook:<br/> <a href='http://twitter.com/kriesi'>Follow me on twitter</a><br/> - <a href='http://www.facebook.com/pages/Kriesi/333648177216'>Join the Facebook Group</a>
Version: 1.0.0
Author: Christian "Kriesi" Budschedl
Author URI: http://aviathemes.com


Copyright 2010  Christian "Kriesi" Budschedl  (email : office@kriesi.at)
*/

//get the start of the next month


if(!function_exists('avia_feedbackbox_init'))
{
	add_action('init', 'avia_feedbackbox_init');    

	###################################################################################
	# initialization and instantiation of the feedback plugin + updates if avialable
	###################################################################################
	
	function avia_feedbackbox_init()
	{
		//get/set lobals
		global $aviaFeedbackBox, $avia_table_name, $wpdb;
		$avia_table_name['user_table'] = $wpdb->prefix . "avia_feedback_anonymous_user";
		$avia_table_name['vote_table'] = $wpdb->prefix . "avia_feedback_votes";
		
		//check if this version corresponds with the one installed
		$avia_feedback_ver = '1.0.0';
		$avia_feedback_ver_installed = get_option('avia_feedback_ver');
		
		//if there is no version or current version is higher than installed one run and update of options
		if ( version_compare($avia_feedback_ver, $avia_feedback_ver_installed, '>') )
		{	
			aviaFeedbackBox::run_update($avia_feedback_ver, $avia_feedback_ver_installed);
		}
		
		
		//add backend options and shortcodes
		require_once('avia_plugin_framework/avia_option_pages.php');
		require_once('avia_plugin_framework/avia_meta_box.php');
		require_once('backend_options.php');
		
		//add external html output functions
		require_once('html_output.php');
		
		//add stylesheet
		wp_enqueue_style( 'avia_feedback_box', AVIA_FEEDBACK_BOX_URL_FOLDER.'avia_feedback_box.css', false, $avia_feedback_ver, 'all' );
		
		//add javascript
		wp_enqueue_script('avia_feedback_box_js', AVIA_FEEDBACK_BOX_URL_FOLDER . 'feedbackbox.js', array('jquery'));
		
		//check if we need to clear the votes
		aviaFeedbackBox::clear_votes();
		
		$aviaFeedbackBox = new aviaFeedbackBox();


		
	}

	add_action('plugin_action_links_' . plugin_basename(__FILE__), 'avia_filter_plugin_actions');

	function avia_filter_plugin_actions($links) 
	{
		$new_links = array();
		$new_links[] = '<a href="admin.php?page=backend_options.php">Settings</a>';
		return array_merge($new_links, $links);
	}
	
	
	
	######################################################################
	# aviaFeedbackBox Main Class
	######################################################################
	
	
	class aviaFeedbackBox{
	
		public $user = "";
		public $settings = array();
		public $message;
		public $addedFeddbackID;
		public $addedCommentID;
		private $ip_adress;
		
		/**
		 * Initialization Function of the Class:
		 * Creates the new Feedback Post type, collects current user data as well as backend settings defined by the admin
		 */
		public function aviaFeedbackBox()
		{
			//create post type
			$this->_createPostType();
		
			//get the backend settings
			$this->_get_settings();
			
			//get the active user
			$this->_get_current_user();
			
			//check if entry or comment was submitted
			$this->submitFeedbackCheck();
			$this->submitFeedbackCommentCheck();
			
			//add content action
			add_filter('the_content', array(&$this, 'content_append'));
		}
		
		/**
		 * Function to append the feedback entries to a page
		 * @return string
		 */		
		public function content_append($content)
		{	
			global $post;
			if($post->ID == $this->settings['feedbackbox_page_id'] && $this->settings['feedbackbox_page_id'] != "")
			{
				$content.= $this->display();
			}
			return $content;
		}
	
	
		/**
		 * Function to display the feedback entries
		 * @return string
		 */
		public function display()
		{

			$feedback = $this->query_feedback();
			$output = avia_feedback_box_html_output($feedback, $this->user, $this->message);
			
			return $output;
		}

		
		/**
		 * Function to query the feedback entries from the wordpress database
		 * @return obj
		 */	
		public function query_feedback($id = '')
		{
			global $paged;
			$sort = "";
			
			if(isset($_REQUEST['feedback_sort'])) $sort = $_REQUEST['feedback_sort'];
			
			preg_match('#^[a-zA-Z_]+$#',$sort,$sorting_parameter);
			switch($sorting_parameter[0])
			{
			    case 'popular':
			    	$sort = '&meta_key=_avia_feedback_box_status&meta_compare=!=&meta_value=closed&orderby=menu_order';
			    break;
			    
			    case 'progress':
			    		$sort = '&meta_key=_avia_feedback_box_icon&meta_value=progress&orderby=menu_order';
			    break;
			    
			    case 'completed':
			    	$sort = '&meta_key=_avia_feedback_box_icon&meta_value=completed&orderby=menu_order';
			    break;
			    
			    case 'newest':
			    break;
			    
			    default:
			    	$sort = '&meta_key=_avia_feedback_box_status&meta_compare=!=&meta_value=closed&orderby=menu_order';
			    break;
			}
			//&meta_key=_avia_feedback_box_status&meta_compare=!=&meta_value=completed
			//parameters for querying feedback entries
			$query_string =  "posts_per_page=".$this->settings['avia_posts_per_page'];
			$query_string .= "&post_type=feedback&paged=$paged";
			$query_string .= $sort;
			if($id != "") $query_string .= "&p=".$id;
			
			// send query
			$feedback = new WP_Query($query_string); 
			
			return $feedback;
		}
		
				
		
		/**
		* Function that checks the submitted feedback entry and saves it to the database
		*/
		public function submitFeedbackCheck()
		{	
			if(isset($_POST['avia_feedback_new_send']) || (isset($_POST['action']) && $_POST['action'] == "submitFeedback") && $this->user->voting_allowed)
			{	
				global $avia_feedback_html_settings;
				
				$this->message = "";
				
				//pre validate and sanitize post array
				foreach($_POST as $key => $value)
				{
					$_POST[$key] = wp_rel_nofollow(wp_kses($value,  $avia_feedback_html_settings ));
				}
				
				$error = false;

				if($this->settings['vote_access'] == 'member')
				{
					$_POST['avia_feedback_name'] = $this->user->display_name;
					$_POST['avia_feedback_mail'] = $this->user->user_email;
				}
				
				
				if(!isset($_POST['avia_feedback_name']) || $_POST['avia_feedback_name'] == "") $error = true;
				if(!isset($_POST['avia_feedback_mail']) || $_POST['avia_feedback_mail'] == "") $error = true;
				if(!isset($_POST['avia_feedback_titel']) || $_POST['avia_feedback_titel'] == "") $error = true;
				if(!isset($_POST['avia_feedback_message']) || $_POST['avia_feedback_message'] == "") $error = true;
				if(isset($_POST['avia_feedback_website']) && $_POST['avia_feedback_website'] != "") $error = true;
				
				if(!$this->user->ID) { $userid = 1;} else {$userid = $this->user->ID;}
				
				if(!$error && $this->user->voting_allowed)
				{
					$post = array(
					  'ping_status' => 'closed', // 'closed' means pingbacks or trackbacks turned off
					  'post_author' => $userid, //The user ID number of the postauthor. if not logged in the admin id
					  'post_content' => wpautop(wptexturize($_POST['avia_feedback_message'])), //The full text of the post.
					  'post_status' => $this->settings['post_status'], //Set the status of the new post. 
					  'post_title' => $_POST['avia_feedback_titel'], //The title of your post.
					  'post_type' => 'feedback' //Sometimes you want to post a page.
					); 
					
					$post_ID = wp_insert_post($post);
					
					update_post_meta($post_ID, '_avia_feedback_author', $_POST['avia_feedback_name']);
					update_post_meta($post_ID, '_avia_feedback_author_mail', $_POST['avia_feedback_mail']);
					update_post_meta($post_ID, '_avia_feedback_box_votes', '0');
					update_post_meta($post_ID, '_avia_feedback_box_status', 'open');
					
					
					$this->addedFeddbackID = $post_ID;
					$this->message = avia_feedback_message('new_feedback_entry', $this->settings);
				}
				else
				{
					$this->message = avia_feedback_message('failed_submit', $this->settings);
				}

				return $this->message;
			}
		}
		
		
		/**
		* Function that checks the submitted feedback comment and saves it to the database
		*/
		public function submitFeedbackCommentCheck()
		{	
			if($this->user->commenting_allowed && (isset($_POST['avia_comment_new_send']) || (isset($_POST['action']) && $_POST['action'] == 'submitComment')))
			{	
				
				global $avia_feedback_html_settings;
				
				$this->message = "";
				
				//pre validate and sanitize post array
				foreach($_POST as $key => $value)
				{
					$_POST[$key] = wp_rel_nofollow(wp_kses($value,  $avia_feedback_html_settings ));
				}
				
				$error = false;

				if($this->settings['vote_access'] == 'member')
				{
					$_POST['avia_comment_name'] = $this->user->display_name;
					$_POST['avia_comment_mail'] = $this->user->user_email;
				}
				
				$id = "";
				if(isset($_POST['avia_comment_post_id']))
				{
					$id = $_POST['avia_comment_post_id'];
				}
				else if(isset($_POST['id']))
				{
					$id = $_POST['id'];
				}
				
				if($id == "") $error = true;
				if(!isset($_POST['avia_comment_name']) || $_POST['avia_comment_name'] == "") $error = true;
				if(!isset($_POST['avia_comment_mail']) || $_POST['avia_comment_mail'] == "") $error = true;
				if(!isset($_POST['avia_comment_message']) || $_POST['avia_comment_message'] == "") $error = true;
				if(isset($_POST['avia_comment_website']) && $_POST['avia_comment_website'] != "") $error = true;
				
				
				if(!$error)
				{
					$post = array(
					  'comment_post_ID'=> $id,
					  'comment_author' => $_POST['avia_comment_name'],
					  'comment_content' => wpautop(wptexturize($_POST['avia_comment_message'])),
					  'comment_author_email' => $_POST['avia_comment_mail'],
					  'comment_author_IP' => $this->user->ip_adress
					); 

					$this->addedCommentID = wp_insert_comment($post);
					
					$this->message = avia_feedback_message('new_comment_entry', $this->settings);
				}
				else
				{
					$this->message = avia_feedback_message('failed_comment_submit', $this->settings);
				}

				return $this->message;
			}
		}
		
		
		######################################################################
		# PROTECTED FUNCTIONS
		######################################################################	
		
		
		/**
		 * Create the Post type needed for the feedback entries
		 */
		protected function _createPostType()
		{
			$labels = array(
				'name' => _x('Feedback', 'post type general name'),
				'singular_name' => _x('Feedback Entry', 'post type singular name'),
				'add_new' => _x('Add New', 'feedback'),
				'add_new_item' => __('Add New Feedback Entry'),
				'edit_item' => __('Edit Feedback Entry'),
				'new_item' => __('New Feedback Entry'),
				'view_item' => __('View Feedback Entry'),
				'search_items' => __('Search Feedback Entries'),
				'not_found' =>  __('No Feedback Entries found'),
				'not_found_in_trash' => __('No Feedback Entries found in Trash'),
				'parent_item_colon' => ''
			);
			
			$slugRule = get_option('category_base');
			if($slugRule == "") $slugRule = 'category';
			
			$args = array(
				'labels' => $labels,
				'public' => true,
				'show_ui' => true,
				'capability_type' => 'post',
				'hierarchical' => false,
				'rewrite' => array('slug'=>$slugRule.'/feedback','with_front'=>true),
				'query_var' => true,
				'show_in_nav_menus'=> false,
				'menu_position' => 225,
				'supports' => array('title','editor','comments','custom-fields','page-attributes')
			);
		
		register_post_type( 'feedback' , $args );
		}

		protected function _createTaxonomies()
		{
			$labels = array(
			    'name'                => _x( 'Categories', 'feedback-categories' ),
			    'singular_name'       => _x( 'Category', 'feedback-categories' ),
			    'search_items'        => __( 'Search Categories' ),
			    'all_items'           => __( 'All Categories' ),
			    'parent_item'         => __( 'Parent Category' ),
			    'parent_item_colon'   => __( 'Parent Category:' ),
			    'edit_item'           => __( 'Edit Category' ), 
			    'update_item'         => __( 'Update Category' ),
			    'add_new_item'        => __( 'Add New Category' ),
			    'new_item_name'       => __( 'New Category' ),
			    'menu_name'           => __( 'Category' )
			  ); 	

			  $taxonomy_args = array(
				'public'              => FALSE,
			    'hierarchical'        => TRUE,
			    'labels'              => $labels,
			    'show_ui'             => TRUE,
			    'show_admin_column'   => TRUE,
			    'query_var'           => FALSE,
			    'rewrite'             => array( 'slug' => 'genre' )
			  );

		register_taxonomy( 'feedback-categories', 'feedback', $taxonomy_args );
		}
		
		
			

		
		/**
		 * Function to get userdata of active user
		 */
		protected function _get_current_user()
		{	
			//only members can vote: handled with the wordpress membership system
			if($this->settings['vote_access'] == 'member')
			{ 
				global $current_user;
	      		get_currentuserinfo();
	      		$current_user->ip_adress = $this->get_ip_address();
	      		$current_user->unified_id = $current_user->ID;
	      		//check and set uservotes
	      		if(!isset($current_user->_avia_feedback_box_user_votes))
	      		{
	      			$current_user->_avia_feedback_box_user_votes = $this->settings['votes_per_month'];
	      			update_user_meta($current_user->ID, '_avia_feedback_box_user_votes', $this->settings['votes_per_month']);
	      		}
	      	}
	      	
	      	//everyone can vote: handled with an additional database table
	      	else if($this->settings['vote_access'] == 'everyone')
	      	{
	      		global $wpdb, $avia_table_name;
				
	      		$current_user->ip_adress = $this->get_ip_address();
	      		if($current_user->ip_adress && $current_user->ip_adress != "000.000.000.000")
	      		{
	      			$current_user->unified_id = $current_user->ip_adress;
	      			$current_user->_avia_feedback_box_user_votes = $wpdb->get_var("SELECT user_votes_left FROM ".$avia_table_name['user_table']." WHERE user_ip = '".$current_user->ip_adress."'");
	      				      				      			
	      			if(empty($current_user->_avia_feedback_box_user_votes) && $current_user->_avia_feedback_box_user_votes !== "0")
	      			{
	      				$current_user->first_vote = true;
	      				$current_user->_avia_feedback_box_user_votes = $this->settings['votes_per_month'];
	      			}
	      		}
	      		else
	      		{
	      			$current_user->_avia_feedback_box_user_votes = 0;
	      		}
	      		
	      	}
			$current_user->vote_access = $this->settings['vote_access'];
			$current_user->voting_allowed = false;
			$current_user->commenting_allowed = false;
			
			//check if the current user is allowed to vote:
			if( ($current_user->vote_access == 'member' && is_user_logged_in() && $current_user->_avia_feedback_box_user_votes > 0) ||
				($current_user->vote_access == 'everyone' && $current_user->_avia_feedback_box_user_votes > 0)  )
			{
				$current_user->voting_allowed = true;	
			}
			
			//check if the current user is allowed to comment:
			if( ($current_user->vote_access == 'member' && is_user_logged_in()) || $current_user->vote_access == 'everyone'  )
			{
				$current_user->commenting_allowed = true;	
			}

			
			$current_user->settings = $this->settings;
			
			//set the user variables
			$this->user = $current_user;



		}
		
		
		/**
		 * Function to get backend settings choosen by the user
		 */
		protected function _get_settings()
		{	
			global $avia_feedback_default_settings;
			
			$settings = ( array ) get_option('avia_feedback_box_settings');
			
			$this->settings = array_merge($avia_feedback_default_settings, $settings);
		}
		
		/**
		* Fetch the IP Address of a visitor, only done if "everyone can vote" is set
		*
		* @return	string
		*/
		protected function get_ip_address()
		{
			if ($_SERVER['REMOTE_ADDR'] AND $_SERVER['HTTP_CLIENT_IP'])
			{
				$this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
			}
			elseif ($_SERVER['HTTP_X_FORWARDED_FOR'] AND $_SERVER['HTTP_CLIENT_IP'])
			{
				$this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			elseif ($_SERVER['REMOTE_ADDR'])
			{
				$this->ip_address = $_SERVER['REMOTE_ADDR'];
			}
			elseif ($_SERVER['HTTP_CLIENT_IP'])
			{
				$this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
			}
			elseif ($_SERVER['HTTP_X_FORWARDED_FOR'])
			{
				$this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
		
			if ($this->ip_address === FALSE)
			{
				$this->ip_address = '0.0.0.0';
				return $this->ip_address;
			}
		
			if (strstr($this->ip_address, ','))
			{
				$x = explode(',', $this->ip_address);
				$this->ip_address = trim(end($x));
			}
		
			if ( ! $this->valid_ip($this->ip_address))
			{
				$this->ip_address = '0.0.0.0';
			}
		
			return $this->ip_address;
		}
		
		// --------------------------------------------------------------------
		
		/**
		* Validate IP Address
		* 
		* @param	string
		* @return	bool
		*/
		protected function valid_ip($ip)
		{
			$ip_segments = explode('.', $ip);
		
			// Always 4 segments needed
			if (count($ip_segments) != 4)
			{
				return FALSE;
			}
			// IP can not start with 0
			if ($ip_segments[0][0] == '0')
			{
				return FALSE;
			}
			// Check each segment
			foreach ($ip_segments as $segment)
			{
				// IP segments must be digits and can not be 
				// longer than 3 digits or greater then 255
				if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
				{
					return FALSE;
				}
			}
		
			return TRUE;
		}
	
		

		######################################################################
		# STATIC FUNCTIONS
		######################################################################		
		
		/**
		* Function to get the votes for a specific post and user that were already made by that user
   		* @param	string $user The unified user id (either the user id from the wordpress database or the ip adress if public voting is allowed)
   		* @param	int $id The post id
		* @return	int Number of votes
		*/
		static public function get_casted_votes($user, $id)
		{
			global $avia_table_name, $wpdb;
			
			$query = $wpdb->get_var("SELECT votes FROM ".$avia_table_name['vote_table']." WHERE user = '$user' AND post_id='$id'");
			return $query;
		}
		
		
		/**
		 * Function to flush the rewrite rules and add new ones so that single entries can be displayed if neccessary
		 */
		static public function flush_rewrite_rules()
		{
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
				
		/**
		 * Function that is run if the version number of the script doesnt match the one saved in the database
		 */
		static public function run_update($scriptVersion, $oldVersion)
		{
			aviaFeedbackBox::install_database_table();
			aviaFeedbackBox::flush_rewrite_rules();
			update_option( 'avia_feedback_ver', $scriptVersion );
		}	
		
		/**
		 * installs the database table for anonymous voting
		 */
		static public function install_database_table()
		{
			global $wpdb, $avia_table_name;
			
			// Check for table
			$new_installation = $wpdb->get_var("show tables like '".$avia_table_name['user_table']."'") != $avia_table_name['user_table'];
			
			if ( $new_installation ) 
			{
				$sql = "CREATE TABLE ".$avia_table_name['user_table']." (
				id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_votes_left mediumint(9) NOT NULL,
				votes_casted mediumint(9) NOT NULL,
				user_ip VARCHAR(30) NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
				
				$sql2 = "CREATE TABLE ".$avia_table_name['vote_table']." (
				id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				post_id mediumint(9) NOT NULL,
				user VARCHAR(16) NOT NULL,
				votes mediumint(30) NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
				
				// Run Query
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

				dbDelta($sql);
				dbDelta($sql2);
			}
		}
		
		
		
		
		/**
		 * Function that displays the pagination for a list of feedback entries
		 * @param int $pages optional. number of pages
		 * @param int $range optional. how many links to display
		 * @return string HTML output with pagination links
		 */
		static public function pagination($pages = '', $currentpage = "")
		{
			$range = 2;
 		    $showitems = ($range * 2)+1;  
 		
 		    global $paged;
 		    $output = "";
 		    if(empty($paged)) $paged = 1;
 		
 		    if($pages == '')
 		    {
 		        global $wp_query;
 		        $pages = $wp_query->max_num_pages;
 		        if(!$pages)
 		        {
 		            $pages = 1;
 		        }
 		    }   
 			
 		    if(1 != $pages)
 		    {
 		        $output .=  "<div class='pagination avia_ajaxed_container'>";
 		        if($paged > 2 && $paged > $range+1 && $showitems < $pages) $output .=  "<a class='avia_link1' href='".avia_pagination_helper(1, $currentpage)."'>&laquo;</a>";
 		        if($paged > 1 && $showitems < $pages) $output .=  "<a class='avia_link".($paged - 1)."' href='".avia_pagination_helper($paged - 1, $currentpage)."'>&lsaquo;</a>";
 		
 		        for ($i=1; $i <= $pages; $i++)
 		        {
 		            if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
 		            {
 		                $output .=  ($paged == $i)? "<span class='current'>".$i."</span>":"<a href='".avia_pagination_helper($i, $currentpage)."' class='inactive avia_link".$i."' >".$i."</a>";
 		            }
 		        }
 		
 		        if ($paged < $pages && $showitems < $pages) $output .=  "<a class='avia_link".($paged + 1)."' href='".avia_pagination_helper($paged + 1, $currentpage)."'>&rsaquo;</a>";  
 		        if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages) $output .=  "<a class='avia_link".$pages."' href='".avia_pagination_helper($pages, $currentpage)."'>&raquo;</a>";
 		        $output .=  "</div>\n";
 		    }
 		    
 		    return $output;
		}
				
		
		/**
		* Function to clear the votes, scheduled monthly. Simply clears the database tables
		*/
		static public function clear_votes()
		{
			$transient = 'avia_feedback_box_clear_votes';
			$month = date('n') + 1;
			$year = date('Y');
			if($month == 13)
			{
				$month = 1;
				$year = $year + 1;
			}
			$run_at =  mktime(0,0,30,$month,1,$year);
			
			$nextupdate = $run_at - time();
			
			//echo human_time_diff($run_at, time());
			
			
			
			if(!get_transient($transient))
			{	
				set_transient($transient, 1, $nextupdate); 
				
				global $avia_table_name, $wpdb;
				$wpdb->query("TRUNCATE ".$avia_table_name['user_table']);
				delete_metadata('user', '','_avia_feedback_box_user_votes','',true);
			}
		}
	}
}














