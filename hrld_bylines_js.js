jQuery(document).ready(function($) {

	var activeUserList = $('#hrld_byline_active_user_list').val().split(",");

	$('#hrld_byline_input').autocomplete({
		source: hrld_bylines_all_users,
		//focus: hrld_bylines_replace_type,
		select: function(event, ui){
			hrld_bylines_replace_type(event, ui);
			hrld_bylines_add_user(ui.item.label, ui.item.value);
			$(this).val('');
		}
	});

	$('#hrld_bylines_current_authors').sortable({
		containment: 'parent',
		stop: hrld_bylines_reorder_users
	});

	$('#hrld_bylines_current_authors').on('click','li .hrld_byline_current_author_remove', function(){
		hrld_bylines_remove_user($(this));
	});

	function hrld_bylines_replace_type( e, ui){
		e.preventDefault();
		$(this).val(ui.item.label);

	}

	function hrld_bylines_verify_user( id){
		if( activeUserList.indexOf( id) == -1)
			return true;
		else
			return false;
	}
	function hrld_bylines_update_active_user_data(){
		var input = $('#hrld_byline_active_user_list')
			input.val(activeUserList);
		return ;

	}
	function hrld_bylines_add_user( display_name, id){
		if( hrld_bylines_verify_user(id)){
			var rand = Math.floor((Math.random() * 100000) );
			var listItemHTML  = '<li class="hrld_byline_current_author" hrld-byline-userid="' + id +'">'
	 			listItemHTML += '<label>' + display_name + '</label>'
				listItemHTML += '<a href="#'+rand+'" class="hrld_byline_current_author_remove" name="hrld_byline_current_author_remove_' + id + ' ">Remove</a>'
				listItemHTML += '</li>'

			$('#hrld_bylines_current_authors').append(listItemHTML);
			$('#hrld_bylines_current_authors').sortable( "refresh" );
			hrld_bylines_update_active_users();
			console.log(activeUserList);
			console.log($('#hrld_byline_active_user_list').val());

		}
	}

	function hrld_bylines_update_active_users(){
		hrld_bylines_reorder_users();
	}
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

	function hrld_bylines_remove_user( deleteButton){
		deleteButton.parent().remove();
		hrld_bylines_update_active_users();
	}
	
});
