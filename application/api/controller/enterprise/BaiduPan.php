<?php


namespace app\api\controller\enterprise;


use app\common\controller\Api;
use fast\Http;
use think\Cache;

/**
 * 百度网盘搜索
 * Class BaiduPan
 * @package app\api\controller\enterprise
 */
class BaiduPan extends Api
{

    protected $noNeedLogin = '*';

    private $urlBase = 'https://www.dalipan.com/api/';

    /**
     * 搜索文件
     * @ApiMethod (POST)
     * @param string $kw 关键字
     * @param int $page 页码
     */
    public function search()
    {
        $kw = $this->request->param('kw');

        $page = $this->request->param('page');
        $page = $page ?: 1;

        $url    = $this->urlBase . 'search';
        $params = [
            'kw'       => trim($kw),
            'pageSize' => 10,
            'page'     => $page,
        ];

        /* 计算缓存键 */
        $cacheKey = md5($url . implode(',', $params));
        $dataList = Cache::get($cacheKey);
        if (!$dataList) {
            $datas = Http::get($url, $params);
            if (!$data = json_decode($datas, true)) {
                halt($datas);
                $this->error('查找数据异常');
            }
            $dataList = ['list' => []];
            if (isset($data['resources']) && $data['resources']) {
                foreach ($data['resources'] as $resource) {
                    $info             = [
                        'id'         => $resource['res']['id'],
                        'filename'   => $resource['res']['filename'],
                        'size'       => format_bytes($resource['res']['size']),
                        'isdir'      => $resource['res']['isdir'],
                        'ext'        => $resource['res']['ext'],
                        'haspwd'     => $resource['res']['haspwd'],
                        'valid'      => $resource['res']['valid'],
                        'expires'    => $resource['res']['expires'] == 0 ? '永久' : date('Y-m-d H:i:s', $resource['res']['expires']),
                        'updatetime' => $resource['res']['utime'],
                        'createtime' => $resource['res']['ctime'],
                    ];
                    $info['filelist'] = null;
                    if (isset($resource['res']['filelist']) && $resource['res']['filelist']) {
                        foreach ($resource['res']['filelist'] as $re) {
                            $info['filelist'][] = [
                                'isdir'        => $re['isdir'],
                                'size'         => format_bytes($re['size']),
                                'path'         => $re['path'],
                                'filename'     => $re['filename'],
                                'server_ctime' => $re['server_ctime'],
                                'originmd5'    => $re['originmd5'],
                                'ext'          => $re['ext'],
                            ];
                        }
                    }

                    $info['views']     = $resource['res']['views'];
                    $info['downloads'] = $resource['res']['downloads'];


                    $dataList['list'][] = $info;
                }
            }

            /* 扩展参数 */
            $dataList['extend'] = [
                'total' => $data['total'],
            ];

            /* 参数说明 */
            $dataList['desc'] = [
                'type' => 'list',
                'list' => [
                    [
                        'key'  => 'id',
                        'name' => '唯一标示',
                        'type' => 'string',
                        'list' => null,
                    ],
                    [
                        'key'  => 'filename',
                        'name' => '文件名',
                        'type' => 'string',
                        'list' => null,
                    ],
                    [
                        'key'  => 'size',
                        'name' => '文件大小',
                        'type' => 'string',
                        'list' => null,
                    ],
                    [
                        'key'  => 'isdir',
                        'name' => '是否为文件夹',
                        'type' => 'bool',
                        'list' => null,
                    ],
                    [
                        'key'  => 'ext',
                        'name' => '文件扩展名',
                        'type' => 'string',
                        'list' => null,
                    ],
                    [
                        'key'  => 'haspwd',
                        'name' => '是否有密码',
                        'type' => 'bool',
                        'list' => null,
                    ],
                    [
                        'key'  => 'valid',
                        'name' => '是否有效',
                        'type' => 'bool',
                        'list' => null,
                    ],
                    [
                        'key'  => 'expires',
                        'name' => '有效期',
                        'type' => 'string',
                        'list' => null,
                    ],
                    [
                        'key'  => 'updatetime',
                        'name' => '更新时间',
                        'type' => 'string',
                        'list' => null,
                    ],
                    [
                        'key'  => 'createtime',
                        'name' => '创建时间',
                        'type' => 'string',
                        'list' => null,
                    ],
                    [
                        'key'  => 'filelist',
                        'name' => '文件列表',
                        'type' => 'array',
                        'list' => [
                            [
                                'key'  => 'isdir',
                                'name' => '是否为文件夹',
                                'type' => 'string',
                                'list' => null,
                            ],
                            [
                                'key'  => 'size',
                                'name' => '文件大小',
                                'type' => 'string',
                                'list' => null,
                            ],
                            [
                                'key'  => 'path',
                                'name' => '文件路径',
                                'type' => 'string',
                                'list' => null,
                            ],
                            [
                                'key'  => 'filename',
                                'name' => '文件名',
                                'type' => 'string',
                                'list' => null,
                            ],
                            [
                                'key'  => 'server_ctime',
                                'name' => '创建时间',
                                'type' => 'string',
                                'list' => null,
                            ],
                            [
                                'key'  => 'originmd5',
                                'name' => '文件MD5',
                                'type' => 'string',
                                'list' => null,
                            ],
                            [
                                'key'  => 'ext',
                                'name' => '文件扩展名',
                                'type' => 'string',
                                'list' => null,
                            ],
                        ],
                    ],
                ],
            ];

            Cache::set($cacheKey, $dataList, 0);
        }

        $this->success('获取成功', $dataList);

    }

}