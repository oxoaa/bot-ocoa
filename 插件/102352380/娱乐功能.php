<?php

if ($this->用户信息 == "娱乐功能") {

    $md = '![娱乐功能 #20px #20px](http://api.ocoa.cn/api/catemoji/1.gif) $ \\textcolor{red}{娱}\\textcolor{blue}{乐}\\textcolor{purple}{功}\\textcolor{green}{能} $ ![娱乐功能 #20px #20px](http://api.ocoa.cn/api/catemoji/2.gif)
![娱乐功能 #1700px #2266px](https://download.nature.qq.com/SnsShare/yxapi/2026-08-01/18:01:34/a1fd277b.jpg)';

    $键盘 = [
        "style" => ["font_size" => "small"],
        "rows" => [
            [
                "buttons" => [
                    [
                        "id" => "1",
                        "render_data" => ["label" => "翻塔罗牌", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "翻塔罗牌", "reply" => false, "enter" => false]
                    ],
                    [
                        "id" => "2",
                        "render_data" => ["label" => "今日运势", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "今日运势", "reply" => false, "enter" => false]
                    ],
                    [
                        "id" => "3",
                        "render_data" => ["label" => "今天吃啥", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "今天吃啥", "reply" => false, "enter" => false]
                    ]
                ]
            ],
            [
                "buttons" => [
                    [
                        "id" => "1",
                        "render_data" => ["label" => "今日老婆", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "今日老婆", "reply" => false, "enter" => false]
                    ],
                    [
                        "id" => "2",
                        "render_data" => ["label" => "Doro结局", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "doro结局", "reply" => false, "enter" => false]
                    ],
                    [
                        "id" => "3",
                        "render_data" => ["label" => "随机超能力", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "随机超能力", "reply" => false, "enter" => false]
                    ]
                ]
            ],
            [
                "buttons" => [
                    [
                        "id" => "99",
                        "render_data" => ["label" => "猫妹交流群", "style" => 1],
                        "action" => ["type" => 0, "permission" => ["type" => 2], "data" => "https://qm.qq.com/q/KilBFgFV26"]
                    ]
                ]
            ]
        ]
    ];

    $this->发送('md', null, $md, $键盘);
}
