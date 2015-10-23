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
use MediaWiki\OAuthClient\Request;
use MediaWiki\OAuthClient\Token;

/**
 * @coversDefaultClass \MediaWiki\OAuthClient\SignatureMethod\RsaSha1
 */
class RsaSha1Test extends \PHPUnit_Framework_TestCase {
	private $method;

	public function setUp() {
		$cert = <<<EOD
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALRiMLAh9iimur8V
A7qVvdqxevEuUkW4K+2KdMXmnQbG9Aa7k7eBjK1S+0LYmVjPKlJGNXHDGuy5Fw/d
7rjVJ0BLB+ubPK8iA/Tw3hLQgXMRRGRXXCn8ikfuQfjUS1uZSatdLB81mydBETlJ
hI6GH4twrbDJCR2Bwy/XWXgqgGRzAgMBAAECgYBYWVtleUzavkbrPjy0T5FMou8H
X9u2AC2ry8vD/l7cqedtwMPp9k7TubgNFo+NGvKsl2ynyprOZR1xjQ7WgrgVB+mm
uScOM/5HVceFuGRDhYTCObE+y1kxRloNYXnx3ei1zbeYLPCHdhxRYW7T0qcynNmw
rn05/KO2RLjgQNalsQJBANeA3Q4Nugqy4QBUCEC09SqylT2K9FrrItqL2QKc9v0Z
zO2uwllCbg0dwpVuYPYXYvikNHHg+aCWF+VXsb9rpPsCQQDWR9TT4ORdzoj+Nccn
qkMsDmzt0EfNaAOwHOmVJ2RVBspPcxt5iN4HI7HNeG6U5YsFBb+/GZbgfBT3kpNG
WPTpAkBI+gFhjfJvRw38n3g/+UeAkwMI2TJQS4n8+hid0uus3/zOjDySH3XHCUno
cn1xOJAyZODBo47E+67R4jV1/gzbAkEAklJaspRPXP877NssM5nAZMU0/O/NGCZ+
3jPgDUno6WbJn5cqm8MqWhW1xGkImgRk+fkDBquiq4gPiT898jusgQJAd5Zrr6Q8
AO/0isr/3aa6O6NLQxISLKcPDk2NOccAfS/xOtfOz4sJYM3+Bs4Io9+dZGSDCA54
Lw03eHTNQghS0A==
-----END PRIVATE KEY-----
EOD;
		$pubcert = <<<EOD
-----BEGIN CERTIFICATE-----
MIIBpjCCAQ+gAwIBAgIBATANBgkqhkiG9w0BAQUFADAZMRcwFQYDVQQDDA5UZXN0
IFByaW5jaXBhbDAeFw03MDAxMDEwODAwMDBaFw0zODEyMzEwODAwMDBaMBkxFzAV
BgNVBAMMDlRlc3QgUHJpbmNpcGFsMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKB
gQC0YjCwIfYoprq/FQO6lb3asXrxLlJFuCvtinTF5p0GxvQGu5O3gYytUvtC2JlY
zypSRjVxwxrsuRcP3e641SdASwfrmzyvIgP08N4S0IFzEURkV1wp/IpH7kH41Etb
mUmrXSwfNZsnQRE5SYSOhh+LcK2wyQkdgcMv11l4KoBkcwIDAQABMA0GCSqGSIb3
DQEBBQUAA4GBAGZLPEuJ5SiJ2ryq+CmEGOXfvlTtEL2nuGtr9PewxkgnOjZpUy+d
4TvuXJbNQc8f4AMWL/tO9w0Fk80rWKp9ea8/df4qMq5qlFWlx6yOLQxumNOmECKb
WpkUQDIDJEoFUzKMVuJf4KO/FJ345+BNLGgbJ6WujreoM1X/gYfdnJ/J
-----END CERTIFICATE-----
EOD;

		$this->method = $this->getMockBuilder(
			'MediaWiki\OAuthClient\SignatureMethod\RsaSha1' )
			->setMethods( array( 'fetchPrivateCert', 'fetchPublicCert' ) )
			->getMock();

		$this->method->method( 'fetchPrivateCert' )
			->willReturn( $cert );
		$this->method->method( 'fetchPublicCert' )
			->willReturn( $pubcert );
	}

	public function testIdentifyAsRsaSha1() {
		$this->assertEquals( 'RSA-SHA1', $this->method->getName() );
	}

	public function testBuildSignature() {
		if ( !function_exists( 'openssl_get_privatekey' ) ) {
			$this->markTestSkipped(
				'OpenSSL not available, can\'t test RSA-SHA1 functionality'
			);
		}

		// Tests taken from http://wiki.oauth.net/TestCases section 9.3
		// ("RSA-SHA1")
		$request = $this->mockRequest(
			// @codingStandardsIgnoreStart Line exceeds 100 characters
			'GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacaction.jpg%26oauth_consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%3D13917289812797014437%26oauth_signature_method%3DRSA-SHA1%26oauth_timestamp%3D1196666512%26oauth_version%3D1.0%26size%3Doriginal'
			// @codingStandardsIgnoreEnd
		);
		$consumer = new Consumer( 'dpf43f3p2l4k3l03', '__unused__' );
		$token = null;
		// @codingStandardsIgnoreStart Line exceeds 100 characters
		$signature = 'jvTp/wX1TYtByB1m+Pbyo0lnCOLIsyGCH7wke8AUs3BpnwZJtAuEJkvQL2/9n4s5wUmUl4aCI4BwpraNx4RtEXMe5qg5T1LVTGliMRpKasKsW//e+RinhejgCuzoH26dyF8iY2ZZ/5D1ilgeijhV/vBka5twt399mXwaYdCwFYE=';
		// @codingStandardsIgnoreEnd
		$this->assertEquals( $signature,
			$this->method->buildSignature( $request, $consumer, $token )
		);
	}

	public function testVerifySignature() {
		if ( !function_exists( 'openssl_get_privatekey' ) ) {
			$this->markTestSkipped(
				'OpenSSL not available, can\'t test RSA-SHA1 functionality'
			);
		}

		// Tests taken from http://wiki.oauth.net/TestCases section 9.3
		// ("RSA-SHA1")
		$request = $this->mockRequest(
			// @codingStandardsIgnoreStart Line exceeds 100 characters
			'GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacaction.jpg%26oauth_consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%3D13917289812797014437%26oauth_signature_method%3DRSA-SHA1%26oauth_timestamp%3D1196666512%26oauth_version%3D1.0%26size%3Doriginal'
			// @codingStandardsIgnoreEnd
		);
		$consumer = new Consumer( 'dpf43f3p2l4k3l03', '__unused__' );
		$token = null;
		// @codingStandardsIgnoreStart Line exceeds 100 characters
		$signature = 'jvTp/wX1TYtByB1m+Pbyo0lnCOLIsyGCH7wke8AUs3BpnwZJtAuEJkvQL2/9n4s5wUmUl4aCI4BwpraNx4RtEXMe5qg5T1LVTGliMRpKasKsW//e+RinhejgCuzoH26dyF8iY2ZZ/5D1ilgeijhV/vBka5twt399mXwaYdCwFYE=';
		// @codingStandardsIgnoreEnd
		$this->assertTrue( $this->method->checkSignature(
			$request, $consumer, $token, $signature
		) );
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
