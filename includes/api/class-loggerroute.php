<?php
/**
 * DecaLog logger read handler
 *
 * Handles all logger reads.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Traffic\API;


use Traffic\System\Role;
use Traffic\Plugin\Feature\Wpcli;
use Traffic\Plugin\Feature\Memory;

/**
 * Define the item operations functionality.
 *
 * Handles all item operations.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class LoggerRoute extends \WP_REST_Controller {

	/**
	 * The acceptable levels.
	 *
	 * @since  2.0.0
	 * @var    array    $bounds    The acceptable levels.
	 */
	protected $bounds = [ 'both', 'inbound', 'outbound' ];

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since  2.0.0
	 */
	public function register_routes() {
		$this->register_route_livelog();
	}

	/**
	 * Register the routes for livelog.
	 *
	 * @since  2.0.0
	 */
	public function register_route_livelog() {
		register_rest_route(
			TRAFFIC_REST_NAMESPACE,
			'livelog',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_livelog' ],
					'permission_callback' => [ $this, 'get_livelog_permissions_check' ],
					'args'                => array_merge( $this->arg_schema_livelog() ),
					'schema'              => [ $this, 'get_schema' ],
				],
			]
		);
	}

	/**
	 * Get the query params for livelog.
	 *
	 * @return array    The schema fragment.
	 * @since  2.0.0
	 */
	public function arg_schema_livelog() {
		return [
			'index'     => [
				'description'       => 'The index to start from.',
				'type'              => 'string',
				'required'          => false,
				'default'           => '0',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'direction' => [
				'description'       => 'The direction to get.',
				'type'              => 'string',
				'enum'              => $this->bounds,
				'required'          => false,
				'default'           => 'both',
				'sanitize_callback' => [ $this, 'sanitize_bound' ],
			],
		];
	}

	/**
	 * Check if a given request has access to get livelogs
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function get_livelog_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			\DecaLog\Engine::eventsLogger( TRAFFIC_SLUG )->warning( 'Unauthenticated API call.', [ 'code' => 401 ] );
			return new \WP_Error( 'rest_not_logged_in', 'You must be logged in to access live logs.', [ 'status' => 401 ] );
		}
		return Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type();
	}

	/**
	 * Sanitization callback for level.
	 *
	 * @param   mixed             $value      Value of the arg.
	 * @param   \WP_REST_Request  $request    Current request object.
	 * @param   string            $param      Name of the arg.
	 * @return  string  The level sanitized.
	 * @since  2.0.0
	 */
	public function sanitize_bound( $value, $request = null, $param = null ) {
		$result = 'both';
		if ( in_array( (string) $value, $this->bounds, true ) ) {
			$result = (string) $value;
		}
		return $result;
	}

	/**
	 * Get a collection of items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_livelog( $request ) {
		if ( '0' === $request['index'] ) {
			$index = array_key_last( Memory::read() );
			if ( ! isset( $index ) ) {
				$index = '0';
			}
			$records = [];
			\DecaLog\Engine::eventsLogger( TRAFFIC_SLUG )->notice( 'Live console launched.' );
		} else {
			$records = Wpcli::records_format( Wpcli::records_filter( Memory::read(), ( 'both' !== $request['direction'] ? [ 'bound' => $request['direction'] ] : [] ), $request['index'] ), 320 );
			$index   = array_key_last( $records );
			if ( ! isset( $index ) ) {
				$index = $request['index'];
			}
		}
		$result          = [];
		$result['index'] = $index;
		$result['items'] = $records;
		return new \WP_REST_Response( $result, 200 );
	}

}