<?php

function get_public_ip($cache = true)
{
    $ipLocal = gethostbyname(gethostname());
    $key     = \App\Constants\CacheConst::IP_LOCAL_KEY.$ipLocal;
    $ip      = $cache ? \Cache::tags(\App\Constants\CacheTag::IP)->get($key) : '';

    if (!$ip) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ifconfig.me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $ip = curl_exec($ch);
        curl_close($ch);

        \Cache::tags(\App\Constants\CacheTag::IP)->put($key, $ip, \App\Constants\CacheConst::IP_LOCAL_KEY_TIMELIFE);
    }

    return $ip ?: $ipLocal;
}
