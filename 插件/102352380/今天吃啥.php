<?php

use function Swoole\Coroutine\Http\get;

if ($this->用户信息 == '今天吃啥') {
    $response = get('http://api.ocoa.cn/api/jtcs.php');
    $data = json_decode((string)$response->getBody(), true);

    if (!empty($data['image'])) {
        $name = $data['image']['name'] ?? '';
        $url = $data['image']['url'] ?? '';
        $luck = $data['luck'] ?? '';
        $md = '![今天吃啥 #480px #480px](' . $url . ')
美食名称：' . $name . '
饥饿值：' . $luck;

        $键盘 = [
            'style' => ['font_size' => 'small'],
            'rows' => [
                [
                    'buttons' => [
                        [
                            'id' => '1',
                            'render_data' => ['label' => '继续获取', 'visited_label' => '', 'style' => 1],
                            'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '今天吃啥', 'reply' => false, 'enter' => false]
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
