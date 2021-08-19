<?php

namespace Think;

class Redis {

    static private $instance = null;

    public function __construct()
    {
        if (self::$instance === null) self::getInstance();
    }

    static private function getInstance($config = []) {
        if (self::$instance === null) {
            if (empty($config)) $config = self::getConfig();

            self::$instance = new \Redis();
            self::$instance->connect($config['host'], $config['port']);

            if($config['password']) self::$instance->auth($config['password']);

            if($config['db']) self::$instance->select($config['db']);
        }

        return self::$instance;
    }

    static private function getConfig() {
        return [
            'host' => C('REDIS_HOST'),
            'port' => C('REDIS_POST'),
            'password' => C('REDIS_PASSWORD'),
            'db' => C('REDIS_DB'),
        ];
    }

    public function set($key, $value, $expire = false)
    {
        if ($expire) {
            return self::$instance->set($key, $value, $expire);
        } else {
            return self::$instance->set($key, $value);
        }
    }

    public function get($key)
    {
        return self::$instance->get($key);
    }

    public function executeEval($lua_script, $params, $params_number)
    {
        return self::$instance->eval($lua_script, $params, $params_number);
    }

    public function setnx($key, $value)
    {
        return self::$instance->setnx($key, $value);
    }

    public function expire($key, $time_limit)
    {
        return self::$instance->expire($key, $time_limit);
    }

    public function multi()
    {
        return self::$instance->multi();
    }

    public function exec()
    {
        return self::$instance->exec();
    }

    public function incr($key)
    {
        return self::$instance->incr($key);
    }

    public function watch($key)
    {
        return self::$instance->watch($key);
    }

    public function discard()
    {
        return self::$instance->discard();
    }

    public function exists($key)
    {
        return self::$instance->exists($key);
    }

    public function hset($key, $field, $value)
    {
        return self::$instance->hset($key, $field, $value);
    }

    public function hget($key, $field)
    {
        return self::$instance->hget($key, $field);
    }

    public function hdel($key, $field)
    {
        return self::$instance->hdel($key, $field);
    }

    public function hsetnx($key, $field, $value)
    {
        return self::$instance->hsetnx($key, $field, $value);
    }

    public function ttl($key)
    {
        return self::$instance->ttl($key);
    }

    public function zadd($key, $score, $member)
    {
        return self::$instance->zadd($key, $score, $member);
    }

    public function del($key)
    {
        return self::$instance->del($key);
    }
}