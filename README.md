# Errors

*Error Handling module for Kohana 3.x*

- **Module Version:** 0.9.0
- **Module URL:** <http://github.com/synapsestudios/kohana-errors>
- **Compatible Kohana Version(s):** 3.1.x

## Description

The Error module allows customization of error handling by overriding Kohana's 
default exception handler.  With the module you can configure options for 
logging, emailing, and displaying errors.  You can also use different 
configuration settings for specific types of errors or exceptions.

## Requirements

- [Email Module](http://github.com/synapsestudios/kohana-email)

## Installation

1. Add the following lines to bootstrap.php before the call to Kohana::init()  
    register_shutdown_function(function(){Error::shutdown_handler();});
