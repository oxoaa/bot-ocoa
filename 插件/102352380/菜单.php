<?php

if ($this->用户信息 == "菜单") {

    // 记录用户ID
    $用户列表 = $this->数据库("读", "菜单/用户列表") ?? [];
    if (!in_array($this->用户ID, $用户列表)) {
        $用户列表[] = $this->用户ID;
        $this->数据库("写", "菜单/用户列表", $用户列表);
    }

    // 记录群号（群聊时）
    if (in_array($this->事件类型, ["GROUP_AT_MESSAGE_CREATE", "GROUP_MESSAGE_CREATE"])) {
        $群列表 = $this->数据库("读", "菜单/群列表") ?? [];
        if (!in_array($this->来源ID, $群列表)) {
            $群列表[] = $this->来源ID;
            $this->数据库("写", "菜单/群列表", $群列表);
        }
    }

    $md = '![功能菜单 #20px #20px](http://api.ocoa.cn/api/catemoji/1.gif) $ \\textcolor{red}{功}\\textcolor{blue}{能}\\textcolor{purple}{列}\\textcolor{green}{表} $ ![功能菜单 #20px #20px](http://api.ocoa.cn/api/catemoji/2.gif)
![功能菜单 #1700px #2266px](https://download.nature.qq.com/SnsShare/yxapi/2026-08-01/18:01:34/a1fd277b.jpg)
>![ABC #20px #20px](http://api.ocoa.cn/api/catemoji/5.gif) <qqbot-cmd-input text="天气查询" show="天气查询"/> | ![ABC #20px #20px](http://api.ocoa.cn/api/catemoji/6.gif) <qqbot-cmd-input text="漫剪视频" show="漫剪视频"/>';

    $键盘 = [
        "style" => ["font_size" => "small"],
        "rows" => [
            [
                "buttons" => [
                    [
                        "id" => "1",
                        "render_data" => ["label" => "图片功能", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "图片功能"]
                    ],
                    [
                        "id" => "2",
                        "render_data" => ["label" => "娱乐功能", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "娱乐功能"]
                    ]
                ]
            ],
            [
                "buttons" => [
                    [
                        "id" => "3",
                        "render_data" => ["label" => "音乐功能", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "音乐功能"]
                    ],
                    [
                        "id" => "4",
                        "render_data" => ["label" => "点歌功能", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "点歌功能"]
                    ],
                    [
                        "id" => "10",
                        "render_data" => ["label" => "小游戏", "style" => 1],
                        "action" => ["type" => 2, "permission" => ["type" => 2], "data" => "小游戏"]
                    ]
                ]
            ],
            [
                "buttons" => [
                    [
                        "id" => "7",
                        "render_data" => ["label" => "知鱼小栈", "style" => 1],
                        "action" => ["type" => 0, "permission" => ["type" => 2], "data" => "https://m.q.qq.com/a/s/6383ae34f73f898c3dcde9e1e30793be"]
                    ],
                    [
                        "id" => "8",
                        "render_data" => ["label" => "猫粮投喂", "style" => 1],
                        "action" => ["type" => 0, "permission" => ["type" => 2], "data" => "https://www.yuque.com/yuqueyonghuniy4cb/kka4vu/cmh27ic4i2debiss"]
                    ]
                ]
            ],

        ]
    ];

    $this->发送('md', null, $md, $键盘);
}
