<?php


namespace Fernbruce\PhpBadWords;
//vendor/yiisoft/yii2/caching/CacheInterface.php
use DfaFilter\SensitiveHelper;
use Fernbruce\PhpBadWords\Cache\RedisCache;
use Medoo\Medoo;

class PhpBadWords
{
    private $files = [];
    private $wordsData = [];
    private $cache;
    private $db;
    private $config = [
        'dir' => __DIR__ . '/../data',
        'output' => '',
        'wordsKey' => 'wordsData',
        'replacement' => '*',
    ];

    public function __construct($cache, $db, $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->cache = $cache;
        $this->db = $db;
    }

    public function run()
    {
        if (file_exists($this->config['dir'])) {
            $this->files = [];
            foreach (scandir($this->config['dir']) as $file) {
                if (!in_array($file, ['.', '..'])) {
                    $this->files[] = $this->config['dir'] . '/' . $file;
                }
            }
            return $this->conbineWords();
        } else {
            return [];
        }
    }

    private function conbineWords()
    {
        foreach ($this->files as $file) {
            $handle = @fopen($file, 'r');
            if ($handle) {
                while (($info = fgets($handle, 1024)) !== false) {
                    $return = $this->filterWord($info);
                    if ($return['success']) {
                        $newInfo = $return['info'];
                        if (!preg_match('/^\w+$/i', $newInfo) && !empty($newInfo)) {
                            $this->wordsData[] = $newInfo;
                        }
                    }
                }
            }
        }
        if ($this->config['syncToFile']) {
            if (!empty($this->config['output'])) {
                if (file_exists($this->config['output'])) {
                    @unlink($this->config['output']);
                }
                if (!file_exists(dirname($this->config['output']))) {
                    mkdir(dirname($this->config['output']), 777, true);
                }
            } else {
                return ['success' => false, 'info' => '发生错误，output未定义。'];
            }
        }
        foreach ($this->wordsData as $word) {
            if ($this->config['syncToFile']) {
                file_put_contents($this->config['output'], $word . PHP_EOL, FILE_APPEND);
            }
            if ($this->config['syncToDb']) {
                try {

                    $this->db->insert($this->config['table_name'], [
                        'title' => $word,
//                        'created_at' => time(),
//                        'updated_at' => time(),
                    ]);
                } catch (\Exception $e) {
                    exit($e->getMessage());
                }

            }
        }
        $this->wordsData = array_values(array_filter(array_unique($this->wordsData)));
        $this->cache->save($this->config['wordsKey'], $this->wordsData);
        return $this->wordsData;
    }

    public function getWords()
    {
        if (!($this->wordsData = $this->cache->fetch($this->config['wordsKey']))) {
            //缓存中不存在
            $this->run();
            $this->wordsData = array_filter(explode(PHP_EOL, file_get_contents($this->config['output'])));
        }
        return $this->wordsData;
    }

    public function create($info)
    {
        $return = $this->filterWord($info, false);
        if (!$return['success']) {
            return $return;
        }
        $newInfo = $return['info'];

        $this->db->insert($this->config['table_name'], [
            'badword' => $newInfo,
            'replacement' => $this->config['replacement'],
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $this->wordsData[] = $newInfo;
        $this->cache->save($this->config['wordsKey'], array_values($this->wordsData));
        return ['success' => true, 'info' => '操作成功，敏感词已入库。'];
    }

    private function filterWord($info, $fromRun = true)
    {
        $newInfo = preg_replace('/[^\x{4e00}-\x{9fa5}a-zA-Z0-9]/u', "", trim($info));
        if (preg_match('/^\w+$/i', $newInfo) || empty($newInfo)) {
            return ['success' => false, 'info' => '不能入库，因为敏感词中没有中文字符。'];
        }
        if (!$fromRun) {
            if (empty($this->wordsData)) {
                $this->wordsData = $this->getWords();
            }
            if (in_array($newInfo, $this->wordsData)) {
                return ['success' => false, 'info' => '不能入库，因为词库中已经有这个敏感词。'];
            }
        }
        return ['success' => true, 'info' => $newInfo];
    }

    private function getWordById($id)
    {
        $data = $this->db->select($this->config['table_name'], [
            'badword'
        ], [
            'id' => $id
        ]);
        return $data[0]['badword'];
    }

    public function update($id, $info)
    {
        $return = $this->filterWord($info, false);
        if (!$return['success']) {
            return $return;
        }
        $newInfo = $return['info'];
        $oldInfo = $this->getWordById($id);
        if (empty($oldInfo)) {
            return ['success' => false, 'info' => "更新失败，在词库里面没有找到id:{$id}所对应的敏感词。"];
        }

        if (empty($this->wordsData)) {
            $this->wordsData = $this->getWords();
        }
        if (($key = array_search($oldInfo, $this->wordsData)) !== false) {
            $this->wordsData[$key] = $newInfo;
            $this->cache->save($this->config['wordsKey'], array_values($this->wordsData));
        }

        $this->db->update($this->config['table_name'], [
            'badword' => $newInfo,
            'updated_at' => time(),
        ], [
            'id' => $id
        ]);

        return ['success' => true, 'info' => '操作成功，敏感词已更新'];

    }

    public function delete($id)
    {
        $id = (int)$id;
        $oldInfo = $this->getWordById($id);

        if (empty($oldInfo)) {
            return ['success' => false, 'info' => '删除失败，在词库里面没有找到之前的敏感词。'];
        }
        $this->db->delete($this->config['table_name'], [
            'id' => $id
        ]);

        if (empty($this->wordsData)) {
            $this->wordsData = $this->getWords();
        }

        if (($key = array_search($oldInfo, $this->wordsData)) !== false) {
            unset($this->wordsData[$key]);
            $this->cache->save($this->config['wordsKey'], array_values($this->wordsData));
        }

        return ['success' => true, 'info' => '删除关键词成功'];

    }

}