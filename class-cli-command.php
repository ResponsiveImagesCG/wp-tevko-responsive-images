<?php

/**
 * Responsive Images CLI commands
 */
class ResponsiveImagesCLI extends WP_CLI_Command {
	/**
	 * Convert all existing <img> tags to use srcset.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Don't save anything, just return stats about potential changes
	 *
	 * @subcommand add-srcset
	 */
	function add_srcset( $args, $assoc_args ) {
		WP_CLI::success( "Command works!" );
	}
}

WP_CLI::add_command( 'respimg', 'ResponsiveImagesCLI' );
