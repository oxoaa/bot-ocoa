<?php

use function Swoole\Coroutine\Http\get;

if ($this->用户信息 == '今日老婆') {
    $response = get('http://api.ocoa.cn/api/jrlp.php');
    $data = json_decode((string)$response->getBody(), true);

    if (!empty($data['url'])) {
        $name = $data['name'] ?? '';
        $wide = $data['wide'] ?? 512;
        $high = $data['high'] ?? 512;
        $url = $data['url'];
        $md = '<@' . $this->用户ID . '>
你的老婆是：**' . $name . '**
![今日老婆 #' . $wide . 'px #' . $high . 'px](' . $url . ')';

        $键盘 = [
            'style' => ['font_size' => 'small'],
            'rows' => [
                [
                    'buttons' => [
                        [
                            'id' => '1',
                            'render_data' => ['label' => '继续获取', 'visited_label' => '', 'style' => 1],
                            'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '今日老婆', 'reply' => false, 'enter' => false]
                        ],
                        [
                            'id' => '2',
                            'render_data' => ['label' => '更多娱乐', 'visited_label' => '', 'style' => 1],
                            'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '娱乐功能', 'reply' => false, 'enter' => false]
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
