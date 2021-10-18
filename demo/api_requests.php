<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;

// Output the demo as plain text, for easier formatting.
header( 'Content-type: text/plain' );

// Get the wiki URL and OAuth consumer details from the config file.
require_once __DIR__ . '/config.php';

// Make the api.php URL from the OAuth URL.
$apiUrl = preg_replace( '/index\.php.*/', 'api.php', $oauthUrl );

// Configure the OAuth client with the URL and consumer details.
$conf = new ClientConfig( $oauthUrl );
$conf->setConsumer( new Consumer( $consumerKey, $consumerSecret ) );
$conf->setUserAgent( 'DemoApp MediaWikiOAuthClient/1.0' );
$client = new Client( $conf );

// Load the Access Token from the session.
session_start();
$accessToken = new Token( $_SESSION['access_key'], $_SESSION['access_secret'] );

// Example 1: get the authenticated user's identity.
$ident = $client->identify( $accessToken );
echo "You are authenticated as $ident->username.\n\n";

// Example 2: do a simple API call.
$userInfo = json_decode( $client->makeOAuthCall(
	$accessToken,
	"$apiUrl?action=query&meta=userinfo&uiprop=rights&format=json"
) );
echo "== User info ==\n\n";
print_r( $userInfo );

// Example 3: make an edit (getting the edit token first).
$editToken = json_decode( $client->makeOAuthCall(
	$accessToken,
	"$apiUrl?action=query&meta=tokens&format=json"
) )->query->tokens->csrftoken;
$apiParams = [
	'action' => 'edit',
	'title' => 'User:' . $ident->username,
	'section' => 'new',
	'summary' => 'Hello World',
	'text' => 'I am learning to use the <code>mediawiki/oauthclient</code> library.',
	'token' => $editToken,
	'format' => 'json',
];
$editResult = json_decode( $client->makeOAuthCall(
	$accessToken,
	$apiUrl,
	true,
	$apiParams
) );
echo "\n== You made an edit ==\n\n";
print_r( $editResult );
