<?php

use function Swoole\Coroutine\Http\get;

if ($this->用户信息 == '随机超能力') {
    $response = get('http://api.ocoa.cn/api/cnl.php');
    $data = json_decode((string)$response->getBody(), true);

    $power = $data['power'] ?? '未知超能力';
    $disadvantage = $data['but'] ?? '未知副作用';
    $md = '你的超能力是：**' . $power . '**

但是副作用是：**' . $disadvantage . '**';

    $键盘 = [
        'style' => ['font_size' => 'small'],
        'rows' => [
            [
                'buttons' => [
                    [
                        'id' => '1',
                        'render_data' => ['label' => '继续获取', 'visited_label' => '', 'style' => 1],
                        'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '随机超能力', 'reply' => false, 'enter' => false]
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
