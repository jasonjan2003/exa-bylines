<?php
/**
 * Plugin Name: Exa Bylines
 * Description: Allows multiple authors for an article.
 * Version: 1.0
 * Author: Jason Chan for The Badger Herald
 * License: GPL2
 */

/**
 *
 *	Add Exa Bylines meta box to post edit screen
 *	and setup saving actio upon different hooks.
 *
 *
 */
function exa_bylines_setup(){
	
	/*add meta box */
	add_meta_box(
		'exa-bylines-meta-box',
		'Multiple Bylines',
		'exa_bylines_meta_box',
		'post',
		'side',
		'high'
		);

	/* Save! */
	add_action( 'save_post', 'exa_bylines_save_data');
	add_action( 'pre_post_update', 'exa_bylines_save_data');
	add_action( 'edit_post', 'exa_bylines_save_data');
	add_action( 'publish_post', 'exa_bylines_save_data');
	add_action( 'edit_page_form', 'exa_bylines_save_data');

	/* Let old formats be processed! */
	exa_bylines_old_posts();
}

add_action( 'load-post.php', 'exa_bylines_setup' );
add_action( 'load-post-new.php', 'exa_bylines_setup' );

function exa_bylines(){
	return;
}
function exa_bylines_activated_plugin( ){

	exa_bylines_old_posts();
	return ;
}
add_action( 'activated_plugin', 'exa_bylines_activated_plugin' );
	

/**
 *
 *	Add Herald Bylines meta box to post edit screen
 *
 *
 *
 */
 function exa_bylines_meta_box( $post){
 	
 	//retrieve the list of users(authors)
 	$userMeta = exa_bylines_get_active_users( $post);

 	//stores list of users active
 	$userActive = array();

 	//create <ul>
	 echo "<ul id='exa_bylines_current_authors'>";

 	//if no users are returned, say so.
 	if( !$userMeta) :
 	?>

 		<li class="exa_byline_no_byline"></li>

 	<?php

	//otherwise, generate list
	else:

	 	//create a random number for the remove button
 		//so the page doesn't jump.
 		$rand = rand(10000,99999);

	 	//for every user, create a <li>
	 	foreach ($userMeta as $user) :
	 	?>
	 		<li class='exa_byline_current_author' exa-byline-userID=<?php echo $user['id']; ?> >
	 			<label><?php echo $user['display_name']; ?><?php echo $user['guest'] ? ' (guest)' : ''; ?></label>
	 			<a href="#<?php echo $rand; ?>" class='exa_byline_current_author_remove' 
	 					name="exa_byline_current_author_remove_<?php echo $user['id']; ?> ">Remove</a>
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
 	<input type="text" id="exa_byline_input" placeholder="Type name here..."/>
 	<hr>
 	<p><b>Can't find someone?</b><br>Just add the name below</p>
 	<input type="text" id="exa_byline_input_guest" placeholder="Guest name"/>
 	<input type="button" id="exa_byline_input_guest_button" class="button exa_byline_input_guest_button" value="Add"/>
 	<input type="hidden" id="exa_byline_active_user_list" name="exa_byline_active_user_list" value="<?php echo implode(",", $userActive); ?>" />
 	<?php
 	exa_bylines_old_posts();
 }

/**
 *
 *
 *
 *	@return (mixed) 2D array of userFullNames, or empty string if no users are found.
 */
