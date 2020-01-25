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
use MediaWiki\OAuthClient\SignatureMethod\HmacSha1;
use MediaWiki\OAuthClient\Token;

/**
 * @covers \MediaWiki\OAuthClient\SignatureMethod\HmacSha1
 */
class HmacSha1Test extends \PHPUnit\Framework\TestCase {
	private $method;

	public function setUp() : void {
		$this->method = new HmacSha1();
	}

	public function testIdentifyAsHmacSha1() {
		$this->assertEquals( 'HMAC-SHA1', $this->method->getName() );
	}

	public function testBuildSignature() {
		// Tests taken from http://wiki.oauth.net/TestCases section 9.2 ( "HMAC-SHA1" )
		$request = $this->mockRequest( 'bs' );
		$consumer = new Consumer( '__unused__', 'cs' );
		$token = null;
		$this->assertEquals( 'egQqG5AJep5sJ7anhXju1unge2I=',
			$this->method->buildSignature( $request, $consumer, $token ) );

		$request  = $this->mockRequest( 'bs' );
		$consumer = new Consumer( '__unused__', 'cs' );
		$token    = new Token( '__unused__', 'ts' );
		$this->assertEquals( 'VZVjXceV7JgPq/dOTnNmEfO0Fv8=',
			$this->method->buildSignature( $request, $consumer, $token ) );

		$request  = $this->mockRequest(
			'GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacation.jpg%26'
			. 'oauth_consumer_key%3Ddpf43f3p2l4k3l03%26'
			. 'oauth_nonce%3Dkllo9940pd9333jh%26'
			. 'oauth_signature_method%3DHMAC-SHA1%26'
			. 'oauth_timestamp%3D1191242096%26'
			. 'oauth_token%3Dnnch734d00sl2jdk%26'
			. 'oauth_version%3D1.0%26size%3Doriginal' );
		$consumer = new Consumer( '__unused__', 'kd94hf93k423kf44' );
		$token    = new Token( '__unused__', 'pfkkdhi9sl3r4s00' );
		$this->assertEquals( 'tR3+Ty81lMeYAr/Fid0kMTYa/WM=',
			$this->method->buildSignature( $request, $consumer, $token ) );
	}

	public function testVerifySignature() {
		// Tests taken from http://wiki.oauth.net/TestCases section 9.2
		// ( "HMAC-SHA1" )
		$request   = $this->mockRequest( 'bs' );
		$consumer  = new Consumer( '__unused__', 'cs' );
		$token     = null;
		$signature = 'egQqG5AJep5sJ7anhXju1unge2I=';
		$this->assertTrue( $this->method->checkSignature(
			$request, $consumer, $token, $signature ) );

		$request   = $this->mockRequest( 'bs' );
		$consumer  = new Consumer( '__unused__', 'cs' );
		$token     = new Token( '__unused__', 'ts' );
		$signature = 'VZVjXceV7JgPq/dOTnNmEfO0Fv8=';
		$this->assertTrue( $this->method->checkSignature(
			$request, $consumer, $token, $signature ) );

		$request   = $this->mockRequest(
			'GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacation.jpg%26'
			. 'oauth_consumer_key%3Ddpf43f3p2l4k3l03%26'
			. 'oauth_nonce%3Dkllo9940pd9333jh%26'
			. 'oauth_signature_method%3DHMAC-SHA1%26'
			. 'oauth_timestamp%3D1191242096%26'
			. 'oauth_token%3Dnnch734d00sl2jdk%26'
			. 'oauth_version%3D1.0%26size%3Doriginal' );
		$consumer  = new Consumer( '__unused__', 'kd94hf93k423kf44' );
		$token     = new Token( '__unused__', 'pfkkdhi9sl3r4s00' );
		$signature = 'tR3+Ty81lMeYAr/Fid0kMTYa/WM=';
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
