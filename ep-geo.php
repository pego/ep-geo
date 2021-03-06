<?php
/**
 * Plugin Name:     ElasticPress Geo
 * Plugin URI:      https://github.com/thinkshout/ep-geo
 * Description:     Geo query integration for ElasticPress
 * Author:          ThinkShout
 * Author URI:      https://thinkshout.com/
 * Text Domain:     ep-geo
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Ep_Geo
 */

/**
 * Setup all feature filters
 */
function ep_geo_setup() {
	add_filter( 'ep_config_mapping', 'ep_geo_config_mapping' );
	add_filter( 'ep_post_sync_args', 'ep_geo_post_sync_args', 10, 2 );
	add_filter( 'ep_formatted_args', 'ep_geo_formatted_args', 10, 2 );
}

/**
 * Alter ES index to add location property.
 *
 * @param array $mapping
 *
 * @return array
 */
function ep_geo_config_mapping( $mapping ) {
	$mapping['mappings']['post']['properties']['pin'] = array(
		'properties' => array(
			'location' => array(
				'type' => 'geo_point',
				'ignore_malformed' => true,
			),
		),
	);

	return $mapping;
}

/**
 * Alter ES sync data to post geo_points.
 *
 * @param array $post_args
 * @param int $post_id
 *
 * @return array
 */
function ep_geo_post_sync_args( $post_args, $post_id ) {
	if ( isset( $post_args['post_meta']['latitude'][0] ) ) {
		$post_args['pin']['location']['lat'] = $post_args['post_meta']['latitude'][0];
	}

	if ( isset( $post_args['post_meta']['longitude'][0] ) ) {
		$post_args['pin']['location']['lon'] = $post_args['post_meta']['longitude'][0];
	}

	return $post_args;
}

/**
 * Alter formatted WP query args for geo filter.
 *
 * @param array $formatted_args
 * @param array $args
 *
 * @return array
 */
function ep_geo_formatted_args( $formatted_args, $args ) {
	if ( isset( $args['geo_query'] ) ) {
		$geo_distance = array();

		if ( isset( $args['geo_query']['distance'] ) ) {
			$geo_distance['distance'] = $args['geo_query']['distance'];
		}

		if ( isset( $args['geo_query']['lat'] ) ) {
			$geo_distance['pin.location']['lat'] = $args['geo_query']['lat'];
		}

		if ( isset( $args['geo_query']['lon'] ) ) {
			$geo_distance['pin.location']['lon'] = $args['geo_query']['lon'];
		}

		$formatted_args['post_filter']['bool']['filter']['geo_distance'] = $geo_distance;

		if ( isset( $args['geo_query']['order'] ) ) {
			array_unshift( $formatted_args['sort'], array(
				'_geo_distance' => array(
					'pin.location' => $geo_distance['pin.location'],
					'order' => $args['geo_query']['order'],
				),
			) );
		}
	}

	return $formatted_args;
}

/**
 * Output feature box summary
 */
function ep_geo_box_summary() {
	echo '<p>' . esc_html_e( 'Integrate geo location data with ElasticSearch, and enable geo queries.', 'ep-geo' ) . '</p>';
}

/**
 * Output feature box long
 */
function ep_geo_box_long() {
	echo '<p>' . esc_html_e( 'Important note: Your geolocation data must be stored in post meta fields named "latitude" and "longitude". They should be plain text fields with lat/lon represented as floats.', 'ep-geo' ) . '</p>';
}

ep_register_feature( 'ep_geo', array(
	'title'                     => 'Geo',
	'setup_cb'                  => 'ep_geo_setup',
	'feature_box_summary_cb'    => 'ep_geo_box_summary',
	'feature_box_long_cb'       => 'ep_geo_box_long',
	'requires_install_reindex'  => true,
) );