function exa_bylines_get_active_users( $post){
	
	//retrieve custom post metadata '_exa_bylines'
 	$userIDsDelimited = get_post_meta($post->ID, '_exa_bylines', true);

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




add_action('add_meta_boxes', 'exa_bylines', 10, 2);

/**
 *
 *	finds the list of users (except subscribers) and localize the data 
 *	for jquery-ui-autocomplete to use.
 *
 *	
 *
 */
function exa_bylines_autocomplete_data(){
	
	
	//enqueue the js and css file for this plugin
	wp_enqueue_script('exa_bylines_js', plugin_dir_url( __FILE__ ) . 'js/exa_bylines_js.js', array('jquery','jquery-ui-autocomplete','jquery-ui-sortable'));
	wp_enqueue_style('exa_bylines_css', plugin_dir_url( __FILE__ ) . 'css/exa_bylines_css.css');


	$users = exa_bylines_get_all_valid_users();

	// localize the all-user information for js use.
	wp_localize_script('exa_bylines_js','exa_bylines_all_users', $users);
}
add_action( 'load-post.php', 'exa_bylines_autocomplete_data' );
add_action( 'load-post-new.php', 'exa_bylines_autocomplete_data' );

function exa_bylines_get_all_valid_users( $js = true, $additionalFields = array() ){

	global $wpdb;

	$users = array();
	$fields = array_merge(array('user_login','display_name', 'ID'), $additionalFields);
	$badUsers = $wpdb->get_col(
        "SELECT `ID` FROM $wpdb->users WHERE `display_name` LIKE \"% and %\" OR `display_name` LIKE \"% & %\" ORDER BY `ID` DESC"
        );

	//retrieve all users with exclusion
	$allUsers = get_users(array('order'=>'ASC', 
								'orderby'=>'login', 
								'fields' => $fields,
								'exclude'=> $badUsers
								)
				);

	//retrieve full name and ID and store them in $users as an array. for js
	
	foreach( $allUsers as $user){
		if( $user->display_name && $user->display_name != ' ')					//users without a display name shouldn't be used.
			if( $js)
				$users[] = array("label" => $user->display_name, "value" => $user->ID);
			else{
				$userMeta = array();
				foreach( $fields as $field){
					$userMeta[$field] = $user->$field;
				}
				$users[] = $userMeta;
			}
	}

	return $users;
}
/*
* 
* 
*
*/
function exa_bylines_save_data( $post_id, $manualInput = false){

	add_post_meta( $post_id, '_exa_bylines', null, true);
	if( isset($_POST['exa_byline_active_user_list']) && $_POST['exa_byline_active_user_list'])
		update_post_meta( $post_id, '_exa_bylines', $_POST['exa_byline_active_user_list']);
	else if( $manualInput != false){
		update_post_meta( $post_id, '_exa_bylines', $manualInput);
	}else
		delete_post_meta( $post_id, '_exa_bylines');
	return ;
}

/**
 * front-end use of exa_bylines. This function creates the byline in an article.
 * while able to call this function directly, this function also filters the_author(), 
 * a native Wordpress template tag. However, for individual author_links, call directly.
 *
 * @param (string)name. is the default author's name
 * @param (boolean)link, optional. makes each author a link to the author page.
 * @param (array)linkArgs, opt. inserts <a> attributes to help style the author links. ignored if $link = false
 *
 * @return (string) the new author link. or the default.
 */
function exa_bylines_the_authors( $name, $link = false, $linkArgs = array()){

	global $post;

	if( $post)
		$authors = get_post_meta( $post->ID, '_exa_bylines', true);
	else 
		return;
	$attrs = '';
	foreach( $linkArgs as $attr => $values){
		$attrs .= $attr . '="' . $values . '" ';
	}
	if( $authors){
		$name = '';
		$authors = explode(',', $authors);
		foreach( $authors as $key => $author){

			$user = get_userdata( $author);
			if( $user && $user->display_name != ''){
				if( $link)
					$name .= '<a href="' . get_author_posts_url( $user->ID) . '" '. $attrs .'>' . $user->display_name . '</a>';
				else
					$name .= $user->display_name;
			}
			else{
				if( $link)
					$name .= "<a $attrs >" . $author . "</a>";
				else
					$name .= $author;
			}

			if( $key < count($authors) -2 )
				$name .= ', ';
			else if( $key < count($authors) -1)
				$name .= ' and ';
		}
	}else{
		if( !$name){
			$user = get_userdata( $post->post_author);
			if( $link)
				$name = '<a href="' . $user->user_url . '" '. $attrs .'>' . $user->display_name . '</a>';
			else
				$name = $user->display_name;
		}
	}
	return $name;
}
add_filter('the_author', 'exa_bylines_the_authors');
add_filter('get_the_author_display_name', 'exa_bylines_the_authors');

/**
 * 
 * retrieve the list of exa_bylines users or, if not set, just the default user.
 *
 * @param none.
 *
 * @return (array) array of WP_User(s).
 */
function exa_bylines_get_the_authors(){

	global $post;
	$authorsList = array();
	$authors = get_post_meta( $post->ID, '_exa_bylines', true);
	if( $authors){
		$authors = explode(',', $authors);
		foreach( $authors as $key => $author){
			$authorsList[] = get_userdata( $author);
		}
	}else
		$authorsList = array( get_userdata( $post->post_author));

	return $authorsList;
}

// under construction
function exa_bylines_author_archive( $query){

	if( is_author() && $query->is_main_query()){
		
	}
}

function exa_bylines_old_posts(){

	global $wpdb;

	$allUsers = exa_bylines_get_all_valid_users( false, array('user_nicename'));						//false: don't format for js

	if ( $badUsers = $wpdb->get_results(
        "SELECT * FROM $wpdb->users WHERE (`display_name` LIKE \"% and %\") OR (`display_name` LIKE \"% & %\") OR (`display_name` LIKE \"% &amp; %\") ORDER BY `id` DESC", ARRAY_A
 	)){

		foreach( $badUsers as $badUser){
			$badUserID = $badUser['ID'];
			$badUserDisplayName = $badUser['display_name'];
			$badUserSeperateNames = array();
			$goodUsers = array();	//Display name, id

			//if have three+ authors( , , , ... and )
			if( strpos($badUserDisplayName, ',') != -1){
				$badUserDisplayName = str_replace(array(',and', ', and', ' & ', ' &amp; '), ' and ', $badUserDisplayName);
				$badUserSeperateNames = explode(',', $badUserDisplayName);

				//and the and at the last
				$size = sizeof($badUserSeperateNames);
				$lastTwoNames = explode(' and ', $badUserSeperateNames[ $size - 1]);
				if( sizeof($lastTwoNames) == 2){
					$badUserSeperateNames[ $size - 1] = $lastTwoNames[0];
					$badUserSeperateNames[ $size    ] = $lastTwoNames[1];
				}
			}else{
				$badUserDisplayName = str_replace(array(' & ',' &amp; ', ', And '), ' and ', $badUserDisplayName);
				$badUserSeperateNames = explode(' and ', $badUserDisplayName);
			}

			
			//attempts to match existing users.
			$allUsersName = array_map(function($e){return $e['display_name'];}, $allUsers);
			
			//active user list
			$activeList = '';

			//print_r( $allUsersName);
			foreach( $badUserSeperateNames as $count => $badUserSeperateName){
				$key = array_search( trim($badUserSeperateName), $allUsersName);
				if( $key !== false){
					$goodUsers[] = array( 'display_name' => $allUsers[$key]['display_name'], 'id' => $allUsers[$key]['ID']);
					$activeList .= $allUsers[$key]['ID']; 
				}
				else{
					$goodUsers[] = array( 'display_name' => $badUserSeperateName, 'id' => $badUserSeperateName);
					$activeList .= $badUserSeperateName;
				}

				if( $count != sizeof( $badUserSeperateNames)-1)
					$activeList .= ',';
			}
			//get posts tied to this $badUser
			$badUserPostIDs = get_posts( array(
								'author'			=> $badUserID,
								'posts_per_page'	=> -1,
								'fields'			=> 'ids'
				));
 			
 			foreach( $badUserPostIDs as $badUserPostID){
 				if( get_post_meta( $badUserPostID, '_exa_bylines', true) == false)
 					exa_bylines_save_data( $badUserPostID, $activeList);
 			}

		}
        return true;
 	}
 	return false;
}

