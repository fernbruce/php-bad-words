<?php


namespace Fernbruce\PhpBadWords\DfaFilter\Exceptions;

use Exception;

class PdsBusinessException extends Exception
{
    const EMPTY_CONTENT = 10001;   // 空检测文本内容
    const EMPTY_WORD_POOL = 10002;    // 空词库
    const CANNOT_FIND_FILE = 10003;    // 找不到词库文件
    const CANNOT_FIND_CACHE = 10004;   //找不到缓存
}