<?php

use function Swoole\Coroutine\Http\get;

if ($this->用户信息 == '漫剪视频') {
    $response = get('http://api.ocoa.cn/api/mjsp.php');
    $data = json_decode((string)$response->getBody(), true);

    if (!empty($data['url'])) {
        $videoUrl = $data['url'];
        $this->发送('视频', $videoUrl);

        $md = ' ';

        $键盘 = [
            'style' => ['font_size' => 'small'],
            'rows' => [
                [
                    'buttons' => [
                        [
                            'id' => '1',
                            'render_data' => ['label' => '继续获取', 'style' => 1],
                            'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '漫剪视频']
                        ],
                        [
                            'id' => '2',
                            'render_data' => ['label' => '更多功能', 'style' => 1],
                            'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '菜单']
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
