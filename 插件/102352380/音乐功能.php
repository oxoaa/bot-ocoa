<?php

use function Swoole\Coroutine\Http\get;

if ($this->用户信息 == '音乐功能') {
    $md = '![音乐功能 #20px #20px](http://api.ocoa.cn/api/catemoji/1.gif) $ \textcolor{red}{音}\textcolor{blue}{乐}\textcolor{purple}{功}\textcolor{green}{能} $ ![音乐功能 #20px #20px](http://api.ocoa.cn/api/catemoji/2.gif)
![音乐功能 #1700px #2266px](https://download.nature.qq.com/SnsShare/yxapi/2026-08-01/18:01:34/a1fd277b.jpg)';

    $键盘 = [
        'style' => ['font_size' => 'small'],
        'rows' => [
            [
                'buttons' => [
                    ['id' => '1', 'render_data' => ['label' => '哈基米', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '哈基米']],
                    ['id' => '3', 'render_data' => ['label' => '王者语音', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '王者语音']]
                ]
            ],
            [
                'buttons' => [
                    ['id' => '2', 'render_data' => ['label' => '古风国风', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '古风国风']],
                    ['id' => '3', 'render_data' => ['label' => '欧美音乐', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '欧美音乐']]
                ]
            ],
            [
                'buttons' => [
                    ['id' => '4', 'render_data' => ['label' => '旋律潮流', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '旋律潮流']],
                    ['id' => '5', 'render_data' => ['label' => '伤感音乐', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '伤感音乐']]
                ]
            ],
            [
                'buttons' => [
                    ['id' => '6', 'render_data' => ['label' => '中文歌曲', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '中文歌曲']],
                    ['id' => '7', 'render_data' => ['label' => '日韩歌曲', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '日韩歌曲']]
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

if (in_array($this->用户信息, ['古风国风', '欧美音乐', '日韩歌曲', '伤感音乐', '旋律潮流', '中文歌曲'])) {
    $cate = $this->用户信息;
    $response = get('http://api.ocoa.cn/api/music.php?type=json&cate=' . urlencode($cate));
    $data = json_decode((string)$response->getBody(), true);

    if (!empty($data['url'])) {
        $this->发送('语音', $data['url']);

        $md = ' ';

        $键盘 = [
        'style' => ['font_size' => 'small'],
            'rows' => [
                [
                    'buttons' => [
                        ['id' => '1', 'render_data' => ['label' => '继续获取', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => $cate]],
                        ['id' => '2', 'render_data' => ['label' => '更多音乐', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '音乐功能']]
                    ]
                ],
                [
                    'buttons' => [
                        ['id' => '99', 'render_data' => ['label' => '猫妹交流群', 'style' => 1], 'action' => ['type' => 0, 'permission' => ['type' => 2], 'data' => 'https://qm.qq.com/q/KilBFgFV26']]
                    ]
                ]
            ]
        ];

        $this->发送('md', null, $md, $键盘);
    }
}

if ($this->用户信息 == '哈基米') {
    $response = get('http://api.ocoa.cn/api/hjm.php');
    $data = json_decode((string)$response->getBody(), true);

    if (!empty($data['url'])) {
        $this->发送('语音', $data['url']);

        $md = ' ';

        $键盘 = [
        'style' => ['font_size' => 'small'],
            'rows' => [
                [
                    'buttons' => [
                        ['id' => '1', 'render_data' => ['label' => '继续获取', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '哈基米']],
                        ['id' => '2', 'render_data' => ['label' => '更多音乐', 'style' => 1], 'action' => ['type' => 2, 'permission' => ['type' => 2], 'data' => '音乐功能']]
                    ]
                ],
                [
                    'buttons' => [
                        ['id' => '99', 'render_data' => ['label' => '猫妹交流群', 'style' => 1], 'action' => ['type' => 0, 'permission' => ['type' => 2], 'data' => 'https://qm.qq.com/q/KilBFgFV26']]
                    ]
                ]
            ]
        ];

        $this->发送('md', null, $md, $键盘);
    }
}
