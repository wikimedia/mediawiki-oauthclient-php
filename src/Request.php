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

namespace MediaWiki\OAuthClient;

/**
 * An OAuth request
 */
class Request {
	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 */
	public static $version = '1.0';

	/**
	 * Used for tests.
	 * @var string
	 */
	public static $POST_INPUT = 'php://input';

	/**
	 * @param string $method
	 * @param string $url
	 * @param array|null $parameters
	 */
	public function __construct( $method, $url, $parameters = null ) {
		$parameters = $parameters ?: [];
		$parameters = array_merge(
			Util::parseParameters( parse_url( $url, PHP_URL_QUERY ) ),
			$parameters
		);
		$this->parameters = $parameters;
		$this->method = $method;
		$this->url = $url;
	}

	/**
	 * Attempt to build up a request from what was passed to the server
	 *
	 * @param string|null $method
	 * @param string|null $url
	 * @param array|null $params
	 * @return Request
	 */
	public static function fromRequest(
		$method = null,
		$url = null,
		array $params = null
	) {
		$scheme = ( !isset( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] != 'on' ) ?
			'http' : 'https';
		$url = ( $url ?: $scheme ) .
			'://' . $_SERVER['SERVER_NAME'] .
			':' .
			$_SERVER['SERVER_PORT'] .
			$_SERVER['REQUEST_URI'];
		$method = $method ?: $_SERVER['REQUEST_METHOD'];

		// We weren't handed any params, so let's find the ones relevant
		// to this request. If you run XML-RPC or similar you should use this
		// to provide your own parsed parameter-list
		if ( !$params ) {
			// Find request headers
			$headers = Util::getHeaders();

			// Parse the query-string to find GET params
			$params = Util::parseParameters( $_SERVER['QUERY_STRING'] );

			// It's a POST request of the proper content-type, so parse POST
			// params and add those overriding any duplicates from GET
			if ( $method === 'POST' &&
				isset( $headers['Content-Type'] ) &&
				strstr( $headers['Content-Type'],
					'application/x-www-form-urlencoded'
				)
			) {
				$post_data = Util::parseParameters(
					file_get_contents( self::$POST_INPUT )
				);
				$params = array_merge( $params, $post_data );
			}

			// We have a Authorization-header with OAuth data. Parse the header
			// and add those overriding any duplicates from GET or POST
			if ( isset( $headers['Authorization'] ) &&
				substr( $headers['Authorization'], 0, 6 ) === 'OAuth '
			) {
				$header_params = Util::splitHeader( $headers['Authorization'] );
				$params = array_merge( $params, $header_params );
			}
		}

		return new Request( $method, $url, $params );
	}

