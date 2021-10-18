<?php

// Require the library and set up the classes we're going to use in this first part.
require_once __DIR__ . '/../vendor/autoload.php';

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;

// Make sure the config file exists. This is just to make sure the demo makes sense if someone loads
// it in the browser without reading the documentation.
$configFile = __DIR__ . '/config.php';
if ( !file_exists( $configFile ) ) {
	echo "Configuration could not be read. Please create $configFile by copying config.dist.php";
	exit( 1 );
}

// Get the wiki URL and OAuth consumer details from the config file.
require_once $configFile;

// Configure the OAuth client with the URL and consumer details.
$conf = new ClientConfig( $oauthUrl );
$conf->setConsumer( new Consumer( $consumerKey, $consumerSecret ) );
$conf->setUserAgent( 'DemoApp MediaWikiOAuthClient/1.0' );
$client = new Client( $conf );

// Send an HTTP request to the wiki to get the authorization URL and a Request Token.
// These are returned together as two elements in an array (with keys 0 and 1).
list( $authUrl, $token ) = $client->initiate();

// Store the Request Token in the session. We will retrieve it from there when the user is sent back
// from the wiki (see demo/callback.php).
session_start();
$_SESSION['request_key'] = $token->key;
$_SESSION['request_secret'] = $token->secret;

// Redirect the user to the authorization URL. This is usually done with an HTTP redirect, but we're
// making it a manual link here so you can see everything in action.
echo "Go to this URL to authorize this demo:<br /><a href='$authUrl'>$authUrl</a>";

// The demo continues in demo/callback.php
