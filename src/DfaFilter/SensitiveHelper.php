<?php


namespace Fernbruce\PhpBadWords\DfaFilter;

use Fernbruce\PhpBadWords\DfaFilter\Exceptions\PdsBusinessException;

class SensitiveHelper
{
    /**
     * 待检测语句长度
     * @var int
     */
    protected $contentLength = 0;

    /**
     * 敏感词单例
     * @var object|null
     */
    private static $_instance = null;

    /**
     * 敏感词库树
     * @var HashMap|null
     */
    protected $wordTree = null;

    /**
     * 存放待检测语句敏感词
     * @var array|null
     */
    protected static $badWordList = null;

    public static function init()
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function setTreeByFile($filepath)
    {
        if (!file_exists($filepath)) {
            throw new PdsBusinessException('词库文件不存在', PdsBusinessException::CANNOT_FIND_FILE);
        }

        $this->wordTree = $this->wordTree ?: new HashMap();

        foreach ($this->yieldToReadFile($filepath) as $word) {
            $this->buildWordToTree(trim($word));
        }

        return $this;
    }

    public function setTree($sensitiveWords = null)
    {
        if (empty($sensitiveWords)) {
            throw new PdsBusinessException(PdsBusinessException::EMPTY_WORD_POOL);
        }

        $this->wordTree = new HashMap();

        foreach ($sensitiveWords as $word) {
            $this->buildWordToTree($word);
        }
        return $this;
    }

    public function getBadWord($content, $matchType = 1, $wordNum = 0)
    {
        $this->contentLength = mb_strlen($content, 'utf-8');
        $badWordList = array();
        for ($length = 0; $length < $this->contentLength; $length++) {
            $matchFlag = 0;
            $flag = false;
            $tempMap = $this->wordTree;
            for ($i = $length; $i < $this->contentLength; $i++) {
                $keyChar = mb_substr($content, $i, 1, 'utf-8');

                // 获取指定节点树
                $nowMap = $tempMap->get($keyChar);

                // 不存在节点树，直接返回
                if (empty($nowMap)) {
                    break;
                }

                // 存在，则判断是否为最后一个
                $tempMap = $nowMap;

                // 找到相应key，偏移量+1
                $matchFlag++;

                // 如果为最后一个匹配规则,结束循环，返回匹配标识数
                if (false === $nowMap->get('ending')) {
                    continue;
                }

                $flag = true;

                // 最小规则，直接退出
                if (1 === $matchType) {
                    break;
                }
            }

            if (!$flag) {
                $matchFlag = 0;
            }

            // 找到相应key
            if ($matchFlag <= 0) {
                continue;
            }

            $matchedWord = mb_substr($content, $length, $matchFlag, 'utf-8');
            if (!in_array($matchedWord, $badWordList)) {
                $badWordList[] = mb_substr($content, $length, $matchFlag, 'utf-8');
            }

            // 有返回数量限制
            if ($wordNum > 0 && count($badWordList) == $wordNum) {
                return $badWordList;
            }

            // 需匹配内容标志位往后移
            $length = $length + $matchFlag - 1;
        }
        return $badWordList;
    }

    public function replace($content, $replaceChar = '', $repeat = false, $matchType = 1)
    {
        if (empty($content)) {
            throw new PdsBusinessException('请填写检测的内容', PdsBusinessException::EMPTY_CONTENT);
        }

        $badWordList = self::$badWordList ? self::$badWordList : $this->getBadWord($content, $matchType);

        // 未检测到敏感词，直接返回
        if (empty($badWordList)) {
            return $content;
        }

        foreach ($badWordList as $badWord) {
            $hasReplacedChar = $replaceChar;
            if ($repeat) {
                $hasReplacedChar = $this->dfaBadWordConversChars($badWord, $replaceChar);
            }
            $content = str_replace($badWord, $hasReplacedChar, $content);
        }
        return $content;
    }

    public function mark($content, $sTag, $eTag, $matchType = 1)
    {
        if (empty($content)) {
            throw new PdsBusinessException('请填写检测的内容', PdsBusinessException::EMPTY_CONTENT);
        }

        $badWordList = self::$badWordList ? self::$badWordList : $this->getBadWord($content, $matchType);

        // 未检测到敏感词，直接返回
        if (empty($badWordList)) {
            return $content;
        }

        foreach ($badWordList as $badWord) {
            $replaceChar = $sTag . $badWord . $eTag;
            $content = str_replace($badWord, $replaceChar, $content);
        }
        return $content;
    }

    public function islegal($content)
    {
        $this->contentLength = mb_strlen($content, 'utf-8');

        for ($length = 0; $length < $this->contentLength; $length++) {
            $matchFlag = 0;

            $tempMap = $this->wordTree;
            for ($i = $length; $i < $this->contentLength; $i++) {
                $keyChar = mb_substr($content, $i, 1, 'utf-8');

                // 获取指定节点树
                $nowMap = $tempMap->get($keyChar);

                // 不存在节点树，直接返回
                if (empty($nowMap)) {
                    break;
                }

                // 找到相应key，偏移量+1
                $tempMap = $nowMap;
                $matchFlag++;

                // 如果为最后一个匹配规则,结束循环，返回匹配标识数
                if (false === $nowMap->get('ending')) {
                    continue;
                }

                return false;
            }

            // 找到相应key
            if ($matchFlag <= 0) {
                continue;
            }

            // 需匹配内容标志位往后移
            $length = $length + $matchFlag - 1;
        }
        return true;
    }

    protected function yieldToReadFile($filepath)
    {
        $fp = fopen($filepath, 'r');
        while (!feof($fp)) {
            yield fgets($fp);
        }
        fclose($fp);
    }

    protected function buildWordToTree($word = '')
    {
        if ('' === $word) {
            return;
        }
        $tree = $this->wordTree;

        $wordLength = mb_strlen($word, 'utf-8');
        for ($i = 0; $i < $wordLength; $i++) {
            $keyChar = mb_substr($word, $i, 1, 'utf-8');

            // 获取子节点树结构
            $tempTree = $tree->get($keyChar);

            if ($tempTree) {
                $tree = $tempTree;
            } else {
                // 设置标志位
                $newTree = new HashMap();
                $newTree->put('ending', false);

                // 添加到集合
                $tree->put($keyChar, $newTree);
                $tree = $newTree;
            }

            // 到达最后一个节点
            if ($i == $wordLength - 1) {
                $tree->put('ending', true);
            }
        }

        return;
    }

    protected function dfaBadWordConversChars($word, $char)
    {
        $str = '';
        $length = mb_strlen($word, 'utf-8');
        for ($counter = 0; $counter < $length; ++$counter) {
            $str .= $char;
        }

        return $str;
    }


}