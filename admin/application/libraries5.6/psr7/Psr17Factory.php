<?php

require_once dirname(__FILE__) . '/../psr17/RequestFactoryInterface.php';
require_once dirname(__FILE__) . '/../psr17/ResponseFactoryInterface.php';
require_once dirname(__FILE__) . '/../psr17/ServerRequestFactoryInterface.php';
require_once dirname(__FILE__) . '/../psr17/StreamFactoryInterface.php';
require_once dirname(__FILE__) . '/../psr17/UploadedFileFactoryInterface.php';
require_once dirname(__FILE__) . '/../psr17/UriFactoryInterface.php';

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class Psr17Factory implements RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
{
	public function createRequest( $method, $uri)
	{
		return new Request($method, $uri);
	}

	public function createResponse( $code = 200, $reasonPhrase = '')
	{
		if (2 > \func_num_args()) {
			// This will make the Response class to use a custom reasonPhrase
			$reasonPhrase = null;
		}

		return new Response($code, [], null, '1.1', $reasonPhrase);
	}

	public function createStream( $content = '')
	{
		return Stream::create($content);
	}

	public function createStreamFromFile( $filename, $mode = 'r')
	{
		try {
			$resource = @\fopen($filename, $mode);
		} catch (\Throwable $e) {
			throw new \RuntimeException(\sprintf('The file "%s" cannot be opened.', $filename));
		}

		if (false === $resource) {
			if ('' === $mode || false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
				throw new \InvalidArgumentException(\sprintf('The mode "%s" is invalid.', $mode));
			}

			throw new \RuntimeException(\sprintf('The file "%s" cannot be opened.', $filename));
		}

		return Stream::create($resource);
	}

	public function createStreamFromResource($resource)
	{
		return Stream::create($resource);
	}

	public function createUploadedFile(StreamInterface $stream, $size = null, $error = \UPLOAD_ERR_OK, $clientFilename = null, $clientMediaType = null)
	{
		if (null === $size) {
			$size = $stream->getSize();
		}

		return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
	}

	public function createUri($uri = '')
	{
		return new Uri($uri);
	}

	public function createServerRequest($method, $uri, array $serverParams = [])
	{
		return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
	}
}
