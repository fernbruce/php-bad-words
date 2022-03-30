<?php


namespace Fernbruce\PhpBadWords;

use Fernbruce\PhpBadWords\DfaFilter\Exceptions\PdsBusinessException;

class PhpBadWords
{
    private $files = [];
    private $wordsData = [];
    private $cache;
    private $db;
    private $filterHandler;
    private $config = [
        'dir' => __DIR__ . '/../data',
        'output' => '',
        'wordsKey' => 'wordsData',
        'replacement' => '*',
        'syncToFile' => true,
        'syncToDb' => false,
    ];

    public function __construct($cache, $db, $filterHandler, $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->cache = $cache;
        $this->db = $db;
        $this->filterHandler = $filterHandler;
    }

    /**
     * 初始化词库到redis和db
     * @return array
     */
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
            return ['success' => false, 'info' => '目录下面没有文件'];
        }
    }

    /**
     * 合并多个文件里面的关键词
     * @return array
     */
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

        $this->wordsData = array_values(array_filter(array_unique($this->wordsData)));
        $this->cache->save($this->config['wordsKey'], $this->wordsData);

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
                        'badword' => $word,
                        'replacement' => '*',
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);
                } catch (\Exception $e) {
                    exit($e->getMessage());
                }

            }
        }

        return $this->wordsData;
    }

    /**
     * 从缓存里面获取词库，如果没有初始化词库
     * @return false|string[]
     */
    private function getWords()
    {
        if (!($this->wordsData = $this->cache->fetch($this->config['wordsKey']))) {
            //缓存中不存在
            $this->run();
            $this->wordsData = array_filter(explode(PHP_EOL, file_get_contents($this->config['output'])));
        }
        return $this->wordsData;
    }

    /**
     * 添加一个敏感词到db
     * @param $info
     * @return array
     */
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
            'is_from' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $this->wordsData[] = $newInfo;
        $this->cache->save($this->config['wordsKey'], array_values($this->wordsData));
        return ['success' => true, 'info' => '操作成功，敏感词已入库。'];
    }

    /**
     * 判断敏感词是否符合入库条件
     * @param $info
     * @param bool $fromRun
     * @return array
     */
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

    /**
     * 通过id从db里面查找敏感词
     * @param $id
     * @return mixed|string
     */
    private function getWordById($id)
    {
        $data = $this->db->select($this->config['table_name'], [
            'badword'
        ], [
            'id' => $id
        ]);
        return $data[0]['badword'] ?? '';
    }

    /**
     * 根据id修改一个敏感词
     * @param $id
     * @param $info
     * @return array
     */
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
            'is_from' => 1,
            'updated_at' => time(),
        ], [
            'id' => $id
        ]);

        return ['success' => true, 'info' => '操作成功，敏感词已更新'];

    }

    /**
     * 通过id从db里面删除里面关键词
     * @param $id
     * @return array
     */
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

    /**
     * 生成敏感词树
     * @return mixed
     * @throws PdsBusinessException
     */
    private function setTree()
    {
        if ($this->wordsData = $this->cache->fetch($this->config['wordsKey'])) {
            return $this->filterHandler->setTree($this->wordsData);
        }
        throw new PdsBusinessException('请先初始化词库到缓存中', PdsBusinessException::CANNOT_FIND_CACHE);
    }

    /**
     * 判断文本里面是否包含敏感词信息
     * @param $content
     * @return array
     */
    public function islegal($content)
    {
        try {
            return [
                'success' => true,
                'info' => $this->setTree()->islegal($content)
            ];
        } catch (PdsBusinessException $e) {
            return [
                'success' => false,
                'info' => $e->getMessage()
            ];
        }
    }

    /**
     * 替换文本中的敏感词
     * @param $content
     * @param string $replaceChar
     * @param false $repeat
     * @param int $matchType
     * @return array
     */
    public function replace($content, $replaceChar = '', $repeat = false, $matchType = 1)
    {
        try {
            return [
                'success' => true,
                'info' => $this->setTree()->replace($content, $replaceChar, $repeat, $matchType),
            ];
        } catch (PdsBusinessException $e) {
            return [
                'success' => false,
                'info' => $e->getMessage()
            ];
        }
    }

    /**
     * 标记文文本中的敏感词
     * @param $content
     * @param $sTag
     * @param $eTag
     * @param int $matchType
     * @return array
     */
    public function mark($content, $sTag, $eTag, $matchType = 1)
    {
        try {
            return [
                'success' => true,
                'info' => $this->setTree()->mark($content, $sTag, $eTag, $matchType),
            ];
        } catch (PdsBusinessException $e) {
            return [
                'success' => false,
                'info' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取文本中的敏感词
     * @param $content
     * @param int $matchType
     * @param int $wordNum
     * @return array
     */
    public function getBadWord($content, $matchType = 1, $wordNum = 0)
    {
        try {
            return [
                'success' => true,
                'info' => $this->setTree()->getBadWord($content, $matchType, $wordNum),
            ];
        } catch (PdsBusinessException $e) {
            return [
                'success' => false,
                'info' => $e->getMessage()
            ];
        }
    }
}