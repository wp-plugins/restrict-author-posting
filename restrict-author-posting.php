<?php
/*
Plugin Name: Restrict Author Posting
Plugin URI: http://www.jamviet.com
Description: Allow you to restrict the category that your site contributors can post to
Author: Mcjambi
Version: 1.1.7
Tags:	restrict user, banned user, user role, posting to category, specific posting category, author role
Author URI: http://www.jamviet.com
*/

/*
Copyright 2014 Jam Viet  (email : mcjambi@gmail.com)
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

		add_action( 'show_user_profile', 'restrict_user_form' );
		add_action( 'edit_user_profile', 'restrict_user_form' );

		function restrict_user_form( $user ) {
			if ( user_can($user->ID, 'administrator'))
				return false;
			$args = array(
				'show_option_all'    => '',
				'show_option_none'   => '== No restrict ==',
				'orderby'            => 'ID', 
				'order'              => 'ASC',
				'show_count'         => 0,
				'hide_empty'         => 0,
				'child_of'           => 0,
				'exclude'            => '',
				'echo'               => 1,
				'selected'           => get_user_meta( $user->ID, '_access', true),
				'hierarchical'       => 0, 
				'name'               => 'allow',
				'id'                 => '',
				'class'              => 'postform',
				'depth'              => 0,
				'tab_index'          => 0,
				'taxonomy'           => 'category',
				'hide_if_empty'      => false,
				'walker'             => ''
			);
		?>

			<h3>Restrict the category in which this user can post to</h3>

			<table class="form-table">
				<tr>
					<th><label for="access">Select category:</label></th>

					<td>
						<?php wp_dropdown_categories($args); ?>
						<br />
						<span class="description">Use to restrict an author posting to just one category.</span>
					</td>
				</tr>

			</table>
		<?php }
		
		/* save the data from admin */
		add_action( 'personal_options_update', 'restrict_save_data' );
		add_action( 'edit_user_profile_update', 'restrict_save_data' );
		
		function restrict_save_data( $user_id ) {

			if ( !current_user_can( 'administrator', $user_id ) )
				return false;
			update_user_meta( $user_id, '_access', $_POST['allow'] );
		}
		
		// check if the user loggin in is author and be restricted
		function is_restrict() {
			if ( get_user_meta(get_current_user_id(), '_access', true) > 0 )
					return true;
			else
					return false;
		}
		
		
		/* auto register category to post that the author's being restricted */
		add_action( 'save_post', 'save_restrict_post' );
		function save_restrict_post( $post_id ) {
			if ( ! wp_is_post_revision( $post_id ) && is_restrict() ){
			remove_action('save_post', 'save_restrict_post');
				wp_set_post_categories( $post_id, get_user_meta( get_current_user_id() , '_access', true) );
			add_action('save_post', 'save_restrict_post');
			}
		}
		
		/* warning author */
		add_action( 'edit_form_after_title', 'restrict_warning' );
		function restrict_warning( $post_data = false ) {
			if (is_restrict()) {
				$c = get_user_meta( get_current_user_id() , '_access', true);
				$data = get_category($c);
				echo 'You are allowing to post to category: <strong>'. $data->name .'</strong><br /><br />';
			}
		}
		
		/* remove category dropdown box in editor */
		function restrict_remove_meta_boxes() {
			if (is_restrict() )
				remove_meta_box('categorydiv', 'post', 'normal');
		}
		add_action( 'admin_menu', 'restrict_remove_meta_boxes' );