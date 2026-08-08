<?php

if ($this->用户信息 == "点歌功能") {

    $md = '![点歌功能 #20px #20px](http://api.ocoa.cn/api/catemoji/1.gif) $ \\textcolor{red}{点}\\textcolor{blue}{歌}\\textcolor{purple}{功}\\textcolor{green}{能} $ ![点歌功能 #20px #20px](http://api.ocoa.cn/api/catemoji/2.gif)
![点歌功能 #1700px #2266px](https://download.nature.qq.com/SnsShare/yxapi/2026-08-01/18:01:34/a1fd277b.jpg)';

    $键盘 = [
        "style" => ["font_size" => "small"],
        "rows" => [
            [
                "buttons" => [
                    [
                        "id" => "1",
                        "render_data" => ["label" => "QQ点歌", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "QQ点歌", "reply" => false, "enter" => false]
                    ],
                    [
                        "id" => "2",
                        "render_data" => ["label" => "网易点歌", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "网易点歌", "reply" => false, "enter" => false]
                    ]
                ]
            ],
            [
                "buttons" => [
                    [
                        "id" => "3",
                        "render_data" => ["label" => "酷狗点歌", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "酷狗点歌", "reply" => false, "enter" => false]
                    ],
                    [
                        "id" => "4",
                        "render_data" => ["label" => "汽水点歌", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "汽水点歌", "reply" => false, "enter" => false]
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
