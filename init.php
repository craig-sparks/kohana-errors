<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Set the error handler to use the Error module
 */
set_error_handler(array('Error', 'handle_error'));

/**
 * Set the exception handler to use the Error module
 */
set_exception_handler(array('Error', 'handle_exception'));

/**
 * ADD THE FOLLOWING LINES TO THE BOOTSTRAP BEFORE THE CALL TO Kohana::init()
 * -----------------------------------------------------------------------------
 * // Register the Error module's shutdown function before Kohana's
 * function custom_shutdown_handler()
 * {
 *	if (method_exists('Error', 'handle_shutdown'))
 * 	{
 * 		Error::handle_shutdown();
 * 	}
 * }
 *
 * register_shutdown_function('custom_shutdown_handler');
 */