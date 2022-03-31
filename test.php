<?php
require __DIR__ . '/vendor/autoload.php';

use Fernbruce\PhpBadWords\Cache\RedisCache;
use Medoo\Medoo;

try {
    $redis = new \Redis();
    $redis->connect('127.0.0.1', 6379);
    $config = [
        'dir' => __DIR__ . '/data',
        'output' => __DIR__ . '/output/wordsData.txt',
        'table_name' => 'qs_badword',
        'syncToFile' => false,
        'syncToDb' => false,
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
    $filterHandle = \Fernbruce\PhpBadWords\DfaFilter\SensitiveHelper::init();
    $phpBadWords = new \Fernbruce\PhpBadWords\PhpBadWords(new RedisCache($redis), $db, $filterHandle, $config);
} catch (Exception $e) {
    exit(iconv('gbk', 'utf-8', $e->getMessage()));
}

//合并去重，初始化词库到文件，db和cache
//$phpBadWords->run();

//创建敏感词
//print_r($phpBadWords->create('敏感词'));
//根据id删除敏感词
//$id=16787;
//print_r($phpBadWords->delete($id));

//修改原有敏感词
//$id = 16787;
//print_r($phpBadWords->update($id, '新敏感词'));

$content = '疫情阴霾之下，中国驰名经济第一强省，率先出招了！

3月25日，广东省拿出了自2020年疫情以来最猛的纾困措施，一口气公布了3份文件、8项行动，涉及工业和服务业。

《广东省促进服务业领域困难行业恢复发展的若干措施》

《广东省促进工业经济平稳增长行动方案》

《加快推进广东预制菜产业高质量发展十条措施》

整整47条措施，细致入微。一场春天的及时雨，来了。

餐饮要额外花钱做防疫的，直接补贴！ 店家在外卖平台的服务费过高的，赶紧下调！ 旅游业遇到困难的，一定出钱！ 各种运输企业遭受成本压力的，支持融资！

当不少地方还在纠结如何精细化防疫的时候，广东再次先行一步，开始着手激发市场主体活力。

一手抓防控，一手抓经济。一场硬核“突围战”，正式打响了！';
//print_r($phpBadWords->islegal($content));
//查找语句中的敏感词
print_r($phpBadWords->getBadWord($content, 2,1));
//标记敏感词
//print_r($phpBadWords->mark($content, '<mark>', '</mark>'));
//echo PHP_EOL . '------------------------------------------' . PHP_EOL;
//替换敏感词
//print_r($phpBadWords->replace($content, '*', true));

