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

namespace MediaWiki\OAuthClient\Test;

use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Request;
use MediaWiki\OAuthClient\SignatureMethod\HmacSha1;
use MediaWiki\OAuthClient\SignatureMethod\Plaintext;
use MediaWiki\OAuthClient\Token;

/**
 * @covers \MediaWiki\OAuthClient\Request
 */
class RequestTest extends \PHPUnit\Framework\TestCase {

	public function testCanGetSingleParameter() {
		$request = new Request( '', '', [ 'test' => 'foo' ] );
		$this->assertEquals( 'foo', $request->getParameter( 'test' ),
			'Failed to read back parameter'
		 );

		$request = new Request(
			'', '', [ 'test' => [ 'foo', 'bar' ] ]
		 );
		$this->assertEquals( [ 'foo', 'bar' ],
			$request->getParameter( 'test' ), 'Failed to read back parameter' );

		$request = new Request(
			'', '', [ 'test' => 'foo', 'bar' => 'baz' ]
		 );
		$this->assertEquals( 'foo', $request->getParameter( 'test' ),
			'Failed to read back parameter'
		 );
		$this->assertEquals( 'baz', $request->getParameter( 'bar' ),
			'Failed to read back parameter'
		 );
	}

	public function testGetAllParameters() {
		// Yes, a awesomely boring test.. But if this doesn't work, the other
		// tests is unreliable
		$request = new Request( '', '', [ 'test' => 'foo' ] );
		$this->assertEquals( [ 'test' => 'foo' ], $request->getParameters(),
			'Failed to read back parameters'
		 );

		$request = new Request(
			'', '', [ 'test' => 'foo', 'bar' => 'baz' ]
		 );
		$this->assertEquals( [ 'test' => 'foo', 'bar' => 'baz' ],
			$request->getParameters(), 'Failed to read back parameters' );

		$request = new Request(
			'', '', [ 'test' => [ 'foo', 'bar' ] ]
		 );
		$this->assertEquals( [ 'test' => [ 'foo', 'bar' ] ],
			$request->getParameters(), 'Failed to read back parameters' );
	}

	public function testSetParameters() {
		$request = new Request( '', '' );
		$this->assertEquals( null, $request->getParameter( 'test' ),
			'Failed to assert that non-existing parameter is null' );

		$request->setParameter( 'test', 'foo' );
		$this->assertEquals( 'foo', $request->getParameter( 'test' ),
			'Failed to set single-entry parameter' );

		$request->setParameter( 'test', 'bar' );
		$this->assertEquals( [ 'foo', 'bar' ],
			$request->getParameter( 'test' ),
			'Failed to set single-entry parameter'
		 );

		$request->setParameter( 'test', 'bar', false );
		$this->assertEquals( 'bar', $request->getParameter( 'test' ),
			'Failed to set single-entry parameter'
		 );
	}

	public function testUnsetParameter() {
		$request = new Request( '', '' );
		$this->assertEquals( null, $request->getParameter( 'test' ) );

		$request->setParameter( 'test', 'foo' );
		$this->assertEquals( 'foo', $request->getParameter( 'test' ) );

		$request->unsetParameter( 'test' );
		$this->assertEquals( null, $request->getParameter( 'test' ),
			'Failed to unset parameter'
		 );
	}

	public function testCreateRequestFromConsumerAndToken() {
		$cons = new Consumer( 'key', 'kd94hf93k423kf44' );
		$token = new Token( 'token', 'pfkkdhi9sl3r4s00' );

		$request = Request::fromConsumerAndToken(
			$cons, $token, 'POST', 'http://example.com'
		 );
		$this->assertEquals( 'POST', $request->getNormalizedMethod() );
		$this->assertEquals( 'http://example.com', $request->getNormalizedUrl() );
		$this->assertEquals( '1.0', $request->getParameter( 'oauth_version' ) );
		$this->assertEquals( $cons->key, $request->getParameter( 'oauth_consumer_key' ) );
		$this->assertEquals( $token->key, $request->getParameter( 'oauth_token' ) );
		$this->assertEquals( time(), $request->getParameter( 'oauth_timestamp' ) );
		$this->assertRegExp( '/[0-9a-f]{32}/', $request->getParameter( 'oauth_nonce' ) );
		// We don't know what the nonce will be, except it'll be md5 and hence
		// 32 hexa digits

		$request = Request::fromConsumerAndToken( $cons, $token, 'POST',
			'http://example.com', [ 'oauth_nonce' => 'foo' ] );
		$this->assertEquals( 'foo', $request->getParameter( 'oauth_nonce' ) );

		$request = Request::fromConsumerAndToken( $cons, null, 'POST',
			'http://example.com', [ 'oauth_nonce' => 'foo' ] );
		$this->assertNull( $request->getParameter( 'oauth_token' ) );

		// Test that parameters given in the $http_url instead of in the
		// $parameters-parameter will still be picked up
		$request = Request::fromConsumerAndToken( $cons, $token, 'POST',
			'http://example.com/?foo=bar' );
		$this->assertEquals( 'http://example.com/', $request->getNormalizedUrl() );
		$this->assertEquals( 'bar', $request->getParameter( 'foo' ) );
	}

