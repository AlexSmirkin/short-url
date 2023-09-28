<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use \Symfony\Component\Cache\Adapter\RedisAdapter;

const CACHE_TTL = 60 * 5;
const SHORT_URL_LENGTH = 9;

$client = RedisAdapter::createConnection(
    "redis://{$_ENV['REDIS_HOST']}:{$_ENV['REDIS_PORT']}"
);
$cache = new RedisAdapter($client, '', CACHE_TTL);

$path = parse_url($_SERVER['REQUEST_URI'])['path'];
$uri = array_filter(explode('/', $path));

if (isset($uri[1], $uri[2]) && $uri[1] === 'shorten') {
    $href = 'http://localhost:8082/' . urlToShortCode($cache, $path);
    echo "Короткая ссылка: <a href='$href'>$href</a><br>";
} else {
    $url = $cache->getItem(substr($path, 1))->get();
    if (!$url) {
        echo "Короткая ссылка не действительна<br>";
    } else {
        header('Location: ' . $url);
    }
}

return;

function urlToShortCode($cache, $path): string
{
    $url = substr($path, 9);

    $str = base64_encode(random_bytes(SHORT_URL_LENGTH));
    $str = str_replace(['+', '/', '='], '', $str);
    $str = substr($str, 0, SHORT_URL_LENGTH);

    $cache->get($str, function () use ($url) {
        return $url;
    });

    return $str;
}
