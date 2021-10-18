<?php

// Require the library and set up the classes we're going to use in this second part of the demo.
require_once __DIR__ . '/../vendor/autoload.php';

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;

if ( !isset( $_GET['oauth_verifier'] ) ) {
	echo "This page should only be access after redirection back from the wiki.";
	exit( 1 );
}

// Get the wiki URL and OAuth consumer details from the config file.
require_once __DIR__ . '/config.php';

// Configure the OAuth client with the URL and consumer details.
$conf = new ClientConfig( $oauthUrl );
$conf->setConsumer( new Consumer( $consumerKey, $consumerSecret ) );
$conf->setUserAgent( 'DemoApp MediaWikiOAuthClient/1.0' );
$client = new Client( $conf );

// Get the Request Token's details from the session and create a new Token object.
session_start();
$requestToken = new Token( $_SESSION['request_key'], $_SESSION['request_secret'] );

// Send an HTTP request to the wiki to retrieve an Access Token.
$accessToken = $client->complete( $requestToken,  $_GET['oauth_verifier'] );

// At this point, the user is authenticated, and the access token can be used to make authenticated
// API requests to the wiki. You can store the Access Token in the session or other secure
// user-specific storage and re-use it for future requests.
$_SESSION['access_key'] = $accessToken->key;
$_SESSION['access_secret'] = $accessToken->secret;

// You also no longer need the Request Token.
unset( $_SESSION['request_key'], $_SESSION['request_secret'] );

// The demo continues in demo/api_requests.php
echo "Continue to <a href='api_requests.php'>api_requests.php</a>";