	public function testBuildRequestFromPost() {
		static::buildRequest(
			'POST', 'http://testbed/test', 'foo=bar&baz=blargh' );
		$this->assertEquals( [ 'foo' => 'bar','baz' => 'blargh' ],
			Request::fromRequest()->getParameters(),
			'Failed to parse POST parameters' );
	}

	public function testBuildRequestFromGet() {
		static::buildRequest( 'GET', 'http://testbed/test?foo=bar&baz=blargh' );
		$this->assertEquals( [ 'foo' => 'bar','baz' => 'blargh' ],
			Request::fromRequest()->getParameters(),
			'Failed to parse GET parameters' );
	}

	public function testBuildRequestFromHeader() {
		$test_header = 'OAuth realm="",oauth_foo=bar,oauth_baz="bla,rgh"';
		static::buildRequest( 'POST', 'http://testbed/test', '', $test_header );
		$this->assertEquals(
			[ 'oauth_foo' => 'bar','oauth_baz' => 'bla,rgh' ],
			Request::fromRequest()->getParameters(),
			'Failed to split auth-header correctly' );
	}

	public function testHasProperParameterPriority() {
		$test_header = 'OAuth realm="",oauth_foo=header';
		static::buildRequest( 'POST', 'http://testbed/test?oauth_foo=get',
			'oauth_foo=post', $test_header );
		$this->assertEquals( 'header',
			Request::fromRequest()->getParameter( 'oauth_foo' ),
			'Loaded parameters in with the wrong priorities' );

		static::buildRequest( 'POST', 'http://testbed/test?oauth_foo=get',
			'oauth_foo=post' );
		$this->assertEquals( 'post',
			Request::fromRequest()->getParameter( 'oauth_foo' ),
			'Loaded parameters in with the wrong priorities' );

		static::buildRequest( 'POST', 'http://testbed/test?oauth_foo=get' );
		$this->assertEquals( 'get',
			Request::fromRequest()->getParameter( 'oauth_foo' ),
			'Loaded parameters in with the wrong priorities' );
	}

	public function testNormalizeHttpMethod() {
		static::buildRequest( 'POST', 'http://testbed/test' );
		$this->assertEquals( 'POST',
			Request::fromRequest()->getNormalizedMethod(),
			'Failed to normalize HTTP method: POST' );

		static::buildRequest( 'post', 'http://testbed/test' );
		$this->assertEquals( 'POST',
			Request::fromRequest()->getNormalizedMethod(),
			'Failed to normalize HTTP method: post' );

		static::buildRequest( 'GET', 'http://testbed/test' );
		$this->assertEquals( 'GET',
			Request::fromRequest()->getNormalizedMethod(),
			'Failed to normalize HTTP method: GET' );

		static::buildRequest( 'PUT', 'http://testbed/test' );
		$this->assertEquals( 'PUT',
			Request::fromRequest()->getNormalizedMethod(),
			'Failed to normalize HTTP method: PUT' );
	}

	public function testNormalizeParameters() {
		// This is mostly repeats of OAuthUtilTest::testParseParameters
		// & OAuthUtilTest::TestBuildHttpQuery

		// Tests taken from
		// http://wiki.oauth.net/TestCases ( "Normalize Request Parameters" )
		static::buildRequest( 'POST', 'http://testbed/test', 'name' );
		$this->assertEquals( 'name=',
			Request::fromRequest()->getSignableParameters() );

		static::buildRequest( 'POST', 'http://testbed/test', 'a=b' );
		$this->assertEquals( 'a=b',
			Request::fromRequest()->getSignableParameters() );

		static::buildRequest( 'POST', 'http://testbed/test', 'a=b&c=d' );
		$this->assertEquals( 'a=b&c=d',
			Request::fromRequest()->getSignableParameters() );

		static::buildRequest( 'POST', 'http://testbed/test', 'a=x%21y&a=x+y' );
		$this->assertEquals( 'a=x%20y&a=x%21y',
			Request::fromRequest()->getSignableParameters() );

		static::buildRequest( 'POST', 'http://testbed/test', 'x%21y=a&x=a' );
		$this->assertEquals( 'x=a&x%21y=a',
			Request::fromRequest()->getSignableParameters() );

		static::buildRequest( 'POST',
			'http://testbed/test', 'a=1&c=hi there&f=25&f=50&f=a&z=p&z=t' );
		$this->assertEquals( 'a=1&c=hi%20there&f=25&f=50&f=a&z=p&z=t',
			Request::fromRequest()->getSignableParameters() );
	}

