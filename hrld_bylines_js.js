jQuery(document).ready(function($) {

	var activeUserList = $('#hrld_byline_active_user_list').val().split(",");


	/*
	*
	* Use auto complete to submit multiple registered authors
	*
	*
	*/
	$('#hrld_byline_input').autocomplete({
		source: hrld_bylines_all_users,								//localized via hrld-bylines.php
		focus: hrld_bylines_replace_type,							//input element changes value as focus over autocomplete ui
		select: function(event, ui){
			hrld_bylines_replace_type(event, ui);					//replace input value to ui.item.label
			hrld_bylines_add_user(ui.item.label, ui.item.value);	
			$(this).val('');										//prepare for next input
		}
	});

	$('#hrld_bylines_current_authors').sortable({					//allow order changing without readding authors.
		containment: 'parent',
		stop: hrld_bylines_reorder_users
	});

	$('#hrld_bylines_current_authors').on('click','li .hrld_byline_current_author_remove', function(){
		hrld_bylines_remove_user($(this));
	});

	$('#hrld_byline_input_guest_button').click(function(e) {		//allow adding authors that does not have a username.
		var value = $('#hrld_byline_input_guest').val();			//	this should be used on a post to post basis.
		if( !value)													//	Therefore, guest author names are not compiled in any one database item. <- needs rephrase
			return;
		else
			hrld_bylines_add_user(value, value, true);				//third arg is guest=true
		return;
	});

	function hrld_bylines_replace_type( e, ui){						//replace input value to ui.item.label
		e.preventDefault();
		$(this).val(ui.item.label);

	}

	function hrld_bylines_verify_user( id){							//check if given id is already active.
		if( activeUserList.indexOf( id) == -1)
			return true;
		else
			return false;
	}
	function hrld_bylines_update_active_user_data(){				//update hidden input element that stores the overall list of active authors
		var input = $('#hrld_byline_active_user_list')
			input.val(activeUserList);
		return ;

	}

	/*
	*
	* Does everything needed to add a user.
	*
	*
	*/
	function hrld_bylines_add_user( display_name, id, guest){
		if( hrld_bylines_verify_user(id)){
			var rand = Math.floor((Math.random() * 100000) );		//avoid page/anchor jump when clicked
			var listItemHTML  = '<li class="hrld_byline_current_author" hrld-byline-userid="' + id +'">'
	 			if( guest)
	 				listItemHTML += '<label>' + display_name + ' (guest)</label>'
	 			else
	 				listItemHTML += '<label>' + display_name + '</label>'
				listItemHTML += ' <a href="#'+rand+'" class="hrld_byline_current_author_remove" name="hrld_byline_current_author_remove_' + id + ' ">Remove</a>'
				listItemHTML += '</li>'

			$('#hrld_bylines_current_authors').append(listItemHTML);
			$('#hrld_bylines_current_authors').sortable( "refresh" );
			hrld_bylines_update_active_users();
		}
	}

	/*
	*
	* recompiles the overall list of active authors 
	* and saves it to the hidden input element.
	*
	*/
	function hrld_bylines_reorder_users(){
		var new_user_list = '';

		if( $('.hrld_byline_no_byline').length > 0)
			$('.hrld_byline_no_byline').remove();

		if( $('#hrld_bylines_current_authors li').length == 0){
			$('#hrld_bylines_current_authors').append('<li class=hrld_byline_no_byline ui-sortable-handle></li>');
			activeUserList = '';
		}else{
			$('#hrld_bylines_current_authors li').each(function(index, el) {
				new_user_list += $(this).attr('hrld-byline-userid') + ',';
			});
			activeUserList = new_user_list.substring(0, new_user_list.length-1).split(',');
		}
		hrld_bylines_update_active_user_data();
		return ;
	}

	/*
	*
	* Alias of (function) hrld_bylines_reorder_users
	*
	*/
	function hrld_bylines_update_active_users(){
		hrld_bylines_reorder_users();
	}

	/*
	*
	* remove the <li> element of unwanted author and recompile the list of active authors.
	*
	*/
	function hrld_bylines_remove_user( deleteButton){
		deleteButton.parent().remove();
		hrld_bylines_update_active_users();
	}
	
});
