<?php

namespace Automattic\WooCommerce\StoreApi\Routes\V1\AI;

use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Patterns\PatternUpdater;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;

/**
 * Patterns class.
 *
 * @internal
 */
class Patterns extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ai/patterns';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'ai/patterns';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/ai/patterns';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
				'args'                => [
					'business_description' => [
						'description' => __( 'The business description for a given store.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
					],
					'images'               => [
						'description' => __( 'The images for a given store.', 'woo-gutenberg-products-block' ),
						'type'        => 'object',
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Update patterns with the content and images powered by AI.
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$business_description = sanitize_text_field( wp_unslash( $request['business_description'] ) );

		$ai_connection = new Connection();

		$site_id = $ai_connection->get_site_id();

		if ( is_wp_error( $site_id ) ) {
			return $this->error_to_response( $site_id );
		}

		$token = $ai_connection->get_jwt_token( $site_id );

		$images = $request['images'];

		try {
			( new PatternUpdater() )->generate_content( $ai_connection, $token, $images, $business_description );
			return rest_ensure_response( array( 'ai_content_generated' => true ) );
		} catch ( \WP_Error $e ) {
			return $this->error_to_response( $e );
		}
	}
}
