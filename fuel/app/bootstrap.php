<?php

// Bootstrap the framework DO NOT edit this
require_once COREPATH.'bootstrap.php';

/**
 * Set the user agent so that we don't get arbitrarily blocked as a spambot.
 */
$tweetbeagle_ua = 'Mozilla/5.0 (TweetBeagle; http://app.tweetbeagle.com/)';
ini_set('user_agent', $tweetbeagle_ua);

Autoloader::add_classes(array(
	// Add classes you want to override here
	// Example: 'View' => APPPATH.'classes/view.php',
	'Html'                => APPPATH.'classes/html.php',
	'Str'                 => APPPATH.'classes/str.php',
	'Controller_Template' => APPPATH.'classes/controller_template.php',
));

// Register the autoloader
Autoloader::register();

// Initialize the framework with the config file.
Fuel::init(include(APPPATH.'config/config.php'));


/* End of file bootstrap.php */