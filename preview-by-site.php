<?php
/**
 * Plugin Name:       Preview by Site
 * Description:       Proof of Concept 'preview by site' functionality, to allow template previewing across a network.
 * Requires at least: 5.9
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Matt Watson
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       preview-by-site
 *
 * @package           preview-by-site
 */

namespace PoC\Preview_By_site;

use WP_Block_Template;
use WP_Query;

const PLUGIN_PREFIX = 'preview_by_site';
const PLUGIN_SLUG   = 'preview-by-site';
const ROOT_DIR      = __DIR__;
const ROOT_FILE     = __FILE__;

/**
 * Enqueue Block Editor Assets.
 *
 * @throws \Error Throws an error if the assets are not compiled.
 *
 * @return void
 */
function enqueue_block_editor_assets() : void {

	$block_editor_asset_path = ROOT_DIR . '/build/index.asset.php';

	if ( ! file_exists( $block_editor_asset_path ) ) {
		throw new \Error(
			esc_html__( 'You need to run `npm start` or `npm run build` in the root of the plugin "preview-by-site" first.', 'preview-by-site' )
		);
	}

	$block_editor_scripts = '/build/index.js';
	$script_asset         = include $block_editor_asset_path;

	/**
	 * Settings.
	 *
	 * Settings have a filter so other parts of the plugin can append settings.
	 */
	$block_settings = apply_filters( PLUGIN_PREFIX . '_block_settings', get_block_settings() );

	wp_enqueue_script(
		PLUGIN_SLUG . '-block-editor',
		plugins_url( $block_editor_scripts, ROOT_FILE ),
		$script_asset['dependencies'],
		$script_asset['version'],
		false
	);

	wp_localize_script(
		PLUGIN_SLUG . '-block-editor',
		'templateSidebarSettings',
		$block_settings
	);

	wp_set_script_translations(
		PLUGIN_SLUG . '-block-editor',
		'preview-by-site',
		ROOT_DIR . '\languages'
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_block_editor_assets', 10 );

/**
 * Get Block Settings.
 *
 * Settings for the block.
 */
function get_block_settings() : array {

	/**
	 * PoC Only.
	 *
	 * This is a quick and dirty hard coded way to get a specific post
	 * preview link. In the final version the preview link would be
	 * generated after you choose the post.
	 */
	switch_to_blog( 4 );
	$preview_link = get_preview_post_link( 36 );
	restore_current_blog();

	return [
		'previewLink' => (array) $preview_link,
	];
}

/**
 * Load Preview Template.
 *
 * This filters the loaded template and replaces it with the
 * template we are previewing.
 *
 * @param  array  $templates     Templates.
 * @param  object $query         WP_Query.
 * @param  string $template_type Post Type.
 *
 * @return void
 */
function load_preview_template( $templates, $query, $template_type ) {

	// Validation and sanitation needed.
	if ( ! isset( $_GET['preview_template'] ) ) {
		return $templates;
	}

	if ( $template_type !== 'wp_template' ) {
		return $templates;
	}

	// PoC only, we would have settings to determine which is the 'control' site.
	switch_to_blog( 1 );

	// Validation and sanitation needed.
	$parts = explode( '//', $_GET['preview_template'] );

	$preview_query = new WP_Query([
		'post_type' => 'wp_template',
		'name' => $parts[1],
	]);

	// More error checking needed.
	$preview_template = $preview_query->posts[0];

	restore_current_blog();

	$template                 = new WP_Block_Template();
	$template->id             = $parts[0] . '//' . $preview_template->post_name;
	$template->theme          = $parts[0];
	$template->content        = $preview_template->post_content;
	$template->slug           = $preview_template->post_name;
	$template->source         = 'theme';
	$template->type           = $template_type;
	$template->title          = $preview_template->post_title;
	$template->status         = 'publish';
	$template->has_theme_file = true;
	$template->is_custom      = true;

	return [ $template ];
}
add_filter( 'pre_get_block_templates', __NAMESPACE__ . '\\load_preview_template', 100, 3 );

/**
 * Filter Template Parts.
 *
 * When previewing a template, we need to make sure we have all of its
 * template parts.
 *
 * This will filter the existing template part query results, and insert
 * template parts from the control site.
 *
 * @param  array  $posts Posts.
 * @param  object $query WP_Query.
 *
 * @return void
 */
function filter_template_parts( $posts, $query ) {

	// Validation and sanitation needed.
	if ( ! isset( $_GET['preview_template'] ) ) {
		return $posts;
	}

	if ( $query->query_vars['post_type'] !== 'wp_template_part' ) {
		return $posts;
	}

	// PoC only, we would have settings to determine which is the 'control' site.
	switch_to_blog( 1 );

	// Avoid infinite loops.
	remove_filter( 'posts_results', __NAMESPACE__ . '\\filter_template_parts' , 100, 2 );

	// Get one template part per query (query by post name)
	$template_part_query = new WP_Query(
		array(
			'post_type'      => 'wp_template_part',
			'post_status'    => 'publish',
			'post_name__in'  => $query->query_vars['post_name__in'],
			'tax_query'      => array(
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'slug',
					'terms'    => $query->query_vars['tax_query'][0]['terms'],
				),
			),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);

	restore_current_blog();

	// Put the filter back in.
	add_filter( 'posts_results', __NAMESPACE__ . '\\filter_template_parts' , 100, 2 );

	// Merge the results.
	return array_merge( $posts, $template_part_query->posts );
}
add_filter( 'posts_results', __NAMESPACE__ . '\\filter_template_parts' , 100, 2 );
