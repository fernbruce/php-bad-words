<?php


namespace Fernbruce\PhpBadWords\Cache;


use Doctrine\Common\Cache\CacheProvider;

class RedisCache extends CacheProvider
{
    private $redisInstance;

    public function __construct($redisInstance)
    {
        $this->redisInstance = $redisInstance;
    }

    public function doContains($id)
    {

    }

    public function doFetch($id)
    {
        $buffer = $this->redisInstance->get($id);
        if (is_array(json_decode($buffer))) {
            return json_decode($buffer, true);
        } else {
            return $buffer;
        }
    }

    public function doGetStats()
    {
    }

    public function doDelete($id)
    {
        return $this->redisInstance->delete($id);
    }

    public function doSave($id, $data, $lifeTime = 0)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $this->redisInstance->set($id, $data);
        if ($lifeTime > 0) {
            $this->redisInstance->expire($id, $lifeTime);
        }
    }

    public function doFlush()
    {

    }
}