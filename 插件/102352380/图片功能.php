<?php

if ($this->用户信息 == "图片功能") {

    $md = '![图片功能 #20px #20px](http://api.ocoa.cn/api/catemoji/1.gif) $ \\textcolor{red}{图}\\textcolor{blue}{片}\\textcolor{purple}{功}\\textcolor{green}{能} $ ![图片功能 #20px #20px](http://api.ocoa.cn/api/catemoji/2.gif)
![图片功能 #1700px #2266px](https://download.nature.qq.com/SnsShare/yxapi/2026-08-01/18:01:34/a1fd277b.jpg)';

    $键盘 = [
        "style" => ["font_size" => "small"],
        "rows" => [
            [
                "buttons" => [
                    [
                        "id" => "1",
                        "render_data" => ["label" => "二次元图", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "二次元图"]
                    ],
                    [
                        "id" => "2",
                        "render_data" => ["label" => "王者皮肤", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "王者皮肤"]
                    ],
                    [
                        "id" => "3",
                        "render_data" => ["label" => "七濑胡桃", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "七濑胡桃"]
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
