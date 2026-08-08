<?php

use function Swoole\Coroutine\Http\get;

if ($this->用户信息 == '二次元图') {
    $response = get('http://api.ocoa.cn/api/pixiv.php');
    $data = json_decode((string)$response->getBody(), true);

    if (!empty($data['url'])) {
        $imgUrl = $data['url'];
        $md = '![二次元图 #1242px #1863px](' . $imgUrl . ')';

        $键盘 = [
            'style' => ['font_size' => 'small'],
            'rows' => [
                [
                    'buttons' => [
                        [
                            'id' => '1',
                            'render_data' => ['label' => '继续获取', 'style' => 1],
                            'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '二次元图']
                        ],
                        [
                            'id' => '2',
                            'render_data' => ['label' => '更多图片', 'style' => 1],
                            'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '图片功能']
                        ]
                    ]
                ],
                [
                    'buttons' => [
                        [
                            'id' => '99',
                            'render_data' => ['label' => '猫妹交流群', 'style' => 1],
                            'action' => ['type' => 0, 'permission' => ['type' => 2], 'data' => 'https://qm.qq.com/q/KilBFgFV26']
                        ]
                    ]
                ]
            ]
        ];

        $this->发送('md', null, $md, $键盘);
    }
}
