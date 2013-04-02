<?php

if(!class_exists('avia_meta_box')){

##################################################################
class avia_meta_box_plugin_helper{
##################################################################
	var $options; 			// options passed by the option file
	var $boxinfo;			// meta box info passed by the option file

	function avia_meta_box_plugin_helper($options, $boxinfo)
	{	
		// set basic options passed by the option file
		$this->options = $options;
		$this->boxinfo = $boxinfo;
		
		add_action('admin_menu', array(&$this, 'init_boxes'));
		add_action('save_post', array(&$this, 'save_postdata'));
		
	}
	
	function init_boxes()
	{	
		global $avia_meta_box_initialized;
		
		#add scripts and styles only when first initialized
		if($avia_meta_box_initialized != true)
		{
			$this->add_script_and_styles();
			$avia_meta_box_initialized = true;
			 
		}
		$this->create_meta_box();
	}
	
	######################################################################
	# add javascript and css files only to the head if these files are called
	######################################################################
	function add_script_and_styles()
	{	
		
		if(basename( $_SERVER['PHP_SELF']) == "page.php" 
		|| basename( $_SERVER['PHP_SELF']) == "page-new.php" 
		|| basename( $_SERVER['PHP_SELF']) == "post-new.php" 
		|| basename( $_SERVER['PHP_SELF']) == "post.php"
		|| basename( $_SERVER['PHP_SELF']) == "media-upload.php")
		{	
		
			
			wp_enqueue_style('avia_custom_fields_css', AVIA_FEEDBACK_BOX_URL_HELPER . 'avia_backend.css');
			
			if(isset($_GET['hijack_target']))
			{	
				add_action('admin_head', array(&$this,'add_hijack_var'));
				add_filter('media_upload_tabs', array(&$this,'add_media_uploader_script'));
			}
		}
	}
	
	
	######################################################################
	# Sets the new target for insertion within a meta tag that can be
	# easily read by jQuery
	######################################################################	
	function add_hijack_var()
	{
		echo "<meta name='hijack_target' content='".$_GET['hijack_target']."' />\n";
		echo "<meta name='avia_change_labels' content='true' />\n";
	}
	
	
	######################################################################
	# Add Scripts and content to the media uploader via media_upload_tabs hook
	######################################################################	
	function add_media_uploader_script($_default_tabs)
	{
		
		#advanced label change for featued images
		if(isset($_GET['avia_insert']))
		{
			echo "<meta name='avia_change_labels_advanced' content='true' />\n";	
		}
		
		return $_default_tabs;
	}
	
	
	######################################################################
	# Add the meta boxes to the page/post or link
	# pass id, name, callback, show at page/post/link, in which area, priority
	######################################################################
	function create_meta_box() 
	{  
		if ( function_exists('add_meta_box') && is_array($this->boxinfo['page']) ) 
		{
			foreach ($this->boxinfo['page'] as $area)
			{	
				if ($this->boxinfo['callback'] == '') $this->boxinfo['callback'] = 'new_meta_boxes';
				
				add_meta_box( 	
					$this->boxinfo['id'], 
					$this->boxinfo['title'],
					array(&$this, $this->boxinfo['callback']),
					$area, $this->boxinfo['context'], 
					$this->boxinfo['priority']
				);  
			}
		}  
	}  
	
	
	
	function new_meta_boxes()
	{	
		global $post;


		//calls the helping function based on value of 'type'
		foreach ($this->options as $option)
		{				
			if (method_exists($this, $option['type']))
			{	
				$meta_box_value = get_post_meta($post->ID, $option['id'], true); 
				if($meta_box_value != "") $option['std'] = $meta_box_value;  
				
				echo '<div class="alt avia_meta_box_alt meta_box_'.$option['type'].' meta_box_'.$this->boxinfo['context'].'">';
				$this->$option['type']($option);
				echo '</div>';
			}
		}
		
		//security field
		echo'<input type="hidden" name="'.$this->boxinfo['id'].'_noncename" id="'.$this->boxinfo['id'].'_noncename" value="'.wp_create_nonce(plugin_basename(__FILE__) ).'" />';  
	}
	
	function save_postdata() 
	{
	
		$post_id = $_POST['post_ID'];
		

		foreach ($this->options as $option)
		{
			// Verify
			if (!wp_verify_nonce($_POST[$this->boxinfo['id'].'_noncename'], plugin_basename(__FILE__))) 
			{	
				return $post_id ;
			}
			
			if ( 'page' == $_POST['post_type'] ) 
			{
				if ( !current_user_can( 'edit_page', $post_id  ))
				return $post_id ;
			} 
			else 
			{
				if ( !current_user_can( 'edit_post', $post_id  ))
				return $post_id ;
			}
			
			$data = htmlspecialchars($_POST[$option['id']], ENT_QUOTES,"UTF-8");
			
			if(get_post_meta($post_id , $option['id']) == "")
			add_post_meta($post_id , $option['id'], $data, true);
			
			elseif($data != get_post_meta($post_id , $option['id'], true))
			update_post_meta($post_id , $option['id'], $data);
			
			elseif($data == "")
			delete_post_meta($post_id , $option['id'], get_post_meta($post_id , $option['id'], true));
			
		}
	}
	
	
	####################################################################################################################################	
	# Rendering Methods
	####################################################################################################################################
	
	##############################################################
	# TITLE
	##############################################################	
	
	function title($values)
	{	
		echo '<p>'.$values['name'].'</p>';
	}
	
	##############################################################
	# TEXT
	##############################################################	
	function text($values)
	{	
		if(isset($this->database_options[$values['id']])) $values['std'] = $this->database_options[$values['id']];
		
		echo '<p>'.$values['name'].'</p>';
		echo '<p><input type="text" size="'.$values['size'].'" value="'.$values['std'].'" id="'.$values['id'].'" name="'.$values['id'].'"/>';
		echo $values['desc'].'<br/></p>';
	    echo '<br/>';
	}
	
	
	##############################################################
	# TEXTAREA
	##############################################################	
	function textarea($values)
	{	
		if(isset($this->database_options[$values['id']])) $values['std'] = $this->database_options[$values['id']];
		
		echo '<p>'.$values['name'].'</p>';
		echo '<p><textarea class="avia_textarea" cols="20" rows="1" id="'.$values['id'].'" name="'.$values['id'].'">'.$values['std'].'</textarea>';
		echo $values['desc'].'<br/></p>';
	    echo '<br/>';
	}
	
	
	
	
	##############################################################
	# CHECKBOX
	##############################################################
	function checkbox($values)
	{	
		if(isset($values['std']) && $values['std'] == 'true') $checked = 'checked = "checked"'; 
		echo '<p>'.$values['name'].'</p>';
		echo '<p><input class="kcheck" type="checkbox" name="'.$values['id'].'" id="'.$values['id'].'" value="true"  '.$checked.' />';
		echo '<label for="'.$values['id'].'">'.$values['desc'].'</label><br/></p>';
	}
	
	##############################################################
	# DROPDOWN
	##############################################################	
	function dropdown($values)
	{	
					
		echo '<p>'.$values['name'].'</p>';
		
			if($values['subtype'] == 'page')
			{
				$select = 'Select page';
				$entries = get_pages('title_li=&orderby=name');
			}
			else if($values['subtype'] == 'cat')
			{
				$select = 'Select category';
				$entries = get_categories('title_li=&orderby=name&hide_empty=0');
			}
			else
			{	
				$select = 'Select...';
				$entries = $values['subtype'];
			}
		
			echo '<p><select class="postform" id="'. $values['id'] .'" name="'. $values['id'] .'"> ';
			echo '<option value="">'.$select .'</option>  ';

			foreach ($entries as $key => $entry)
			{
				if($values['subtype'] == 'page')
				{
					$id = $entry->ID;
					$title = $entry->post_title;
				}
				else if($values['subtype'] == 'cat')
				{
					$id = $entry->term_id;
					$title = $entry->name;
				}
				else
				{
					$id = $entry;
					$title = $key;				
				}

				if ($values['std'] == $id )
				{
					$selected = "selected='selected'";	
				}
				else
				{
					$selected = "";		
				}
				
				echo"<option $selected value='". $id."'>". $title."</option>";
			}
		
		echo '</select>';
		echo $values['desc'].'<br/></p>';
		 
	    echo '<br/>';
	}
	
	
	
##################################################################
} # End Class
##################################################################
}		   