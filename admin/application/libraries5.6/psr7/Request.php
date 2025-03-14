<?php

require_once dirname(__FILE__) . '/MessageTrait.php';
require_once dirname(__FILE__) . '/RequestTrait.php';

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class Request implements RequestInterface
{
	use MessageTrait;
	use RequestTrait;

	/**
	 * @param string $method HTTP method
	 * @param string|UriInterface $uri URI
	 * @param array $headers Request headers
	 * @param string|resource|StreamInterface|null $body Request body
	 * @param string $version Protocol version
	 */
	public function __construct( $method, $uri, array $headers = [], $body = null, $version = '1.1')
	{
		if (!($uri instanceof UriInterface)) {
			$uri = new Uri($uri);
		}

		$this->method = $method;
		$this->uri = $uri;
		$this->setHeaders($headers);
		$this->protocol = $version;

		if (!$this->hasHeader('Host')) {
			$this->updateHostFromUri();
		}

		// If we got no body, defer initialization of the stream until Request::getBody()
		if ('' !== $body && null !== $body) {
			$this->stream = Stream::create($body);
		}
	}
}
