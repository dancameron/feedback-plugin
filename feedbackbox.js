//execute once dom is loaded
jQuery(function(){	 jQuery('#avia_feedback_box').avia_feedback_box(); avia_simple_tooltip('.avia_feedback_info, .avia_feedback_status');   });


function avia_simple_tooltip(target_items){
jQuery(target_items).each(function(i){
		
		var $item = jQuery(this),
			$tooltip = $item.find('.avia_info_hover').appendTo('body');
		
		if($tooltip.html() != "")
		{	
			$tooltip.append('<span class="arrow_down"></span>');
			
			jQuery(this).mouseover(function(){
					$tooltip.css({opacity:0.88, display:"none"}).fadeIn(400);				
			}).mousemove(function(kmouse){
					$tooltip.css({left:kmouse.pageX-parseInt($tooltip.width())-70, top:kmouse.pageY-30});
			}).mouseout(function(){
					$tooltip.fadeOut(400);
			});
		}
	});
}


//feedback box javascript
(function($)
{
	$.fn.avia_feedback_box = function(options) 
	{
		return this.each(function()
		{
			var container = $(this),
				entries = $('.avia_feedback_entry', container),
				ajaxedLinks = $('.avia_ajaxed, .avia_ajaxed_container a', container),
				ajaxLoader = $('.avia_ajax_loader', container).css({display:'none',visibility:'visible'}),
				mainNav = $('.avia_feedback_navigation a:not(.avia_feedback_add)', container),
				commentlinks = $('.avia_feedback_comment', container),
				newFeedbackForm = $('.avia_feedback_form:first', container),
				votelinks = $('.uservotes', container),
				addlink = $('.avia_feedback_add', container),
				ajaxUrl = $("input[name=avia_box_ajax_url]").val() + "ajax.php",
				paginationUrl = $("input[name=avia_box_oagination_url]").val(),
				voteCountContainer = $('strong', votelinks),
				commentButton = $('.avia_text_button', entries),
				feeedbackSubmitButton = $('.avia_feedback_inner_form .avia_text_button');
			
			
			if(voteCountContainer[0] != undefined)
			{
				var voteCounts = parseInt(voteCountContainer[0].innerHTML);
			}								
			
			container.methods = 
			{
				displayComments: function()
				{	
					var link = this,
						id = link.id.replace('avia_feedback_comments-',''),
						parent = $(link).parents('.avia_feedback_entry_content'),
						appendContainer = parent.find('.avia_feedback_box_comments'),
						commentsContainer = $('.avia_feedback_box_comments', parent),
						commentsHeight = commentsContainer.height(),
						showSpeed = commentsHeight*0.8,
						jquLink = $(link);
						
					if(showSpeed < 400) showSpeed = 400;
						
					if(!jquLink.is('.avia_feedback_comments_fetched'))
					{	
						jquLink.addClass('avia_feedback_comments_fetched');		
						$.ajax({ type: "POST", url: ajaxUrl, data: "action=showComments&id="+id,
			 			  success: function(msg)
			 			  {
							if(msg != "Error")
							{	
							
								appendContainer.prepend(msg);
								commentsHeight = commentsContainer.height();
								showSpeed = commentsHeight*1.5;
								if(showSpeed < 400) showSpeed = 400;
								appendContainer.slideDown(showSpeed);
								jquLink.addClass('avia_comment_open');
							}
			 			  }
			 			});
			 		}
			 		else
			 		{
			 			if(commentsContainer.is(':visible'))
			 			{	
			 				jquLink.removeClass('avia_comment_open');
			 				commentsContainer.slideUp();
			 			}
			 			else
			 			{
			 				jquLink.addClass('avia_comment_open');
			 				commentsContainer.slideDown(showSpeed);
			 			}

			 		}
			 		return false;
				}, //end displayCommentForm 
				
				displayCommentsForm: function()
				{
					
					if(newFeedbackForm.is(':visible'))
			 			{
			 				newFeedbackForm.slideUp();
			 			}
			 			else
			 			{
			 				newFeedbackForm.slideDown();
			 			}
			 			
					return false;
				}, //end displayCommentForm
				
				vote: function()
				{
					var link = this;
					
					if(voteCounts > 0 && !$(link).is('.avia_inactive'))
					{
						
						var id = link.id.replace('avia_addvote-',''),
						currentEntry = $(link).parents('.avia_feedback_entry'),
						itemVoteCounter = currentEntry.find('.avia_feedback_entry_vote_count strong'),
						castedVotes = currentEntry.find('.avia_feedback_casted_votes'),
						castedVotesInt = 0,
						currentVote = parseInt(itemVoteCounter.html());
						
						if(castedVotes.html() != "") castedVotesInt = parseInt(castedVotes.html());
						
						
						jQuery.ajax({ type: "POST", url: ajaxUrl, data: "action=vote&id="+id,
			 			  beforeSend: function()
			 			  {
			 			  	castedVotesInt++;
			 			  	voteCounts--;
							voteCountContainer.html(voteCounts);
							castedVotes.css('display','block').html(castedVotesInt);
						
							itemVoteCounter.html(currentVote + 1);
			 			  }
			 			  
			 			});

					}
					
					if(voteCounts == 0 && !$(link).is('.avia_inactive'))
					{
						votelinks.addClass('avia_inactive');
						addlink.fadeOut();
					}
					
					return false;
				}, // end vote
				
				checkData: function(elements)
				{	
					var error = false;
					elements.each(function()
					{
						element = $(this);
						if(element.val() == "" || (element.attr('name').match(/mail/) && !element.val().match(/^\w[\w|\.|\-]+@\w[\w|\.|\-]+\.[a-zA-Z]{2,4}$/)))
						{
							error = true;
							element.parent('p').addClass('avia_error_field');
						}
						else
						{
							element.parent('p').removeClass('avia_error_field');
						}
					});
					
					return error;
				},
				
				submitFeedback: function(event)
				{
					var button = $(this),
						parent = button.parents('.avia_feedback_inner_form'),
						commentParent = button.parents('.avia_feedback_entry_content'),
						commentCounter = $('.avia_feedback_comment span', commentParent),
						elements = $('input[type=text]:visible, textarea',parent),
						id = $('.avia_send_by_js',parent).val(),
						datacheck = container.methods.checkData(elements),
						datastring = "ajax=true&action="+event.data.context+"&id="+id;
										
					if(!datacheck)
					{
						elements.each(function()
						{
							var currentElement = $(this),
								value = currentElement.val(),
								name = currentElement.attr('name');
							
							datastring  += "&" + encodeURIComponent(name) + "=" + encodeURIComponent(value);
						});
					
						$.ajax({
							type: "POST",
							url: ajaxUrl,
							data:datastring,
							success: function($msg)
							{	
								if(event.data.context == 'submitFeedback')
								{
									var ajaxContainer = $('#avia_feedback_ajaxed:last')
										newentry = $($msg).prependTo(ajaxContainer).css('display','none');
									
									newentry.slideDown();
									newFeedbackForm.slideUp();
									newFeedbackForm.find('input[type=text]:visible, textarea').val('');
									votelinks = $('.uservotes', container);
									voteCountContainer = $('strong', votelinks);
								}
								else
								{
									var lastentry = $(".avia_feedback_form",commentParent),
										newentry = $($msg).insertBefore(lastentry).css('display','none');
									
									commentCounter.html(parseInt(commentCounter.html()) + 1);
									newentry.slideDown();
									commentParent.find('input[type=text]:visible, textarea').val('');
									lastentry.slideUp();
								}
							}
						});
					}
					
					return false;
				},
				
				ajax_page_switch: function()
				{	
					var link = this,
						containername = '#avia_feedback_ajaxed:first',
						container = $(containername);
					
					if(!$(link).is('.avia_ajax_inactive'))
					{	
						$('.avia_ajax_inactive').removeClass('avia_ajax_inactive');
						link.className += " avia_ajax_inactive ";
						
						var datastring = [];
						datastring = link.href.match(/\?(.+)/);
						
						if(datastring == null){datastring = []; datastring[1] = 'feedback_sort='}
						
						if($(link).parent().is('.pagination'))
						{	
							var link_paged = link.className.match(/avia_link(\d+)/);
							datastring[1] = datastring[1] + "&get_page=" + link_paged[1];
						}
						
						$.ajax({
							type: "POST",
							url: ajaxUrl,
							data:datastring[1] + "&action=switchContent",
							beforeSend: function()
							{
								container.animate({opacity:0});
							},
							success: function(msg)
							{
								container.html(msg).animate({opacity:1});
								votelinks = $('.uservotes', container);
								voteCountContainer = $('strong', votelinks);
								avia_simple_tooltip('.avia_feedback_info, .avia_feedback_status');
							}
						});
		
					}
					return false;
				},
				
				ajax_loader: function()
				{
					
					if(ajaxLoader.is(':visible'))
					{
						ajaxLoader.stop().css('opacity',1).delay('700').fadeOut();
					}
					else
					{
						ajaxLoader.stop().fadeIn();
					}
				},
				
				activeNav: function()
				{
					var link = $(this);
					if(!link.is('.avia_active_menu'))
					{
						$('.avia_active_menu', container).removeClass('avia_active_menu');
						link.addClass('avia_active_menu');
					}
				}
				
			};
			
			//bind events
			
				mainNav.bind('click', container.methods.activeNav );
				container.bind("ajaxStart ajaxStop" , container.methods.ajax_loader);
				ajaxedLinks.live('click', container.methods.ajax_page_switch );
			
			
				commentlinks.live('click', container.methods.displayComments );
				votelinks.live('click', container.methods.vote );
				addlink.bind('click', container.methods.displayCommentsForm );
				commentButton.live('click',{context: "submitComment"}, container.methods.submitFeedback );
				feeedbackSubmitButton.live('click',{context: "submitFeedback"}, container.methods.submitFeedback );
			
			
			
		});
	}
})(jQuery);	
