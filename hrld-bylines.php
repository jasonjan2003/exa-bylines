<?php
/**
 * Plugin Name: Herald Bylines
 * Description: Allows multiple authors for an article.
 * Version: 1.0
 * Author: Jason Chan for The Badger Herald
 * License: GPL2
 */

/**
 *
 *	Add Herald Bylines meta box to post edit screen
 *	and setup saving actio upon different hooks.
 *
 *
 */
function hrld_bylines_setup(){
	
	/*add meta box */
	add_meta_box(
		'hrld-bylines-meta-box',
		'Multiple Bylines',
		'hrld_bylines_meta_box',
		'post',
		'side',
		'high'
		);

	/* Save! */
	add_action( 'save_post', 'hrld_bylines_save_data');
	add_action( 'pre_post_update', 'hrld_bylines_save_data');
	add_action( 'edit_post', 'hrld_bylines_save_data');
	add_action( 'publish_post', 'hrld_bylines_save_data');
	add_action( 'edit_page_form', 'hrld_bylines_save_data');
}

add_action( 'load-post.php', 'hrld_bylines_setup' );
add_action( 'load-post-new.php', 'hrld_bylines_setup' );

function hrld_bylines(){
	return;
}
function hrld_bylines_init( ){

	return ;
}
add_action( 'init', 'hrld_bylines_init' );
	

/**
 *
 *	Add Herald Bylines meta box to post edit screen
 *
 *
 *
 */
 function hrld_bylines_meta_box( $post){
 	
 	//retrieve the list of users(authors)
 	$userMeta = hrld_bylines_get_active_users( $post);

 	//stores list of users active
 	$userActive = array();

 	//create <ul>
	 echo "<ul id='hrld_bylines_current_authors'>";

 	//if no users are returned, say so.
 	if( !$userMeta) :
 	?>

 		<li class="hrld_byline_no_byline"></li>

 	<?php

	//otherwise, generate list
	else:

	 	//create a random number for the remove button
 		//so the page doesn't jump.
 		$rand = rand(10000,99999);

	 	//for every user, create a <li>
	 	foreach ($userMeta as $user) :
	 	?>
	 		<li class='hrld_byline_current_author' hrld-byline-userID=<?php echo $user['id']; ?> >
	 			<label><?php echo $user['display_name']; ?><?php echo $user['guest'] ? ' (guest)' : ''; ?></label>
	 			<a href="#<?php echo $rand; ?>" class='hrld_byline_current_author_remove' 
	 					name="hrld_byline_current_author_remove_<?php echo $user['id']; ?> ">Remove</a>
	 		</li>
	 	<?php

	 		//save id into $userActive
	 		$userActive[] = $user['id'];

	 	endforeach;

 	endif;
 	//close <ul>
 	echo "</ul>";

 	// create the HTML to add users
 	?>
 	<hr>
 	<p><b>Existing author</b><br>choose and press enter</p>
 	<input type="text" id="hrld_byline_input" placeholder="Type name here..."/>
 	<hr>
 	<p><b>Can't find someone?</b><br>Just add the name below</p>
 	<input type="text" id="hrld_byline_input_guest" placeholder="Guest name"/>
 	<input type="button" id="hrld_byline_input_guest_button" class="button hrld_byline_input_guest_button" value="Add"/>
 	<input type="hidden" id="hrld_byline_active_user_list" name="hrld_byline_active_user_list" value="<?php echo implode(",", $userActive); ?>" />
 	<?php
 }

/**
 *
 *
 *
 *	@return (mixed) 2D array of userFullNames, or empty string if no users are found.
 */
function hrld_bylines_get_active_users( $post){
	
	//retrieve custom post metadata '_hrld_bylines'
 	$userIDsDelimited = get_post_meta($post->ID, '_hrld_bylines', true);

	//return array(array('display_name' => 'Jason Chan', 'id' => 'Jason Chan', 'guest' => true), array('display_name' => 'Will Haynes', 'id' => '321', 'guest' => false));
 	//if no userIDs are retrieved(ie. single user posts, old posts, new posts)
 	if( !$userIDsDelimited)
 		return '';

 	//explode metadata into array, delimited by ','
 	$userIDs = explode(',', $userIDsDelimited);

 	//stores the full name and ID of users as 2-D array
 	$userFullNames = array();

 	//find the display name and id
 	foreach( $userIDs as $userID){
 		if( !is_numeric($userID))
 			$userFullNames[] = array('display_name' => $userID, 'id' => $userID, 'guest' => true);
 		else
 			$userFullNames[] = array('display_name' => get_user_meta( $userID, 'first_name', true).' '.get_user_meta( $userID, 'last_name', true), 
 										'id' => $userID,
 										'guest' => false);
 	}
 	return $userFullNames;
}




add_action('add_meta_boxes', 'hrld_bylines', 10, 2);

/**
 *
 *	finds the list of users (except subscribers) and localize the data 
 *	for jquery-ui-autocomplete to use.
 *
 *	
 *
 */
function hrld_bylines_autocomplete_data(){
	
	//wp_enqueue_script('jquery-ui-autocomplete');
	
	//enqueue the js and css file for this plugin
	wp_enqueue_script('hrld_bylines_js', plugin_dir_url( __FILE__ ) . 'hrld_bylines_js.js', array('jquery','jquery-ui-autocomplete','jquery-ui-sortable'));
	wp_enqueue_style('hrld_bylines_css', plugin_dir_url( __FILE__ ) . 'hrld_bylines_css.css');


	$users = array();

	//retrieve all users
	$allUsers = get_users(array('order'=>'ASC', 'orderby'=>'login', 'fields' => array('user_login','display_name', 'ID')));

	//retrieve full name and ID and store them in $users as an array.
	foreach( $allUsers as $user){
		if( $user->display_name && $user->display_name != ' ')					//users without a display name shouldn't be used.
			$users[] = array("label" => $user->display_name, "value" => $user->ID);
	}

	// localize the all-user information for js use.
	wp_localize_script('hrld_bylines_js','hrld_bylines_all_users', $users);
}

add_action('admin_head', 'hrld_bylines_autocomplete_data', 10);

function hrld_bylines_save_data( $post_id){

	add_post_meta( $post_id, '_hrld_bylines', null, true);
	if( isset($_POST['hrld_byline_active_user_list']) && $_POST['hrld_byline_active_user_list'])
		update_post_meta( $post_id, '_hrld_bylines', $_POST['hrld_byline_active_user_list']);
	else
		delete_post_meta( $post_id, '_hrld_bylines');
	return ;
}



