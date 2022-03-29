<?php
require __DIR__ . '/vendor/autoload.php';

use Fernbruce\PhpBadWords\Cache\RedisCache;
use HashyooWordsSafe\WordsSafe;
use Medoo\Medoo;
use WangNingkai\SimpleDictionary\CharIterator;
use WangNingkai\SimpleDictionary\SimpleDictionary;
use FilterWordData\FilterFiles;
use DfaFilter\SensitiveHelper;

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$config = [
    'dir' => __DIR__ . '/data',
    'output' => __DIR__ . '/output/wordsData.txt',
    'table_name' => 'qs_badword',
];
$db = new Medoo([
    'database_type' => 'mysql',
    'server' => '192.168.100.27',
    'database_name' => 'www_haolietou_com',
    'username' => 'ruifan',
    'password' => 'ruifan123456',
    'charset' => 'utf8',
    'port' => 3307,
]);
$phpBadWords = new \Fernbruce\PhpBadWords\PhpBadWords(new RedisCache($redis), $db, $config);
