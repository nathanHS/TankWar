<?php
    namespace Init;

    function initRedis() {
        $redis_instance = new \Redis();
        $redis_instance->pconnect('127.0.0.1',6379);
        return $redis_instance;
    }

