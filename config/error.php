<?php defined('SYSPATH') OR die('No direct access allowed.');

return array(
	// EMAIL SETTINGS
	'email' => array(
		'to'   => 'dev@synapsestudios.com',
		'from' => array('error@synapsestudios.com', 'ERROR!'),
	),

	// ERROR HANDLING SETTINGS
	'_default' => array(
		/**
		 * LOGGING
		 *
		 * If `log` is TRUE, then the error will be logged. If FALSE, then it
		 * will not be logged.
		 */
		'log' => TRUE,

		/**
		 * EMAIL
		 *
		 * If `email` is TRUE, then the default email will be sent. If FALSE,
		 * no email will be sent. If it is a string, then the string will
		 * be treated as a path to a view which will replace the default email.
		 */
		'email' => FALSE,

		/**
		 * ACTION
		 *
		 * If `action` is not an array or has an invalid or missing type, then
		 * the error will be displayed just like the normal
		 * `Kohana::exception_handler`. If it is an array, then the specified
		 * action will be taken with the options specified.
		 */
		'action' => array(
			/**
			 * EXAMPLE: "display"
			 *
			 * 'type'    => 'display',
			 * 'options' => array(
			 * 		// View used to replace the default error display
			 * 		'view'  => 'errors/_default',
			 * ),
			 */
			
			/**
			 * EXAMPLE: "callback"
			 *
			 * 'type'    => 'callback',
			 * 'options' => array(
			 * 		// Callback to apply to the error (uses `Arr::callback` syntax)
			 * 		'callback' => 'Error::demo_callback',
			 * ),
			 */
			
			/**
			 * EXAMPLE: "redirect"
			 *
			 * 'type'    => 'redirect',
			 * 'options' => array(
			 * 		// This is where the user will be redirected to
			 * 		'url'     => 'welcome/index',
			 * 		// The message to be sent as a Notice (requires Notices module)
			 * 		'message' => 'There was an error which prevented the page you requested from being loaded.',
			 * ),
			*/
		),
	),
);
