jQuery.noConflict();

jQuery(document).ready(function()
{	
	avia_hijack_media_uploader();
	avia_hijack_preview_pic();
	avia_dropdown_superlink();
	avia_copy_feature_box();
});




function avia_hijack_preview_pic()
{
	jQuery('.kriesi_preview_pic_input').each(function()
	{
		jQuery(this).bind('change focus blur ktrigger', function()
		{	
			
			$select = '#' + jQuery(this).attr('name') + '_div';
			$value = jQuery(this).val();
			$image = '<img src ="'+$value+'" />';
						
			var $image = jQuery($select).html('').append($image).find('img');
			
			//set timeout because of safari
			window.setTimeout(function()
			{
			 	if(parseInt($image.attr('width')) < 20)
				{	
					jQuery($select).html('');
				}
			},500);
		});
	});
}




function avia_hijack_media_uploader()
{		
		$buttons = jQuery('.k_hijack');
		$realmediabuttons = jQuery('.media-buttons a');
		
		
		window.custom_editor = false;
		
		// set a variable depending on what has been clicked, normal media uploader or kriesi hijacked uploader
		$buttons.click(function()
		{	
			window.custom_editor = jQuery(this).attr('id');			
		});
		
		$realmediabuttons.click(function()
		{
			window.custom_editor = false;
		});

		window.original_send_to_editor = window.send_to_editor;
		window.send_to_editor = function(html)
		{	
			
			if (custom_editor) 
			{	
				$img = jQuery(html).attr('src') || jQuery(html).find('img').attr('src') || jQuery(html).attr('href');
				
				jQuery('input[name='+custom_editor+']').val($img).trigger('ktrigger');
				custom_editor = false;
				window.tb_remove();
			}
			else 
			{
				window.original_send_to_editor(html);
			}
		};
}



function avia_dropdown_superlink()
{
	jQuery('.avia_dropdown_superlink').each(function()
	{
		var container = jQuery(this),
			superselector = container.find('.superselector'),
			subselector = container.find('.page, .post, .cat, .manually'),
			pageSelect = container.find('.page'),
			postSelect = container.find('.post'),
			catSelect = container.find('.cat'),
			manuallySelect = container.find('.manually'),
			hiddenValue = container.find('.value'),
			baseVal = superselector.val() + "$:$";
			
			
			superselector.bind("change", function()
			{	
				var newValue = superselector.val();
				
				//find all subSelects and subInputs and show only the selected one
				container.find('.page, .post, .cat, .manually').css("display","none");
				
				//set the new value for the input field
				if (newValue.length > 1) 
				{	
					container.find("."+newValue).fadeIn();
					newValue = newValue + "$:$";
				}
				
				hiddenValue.val(newValue);
				baseVal = newValue;
				
			});
			
			subselector.bind("change keyup blur", function()
			{	
			
				var current = jQuery(this),
					newValue = current.val();
				
				
				//set the new value for the input field
				
					hiddenValue.val(baseVal + newValue);
				
			});
	});
}


function avia_copy_feature_box()
{
	var addbutton = jQuery('.add_featured_img'),
		removebutton = jQuery('.delete_featured_img'),
		containerAll = jQuery('#post_thumb_add'),
		clone = jQuery('.meta_box_featured', containerAll).first();
		
		addbutton.click(function()
		{
			var newClone = clone.clone(true),
				realValueInput = newClone.find('.realValue'),
				previewpic = newClone.find('.kriesi_preview_pic_big');
				previewpic.html("");
				realValueInput.val("");
				
				newClone.insertAfter(jQuery(this).parents('.meta_box_featured')).removeClass('hidden');
				avia_setfeatured_classes();
				
			return false;
		});
		
		removebutton.click(function()
		{	
			var visible_container = containerAll.find('.meta_box_featured:visible'),
				container = jQuery(this).parents('.meta_box_featured'),
				image = container.find('.kriesi_preview_pic_big'),
				realValueInput = container.find('.realValue');			
				
			if(visible_container.length < 2)
			{
				image.html("");
				realValueInput.val('');
				
			}
			else
			{
				realValueInput.val('');
				container.appendTo(containerAll).addClass('hidden');
			}
			
			avia_setfeatured_classes();
			return false;
		});
}


function avia_setfeatured_classes()
{
	var container = jQuery('#post_thumb_add'),
	containers = jQuery('.meta_box_featured', container);
	
	containers.each(function(i)
	{
		
		var nextClone = jQuery(this),
			lightboxlink = nextClone.find('.k_hijack'),
			lightboxHref = lightboxlink.attr('href'),
			realValueInput = nextClone.find('.realValue'),
			pic = nextClone.find('.kriesi_preview_pic_big'),
			iteration = i + 1,
			id = "_kriesi_featured_image"+ iteration;			
			
			realValueInput.attr('name',id);
			lightboxlink.attr('id',id).attr('href',lightboxHref.replace(/_kriesi_featured_image(\d)+/,id));
			pic.attr('id',id+"_div");
	});
	
	
	
	
}
