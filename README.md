"# php-bad-words" 
1.创建数据库,测试数据在test/目录里面 表结构数据是table_structure.sql,运行完之后需要按照项目的需要修改表名
2.把收集过来的敏感词文件放在data/目录下面
3.配置选项
'dir' 存放待合并敏感词文件的目录
'output' 合并处理之后存放敏感词的文件
'table_name' 存放敏感词的表名
'syncToFile' 是否把词库存放到文件里面
'syncToDb' 是否把词库存放到db里面
4.实例PhpBadWords对象,运行run方法,把加工处理过的敏感词同步到db和redis里面
5.用到的方法
  create创建敏感词，
  update更新敏感词，
  delete删除敏感词，
  islegal判断文本里面有没有敏感词，
  replace替换敏感词，
  mark标记敏感词，
  getBadWord获取文本里面出现的敏感词，
  
