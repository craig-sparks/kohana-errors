<?php

class Error {

	public $type    = NULL;
	public $code    = NULL;
	public $message = NULL;
	public $file    = NULL;
	public $line    = NULL;
	public $text    = NULL;
	public $trace   = array();
	public $display = NULL;

	/**
	 * Replaces Kohana's `Kohana::exception_handler()` method. This does the
	 * same thing, but also adds email functionality and the ability to perform
	 * an action in response to the exception. These actions and emails are
	 * customizable per type in the config file for this module.
	 *
	 * @uses    Kohana::exception_text
	 * @param   object   exception object
	 * @return  boolean
	 */
	public static function handler(Exception $e)
	{
		try
		{
			$error = new Error();

			// Get the exception information
			$error->type    = get_class($e);
			$error->code    = $e->getCode();
			$error->message = $e->getMessage();
			$error->file    = $e->getFile();
			$error->line    = $e->getLine();

			// Create a text version of the exception
			$error->text = Kohana::exception_text($e);

			if (Kohana::$is_cli)
			{
				// Just display the text of the exception
				echo "\n{$error->text}\n";

				return TRUE;
			}

			// Get the exception backtrace
			$error->trace = $e->getTrace();

			if ($e instanceof ErrorException)
			{
				if (isset(Kohana::$php_errors[$error->code]))
				{
					// Use the human-readable error name
					$error->code = Kohana::$php_errors[$error->code];
				}

				if (version_compare(PHP_VERSION, '5.3', '<'))
				{
					// Workaround for a bug in ErrorException::getTrace() that exists in
					// all PHP 5.2 versions. @see http://bugs.php.net/bug.php?id=45895
					for ($i = count($error->trace) - 1; $i > 0; --$i)
					{
						if (isset($error->trace[$i - 1]['args']))
						{
							// Re-position the args
							$error->trace[$i]['args'] = $error->trace[$i - 1]['args'];

							// Remove the args
							unset($error->trace[$i - 1]['args']);
						}
					}
				}
			}

			if ( ! headers_sent())
			{
				// Make sure the proper content type is sent with a 500 status
				header('Content-Type: text/html; charset='.Kohana::$charset, TRUE, 500);
			}

			// Get the contents of the output buffer
			$error->display = $error->render();

			// Log the error
			$error->log();

			// Email the error
			$error->email();

			// Respond to the error
			$error->action();

			return TRUE;
		}
		catch (Exception $e)
		{
			// Clean the output buffer if one exists
			ob_get_level() and ob_clean();

			// Display the exception text
			echo Kohana::exception_text($e), "\n";

			// Exit with an error status
			exit(1);
		}
	}

	/**
	 * Replace Kohana's `Kohana::shutdown_handler()` method with one that will
	 * use our error handler. This is to catch errors that are not normally
	 * caught by the error handler, such as E_PARSE.
	 *
	 * @uses    Error::handler
	 * @return  void
	 */
	public static function shutdown_handler()
	{
		if (Kohana::$errors AND $error = error_get_last() AND (error_reporting() & $error['type']))
		{
			// If an output buffer exists, clear it
			ob_get_level() and ob_clean();

			// Fake an exception for nice debugging
			Error::handler(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));

			// Shutdown now to avoid a "death loop"
			exit(1);
		}
	}

	/**
	 * Retrieves the config settings for the exception type, and cascades down
	 * to the _default settings if there is nothing relavant to the type.
	 *
	 * @param   string  $key      The config key
	 * @param   mixed   $default  A default value to return
	 * @return  mixed
	 */
	public function config($key, $default = NULL)
	{
		$config = Kohana::config('error.'.$this->type.'.'.$key);
		$config = $config ? $config : Kohana::config('error._default.'.$key);
		return $config ? $config : $default;
	}

	/**
	 * Renders an error with a view file. The view library is not used because
	 * there is a chance that it will fail within this context.
	 *
	 * @param   string  $view  The view file
	 * @return  string
	 */
	public function render($view = 'kohana/error')
	{
		// Start an output buffer
		ob_start();

		// Import error variables into View's scope
		$error = get_object_vars($this);
		unset($error['display']);
		extract($error);

		// Include the exception HTML
		include Kohana::find_file('views', $view);

		// Get the contents of the output buffer
		return ob_get_clean();
	}

	/**
	 * Performs the logging is enabled
	 */
	public function log()
	{
		if ($this->config('log', TRUE) AND is_object(Kohana::$log))
		{
			Kohana::$log->add(Kohana::ERROR, $this->text);
		}
	}

	/**
	 * Sends the email if enabled
	 *
	 * @return  void
	 */
	public function email()
	{
		$content = $this->display;

		$config = $this->config('email', FALSE);

		if ( ! $config)
		{
			return;
		}
		elseif (is_string($config))
		{
			$content = $this->render($config);
		}

		$email_available = (class_exists('Email') AND method_exists('Email', 'send'));
		if ( ! $email_available)
		{
			throw new Exception('The email functionality of the Synapse Studios Error module requires the Synapse Studios Email module.');
		}

		$success = Email::send(
			Kohana::config('error.email.to'),
			Kohana::config('error.email.from'),
			'Error: '.$this->type,
			$content,
			TRUE
		);

		if ( ! $success)
		{
			throw new Exception('The error email failed to be sent.');
		}
	}

	/**
	 * Performs the action set in configuration
	 *
	 * @return  boolean
	 */
	public function action()
	{
		$type = '_action_'.$this->config('action.type', NULL);
		$options = $this->config('action.options', array());
		$this->$type($options);
		return TRUE;
	}

	/**
	 * Redirects the user upon error
	 *
	 * @param   array  $options  Options from config
	 * @return  void
	 */
	protected function _action_redirect(array $options = array())
	{
		if ($this->code === 'Parse Error')
		{
			echo '<p><strong>NOTE:</strong> Cannot redirect on a parse error, because it might cause a redirect loop.</p>';
			echo $this->display;
			return;
		}

		$notices_available = (class_exists('Notices') AND method_exists('Notices', 'add'));
		$message = Arr::get($options, 'message', FALSE);
		if ($notices_available AND $message)
		{
			Notices::add('error', $message);
		}

		$url = Arr::get($options, 'url');
		if (strpos($url, '://') === FALSE)
		{
			// Make the URI into a URL
			$url = URL::site($url, TRUE);
		}
		header("Location: $url", TRUE);
		exit;
	}

	/**
	 * Displays the error
	 *
	 * @param   array  $options  Options from config
	 * @return  void
	 */
	protected function _action_display(array $options = array())
	{
		$view = Arr::get($options, 'view', 'errors/_default');

		$this->display = $this->render($view);

		echo $this->display;
	}

	/**
	 * Performs a callback on the error before displaying
	 *
	 * @param   array  $options  Options from config
	 * @return  void
	 */
	protected function _action_callback(array $options = array())
	{
		$callback = Arr::get($options, 'callback');
		@list($method,) = Arr::callback($callback);
		if (is_callable($method))
		{
			call_user_func($method, $this);
		}

		echo $this->display;
	}

	/**
	 * CatchAll for actions. Just displays the error.
	 *
	 * @param  string  $method
	 * @param  array   $args
	 */
	public function __call($method, $args)
	{
		echo $this->display;
	}

	/**
	 * This is a demo callback that serves an example for how to use the
	 * callback action type.
	 *
	 * @param  object  $error  The error object
	 */
	public static function demo_callback($error)
	{
		$error->display = '<p>THERE WAS AN ERROR!</p>';
	}

}