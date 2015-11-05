<?php // Functions for BIC Stationery Wordpress site

/**
 * ALTER 'POSTS' NAME
 *
 * Function block to changes "posts" to "products" in the Wordpress backend.
 *
 * @author MVC <michaelc@drinkcaffeine.com>
 */

 // Admin Menu Rename
 //
 // @param object $menu A Wordpress menu object
 // @return object The altered menu
function bic_rename_admin_menu_items($menu)
{
	$menu = str_ireplace('Posts', 'Products', $menu);
	$menu = str_ireplace('Post', 'Product', $menu);

	return $menu;
}

 // Label Rename
 //
 // @return none Updates via reference
function bic_rename_post_types()
{
    # Get necessary data.
	global $wp_post_types;
	$labels = &$wp_post_types['post']->labels;
	$labels->name = 'Products';

    # Rename the items.
	$labels->singular_name      = 'Product';
	$labels->add_new            = 'Add Product';
	$labels->add_new_item       = 'Add Product';
	$labels->edit_item          = 'Edit Products';
	$labels->new_item           = 'Product';
	$labels->view_item          = 'View Product';
	$labels->search_items       = 'Search Products';
	$labels->not_found          = 'No products found';
	$labels->not_found_in_trash = 'No products found in Trash';
	$labels->all_items          = 'All Products';
	$labels->menu_name          = 'Products';
	$labels->name_admin_bar     = 'Product';
}

 // Execute Renames
add_filter('gettext', 'bic_rename_admin_menu_items');
add_filter('ngettext', 'bic_rename_admin_menu_items');
add_action('init', 'bic_rename_post_types');

/**
 * Remove Menu Items
 *
 * Takes out unwanted items.
 *
 * @return none Updates via filter
 * @author MVC <michaelc@drinkcaffeine.com>
 */
function bic_remove_menu_items()
{
	remove_menu_page('edit-comments.php');
	remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');
}
add_action('admin_menu', 'bic_remove_menu_items');

/**
 * IMAGE FILTERS
 *
 * Fixes URLs from before the settings change and other image-related changes.
 *
 * @author MVC <michaelc@drinkcaffeine.com>
 */

 // Content Filter
 //
 // @param string $content The Wordpress page content
 // @return string The filtered content
function bic_content_filter($content)
{
    # Old URLs.
	return str_ireplace('wp-content/uploads', 'uploads', $content);
}

 // Image Title
 //
 // @param integer $id A Wordpress post ID
function bic_gallery_image_title($id)
{
	$att = get_post($id);
	return ($att->post_excerpt) ?: 'Product Image';
}

 // Product Shots Custom Gallery
 //
 // @param array $atts An array of post attributes
 // @param boolean $outside Whether we are outside of the_loop()
function bic_gallery_shortcode($atts, $outside = false)
{

	// Must have attributes and not be in the loop
	if(count($atts) && $outside)
	{
		$ids = explode(',', $atts['ids']);
		# Only if there are post IDs
		if(!empty($ids[0]))
		{
			$output = '<section id="images"><h3>Product Images</h3><ul>';
			# Loop through gallery images
			foreach($ids as $file)
			{
				# Get URI to file and process BIC image
				$this_file = wp_get_attachment_image_src($file, 'full');
				$this_title = bic_gallery_image_title($file);
				$img_src = str_ireplace('wp-content/uploads', 'uploads', $this_file[0]);
				$output.= '<li><a href="' . $img_src . '" target="_blank" title="' . $this_title . '"><img alt="" border="0" src="' . $img_src . '" /></a></li>';
			}
			$output.= '</ul></section>';
		}
	}

	return $output;

}

 // Execute image stuff
add_filter('the_content', 'bic_content_filter');
remove_shortcode('gallery');
add_shortcode('gallery', 'bic_gallery_shortcode');

/**
 * PRODUCT INFO META BOX
 *
 * Adds the product info meta box to the post editing screen
 *
 * @author MVC <michaelc@drinkcaffeine.com>
 */

 // Setup
 //
 // @return none Updates via hook
