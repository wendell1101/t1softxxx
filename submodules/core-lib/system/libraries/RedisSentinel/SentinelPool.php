<?php

namespace RedisSentinel;

/**
 * Class SentinelPool
 * @package RedisSentinel
 *
 * @method string ping()
 * @method array masters()
 * @method array master(string $master_name)
 * @method array slaves(string $master_name)
 * @method array sentinels(string $master_name)
 * @method array getMasterAddrByName(string $master_name)
 * @method int reset(string $pattern)
 * @method boolean failOver(string $master_name)
 * @method boolean ckquorum(string $master_name)
 * @method boolean checkQuorum(string $master_name)
 * @method boolean flushConfig()
 * @method boolean monitor($master_name, $ip, $port, $quorum)
 * @method boolean remove($master_name)
 * @method boolean set($master_name, $option, $value)
 * @method
 */
class SentinelPool
{
    /**
     * @var Sentinel[]
     */
    protected $sentinels = array();

    /**
     * @var Sentinel
     */
    protected $currentSentinel = null;

    /**
     * SentinelPool constructor.
     * @param array $sentinels [['host'=>'host', 'port'=>'port']]
     */
    public function __construct(array $sentinels = array())
    {
        foreach ($sentinels as $sentinel) {
            $this->addSentinel($sentinel['host'], $sentinel['port']);
        }
    }

    /**
     * add sentinel to sentinel pool
     *
     * @param string $host sentinel server host
     * @param int $port sentinel server port
     * @param null|float $timeout connect timeout in seconds
     * @return bool
     */
    public function addSentinel($host, $port, $timeout = null)
    {
        $sentinel = new Sentinel($timeout);
        // if connect to sentinel successfully, add it to sentinels array
        if ($sentinel->connect($host, $port)) {
            $this->sentinels[] = $sentinel;
            return true;
        }

        return false;
    }

    /**
     * get Redis object by master name
     *
     * @param $master_name
     * @return \Redis
     * @throws \RedisException
     */
    public function getRedis($master_name)
    {
        $address = $this->getMasterAddrByName($master_name);
        $redis = new \Redis();
        if (!$redis->connect($address['ip'], $address['port'], $this->currentSentinel->getTimeout())) {
            throw new \RedisException("connect to redis failed");
        }

        return $redis;
    }

    public function __call($name, $arguments)
    {
        foreach ($this->sentinels as $sentinel) {
            if (!method_exists($sentinel, $name)) {
                throw new \BadMethodCallException("method not exists. method: {$name}");
            }
            try {
                $this->currentSentinel = $sentinel;
                return call_user_func_array(array($sentinel, $name), $arguments);
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new SentinelClientNotConnectException("all sentinel failed");
    }

    /**
     * @return Sentinel
     */
    public function getCurrentSentinel()
    {
        return $this->currentSentinel;
    }
}