jQuery(document).ready(function($) {

	var activeUserList = $('#exa_byline_active_user_list').val().split(",");


	/*
	*
	* Use auto complete to submit multiple registered authors
	*
	*
	*/
	$('#exa_byline_input').autocomplete({
		source: exa_bylines_all_users,								//localized via exa-bylines.php
		focus: exa_bylines_replace_type,							//input element changes value as focus over autocomplete ui
		select: function(event, ui){
			exa_bylines_replace_type(event, ui);					//replace input value to ui.item.label
			exa_bylines_add_user(ui.item.label, ui.item.value);	
			$(this).val('');										//prepare for next input
		}
	});

	$('#exa_bylines_current_authors').sortable({					//allow order changing without readding authors.
		containment: 'parent',
		stop: exa_bylines_reorder_users
	});

	$('#exa_bylines_current_authors').on('click','li .exa_byline_current_author_remove', function(){
		exa_bylines_remove_user($(this));
	});

	$('#exa_byline_input_guest_button').click(function(e) {		//allow adding authors that does not have a username.
		var value = $('#exa_byline_input_guest').val();			//	this should be used on a post to post basis.
		if( !value || !isNaN(value)){								//	Therefore, guest author names are not compiled in any one database item. <- needs rephrase
			$('#exa_byline_input_guest').val('');
			alert('Guest names can\'t be a number.');
		}else
			exa_bylines_add_user(value, value, true);				//third arg is guest=true
		return;
	});

	function exa_bylines_replace_type( e, ui){						//replace input value to ui.item.label
		e.preventDefault();
		$(this).val(ui.item.label);

	}

	function exa_bylines_verify_user( id){							//check if given id is already active.
		if( activeUserList.indexOf( id) == -1)
			return true;
		else
			return false;
	}
	function exa_bylines_update_active_user_data(){				//update hidden input element that stores the overall list of active authors
		var input = $('#exa_byline_active_user_list')
			input.val(activeUserList);
		return ;

	}

	/*
	*
	* Does everything needed to add a user.
	*
	*
	*/
	function exa_bylines_add_user( display_name, id, guest){
		if( exa_bylines_verify_user(id)){
			var rand = Math.floor((Math.random() * 100000) );		//avoid page/anchor jump when clicked
			var listItemHTML  = '<li class="exa_byline_current_author" exa-byline-userid="' + id +'">'
	 			if( guest)
	 				listItemHTML += '<label>' + display_name + ' (guest)</label>'
	 			else
	 				listItemHTML += '<label>' + display_name + '</label>'
				listItemHTML += ' <a href="#'+rand+'" class="exa_byline_current_author_remove" name="exa_byline_current_author_remove_' + id + ' ">Remove</a>'
				listItemHTML += '</li>'

			$('#exa_bylines_current_authors').append(listItemHTML);
			$('#exa_bylines_current_authors').sortable( "refresh" );
			exa_bylines_update_active_users();
		}
	}

	/*
	*
	* recompiles the overall list of active authors 
	* and saves it to the hidden input element.
	*
	*/
	function exa_bylines_reorder_users(){
		var new_user_list = '';

		if( $('.exa_byline_no_byline').length > 0)
			$('.exa_byline_no_byline').remove();

		if( $('#exa_bylines_current_authors li').length == 0){
			$('#exa_bylines_current_authors').append('<li class=exa_byline_no_byline ui-sortable-handle></li>');
			activeUserList = '';
		}else{
			$('#exa_bylines_current_authors li').each(function(index, el) {
				new_user_list += $(this).attr('exa-byline-userid') + ',';
			});
			activeUserList = new_user_list.substring(0, new_user_list.length-1).split(',');
		}
		exa_bylines_update_active_user_data();
		return ;
	}

	/*
	*
	* Alias of (function) exa_bylines_reorder_users
	*
	*/
	function exa_bylines_update_active_users(){
		exa_bylines_reorder_users();
	}

	/*
	*
	* remove the <li> element of unwanted author and recompile the list of active authors.
	*
	*/
	function exa_bylines_remove_user( deleteButton){
		deleteButton.parent().remove();
		exa_bylines_update_active_users();
	}
	
});