function bic_product_info()
{
	add_meta_box(
		'bic_product_info',
		__('Product Information', 'bic'),
		'bic_product_info_box',
		'post',
		'advanced'
	);
}

 // Display
 //
 // @param object $post The Wordpress post object
 // @return mixed Outputs HTML
function bic_product_info_box($post)
{

	# Nonce.
	wp_nonce_field('bic_product_info_box', 'bic_product_info_nonce');

	# Template.
	include 'bic_sku_template.php.inc';

	# Array search rules.
	# TODO: Move outside?
	$pct_search = ['%%onoff%%', '%%onoff_val%%', '%%product_sku%%', '%%product_sku_val%%', '%%product_desc%%', '%%product_desc_val%%', '%%ink_black%%', '%%ink_black_check%%', '%%ink_black_item%%', '%%ink_black_item_val%%', '%%ink_black_chip%%', '%%ink_black_chip_val%%', '%%ink_black_case%%', '%%ink_black_case_val%%', '%%ink_blue%%', '%%ink_blue_check%%', '%%ink_blue_item%%', '%%ink_blue_item_val%%', '%%ink_blue_chip%%', '%%ink_blue_chip_val%%', '%%ink_blue_case%%', '%%ink_blue_case_val%%', '%%ink_red%%', '%%ink_red_check%%', '%%ink_red_item%%', '%%ink_red_item_val%%', '%%ink_red_chip%%', '%%ink_red_chip_val%%', '%%ink_red_case%%', '%%ink_red_case_val%%', '%%ink_green%%', '%%ink_green_check%%', '%%ink_green_item%%', '%%ink_green_item_val%%', '%%ink_green_chip%%', '%%ink_green_chip_val%%', '%%ink_green_case%%', '%%ink_green_case_val%%', '%%ink_purple%%', '%%ink_purple_check%%', '%%ink_purple_item%%', '%%ink_purple_item_val%%', '%%ink_purple_chip%%', '%%ink_purple_chip_val%%', '%%ink_purple_case%%', '%%ink_purple_case_val%%', '%%ink_yellow%%', '%%ink_yellow_check%%', '%%ink_yellow_item%%', '%%ink_yellow_item_val%%', '%%ink_yellow_chip%%', '%%ink_yellow_chip_val%%', '%%ink_yellow_case%%', '%%ink_yellow_case_val%%', '%%ink_pink%%', '%%ink_pink_check%%', '%%ink_pink_item%%', '%%ink_pink_item_val%%', '%%ink_pink_chip%%', '%%ink_pink_chip_val%%', '%%ink_pink_case%%', '%%ink_pink_case_val%%', '%%ink_white%%', '%%ink_white_check%%', '%%ink_white_item%%', '%%ink_white_item_val%%', '%%ink_white_chip%%', '%%ink_white_chip_val%%', '%%ink_white_case%%', '%%ink_white_case_val%%', '%%ink_multiple%%', '%%ink_multiple_check%%', '%%ink_multiple_item%%', '%%ink_multiple_item_val%%', '%%ink_multiple_chip%%', '%%ink_multiple_chip_val%%', '%%ink_multiple_case%%', '%%ink_multiple_case_val%%', '%%ppd%%', '%%ppd_val%%', '%%dpc%%', '%%dpc_val%%', '%%pccxl%%', '%%pccxl_val%%', '%%pdim%%', '%%pdim_val%%', '%%cdim%%', '%%cdim_val%%', '%%cuft%%', '%%cuft_val%%', '%%lbs%%', '%%lbs_val%%', '%%coo%%', '%%coo_val%%'];

	# Grab post meta.
	$meta_info = get_post_meta($post->ID, 'product_information', true);
	$count = 0;
	if($meta_info)
	{
		# Transform back to array.
		$product_information = unserialize($meta_info);
		foreach($product_information as $row => $details)
		{
			$count++;
			# Corresponds to big array above.
			# TODO: Handle outside?
			$pct_replace = [
				'onoff_' . $row,'1',
				'product_' . $row . '_sku',
				$details['sku'],
				'product_' . $row . '_desc',
				$details['desc'],
				'ink_' . $row . '_black',
				($details['black']) ? ' checked="checked"' : '',
				'ink_' . $row . '_black_item',
				$details['black_item'],
				'ink_' . $row . '_black_chip',
				$details['black_chip'],
				'ink_' . $row . '_black_case',
				$details['black_case'],
				'ink_' . $row . '_blue',
				($details['blue']) ? ' checked="checked"' : '',
				'ink_' . $row . '_blue_item',
				$details['blue_item'],
				'ink_' . $row . '_blue_chip',
				$details['blue_chip'],
				'ink_' . $row . '_blue_case',
				$details['blue_case'],
				'ink_' . $row . '_red',
				($details['red']) ? ' checked="checked"' : '',
				'ink_' . $row . '_red_item',
				$details['red_item'],
				'ink_' . $row . '_red_chip',
				$details['red_chip'],
				'ink_' . $row . '_red_case',
				$details['red_case'],
				'ink_' . $row . '_green',
				($details['green']) ? ' checked="checked"' : '',
				'ink_' . $row . '_green_item',
				$details['green_item'],
				'ink_' . $row . '_green_chip',
				$details['green_chip'],
				'ink_' . $row . '_green_case',
				$details['green_case'],
				'ink_' . $row . '_purple',
				($details['purple']) ? ' checked="checked"' : '',
				'ink_' . $row . '_purple_item',
				$details['purple_item'],
				'ink_' . $row . '_purple_chip',
				$details['purple_chip'],
				'ink_' . $row . '_purple_case',
				$details['purple_case'],
				'ink_' . $row . '_yellow',
				($details['yellow']) ? ' checked="checked"' : '',
				'ink_' . $row . '_yellow_item',
				$details['yellow_item'],
				'ink_' . $row . '_yellow_chip',
				$details['yellow_chip'],
				'ink_' . $row . '_yellow_case',
				$details['yellow_case'],
				'ink_' . $row . '_pink',
				($details['pink']) ? ' checked="checked"' : '',
				'ink_' . $row . '_pink_item',
				$details['pink_item'],
				'ink_' . $row . '_pink_chip',
				$details['pink_chip'],
				'ink_' . $row . '_pink_case',
				$details['pink_case'],
				'ink_' . $row . '_white',
				($details['white']) ? ' checked="checked"' : '',
				'ink_' . $row . '_white_item',
				$details['white_item'],
				'ink_' . $row . '_white_chip',
				$details['white_chip'],
				'ink_' . $row . '_white_case',
				$details['white_case'],
				'ink_' . $row . '_multiple',
				($details['multiple']) ? ' checked="checked"' : '',
				'ink_' . $row . '_multiple_item',
				$details['multiple_item'],
				'ink_' . $row . '_multiple_chip',
				$details['multiple_chip'],
				'ink_' . $row . '_multiple_case',
				$details['multiple_case'],
				'ppd_' . $row,
				$details['ppd'],
				'dpc_' . $row,
				$details['dpc'],
				'pccxl_' . $row,
				$details['pccxl'],
				'pdim_' . $row,
				$details['pdim'],
				'cdim_' . $row,
				$details['cdim'],
				'cuft_' . $row,
				$details['cuft'],
				'lbs_' . $row,
				$details['lbs'],
				'coo_' . $row,
				$details['coo']
			];
			$this_sku = str_replace($pct_search, $pct_replace, $sku_template);
			$box_init.= bic_tpl_sku($row, $this_sku);
		}
	}

	echo $box_init;

}

 // Save
 //
 // @param integer $post_id The Wordpress post ID
 // @return mixed The ID (failed save) or updates via hook
