<?php


namespace Fernbruce\PhpBadWords;

use DfaFilter\SensitiveHelper;

class PhpBadWords
{
    private $dir;
    private $files = [];
    private $wordsData = [];
    private $punctuation = [32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 94, 95, 96, 123, 124, 125, 126, 127];

    public function __construct($dir = '')
    {
        if (!empty($dir)) {
            $this->dir = $dir;
        } else {
            $this->dir = dirname(__DIR__) . '/data';
        }
    }

    public function run()
    {
        if (file_exists($this->dir)) {
            foreach (scandir($this->dir) as $file) {
                if (!in_array($file, ['.', '..'])) {
                    $this->files[] = $this->dir . '/' . $file;
                }
            }
            return $this->conbineWords();
        } else {
            return [];
        }
    }

    public function conbineWords()
    {
        foreach ($this->files as $file) {
            $handle = @fopen($file, 'r');
            if ($handle) {
                while (($info = fgets($handle, 1024)) !== false) {
                    $newInfo = "";
                    $itr = new \Fernbruce\PhpBadWords\CharIterator(trim($info));
                    foreach ($itr as $char) {
                        if (ord($char) < 128) {
                            if (in_array(ord($char), $this->punctuation)) {
                                continue;
                            }
                        }
                        $newInfo .= $char;
                    }
                    if (!preg_match('/^\w+$/i', $newInfo)) {
                        $this->wordsData[] = $newInfo;
                    }
                }
            }
        }
        return $this->wordsData = array_filter(array_unique($this->wordsData));
    }


}