function avia_duplicate_slider_cat()
{	
	var $dropdown_wrap = jQuery('.multiple_box');
	
	$dropdown_wrap.each(function()
	{			
		var $dropdown = jQuery(this).find('.multiply_me');
		var $current_dropdown_wrapper = jQuery(this);
		
			$dropdown.each(function(i)
			{
			$name = jQuery(this).attr('name').replace(/\d+$/,"");
			jQuery(this).attr('id', $name + i);
			jQuery(this).attr('name', $name + i);
			jQuery('.'+$name+'hidden').attr('value', $dropdown.length);
		
			jQuery(this).unbind('change').bind('change',function()
			{	
				if(jQuery(this).val() && $dropdown.length == i+1)
				{
				jQuery(this).clone().appendTo($current_dropdown_wrapper);
				avia_duplicate_slider_cat();
				}
				else if(!(jQuery(this).val()) && !($dropdown.length == i+1))
				{
				jQuery(this).remove();
				avia_duplicate_slider_cat();
			}
			
			});
		});
	});
}


function avia_copy_table()
{
	var $multitable_wrap = jQuery('.multitables');
	
	$multitable_wrap.each(function()
	{
		var $add_next = jQuery(this).find('.add_table');
		var $del_this = jQuery(this).find('.del_table');
		var $count = jQuery(this).find('.super_matrix_count');
		var $current_table = jQuery(this);
		
		
		$add_next.unbind('click').bind('click',function()
			{
			$count.val(parseInt($count.val())+1);
			$current_number = $count.val();
			$newclone = jQuery('.clone_me').clone().insertBefore(jQuery('.clone_me'));
			$newclone.removeClass('hidden').removeClass('clone_me');
			
			avia_helper_correct_numbers($current_table)
			avia_duplicate_slider_cat();
			avia_copy_table();
			
			return false;
			});
			
		$del_this.bind('click',function()
			{
			$count.val(parseInt($count.val())-1);
			jQuery(this).parents('.multitable').remove();
			avia_helper_correct_numbers($current_table);
			return false;
			});
		
		
	});
	
}

function avia_helper_correct_numbers($current_table)
{
	$current_table.find('.multitable').each(function(i){
		var $current_sub_table = jQuery(this);
		$current_sub_table.find('.changenumber').html(i+1);
		$current_sub_table.find('select, .changeable').each(function(){
				var $multiply_me = '';
				var $newname = jQuery(this).attr('name').replace(/\d+/,i);
				if (jQuery(this).hasClass('multiply_me')) $multiply_me = 'multiply_me';
				jQuery(this).attr({'name': $newname,'id': $newname, 'class': $newname + " " + $multiply_me});
			});
			
		var $newname = $current_sub_table.find('.multiple_box>input[type=hidden]').attr('name').replace(/\d+/,i);
			$current_sub_table.find('.multiple_box>input[type=hidden]').attr({'name': $newname,'id': $newname, 'class': $newname});
		
	});
}
	
	
function avia_how_to_populate()
{
	var $group = jQuery('.avia_how_to_populate');
	$group.each(function()
	{	
		var	$currentgroup = jQuery(this);
		var $dropdown = jQuery(this).find('.selector');
		$dropdown.each(function(i)
		{
			jQuery(this).bind('change',function()
			{
				$currentgroup.find('span').css({display:"none"});
				
				if(jQuery(this).val())
				{
				$show = ".selected_"+jQuery(this).val();
				$currentgroup.find($show).css({display:"block"});
				}
			});
		});
	});
}	
	

jQuery(document).ready(function(){
	avia_how_to_populate();
	avia_duplicate_slider_cat();
	avia_copy_table();
});
