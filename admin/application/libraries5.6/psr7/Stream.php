<?php

use Psr\Http\Message\StreamInterface;
// use Symfony\Component\Debug\ErrorHandler as SymfonyLegacyErrorHandler;
// use Symfony\Component\ErrorHandler\ErrorHandler as SymfonyErrorHandler;

/**
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class Stream implements StreamInterface
{
	/** @var resource|null A resource reference */
	private $stream;

	/** @var bool */
	private $seekable;

	/** @var bool */
	private $readable;

	/** @var bool */
	private $writable;

	/** @var array|mixed|void|bool|null */
	private $uri;

	/** @var int|null */
	private $size;

	/** @var array Hash of readable and writable stream types */
	const READ_WRITE_HASH = [
		'read' => [
			'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
			'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
			'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
			'x+t' => true, 'c+t' => true, 'a+' => true,
		],
		'write' => [
			'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
			'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
			'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
			'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
		],
	];

	private function __construct()
	{
	}

	/**
	 * Creates a new PSR-7 stream.
	 *
	 * @param string|resource|StreamInterface $body
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function create($body = '')
	{
		if ($body instanceof StreamInterface) {
			return $body;
		}

		if (\is_string($body)) {
			$resource = \fopen('php://temp', 'rw+');
			\fwrite($resource, $body);
			\rewind($resource);
			$body = $resource;
		}

		if (\is_resource($body)) {
			$new = new self();
			$new->stream = $body;
			$meta = \stream_get_meta_data($new->stream);
			$new->seekable = $meta['seekable'] && 0 === \fseek($new->stream, 0, \SEEK_CUR);
			$new->readable = array_key_exists($meta['mode'], self::READ_WRITE_HASH['read']);
			$new->writable = array_key_exists($meta['mode'], self::READ_WRITE_HASH['write']);

			return $new;
		}

		throw new \InvalidArgumentException('First argument to Stream::create() must be a string, resource or StreamInterface.');
	}

	/**
	 * Closes the stream when the destructed.
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		try {
			if ($this->isSeekable()) {
				$this->seek(0);
			}

			return $this->getContents();
		} catch (\Throwable $e) {
			if (\PHP_VERSION_ID >= 70400) {
				throw $e;
			}

			if (\is_array($errorHandler = \set_error_handler('var_dump'))) {
				$errorHandler = isset($errorHandler[0]) ? $errorHandler[0] : null;
			}
			\restore_error_handler();

			// if ($e instanceof \Error || $errorHandler instanceof SymfonyErrorHandler || $errorHandler instanceof SymfonyLegacyErrorHandler) {
			if ($e instanceof \Error){ // || $errorHandler instanceof SymfonyErrorHandler || $errorHandler instanceof SymfonyLegacyErrorHandler) {
				return \trigger_error((string) $e, \E_USER_ERROR);
			}

			return '';
		}
	}

	public function close()
	{
		if (isset($this->stream)) {
			if (\is_resource($this->stream)) {
				\fclose($this->stream);
			}
			$this->detach();
		}
	}

	public function detach()
	{
		if (!isset($this->stream)) {
			return null;
		}

		$result = $this->stream;
		unset($this->stream);
		$this->size = $this->uri = null;
		$this->readable = $this->writable = $this->seekable = false;

		return $result;
	}

	private function getUri()
	{
		if (false !== $this->uri) {
			$this->uri = !empty($this->getMetadata('uri')) ? $this->getMetadata('uri') : false;
		}

		return $this->uri;
	}

	public function getSize()
	{
		if (null !== $this->size) {
			return $this->size;
		}

		if (!isset($this->stream)) {
			return null;
		}

		// Clear the stat cache if the stream has a URI
		if ($uri = $this->getUri()) {
			\clearstatcache(true, $uri);
		}

		$stats = \fstat($this->stream);
		if (isset($stats['size'])) {
			$this->size = $stats['size'];

			return $this->size;
		}

		return null;
	}

	public function tell()
	{
		if (false === $result = \ftell($this->stream)) {
			throw new \RuntimeException('Unable to determine stream position');
		}

		return $result;
	}

	public function eof()
	{
		return !$this->stream || \feof($this->stream);
	}

	public function isSeekable()
	{
		return $this->seekable;
	}

	public function seek($offset, $whence = \SEEK_SET)
	{
		if (!$this->seekable) {
			throw new \RuntimeException('Stream is not seekable');
		}

		if (-1 === \fseek($this->stream, $offset, $whence)) {
			throw new \RuntimeException('Unable to seek to stream position "' . $offset . '" with whence ' . \var_export($whence, true));
		}
	}

	public function rewind()
	{
		$this->seek(0);
	}

	public function isWritable()
	{
		return $this->writable;
	}

	public function write($string)
	{
		if (!$this->writable) {
			throw new \RuntimeException('Cannot write to a non-writable stream');
		}

		// We can't know the size after writing anything
		$this->size = null;

		if (false === $result = \fwrite($this->stream, $string)) {
			throw new \RuntimeException('Unable to write to stream');
		}

		return $result;
	}

	public function isReadable()
	{
		return $this->readable;
	}

	public function read($length)
	{
		if (!$this->readable) {
			throw new \RuntimeException('Cannot read from non-readable stream');
		}

		if (false === $result = \fread($this->stream, $length)) {
			throw new \RuntimeException('Unable to read from stream');
		}

		return $result;
	}

	public function getContents()
	{
		if (!isset($this->stream)) {
			throw new \RuntimeException('Unable to read stream contents');
		}

		if (false === $contents = \stream_get_contents($this->stream)) {
			throw new \RuntimeException('Unable to read stream contents');
		}

		return $contents;
	}

	public function getMetadata($key = null)
	{
		if (!isset($this->stream)) {
			return $key ? null : [];
		}

		$meta = \stream_get_meta_data($this->stream);

		if (null === $key) {
			return $meta;
		}

		return isset($meta[$key]) ? $meta[$key] : null;
	}
}
