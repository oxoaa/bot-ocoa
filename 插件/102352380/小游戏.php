<?php
if ($this->用户信息 == "小游戏") {

    $md = '![小游戏 #20px #20px](http://api.ocoa.cn/api/catemoji/1.gif) $ \\textcolor{red}{小}\\textcolor{blue}{游}\\textcolor{purple}{戏}\\textcolor{green}{合集} $ ![小游戏 #20px #20px](http://api.ocoa.cn/api/catemoji/2.gif)
![小游戏 #1700px #2266px](https://download.nature.qq.com/SnsShare/yxapi/2026-08-01/18:01:34/a1fd277b.jpg)';

    $键盘 = [
        "style" => ["font_size" => "small"],
        "rows" => [
            [
                "buttons" => [
                    ["id" => "1", "render_data" => ["label" => "🔍 文字找茬", "style" => 1],
                     "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "文字找茬", "reply" => false, "enter" => false]],
                    ["id" => "2", "render_data" => ["label" => "🎨 找色差", "style" => 1],
                     "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "找色差", "reply" => false, "enter" => false]],
                    ["id" => "3", "render_data" => ["label" => "✊ 石头剪刀布", "style" => 1],
                     "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "石头剪刀布", "reply" => false, "enter" => false]]
                ]
            ],
            [
                "buttons" => [
                    ["id" => "4", "render_data" => ["label" => "📝 成语填空", "style" => 1],
                     "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "成语填空", "reply" => false, "enter" => false]],
                    ["id" => "5", "render_data" => ["label" => "💣 扫雷", "style" => 1],
                     "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "扫雷", "reply" => false, "enter" => false]]
                ]
            ]
        ]
    ];

    $this->发送('md', null, $md, $键盘);
}
