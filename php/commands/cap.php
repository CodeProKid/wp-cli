<?php

/**
 * Manage user capabilities.
 *
 * ## EXAMPLES
 *
 *     # Add 'spectate' capability to 'author' role
 *     $ wp cap add 'author' 'spectate'
 *     Success: Added 1 capabilities to 'author' role.
 *
 *     # Add all caps from 'editor' role to 'author' role
 *     $ wp cap list 'editor' | xargs wp cap add 'author'
 *     Success: Added 24 capabilities to 'author' role.
 *
 *     # Remove all caps from 'editor' role that also appear in 'author' role
 *     $ wp cap list 'author' | xargs wp cap remove 'editor'
 *     Success: Removed 34 capabilities from 'editor' role.
 */
class Capabilities_Command extends WP_CLI_Command {

	private $fields = array(
		'name'
	);

	/**
	 * List capabilities for a given role.
	 *
	 * ## OPTIONS
	 *
	 * <role>
	 * : Key for the role.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count, yaml. Default: table
	 *
	 * ## EXAMPLES
	 *
	 *     # Display alphabetical list of Contributor capabilities
	 *     $ wp cap list 'contributor' | sort
	 *     delete_posts
	 *     edit_posts
	 *     level_0
	 *     level_1
	 *     read
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$role_obj = self::get_role( $args[0] );

		$output_caps = array();
		foreach ( array_keys( $role_obj->capabilities ) as $cap ) {
			$output_cap = new stdClass;

			$output_cap->name = $cap;

			$output_caps[] = $output_cap;
		}
		$formatter = new \WP_CLI\Formatter( $assoc_args, $this->fields );
		$formatter->display_items( $output_caps );
	}

	/**
	 * Add capabilities to a given role.
	 *
	 * ## OPTIONS
	 *
	 * <role>
	 * : Key for the role.
	 *
	 * <cap>...
	 * : One or more capabilities to add.
	 *
	 * ## EXAMPLES
	 *
	 *     # Add 'spectate' capability to 'author' role
	 *     $ wp cap add author spectate
	 *     Success: Added 1 capabilities to 'author' role.
	 */
	public function add( $args ) {
		self::persistence_check();

		$role = array_shift( $args );

		$role_obj = self::get_role( $role );

		$count = 0;

		foreach ( $args as $cap ) {
			if ( $role_obj->has_cap( $cap ) )
				continue;

			$role_obj->add_cap( $cap );

			$count++;
		}

		WP_CLI::success( sprintf( "Added %d capabilities to '%s' role." , $count, $role ) );
	}

	/**
	 * Remove capabilities from a given role.
	 *
	 * ## OPTIONS
	 *
	 * <role>
	 * : Key for the role.
	 *
	 * <cap>...
	 * : One or more capabilities to remove.
	 *
	 * ## EXAMPLES
	 *
	 *     # Remove 'spectate' capability from 'author' role
	 *     $ wp cap remove author spectate
	 *     Success: Removed 1 capabilities from 'author' role.
	 */
	public function remove( $args ) {
		self::persistence_check();

		$role = array_shift( $args );

		$role_obj = self::get_role( $role );

		$count = 0;

		foreach ( $args as $cap ) {
			if ( !$role_obj->has_cap( $cap ) )
				continue;

			$role_obj->remove_cap( $cap );

			$count++;
		}

		WP_CLI::success( sprintf( "Removed %d capabilities from '%s' role." , $count, $role ) );
	}

	private static function get_role( $role ) {
		global $wp_roles;

		$role_obj = $wp_roles->get_role( $role );

		if ( !$role_obj )
			WP_CLI::error( "'$role' role not found." );

		return $role_obj;
	}

	private static function persistence_check() {
		global $wp_roles;

		if ( !$wp_roles->use_db )
			WP_CLI::error( "Role definitions are not persistent." );
	}
}

WP_CLI::add_command( 'cap', 'Capabilities_Command' );

