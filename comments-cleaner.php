<?php
/*
Plugin Name: Comments Cleaner
Plugin URI: http://www.poradnik-webmastera.com/projekty/comments_cleaner/
Description: This plugin removes all HTML tags, BBCode tags and links from added comments.
Author: Daniel Frużyński
Version: 1.3
Author URI: http://www.poradnik-webmastera.com/
Text Domain: comments-cleaner
License: GPL2
*/

/*  Copyright 2009-2011  Daniel Frużyński  (email : daniel [A-T] poradnik-webmastera.com)

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

if ( !class_exists( 'CommentsCleaner' ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG  ) ) {

class CommentsCleaner {
	var $replaced_fun = false;
	
	var $template = null;
	
	// Constructor
	function CommentsCleaner() {
		// Initialisation and admin section
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		
		// Clean comment - new
		add_filter( 'preprocess_comment', array( &$this, 'preprocess_comment' ) );
		
		// Clean comment - existing
		if ( get_option( 'comcln_remove_author_url_old' ) ) {
			add_filter( 'get_comment_author_link', array( &$this, 'get_comment_author_link' ) );
		}
		add_filter( 'get_comment_text', array( &$this, 'get_comment_text' ) );
	}
	
	// Initialise plugin
	function init() {
		load_plugin_textdomain( 'comments-cleaner', false, dirname( plugin_basename( __FILE__ ) ).'/lang' );
	}
	
	// Initialise plugin - admin part
	function admin_init() {
		register_setting( 'comments-cleaner', 'comcln_remove_author_url', array( &$this, 'sanitise_bool' ) );
		register_setting( 'comments-cleaner', 'comcln_remove_html', array( &$this, 'sanitise_bool' ) );
		register_setting( 'comments-cleaner', 'comcln_remove_bbcode', array( &$this, 'sanitise_bool' ) );
		register_setting( 'comments-cleaner', 'comcln_remove_links', array( &$this, 'sanitise_bool' ) );
		register_setting( 'comments-cleaner', 'comcln_remove_author_url_old', array( &$this, 'sanitise_bool' ) );
		register_setting( 'comments-cleaner', 'comcln_remove_html_old', array( &$this, 'sanitise_bool' ) );
		register_setting( 'comments-cleaner', 'comcln_remove_bbcode_old', array( &$this, 'sanitise_bool' ) );
		register_setting( 'comments-cleaner', 'comcln_remove_links_old', array( &$this, 'sanitise_bool' ) );
	}
	
	// Clean comment
	function preprocess_comment( $commentdata ) {
		// Remove Author's URL
		if ( get_option( 'comcln_remove_author_url' ) ) {
			$commentdata['comment_author_url'] = null;
		}
		
		// Remove links from comment's content
		$commentdata['comment_content'] = $this->clean( $commentdata['comment_content'], 
			get_option( 'comcln_remove_html' ),
			get_option( 'comcln_remove_bbcode' ),
			get_option( 'comcln_remove_links' ) );
		
		return $commentdata;
	}
	
	// Existing comment - remove Author's URL
	function get_comment_author_link( $link ) {
		return strip_tags( $link );
	}
	
	// Existing comment - remove links from content
	function get_comment_text( $comment ) {
		return $this->clean( $comment, 
			get_option( 'comcln_remove_html_old' ),
			get_option( 'comcln_remove_bbcode_old' ),
			get_option( 'comcln_remove_links_old' ) );
	}
	
	// These functions are modified copies of WP's make_clickable() and its helper functions
	// Callback to remove URI match.
	function _remove_url_cb( $matches ) {
		$url = $matches[2];
		$suffix = '';
		
		/** Include parentheses in the URL only if paired **/
		while ( substr_count( $url, '(' ) < substr_count( $url, ')' ) ) {
			$suffix = strrchr( $url, ')' ) . $suffix;
			$url = substr( $url, 0, strrpos( $url, ')' ) );
		}
		
		$url = esc_url($url);
		if ( empty($url) )
			return $matches[0];
		
		return $matches[1] . $suffix;
	}
	// Callback to remove URL match.
	function _remove_web_ftp_cb( $matches ) {
		$ret = '';
		$dest = $matches[2];
		$dest = 'http://' . $dest;
		$dest = esc_url($dest);
		if ( empty($dest) )
			return $matches[0];
		
		// removed trailing [.,;:)] from URL
		if ( in_array( substr($dest, -1), array('.', ',', ';', ':', ')') ) === true ) {
			$ret = substr($dest, -1);
			$dest = substr($dest, 0, strlen($dest)-1);
		}
		return $matches[1] . $ret;
	}
	// Callback to remove email address match.
	function _remove_email_cb( $matches ) {
		return $matches[1];
	}
	// Remove plaintext URIs.
	function remove_plaintext_links( $ret ) {
		$ret = ' ' . $ret;
		// in testing, using arrays here was found to be faster
		$save = @ini_set('pcre.recursion_limit', 10000);
		$retval = preg_replace_callback('#(?<!=[\'"])(?<=[*\')+.,;:!&$\s>])(\()?([\w]+?://(?:[\w\\x80-\\xff\#%~/?@\[\]-]{1,2000}|[\'*(+.,;:!=&$](?![\b\)]|(\))?([\s]|$))|(?(1)\)(?![\s<.,;:]|$)|\)))+)#is', array( &$this, '_remove_url_cb' ), $ret);
		if ( !is_null( $retval ) )
			$ret = $retval;
		@ini_set('pcre.recursion_limit', $save);
		$ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', array( &$this, '_remove_web_ftp_cb' ), $ret);
		$ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', array( &$this, '_remove_email_cb' ), $ret);
		// this one is not in an array because we need it to run last, for cleanup of accidental links within links
		$ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
		$ret = trim($ret);
		return $ret;
	}
	
	// Remove tags and links from text
	function clean( $text, $remove_html, $remove_bbcode, $remove_links ) {
		// Repeat to make sure multiple-encoded stuff will be stripped
		do {
			$text_ori = $text;
			
			// Remove HTML tags
			if ( $remove_html ) {
				$text = wp_strip_all_tags( $text );
			}
			// Remove BBCode tags
			if ( $remove_bbcode ) {
				$text = preg_replace( '#\[[^\]]+\]#', '', $text );
			}
			// Remove plain text links
			if ( $remove_links ) {
				$text = $this->remove_plaintext_links( $text );
			}
		} while ( $text_ori != $text );
		
		return $text;
	}
	
	// Add Admin menu option
	function admin_menu() {
		add_submenu_page( 'options-general.php', 'Comments Cleaner', 
			'Comments Cleaner', 'manage_options', __FILE__, array( &$this, 'options_panel' ) );
	}
	
	// Handle options panel
	function options_panel() {
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php _e('Comments Cleaner - Options', 'comments-cleaner'); ?></h2>

<form name="dofollow" action="options.php" method="post">
<?php settings_fields( 'comments-cleaner' ); ?>
<table class="form-table">

<tr><th colspan="2"><h3><?php _e('New comments:', 'comments-cleaner'); ?></h3></th></tr>

<tr><th colspan="2"><?php _e('Note: links and tags are removed permanently. If you want to keep them but do not display, enable options for existing only.', 'comments-cleaner'); ?></th></tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="comcln_remove_author_url"><?php _e('Remove Author\'s URL', 'comments-cleaner'); ?>: </label>
</th>
<td>
<input type="checkbox" id="comcln_remove_author_url" name="comcln_remove_author_url" value="yes" <?php checked( true, get_option( 'comcln_remove_author_url' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="comcln_remove_html"><?php _e('Remove HTML tags', 'comments-cleaner'); ?>: </label>
</th>
<td>
<input type="checkbox" id="comcln_remove_html" name="comcln_remove_html" value="yes" <?php checked( true, get_option( 'comcln_remove_html' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="comcln_remove_bbcode"><?php _e('Remove BBCode tags', 'comments-cleaner'); ?>: </label>
</th>
<td>
<input type="checkbox" id="comcln_remove_bbcode" name="comcln_remove_bbcode" value="yes" <?php checked( true, get_option( 'comcln_remove_bbcode' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="comcln_remove_links"><?php _e('Remove plain text links', 'comments-cleaner'); ?>: </label>
</th>
<td>
<input type="checkbox" id="comcln_remove_links" name="comcln_remove_links" value="yes" <?php checked( true, get_option( 'comcln_remove_links' ) ); ?> />
</td>
</tr>

<tr><th colspan="2"><h3><?php _e('Existing comments:', 'comments-cleaner'); ?></h3></th></tr>

<tr><th colspan="2"><?php _e('Note: links and tags are removed on the fly only (they are kept in database).', 'comments-cleaner'); ?></th></tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="comcln_remove_author_url_old"><?php _e('Remove Author\'s URL', 'comments-cleaner'); ?>: </label>
</th>
<td>
<input type="checkbox" id="comcln_remove_author_url_old" name="comcln_remove_author_url_old" value="yes" <?php checked( true, get_option( 'comcln_remove_author_url_old' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="comcln_remove_html_old"><?php _e('Remove HTML tags', 'comments-cleaner'); ?>: </label>
</th>
<td>
<input type="checkbox" id="comcln_remove_html_old" name="comcln_remove_html_old" value="yes" <?php checked( true, get_option( 'comcln_remove_html_old' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="comcln_remove_bbcode_old"><?php _e('Remove BBCode tags', 'comments-cleaner'); ?>: </label>
</th>
<td>
<input type="checkbox" id="comcln_remove_bbcode_old" name="comcln_remove_bbcode_old" value="yes" <?php checked( true, get_option( 'comcln_remove_bbcode_old' ) ); ?> />
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="comcln_remove_links_old"><?php _e('Remove plain text links', 'comments-cleaner'); ?>: </label>
</th>
<td>
<input type="checkbox" id="comcln_remove_links_old" name="comcln_remove_links_old" value="yes" <?php checked( true, get_option( 'comcln_remove_links_old' ) ); ?> />
</td>
</tr>

</table>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Save settings', 'comments-cleaner'); ?>" /> 
</p>

</form>
</div>
<?php
	}

	// Sanitise function for boolean options
	function sanitise_bool( $value ) {
		return isset( $value ) && ( $value == 'yes' );
	}
}

// Add functions from WP2.8 for previous WP versions
if ( !function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return wp_specialchars( $text );
	}
}
if ( !function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return attribute_escape( $text );
	}
}

// Add functions from WP2.9 for previous WP versions
if ( !function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags($string, $remove_breaks = false) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
		$string = strip_tags($string);
	
		if ( $remove_breaks )
			$string = preg_replace('/[\r\n\t ]+/', ' ', $string);
	
		return trim($string);
	}
}

add_option( 'comcln_remove_author_url', true ); // Remove Author's URL from new comments
add_option( 'comcln_remove_html', true ); // Remove HTML tags from new comments
add_option( 'comcln_remove_bbcode', true ); // Remove BBCode tags from new comments
add_option( 'comcln_remove_links', true ); // Remove plain text links from new comments
add_option( 'comcln_remove_author_url_old', true ); // Remove Author's URL from existing comments
add_option( 'comcln_remove_html_old', true ); // Remove HTML tags from existing comments
add_option( 'comcln_remove_bbcode_old', true ); // Remove BBCode tags from existing comments
add_option( 'comcln_remove_links_old', true ); // Remove plain text links from existing comments

$wp_comments_cleaner = new CommentsCleaner();

} /* END */

?>