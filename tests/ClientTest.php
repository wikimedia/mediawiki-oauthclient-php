<?php

namespace MediaWiki\OAuthClient\Test;

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \MediaWiki\OAuthClient\Client
 */
class ClientTest extends TestCase {

	/**
	 * Get an instance of Client with the given method made public.
	 * @param $methodName
	 * @return array
\	 */
	protected function getMethodToTest( $methodName ) {
		$client = new Client( new ClientConfig( 'https://example.com/' ) );
		$reflector = new ReflectionClass( $client );
		$method = $reflector->getMethod( $methodName );
		$method->setAccessible( true );
		return [ $client, $method ];
	}

	/**
	 * Test that a JSON Web Token that doesn't contain the required three parts throws a meaningful
	 * exception message.
	 */
	public function testDecodeJwtInvalid() {
		list( $client, $method ) = $this->getMethodToTest( 'decodeJWT' );
		static::expectExceptionMessage( 'JWT has incorrect format. Received: incorrect-jwt-string' );
		$method->invokeArgs( $client, [ 'incorrect-jwt-string', '' ] );
	}

	/**
	 * Test that non-base64 strings throw an exception.
	 */
	public function testUrlsafeB64Decode() {
		list( $client, $method ) = $this->getMethodToTest( 'urlsafeB64Decode' );
		static::expectExceptionMessage( 'Unable to decode base64 value: #non base64#' );
		$method->invokeArgs( $client, [ '#non base64#' ] );
	}
}
