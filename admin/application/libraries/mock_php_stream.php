<?php
class Mock_php_stream
{
    private static $data = array();
    private $path;
    private $content = '';
    private $index = 0;
    private $length = 0;

    public function register()
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', static::class);
    }

    public function restore()
    {
        stream_wrapper_restore('php');
    }

    public function set_data($data)
    {
        file_put_contents('php://input', $data);
    }

    public function stream_stat()
    {
        return array();
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        if (strpos($mode, 'w') !== false) {
            unset(self::$data[$path]);
        }

        if (isset(self::$data[$path])) {
            $this->content = self::$data[$path];
            $this->index   = 0;
            $this->length  = strlen($this->content);
        }

        $this->path = $path;

        return true;
    }

    public function stream_write($data)
    {
        $this->content .= $data;
        $this->length  += strlen($data);

        return strlen($data);
    }

    public function stream_eof()
    {
        return $this->index >= $this->length;
    }

    public function stream_read($count)
    {
        if (empty($this->content)) {
            return '';
        }

        $length      = min($count, $this->length - $this->index);
        $data        = substr($this->content, $this->index, $length);
        $this->index += $length;

        return $data;
    }

    public function stream_close()
    {
        if (!empty($this->content)) {
            if (isset(self::$data)) {
                self::$data[$this->path] = $this->content;
            }
        }
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return true;
    }

    public function stream_tell()
    {
        return $this->index;
    }
}