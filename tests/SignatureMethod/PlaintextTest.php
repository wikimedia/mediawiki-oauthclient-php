<?php
/**
 * @section LICENSE
 * Copyright (c) 2007 Andy Smith
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including without
 * limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to
 * whom the Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @file
 */

namespace MediaWiki\OAuthClient\Test\SignatureMethod;

use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\SignatureMethod\Plaintext;
use MediaWiki\OAuthClient\Token;

/**
 * @covers \MediaWiki\OAuthClient\SignatureMethod\Plaintext
 */
class PlaintextTest extends \PHPUnit\Framework\TestCase {
	private $method;

	public function setUp() {
		$this->method = new Plaintext();
	}

	public function testIdentifyAsPlaintext() {
		$this->assertEquals( 'PLAINTEXT', $this->method->getName() );
	}

	public function testBuildSignature() {
		// Tests based on from http://wiki.oauth.net/TestCases section 9.2 ( "HMAC-SHA1" )
		$request  = $this->mockRequest( '__unused__' );
		$consumer = new Consumer( '__unused__', 'cs' );
		$token = null;
		$this->assertEquals( 'cs&', $this->method->buildSignature( $request, $consumer, $token ) );

		$request  = $this->mockRequest( '__unused__' );
		$consumer = new Consumer( '__unused__', 'cs' );
		$token    = new Token( '__unused__', 'ts' );
		$this->assertEquals( 'cs&ts', $this->method->buildSignature( $request, $consumer, $token ) );

		$request  = $this->mockRequest( '__unused__' );
		$consumer = new Consumer( '__unused__', 'kd94hf93k423kf44' );
		$token    = new Token( '__unused__', 'pfkkdhi9sl3r4s00' );
		$this->assertEquals( 'kd94hf93k423kf44&pfkkdhi9sl3r4s00',
			$this->method->buildSignature( $request, $consumer, $token ) );

		// Tests taken from Chapter 9.4.1 ( "Generating Signature" ) from the spec
		$request  = $this->mockRequest( '__unused__' );
		$consumer = new Consumer( '__unused__', 'djr9rjt0jd78jf88' );
		$token    = new Token( '__unused__', 'jjd999tj88uiths3' );
		$this->assertEquals( 'djr9rjt0jd78jf88&jjd999tj88uiths3',
			$this->method->buildSignature( $request, $consumer, $token ) );

		$request  = $this->mockRequest( '__unused__' );
		$consumer = new Consumer( '__unused__', 'djr9rjt0jd78jf88' );
		$token    = new Token( '__unused__', 'jjd99$tj88uiths3' );
		$this->assertEquals( 'djr9rjt0jd78jf88&jjd99%24tj88uiths3',
			$this->method->buildSignature( $request, $consumer, $token ) );
	}

	public function testVerifySignature() {
		// Tests based on from http://wiki.oauth.net/TestCases section 9.2 ( "HMAC-SHA1" )
		$request = $this->mockRequest( '__unused__' );
		$consumer = new Consumer( '__unused__', 'cs' );
		$token = null;
		$signature = 'cs&';
		$this->assertTrue( $this->method->checkSignature(
			$request, $consumer, $token, $signature ) );

		$request   = $this->mockRequest( '__unused__' );
		$consumer  = new Consumer( '__unused__', 'cs' );
		$token     = new Token( '__unused__', 'ts' );
		$signature = 'cs&ts';
		$this->assertTrue( $this->method->checkSignature(
			$request, $consumer, $token, $signature ) );

		$request   = $this->mockRequest( '__unused__' );
		$consumer  = new Consumer( '__unused__', 'kd94hf93k423kf44' );
		$token     = new Token( '__unused__', 'pfkkdhi9sl3r4s00' );
		$signature = 'kd94hf93k423kf44&pfkkdhi9sl3r4s00';
		$this->assertTrue( $this->method->checkSignature(
			$request, $consumer, $token, $signature ) );

		// Tests taken from Chapter 9.4.1 ( "Generating Signature" ) from the
		// spec
		$request   = $this->mockRequest( '__unused__' );
		$consumer  = new Consumer( '__unused__', 'djr9rjt0jd78jf88' );
		$token     = new Token( '__unused__', 'jjd999tj88uiths3' );
		$signature = 'djr9rjt0jd78jf88&jjd999tj88uiths3';
		$this->assertTrue( $this->method->checkSignature(
			$request, $consumer, $token, $signature ) );

		$request   = $this->mockRequest( '__unused__' );
		$consumer  = new Consumer( '__unused__', 'djr9rjt0jd78jf88' );
		$token     = new Token( '__unused__', 'jjd99$tj88uiths3' );
		$signature = 'djr9rjt0jd78jf88&jjd99%24tj88uiths3';
		$this->assertTrue( $this->method->checkSignature(
			$request, $consumer, $token, $signature ) );
	}

	protected function mockRequest( $baseStr ) {
		$stub = $this->getMockBuilder( 'MediaWiki\OAuthClient\Request' )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'getSignatureBaseString' )
			->willReturn( $baseStr );
		return $stub;
	}
}
