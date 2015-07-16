<?php
/*
Plugin Name: Restrict Author Posting
Plugin URI: http://www.jamviet.com/2015/05/restrict-author-posting.html
Description: This plugin help you to add restriction posting to editor/author in your blog.
Author: Jam Việt
Version: 2.0.2
Tags:	restrict user, banned user, user role, posting to category, specific posting category, author role
Author URI: http://www.jamviet.com
Donate link: http://www.jamviet.com/2015/05/restrict-author-posting.html
*/

/*
Thanks to my friend: Pete Stoves  (email : stovesy@gmail.com)
	Changed to allow the selection of more than one category restriction.
	We use jQuery and...
		the jquery.multiple.select plugin (http://wenzhixin.net.cn/p/multiple-select)
		Copyright (c) 2012-2014 Zhixin Wen <wenzhixin2010@gmail.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action( 'admin_enqueue_scripts', 'restrict_user_form_enqueue_scripts' );
function restrict_user_form_enqueue_scripts($hook) {
	if ( ! in_array($hook, array('profile.php', 'user-edit.php' )))
		return;
	wp_enqueue_script('jquery');
	wp_enqueue_script( 'jquery.multiple.select', plugin_dir_url( __FILE__ ) . 'inc/jquery.multiple.select.js' );
	wp_register_style( 'jquery.multiple.select_css', plugin_dir_url( __FILE__ ) . 'inc/multiple-select.css', false, '1.0.0' );
	wp_enqueue_style( 'jquery.multiple.select_css' );
}

add_filter( 'get_terms_args', 'restrict_user_get_terms_args', 10, 2 );
/**
* Exclude categories which arent selected for this user.
*/
function restrict_user_get_terms_args( $args, $taxonomies ) {
	// Dont worry if we're not in the admin screen
	if (! is_admin() || $taxonomies[0] !== 'category')
		return $args;
	// Admin users are exempt.
	$currentUser = wp_get_current_user();
	if (in_array('administrator', $currentUser->roles))
		return $args;
	
	$include = get_user_meta( $currentUser->ID, '_access', true);
	
	$args['include'] = $include;
	return $args;
}

add_action( 'show_user_profile', 'restrict_user_form' );
add_action( 'edit_user_profile', 'restrict_user_form' );
function restrict_user_form( $user ) {
	// A little security
	if ( ! current_user_can('add_users'))
		return false;
	$args = array(
		'show_option_all'    => '',
		'orderby'            => 'ID', 
		'order'              => 'ASC',
		'show_count'         => 0,
		'hide_empty'         => 0,
		'child_of'           => 0,
		'exclude'            => '',
		'echo'               => 0,
		'hierarchical'       => 1, 
		'name'               => 'allow',
		'id'                 => '',
		'class'              => 'postform',
		'depth'              => 0,
		'tab_index'          => 0,
		'taxonomy'           => 'category',
		'hide_if_empty'      => false,
		'walker'             => ''
	);

	$dropdown = wp_dropdown_categories($args);
	// We are going to modify the dropdown a little bit.
	$dom = new DOMDocument();
	/*
		@http://ordinarygentlemen.co.uk
		There's an error here, while using PHP 5.4 not support LIBXML_HTML_NOIMPLIED or LIBXML_HTML_NODEFDTD
		Vietnamese error, So fixed it by adding mb_convert_encoding() !
	*/
	//$dom->loadHTML($dropdown, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	$dom->loadHTML( mb_convert_encoding($dropdown, 'HTML-ENTITIES', 'UTF-8') );
	$xpath = new DOMXpath($dom);
	$selectPath = $xpath->query("//select[@id='allow']");

	if ($selectPath != false) {
		// Change the name to an array.
		$selectPath->item(0)->setAttribute('name', 'allow[]');
		// Allow multi select.
		$selectPath->item(0)->setAttribute('multiple', 'yes');
		
		$selected = get_user_meta( $user->ID, '_access', true);
		// Flag as selected the categories we've previously chosen
		// Do not throught error in user's screen ! // @JamViet
		if ( $selected )
		foreach ($selected as $term_id) {
			if (!empty($term_id)){
				$option = $xpath->query("//select[@id='allow']//option[@value='$term_id']");
				$option->item(0)->setAttribute('selected', 'selected');
			}
		}
	}
?>
	<h3>Restrict the categories in which this user can post to</h3>
	<table class="form-table">
		<tr>
			<th><label for="access">Select categories:</label></th>
			<td>
				<?php echo $dom->saveXML($dom);?>
				<span class="description">Author restriced to post selected categories only.</span>
			</td>
		</tr>

	</table>
	<script>
		var $jq = jQuery.noConflict(true);
		$jq('select#allow').multipleSelect();
	</script>
<?php 
}

/* save the category selections from admin */
add_action( 'personal_options_update', 'restrict_save_data' );
add_action( 'edit_user_profile_update', 'restrict_save_data' );
function restrict_save_data( $user_id ) {
	// check security
	if ( ! current_user_can( 'add_users' ) )
		return false;
	// admin can not restrict himself
	if ( get_current_user_id() == $user_id )
		return false;
	// and last, save it 
	if ( ! empty ($_POST['allow']) )
		update_user_meta( $user_id, '_access', $_POST['allow'] );
	else 
		delete_user_meta( $user_id, '_access' );
}