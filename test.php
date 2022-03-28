<?php
require __DIR__ . '/vendor/autoload.php';
//$phpbadwords = new \Fernbruce\PhpBadWords\PhpBadWords();
//var_dump($phpbadwords->run());
//$phpbadwords->import('data/txt/百度过滤词.txt');
use HashyooWordsSafe\WordsSafe;
use WangNingkai\SimpleDictionary\CharIterator;
use WangNingkai\SimpleDictionary\SimpleDictionary;
use FilterWordData\FilterFiles;
use DfaFilter\SensitiveHelper;

$letters = [48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 5, 86, 87, 88, 89, 90, 97, 98, 99, 100, 101, 102, 103104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122];
$punctuation = [32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 94, 95, 96, 123, 124, 125, 126, 127];

/*$handle = SensitiveHelper::init()->setTree($array);
$content = '中华人民共和国中央人民政府';
$islegal = $handle->islegal($content);
//var_dump($islegal);
$words = $handle->getBadWord($content);
var_dump($words);
$filterContent = $handle->replace($content, '*', true);
var_dump($filterContent);*/
$phpBadWords = new \Fernbruce\PhpBadWords\PhpBadWords(__DIR__ . '/data', __DIR__ . '/output/wordsData.txt');
//$wordsData = $phpBadWords->run();
//print_r($wordsData);
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$redis->set('aaa', 'afafaf');
var_dump($redis->get('aaa'));
$driver = new \Cake\Database\Driver\Mysql([
    'database' => 'yii',
    'username' => 'root',
    'password' => '0000'
]);
$connection = new \Cake\Database\Connection([
    'driver' => $driver
]);
$statement = $connection->execute('select * from yzm_article');
while($row = $statement->fetch('assoc')){
    echo $row['title'].PHP_EOL;
}
exit;
//require_once "class/Banned.php";
//$banned = new Banned();
//$banned->check_type = "file";
//$banned->checkAll();
//var_dump($banned);

$rs = SimpleDictionary::make('./data/txt/百度敏感词.txt', './data/output_txt/baidu.txt');
var_dump($rs);


