<?php
/**
 * Hooks View
 *
 * @package    Hooks View
 * @subpackage HooksView Main function
	Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$hooksview = new HooksView();

/** ==================================================
 * Management screen
 */
class HooksView {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_wp_admin_style' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

	}

	/** ==================================================
	 * A-dd a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'hooks-view/hooksview.php';
		}
		if ( $file === $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'tools.php?page=hooksview' ) . '">' . __( 'View' ) . '</a>';
		}
			return $links;
	}

	/** ===-===============================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_menu() {
		add_management_page( 'Hooks View Options', 'Hooks View', 'manage_options', 'hooksview', array( $this, 'plugin_options' ) );
	}

	/** ==================================================
	 * Add Css and Script
	 *
	 * @since 1.00
	 */
	public function load_custom_wp_admin_style() {
		if ( $this->is_my_plugin_screen() ) {
			wp_enqueue_style( 'hooksview-css', plugin_dir_url( __DIR__ ) . 'css/hooksview.css', array(), '1.0.0' );
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 1.00
	 */
	private function is_my_plugin_screen() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'tools_page_hooksview' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * Se-ttings page
	 *
	 * @since 1.00
	 */
	public function plugin_options() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$search_type = 'hook_name';
		$search_text = null;
		if ( isset( $_POST['Hooksearch'] ) && ! empty( $_POST['Hooksearch'] ) ) {
			if ( check_admin_referer( 'hk_search', 'hooksview_search' ) ) {
				if ( isset( $_POST['search_type'] ) && ! empty( $_POST['search_type'] ) ) {
					$search_type = sanitize_text_field( wp_unslash( $_POST['search_type'] ) );
				}
				if ( isset( $_POST['search_text'] ) && ! empty( $_POST['search_text'] ) ) {
					$search_text = sanitize_text_field( wp_unslash( $_POST['search_text'] ) );
				}
			}
		}

		$scriptname = admin_url( 'tools.php?page=hooksview' );

		global $wpdb;
		if ( empty( $search_text ) ) {
			$hooks = $wpdb->get_results( 'SELECT * FROM wp_hook_list ORDER BY first_call' );
		} else {
			$search_text_db = '%%' . $search_text . '%%';
			if ( 'called_by' === $search_type ) {
				$hooks = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM wp_hook_list WHERE called_by LIKE %s ORDER BY first_call', $search_text_db ) );
			} else {
				$hooks = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM wp_hook_list WHERE hook_name LIKE %s ORDER BY first_call', $search_text_db ) );
			}
		}

		?>

		<div class="wrap">
		<h2>Hooks View</h2>

			<details>
			<summary><strong><?php esc_html_e( 'Various links of this plugin', 'hooks-view' ); ?></strong></summary>
			<?php $this->credit(); ?>
			</details>

			<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
			<?php wp_nonce_field( 'hk_search', 'hooksview_search' ); ?>
			<div>
			<strong><?php esc_html_e( 'Type of search' ); ?> : </strong>
			<input type="radio" name="search_type" value="hook_name" 
			<?php
			if ( 'hook_name' === $search_type ) {
				echo 'checked';
			}
			?>
			>hook_name&nbsp;&nbsp;
			<input type="radio" name="search_type" value="called_by" 
			<?php
			if ( 'called_by' === $search_type ) {
				echo 'checked';
			}
			?>
			>called_by
			</div>
			<input type="text" name="search_text" value="<?php echo esc_attr( $search_text ); ?>">
			<?php submit_button( __( 'Search' ), 'large', 'Hooksearch', false ); ?>
			</form>

			<table class="hookstable">
			<colgroup>
				<col style="width: 6%;">
				<col style="width: 25%;">
				<col style="width: 10%;">
				<col style="width: 22%;">
				<col style="width: 6%;">
				<col style="width: 25%;">
				<col style="width: 6%;">
			</colgroup>
			<thead>
			<tr>
			<th>first_call</th>
			<th>hook_name</th>
			<th>hook_type</th>
			<th>called_by</th>
			<th>arg_count</th>
			<th>file_name</th>
			<th>line_num</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $hooks as $hook ) {
				?>
				<tr>
				<td><?php echo esc_html( $hook->first_call ); ?></td>
				<td><?php echo esc_html( $hook->hook_name ); ?></td>
				<td><?php echo esc_html( $hook->hook_type ); ?></td>
				<td><?php echo esc_html( $hook->called_by ); ?></td>
				<td><?php echo esc_html( $hook->arg_count ); ?></td>
				<td><?php echo esc_html( $hook->file_name ); ?></td>
				<td><?php echo esc_html( $hook->line_num ); ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
			</table>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'hooks-view' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'hooks-view' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'hooks-view' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php

	}

}


