<?php

require_once dirname(__FILE__) . '/MessageTrait.php';
require_once dirname(__FILE__) . '/RequestTrait.php';

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class ServerRequest implements ServerRequestInterface
{
	use MessageTrait;
	use RequestTrait;

	/** @var array */
	private $attributes = [];

	/** @var array */
	private $cookieParams = [];

	/** @var array|object|null */
	private $parsedBody;

	/** @var array */
	private $queryParams = [];

	/** @var array */
	private $serverParams;

	/** @var UploadedFileInterface[] */
	private $uploadedFiles = [];

	/**
	 * @param string $method HTTP method
	 * @param string|UriInterface $uri URI
	 * @param array $headers Request headers
	 * @param string|resource|StreamInterface|null $body Request body
	 * @param string $version Protocol version
	 * @param array $serverParams Typically the $_SERVER superglobal
	 */
	public function __construct($method, $uri, array $headers = [], $body = null, $version = '1.1', array $serverParams = [])
	{
		$this->serverParams = $serverParams;

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

		// If we got no body, defer initialization of the stream until ServerRequest::getBody()
		if ('' !== $body && null !== $body) {
			$this->stream = Stream::create($body);
		}
	}

	public function getServerParams()
	{
		return $this->serverParams;
	}

	public function getUploadedFiles()
	{
		return $this->uploadedFiles;
	}

	public function withUploadedFiles(array $uploadedFiles)
	{
		$new = clone $this;
		$new->uploadedFiles = $uploadedFiles;

		return $new;
	}

	public function getCookieParams()
	{
		return $this->cookieParams;
	}

	public function withCookieParams(array $cookies)
	{
		$new = clone $this;
		$new->cookieParams = $cookies;

		return $new;
	}

	public function getQueryParams()
	{
		return $this->queryParams;
	}

	public function withQueryParams(array $query)
	{
		$new = clone $this;
		$new->queryParams = $query;

		return $new;
	}

	public function getParsedBody()
	{
		return $this->parsedBody;
	}

	public function withParsedBody($data)
	{
		if (!\is_array($data) && !\is_object($data) && null !== $data) {
			throw new \InvalidArgumentException('First parameter to withParsedBody MUST be object, array or null');
		}

		$new = clone $this;
		$new->parsedBody = $data;

		return $new;
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function getAttribute($attribute, $default = null)
	{
		if (false === \array_key_exists($attribute, $this->attributes)) {
			return $default;
		}

		return $this->attributes[$attribute];
	}

	public function withAttribute($attribute, $value)
	{
		$new = clone $this;
		$new->attributes[$attribute] = $value;

		return $new;
	}

	public function withoutAttribute($attribute)
	{
		if (false === \array_key_exists($attribute, $this->attributes)) {
			return $this;
		}

		$new = clone $this;
		unset($new->attributes[$attribute]);

		return $new;
	}
}