	public function testNormalizeHttpUrl() {
		static::buildRequest( 'POST', 'http://example.com' );
		$this->assertEquals( 'http://example.com',
			Request::fromRequest()->getNormalizedUrl() );

		static::buildRequest( 'POST', 'https://example.com' );
		$this->assertEquals( 'https://example.com',
			Request::fromRequest()->getNormalizedUrl() );

		// Tests that http on !80 and https on !443 keeps the port
		static::buildRequest( 'POST', 'http://example.com:8080' );
		$this->assertEquals( 'http://example.com:8080',
			Request::fromRequest()->getNormalizedUrl() );

		static::buildRequest( 'POST', 'https://example.com:80' );
		$this->assertEquals( 'https://example.com:80',
			Request::fromRequest()->getNormalizedUrl() );

		static::buildRequest( 'POST', 'http://example.com:443' );
		$this->assertEquals( 'http://example.com:443',
			Request::fromRequest()->getNormalizedUrl() );

		static::buildRequest( 'POST', 'http://Example.COM' );
		$this->assertEquals( 'http://example.com',
			Request::fromRequest()->getNormalizedUrl() );

		// Emulate silly behavior by some clients, where there Host header
		// includes the port
		static::buildRequest( 'POST', 'http://example.com' );
		$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] . ':' .
			$_SERVER['SERVER_PORT'];
		$this->assertEquals( 'http://example.com',
			Request::fromRequest()->getNormalizedUrl() );
	}

	public function testBuildPostData() {
		static::buildRequest( 'POST', 'http://example.com' );
		$this->assertEquals( '', Request::fromRequest()->toPostData() );

		static::buildRequest( 'POST', 'http://example.com', 'foo=bar' );
		$this->assertEquals( 'foo=bar', Request::fromRequest()->toPostData() );

		static::buildRequest( 'GET', 'http://example.com?foo=bar' );
		$this->assertEquals( 'foo=bar', Request::fromRequest()->toPostData() );
	}

	public function testBuildUrl() {
		static::buildRequest( 'POST', 'http://example.com' );
		$this->assertEquals( 'http://example.com',
			Request::fromRequest()->toUrl() );

		static::buildRequest( 'POST', 'http://example.com', 'foo=bar' );
		$this->assertEquals( 'http://example.com?foo=bar',
			Request::fromRequest()->toUrl() );

		static::buildRequest( 'GET', 'http://example.com?foo=bar' );
		$this->assertEquals( 'http://example.com?foo=bar',
			Request::fromRequest()->toUrl() );
	}

	public function testConvertToString() {
		static::buildRequest( 'POST', 'http://example.com' );
		$this->assertEquals( 'http://example.com',
			(string)Request::fromRequest() );

		static::buildRequest( 'POST', 'http://example.com', 'foo=bar' );
		$this->assertEquals( 'http://example.com?foo=bar',
			(string)Request::fromRequest() );

		static::buildRequest( 'GET', 'http://example.com?foo=bar' );
		$this->assertEquals( 'http://example.com?foo=bar',
			(string)Request::fromRequest() );
	}

	public function testBuildHeader() {
		static::buildRequest( 'POST', 'http://example.com' );
		$this->assertEquals( 'Authorization: OAuth',
			Request::fromRequest()->toHeader() );
		$this->assertEquals( 'Authorization: OAuth realm="test"',
			Request::fromRequest()->toHeader( 'test' ) );

		static::buildRequest( 'POST', 'http://example.com', 'foo=bar' );
		$this->assertEquals( 'Authorization: OAuth',
			Request::fromRequest()->toHeader() );
		$this->assertEquals( 'Authorization: OAuth realm="test"',
			Request::fromRequest()->toHeader( 'test' ) );

		static::buildRequest( 'POST', 'http://example.com', 'oauth_test=foo' );
		$this->assertEquals( 'Authorization: OAuth oauth_test="foo"',
			Request::fromRequest()->toHeader() );
		$this->assertEquals(
			'Authorization: OAuth realm="test",oauth_test="foo"',
			Request::fromRequest()->toHeader( 'test' ) );

		// Is headers supposted to be Urlencoded. More to the point:
		// Should it be baz = bla,rgh or baz = bla%2Crgh ??
		// - morten.fangel
		static::buildRequest( 'POST', 'http://example.com',
			'', 'OAuth realm="",oauth_foo=bar,oauth_baz="bla,rgh"' );
		$this->assertEquals(
			'Authorization: OAuth oauth_foo="bar",oauth_baz="bla%2Crgh"',
			Request::fromRequest()->toHeader() );
		$this->assertEquals(
			'Authorization: OAuth realm="test",oauth_foo="bar",oauth_baz="bla%2Crgh"',
			Request::fromRequest()->toHeader( 'test' ) );
	}

	/**
	 * @expectedException \MediaWiki\OAuthClient\Exception
	 */
	public function testWontBuildHeaderWithArrayInput() {
		static::buildRequest( 'POST', 'http://example.com',
			'oauth_foo=bar&oauth_foo=baz' );
		Request::fromRequest()->toHeader();
	}

	public function testBuildBaseString() {
		static::buildRequest( 'POST', 'http://testbed/test', 'n=v' );
		$this->assertEquals(
			'POST&http%3A%2F%2Ftestbed%2Ftest&n%3Dv',
			Request::fromRequest()->getSignatureBaseString()
		 );

		static::buildRequest( 'POST', 'http://testbed/test', 'n=v&n=v2' );
		$this->assertEquals( 'POST&http%3A%2F%2Ftestbed%2Ftest&n%3Dv%26n%3Dv2',
			Request::fromRequest()->getSignatureBaseString() );

		static::buildRequest( 'GET', 'http://example.com?n=v' );
		$this->assertEquals( 'GET&http%3A%2F%2Fexample.com&n%3Dv',
			Request::fromRequest()->getSignatureBaseString() );

		$params = 'oauth_version=1.0&oauth_consumer_key=dpf43f3p2l4k3l03'
			. '&oauth_timestamp=1191242090'
			. '&oauth_nonce=hsu94j3884jdopsl'
			. '&oauth_signature_method=PLAINTEXT&oauth_signature=ignored';
		static::buildRequest( 'POST', 'https://photos.example.net/request_token', $params );
		$this->assertEquals(
			'POST&https%3A%2F%2Fphotos.example.net%2Frequest_token&oauth_'
			. 'consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%3Dhsu94j3884j'
			. 'dopsl%26oauth_signature_method%3DPLAINTEXT%26oauth_timestam'
			. 'p%3D1191242090%26oauth_version%3D1.0',
			Request::fromRequest()->getSignatureBaseString() );

		$params = 'file=vacation.jpg&size=original&oauth_version=1.0';
		$params .= '&oauth_consumer_key=dpf43f3p2l4k3l03';
		$params .= '&oauth_token=nnch734d00sl2jdk&oauth_timestamp=1191242096';
		$params .= '&oauth_nonce=kllo9940pd9333jh';
		$params .= '&oauth_signature=ignored&oauth_signature_method=HMAC-SHA1';
		static::buildRequest( 'GET', 'http://photos.example.net/photos?' . $params );
		$this->assertEquals(
			'GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacation'
			. '.jpg%26oauth_consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%'
			. '3Dkllo9940pd9333jh%26oauth_signature_method%3DHMAC-SHA1%26o'
			. 'auth_timestamp%3D1191242096%26oauth_token%3Dnnch734d00sl2jd'
			. 'k%26oauth_version%3D1.0%26size%3Doriginal',
			Request::fromRequest()->getSignatureBaseString() );
	}

	public function testBuildSignature() {
		$params = 'file=vacation.jpg&size=original&oauth_version=1.0'
			. '&oauth_consumer_key=dpf43f3p2l4k3l03'
			. '&oauth_token=nnch734d00sl2jdk'
			. '&oauth_timestamp=1191242096&oauth_nonce=kllo9940pd9333jh'
			. '&oauth_signature=ignored&oauth_signature_method=HMAC-SHA1';
		static::buildRequest( 'GET', 'http://photos.example.net/photos?' . $params );
		$r = Request::fromRequest();

		$cons = new Consumer( 'key', 'kd94hf93k423kf44' );
		$token = new Token( 'token', 'pfkkdhi9sl3r4s00' );

		$hmac = new HmacSha1();
		$plaintext = new Plaintext();

		$this->assertEquals( 'tR3+Ty81lMeYAr/Fid0kMTYa/WM=',
			$r->buildSignature( $hmac, $cons, $token ) );

		$this->assertEquals( 'kd94hf93k423kf44&pfkkdhi9sl3r4s00',
			$r->buildSignature( $plaintext, $cons, $token ) );
	}

	public function testSign() {
		$params = 'file=vacation.jpg&size=original&oauth_version=1.0'
			. '&oauth_consumer_key=dpf43f3p2l4k3l03'
			. '&oauth_token=nnch734d00sl2jdk'
			. '&oauth_timestamp=1191242096&oauth_nonce=kllo9940pd9333jh'
			. '&oauth_signature=__ignored__&oauth_signature_method=HMAC-SHA1';
		static::buildRequest( 'GET',
			'http://photos.example.net/photos?' . $params );
		$r = Request::fromRequest();

		$cons = new Consumer( 'key', 'kd94hf93k423kf44' );
		$token = new Token( 'token', 'pfkkdhi9sl3r4s00' );

		$hmac = new HmacSha1();
		$plaintext = new Plaintext();

		// We need to test both what the parameter is, and how the serialized
		// request is..

		$r->signRequest( $hmac, $cons, $token );
		$this->assertEquals( 'HMAC-SHA1',
			$r->getParameter( 'oauth_signature_method' ) );
		$this->assertEquals( 'tR3+Ty81lMeYAr/Fid0kMTYa/WM=',
			$r->getParameter( 'oauth_signature' ) );
		$expectedPostdata = 'file=vacation.jpg'
			. '&oauth_consumer_key=dpf43f3p2l4k3l03'
			. '&oauth_nonce=kllo9940pd9333jh'
			. '&oauth_signature=tR3%2BTy81lMeYAr%2FFid0kMTYa%2FWM%3D'
			. '&oauth_signature_method=HMAC-SHA1'
			. '&oauth_timestamp=1191242096&oauth_token=nnch734d00sl2jdk'
			. '&oauth_version=1.0&size=original';
		$this->assertEquals( $expectedPostdata, $r->toPostData() );

		$r->signRequest( $plaintext, $cons, $token );
		$this->assertEquals( 'PLAINTEXT',
			$r->getParameter( 'oauth_signature_method' ) );
		$this->assertEquals( 'kd94hf93k423kf44&pfkkdhi9sl3r4s00',
			$r->getParameter( 'oauth_signature' ) );
		$expectedPostdata = 'file=vacation.jpg'
			. '&oauth_consumer_key=dpf43f3p2l4k3l03'
			. '&oauth_nonce=kllo9940pd9333jh&'
			. 'oauth_signature=kd94hf93k423kf44%26pfkkdhi9sl3r4s00'
			. '&oauth_signature_method=PLAINTEXT'
			. '&oauth_timestamp=1191242096&oauth_token=nnch734d00sl2jdk'
			. '&oauth_version=1.0&size=original';
		$this->assertEquals( $expectedPostdata, $r->toPostData() );
	}

	/**
	 * Populates $_{SERVER,GET,POST} and whatever environment-variables needed
	 * to test everything.
	 *
	 * @param string $method GET or POST
	 * @param string $uri What URI is the request to ( eg http://example.com/foo?bar=baz )
	 * @param string $post_data What should the post-data be
	 * @param string $auth_header What to set the Authorization header to
	 */
	public static function buildRequest(
		$method, $uri, $post_data = '', $auth_header = ''
	 ) {
		$_SERVER = [];

		$method = strtoupper( $method );

		$parts = parse_url( $uri );

		$scheme = $parts['scheme'];
		$port   = isset( $parts['port'] ) && $parts['port'] ?
			$parts['port'] : ( $scheme === 'https' ? '443' : '80' );
		$host   = $parts['host'];
		$path   = isset( $parts['path'] ) ? $parts['path'] : null;
		$query  = isset( $parts['query'] ) ? $parts['query'] : null;

		if ( $scheme == 'https' ) {
			$_SERVER['HTTPS'] = 'on';
		}

		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['HTTP_HOST'] = $host;
		$_SERVER['SERVER_NAME'] = $host;
		$_SERVER['SERVER_PORT'] = $port;
		$_SERVER['SCRIPT_NAME'] = $path;
		$_SERVER['REQUEST_URI'] = $path . '?' . $query;
		$_SERVER['QUERY_STRING'] = $query . '';

		if ( $method == 'POST' ) {
			$_SERVER['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
			Request::$POST_INPUT = 'data:application/x-www-form-urlencoded,' . $post_data;
		}

		if ( $auth_header != '' ) {
			$_SERVER['HTTP_AUTHORIZATION'] = $auth_header;
		}
	}
}