	/**
	 * @param Consumer $consumer
	 * @param Token|null $token
	 * @param string $method
	 * @param string $url
	 * @param array|null $parameters
	 * @return Request
	 */
	public static function fromConsumerAndToken(
		Consumer $consumer,
		?Token $token,
		$method,
		$url,
		array $parameters = null
	) {
		$parameters = $parameters ?: [];
		$defaults = [
			'oauth_version' => static::$version,
			'oauth_nonce' => md5( microtime() . mt_rand() ),
			'oauth_timestamp' => time(),
			'oauth_consumer_key' => $consumer->key,
		];
		if ( $token ) {
			$defaults['oauth_token'] = $token->key;
		}
		$parameters = array_merge( $defaults, $parameters );

		return new self( $method, $url, $parameters );
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param bool $allow_duplicates
	 */
	public function setParameter( $name, $value, $allow_duplicates = true ) {
		if ( $allow_duplicates && isset( $this->parameters[$name] ) ) {
			// We have already added parameter(s) with this name, so add to
			// the list
			if ( is_scalar( $this->parameters[$name] ) ) {
				// This is the first duplicate, so transform scalar (string)
				// into an array so we can add the duplicates
				$this->parameters[$name] = [ $this->parameters[$name] ];
			}

			$this->parameters[$name][] = $value;
		} else {
			$this->parameters[$name] = $value;
		}
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getParameter( $name ) {
		return isset( $this->parameters[$name] ) ?
			$this->parameters[$name] : null;
	}

	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @param string $name
	 */
	public function unsetParameter( $name ) {
		unset( $this->parameters[$name] );
	}

	/**
	 * The request parameters, sorted and concatenated into a normalized string.
	 * @return string
	 */
	public function getSignableParameters() {
		// Grab all parameters
		$params = $this->parameters;

		// Remove oauth_signature if present
		// Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
		if ( isset( $params['oauth_signature'] ) ) {
			unset( $params['oauth_signature'] );
		}

		return Util::buildHttpQuery( $params );
	}

	/**
	 * Returns the base string of this request
	 *
	 * The base string defined as the method, the url
	 * and the parameters (normalized), each urlencoded
	 * and the concated with &.
	 * @return string
	 */
	public function getSignatureBaseString() {
		$parts = [
			$this->getNormalizedMethod(),
			$this->getNormalizedUrl(),
			$this->getSignableParameters()
		];

		$parts = Util::urlencode( $parts );

		return implode( '&', $parts );
	}

	/**
	 * @return string
	 */
	public function getNormalizedMethod() {
		return strtoupper( $this->method );
	}

	/**
	 * Parses the url and rebuilds it to be scheme://host/path
	 * @return string
	 */
	public function getNormalizedUrl() {
		$parts = parse_url( $this->url );

		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'http';
		$port = isset( $parts['port'] ) ?
			$parts['port'] : ( $scheme === 'https' ? '443' : '80' );
		$host = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';
		$path = isset( $parts['path'] ) ? $parts['path'] : '';

		if ( ( $scheme === 'https' && $port != '443' ) ||
			( $scheme === 'http' && $port != '80' )
		) {
			$host = "{$host}:{$port}";
		}
		return "{$scheme}://{$host}{$path}";
	}

	/**
	 * Builds a url usable for a GET request
	 * @return string
	 */
	public function toUrl() {
		$post_data = $this->toPostData();
		$out = $this->getNormalizedUrl();
		if ( $post_data ) {
			$out .= '?' . $post_data;
		}
		return $out;
	}

	/**
	 * Builds the data one would send in a POST request
	 * @return string
	 */
	public function toPostData() {
		return Util::buildHttpQuery( $this->parameters );
	}

	/**
	 * Builds the Authorization: header
	 * @param string|null $realm
	 * @return string
	 */
	public function toHeader( $realm = null ) {
		$first = true;
		if ( $realm ) {
			$out = 'Authorization: OAuth realm="' .
				Util::urlencode( $realm ) . '"';
			$first = false;
		} else {
			$out = 'Authorization: OAuth';
		}

		foreach ( $this->parameters as $k => $v ) {
			if ( substr( $k, 0, 5 ) !== 'oauth' ) {
				continue;
			}
			if ( is_array( $v ) ) {
				throw new Exception( 'Arrays not supported in headers' );
			}
			$out .= ( $first ) ? ' ' : ',';
			$out .= Util::urlencode( $k ) . '="' . Util::urlencode( $v ) . '"';
			$first = false;
		}
		return $out;
	}

	public function __toString() {
		return $this->toUrl();
	}

	/**
	 * @param SignatureMethod $signature_method
	 * @param Consumer $consumer
	 * @param Token|null $token
	 */
	public function signRequest(
		SignatureMethod $signature_method,
		Consumer $consumer,
		Token $token = null
	) {
		$this->setParameter(
			'oauth_signature_method',
			$signature_method->getName(),
			false
		);
		$signature = $this->buildSignature(
			$signature_method, $consumer, $token
		);
		$this->setParameter( 'oauth_signature', $signature, false );
	}

	/**
	 * @param SignatureMethod $signature_method
	 * @param Consumer $consumer
	 * @param Token|null $token
	 * @return mixed
	 */
	public function buildSignature(
		SignatureMethod $signature_method,
		Consumer $consumer,
		Token $token = null
	) {
		$signature = $signature_method->buildSignature(
			$this, $consumer, $token
		);
		return $signature;
	}
}