function bic_product_info_save($post_id)
{

	// Nonce check?
	if(!isset($_POST['bic_product_info_nonce'])) return $post_id;

	// Nonce verify?
	$nonce = $_POST['bic_product_info_nonce'];
	if(!wp_verify_nonce($nonce, 'bic_product_info_box')) return $post_id;

	// Autosave?
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

	// Permission?
	if(!current_user_can('edit_post', $post_id)) return $post_id;

	// Real post?
	if(!is_numeric($rows = $_POST['sku_count'])) return $post_id;

	// Save it
	$save_info = [];
	$r = 0;
	# Loop based on row count.
	for($i = 0; $i < $rows; $i++)
	{
		# $r is so that only rows that haven't been deleted are saved.
		if($_POST['onoff_' . $i])
		{
			$save_info[$r] = [
				'sku'           => sanitize_text_field($_POST['product_' . $i . '_sku']),
				'desc'          => htmlspecialchars($_POST['product_' . $i . '_desc'], ENT_QUOTES),
				'black'         => (isset($_POST['ink_' . $i . '_black'])) ? ' checked="checked"' : '',
				'black_item'    => (isset($_POST['ink_' . $i . '_black'])) ? sanitize_text_field($_POST['ink_' . $i . '_black_item']) : '',
				'black_chip'    => (isset($_POST['ink_' . $i . '_black'])) ? sanitize_text_field($_POST['ink_' . $i . '_black_chip']) : '',
				'black_case'    => (isset($_POST['ink_' . $i . '_black'])) ? sanitize_text_field($_POST['ink_' . $i . '_black_case']) : '',
				'blue'          => (isset($_POST['ink_' . $i . '_blue'])) ? ' checked="checked"' : '',
				'blue_item'     => (isset($_POST['ink_' . $i . '_blue'])) ? sanitize_text_field($_POST['ink_' . $i . '_blue_item']) : '',
				'blue_chip'     => (isset($_POST['ink_' . $i . '_blue'])) ? sanitize_text_field($_POST['ink_' . $i . '_blue_chip']) : '',
				'blue_case'     => (isset($_POST['ink_' . $i . '_blue'])) ? sanitize_text_field($_POST['ink_' . $i . '_blue_case']) : '',
				'red'           => (isset($_POST['ink_' . $i . '_red'])) ? ' checked="checked"' : '',
				'red_item'      => (isset($_POST['ink_' . $i . '_red'])) ? sanitize_text_field($_POST['ink_' . $i . '_red_item']) : '',
				'red_chip'      => (isset($_POST['ink_' . $i . '_red'])) ? sanitize_text_field($_POST['ink_' . $i . '_red_chip']) : '',
				'red_case'      => (isset($_POST['ink_' . $i . '_red'])) ? sanitize_text_field($_POST['ink_' . $i . '_red_case']) : '',
				'green'         => (isset($_POST['ink_' . $i . '_green'])) ? ' checked="checked"' : '',
				'green_item'    => (isset($_POST['ink_' . $i . '_green'])) ? sanitize_text_field($_POST['ink_' . $i . '_green_item']) : '',
				'green_chip'    => (isset($_POST['ink_' . $i . '_green'])) ? sanitize_text_field($_POST['ink_' . $i . '_green_chip']) : '',
				'green_case'    => (isset($_POST['ink_' . $i . '_green'])) ? sanitize_text_field($_POST['ink_' . $i . '_green_case']) : '',
				'purple'        => (isset($_POST['ink_' . $i . '_purple'])) ? ' checked="checked"' : '',
				'purple_item'   => (isset($_POST['ink_' . $i . '_purple'])) ? sanitize_text_field($_POST['ink_' . $i . '_purple_item']) : '',
				'purple_chip'   => (isset($_POST['ink_' . $i . '_purple'])) ? sanitize_text_field($_POST['ink_' . $i . '_purple_chip']) : '',
				'purple_case'   => (isset($_POST['ink_' . $i . '_purple'])) ? sanitize_text_field($_POST['ink_' . $i . '_purple_case']) : '',
				'yellow'        => (isset($_POST['ink_' . $i . '_yellow'])) ? ' checked="checked"' : '',
				'yellow_item'   => (isset($_POST['ink_' . $i . '_yellow'])) ? sanitize_text_field($_POST['ink_' . $i . '_yellow_item']) : '',
				'yellow_chip'   => (isset($_POST['ink_' . $i . '_yellow'])) ? sanitize_text_field($_POST['ink_' . $i . '_yellow_chip']) : '',
				'yellow_case'   => (isset($_POST['ink_' . $i . '_yellow'])) ? sanitize_text_field($_POST['ink_' . $i . '_yellow_case']) : '',
				'pink'          => (isset($_POST['ink_' . $i . '_pink'])) ? ' checked="checked"' : '',
				'pink_item'     => (isset($_POST['ink_' . $i . '_pink'])) ? sanitize_text_field($_POST['ink_' . $i . '_pink_item']) : '',
				'pink_chip'     => (isset($_POST['ink_' . $i . '_pink'])) ? sanitize_text_field($_POST['ink_' . $i . '_pink_chip']) : '',
				'pink_case'     => (isset($_POST['ink_' . $i . '_pink'])) ? sanitize_text_field($_POST['ink_' . $i . '_pink_case']) : '',
				'white'         => (isset($_POST['ink_' . $i . '_white'])) ? ' checked="checked"' : '',
				'white_item'    => (isset($_POST['ink_' . $i . '_white'])) ? sanitize_text_field($_POST['ink_' . $i . '_white_item']) : '',
				'white_chip'    => (isset($_POST['ink_' . $i . '_white'])) ? sanitize_text_field($_POST['ink_' . $i . '_white_chip']) : '',
				'white_case'    => (isset($_POST['ink_' . $i . '_white'])) ? sanitize_text_field($_POST['ink_' . $i . '_white_case']) : '',
				'multiple'      => (isset($_POST['ink_' . $i . '_multiple'])) ? ' checked="checked"' : '',
				'multiple_item' => (isset($_POST['ink_' . $i . '_multiple'])) ? sanitize_text_field($_POST['ink_' . $i . '_multiple_item']) : '',
				'multiple_chip' => (isset($_POST['ink_' . $i . '_multiple'])) ? sanitize_text_field($_POST['ink_' . $i . '_multiple_chip']) : '',
				'multiple_case' => (isset($_POST['ink_' . $i . '_multiple'])) ? sanitize_text_field($_POST['ink_' . $i . '_multiple_case']) : '',
				'ppd'           => sanitize_text_field($_POST['ppd_' . $i]),
				'dpc'           => sanitize_text_field($_POST['dpc_' . $i]),
				'pccxl'         => sanitize_text_field($_POST['pccxl_' . $i]),
				'pdim'          => sanitize_text_field($_POST['pdim_' . $i]),
				'cdim'          => sanitize_text_field($_POST['cdim_' . $i]),
				'cuft'          => sanitize_text_field($_POST['cuft_' . $i]),
				'lbs'           => sanitize_text_field($_POST['lbs_' . $i]),
				'coo'           => sanitize_text_field($_POST['coo_' . $i])
			];
			# Increment
			$r++;
		}
	}
	# Serialize the array and save it to _product_information.
	$meta_info = serialize($save_info);
	update_post_meta($post_id, 'product_information', $meta_info);

}

 // Execute Save
add_action('add_meta_boxes', 'bic_product_info');
add_action('save_post', 'bic_product_info_save');

/**
 * EDITOR ROLE CHANGE
 *
 * Allows the editor role to change menus.
 *
 * @author MVC <michaelc@drinkcaffeine.com>
 */

 // Add Capability
 //
$role_object = get_role('editor');
if(!$role_object->has_cap('edit_theme_options')) $role_object->add_cap('edit_theme_options');

 // Alter Capabilities
 //
 // @return none Updates via hook
function bic_change_menu_editor()
{
	if(!current_user_can('install_plugins'))
	{
		# Get menus
		global $menu;
		global $submenu;

		# Take out stuff
		remove_submenu_page('themes.php', 'themes.php');
		remove_submenu_page('themes.php', 'customize.php');

		# Add stuff
		$menu[60][0] = 'Menus';
		$submenu['themes.php'][10][0] = 'Edit Menus';

	}
}

 // Execute Role Changes
add_action('admin_head', 'bic_change_menu_editor');
