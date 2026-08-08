
if (!function_exists("_gameAt")) {
function _gameAt(string $id): string {
    return empty($id) ? "" : "<@" . $id . ">";
}
}
<?php
/**
 * 石头剪刀布 ✊✌️🖐️
 * 命令: 石头剪刀布
 * 回调按钮: rps:{choice}
 */

// ===== 数据库 =====
$_dataDir = __DIR__ . "/../../数据";
if (!is_dir($_dataDir)) { @mkdir($_dataDir, 0755, true); }
$_rpsFile = $_dataDir . "/rps_stats.json";

$_rps_read = function($f) {
    $fp = @fopen($f, "r");
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $c = fread($fp, filesize($f) ?: 1);
    flock($fp, LOCK_UN);
    fclose($fp);
    return json_decode($c ?: "{}", true) ?: [];
};

$_rps_write = function($f, $d) {
    $fp = @fopen($f, "c");
    if (!$fp) return;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
};

// ===== 游戏逻辑 =====
$_choices = ["rock" => "✊ 石头", "scissors" => "✌️ 剪刀", "paper" => "🖐️ 布"];
$_emoji = ["rock" => "✊", "scissors" => "✌️", "paper" => "🖐️"];

$_rps_result = function(string $player, string $bot): string {
    if ($player === $bot) return "draw";
    if (($player === "rock" && $bot === "scissors") ||
        ($player === "scissors" && $bot === "paper") ||
        ($player === "paper" && $bot === "rock")) {
        return "win";
    }
    return "lose";
};

$_rps_random = function(): string {
    $opts = ["rock", "scissors", "paper"];
    return $opts[array_rand($opts)];
};

// ===== 键盘 =====
$_rps_kb = function() {
    return [
        "style" => ["font_size" => "small"],
        "rows" => [[
            "buttons" => [
                ["id" => "rock", "render_data" => ["label" => "✊ 石头", "style" => 1],
                 "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "rps:rock"]],
                ["id" => "scissors", "render_data" => ["label" => "✌️ 剪刀", "style" => 1],
                 "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "rps:scissors"]],
                ["id" => "paper", "render_data" => ["label" => "🖐️ 布", "style" => 1],
                 "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "rps:paper"]],
            ]
        ]],
        [[
            "buttons" => [
                ["id" => "stats", "render_data" => ["label" => "📊 战绩", "style" => 2],
                 "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "rps:stats"]],
                ["id" => "again", "render_data" => ["label" => "🔄 再来一局", "style" => 1],
                 "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "rps:again"]]
            ]
        ]]
    ];
};

// ===== 统一入口 =====
$_uid = (string)($this->用户ID ?? "");
$_src = (string)($this->来源ID ?? "");
$_msg = '';

if (in_array($this->事件类型, ["C2C_MESSAGE_CREATE", "GROUP_AT_MESSAGE_CREATE", "GROUP_MESSAGE_CREATE"])) {
    $_msg = trim($this->用户信息);
} elseif ($this->事件类型 === "INTERACTION_CREATE" && !empty($this->按钮数据)) {
    $_msg = trim($this->按钮数据);
} else {
    return;
}

// ===== 开始游戏 =====
if ($_msg === "石头剪刀布") {
    // 记录房间归属
    $rooms = $_rps_read($_rpsFile);
    $rooms["_room_" . $_src] = $_uid;
    $_rps_write($_rpsFile, $rooms);
    
    $this->发送("md", null, "<@" . $_uid . "> " . 
        "✊✌️🖐️ **石头剪刀布！**\n\n"
        . "<@" . $_uid . "> 请出拳！\n"
        . "选一个吧 👇",
        $_rps_kb()
    );
    return;
}

// ===== 按钮回调 =====
if (strpos($_msg, "rps:") === 0) {
    $choice = substr($_msg, 4);
    
    // 验证房间归属
    $rooms = $_rps_read($_rpsFile);
    $roomOwner = $rooms["_room_" . $_src] ?? "";
    if ($roomOwner && $roomOwner !== $_uid) {
        $this->发送("md", null, "<@" . $_uid . "> " . "❌ 这不是你的游戏哦～ 「石头剪刀布」你自己的！");
        return;
    }
    
    // 查看战绩
    if ($choice === "stats") {
        $stats = $_rps_read($_rpsFile);
        $user = $stats[$_uid] ?? ["win" => 0, "lose" => 0, "draw" => 0, "streak" => 0, "best" => 0];
        $total = $user["win"] + $user["lose"] + $user["draw"];
        $rate = $total > 0 ? round($user["win"] / $total * 100, 1) : 0;
        
        $this->发送("md", null, "<@" . $_uid . "> " . 
            "📊 **<@" . $_uid . "> 的战绩**\n\n"
            . "📈 胜率：{$rate}%\n"
            . "🔥 连胜：{$user['streak']}  |  最佳：{$user['best']}连胜\n"
            . "---\n"
            . "总场次：{$total}",
            $_rps_kb()
        );
        return;
    }
    
    // 再来一局
    if ($choice === "again") {
        $this->发送("md", null, "<@" . $_uid . "> " . 
            "✊✌️🖐️ **再来！**\n\n"
            . "<@" . $_uid . "> 请出拳 👇",
            $_rps_kb()
        );
        return;
    }
    
    // 出拳
    if (!in_array($choice, ["rock", "scissors", "paper"])) return;
    
    $botChoice = $_rps_random();
    $result = $_rps_result($choice, $botChoice);
    
    // 更新战绩
    $stats = $_rps_read($_rpsFile);
    if (!isset($stats[$_uid])) {
        $stats[$_uid] = ["win" => 0, "lose" => 0, "draw" => 0, "streak" => 0, "best" => 0];
    }
    $user = &$stats[$_uid];
    $user[$result]++;
    
    if ($result === "win") {
        $user["streak"]++;
        if ($user["streak"] > $user["best"]) {
            $user["best"] = $user["streak"];
        }
    } else {
        $user["streak"] = 0;
    }
    $_rps_write($_rpsFile, $stats);
    
    // 结果
    $playerEmoji = $_emoji[$choice];
    $botEmoji = $_emoji[$botChoice];
    
    if ($result === "win") {
        $msgs = [
            "🎉 赢了！{$playerEmoji} 克 {$botEmoji}！",
            "😎 赢麻了！{$playerEmoji} 胜 {$botEmoji}！",
            "✨ 厉害！{$playerEmoji} 吃掉 {$botEmoji}！",
            "🔥 {$playerEmoji} 干翻 {$botEmoji}！你赢了！",
        ];
        $title = $msgs[array_rand($msgs)];
        $streakText = $user["streak"] > 1 ? "\n🔥 当前连胜：{$user['streak']}" : "";
    } elseif ($result === "lose") {
        $msgs = [
            "😢 输了！{$botEmoji} 克 {$playerEmoji}～",
            "💔 败了！{$botEmoji} 胜 {$playerEmoji}～",
            "😤 {$botEmoji} 把 {$playerEmoji} 吃掉了！",
            "🤣 {$botEmoji} 完克 {$playerEmoji}！你输了～",
        ];
        $title = $msgs[array_rand($msgs)];
        $streakText = "";
    } else {
        $msgs = [
            "🤝 平局！都是{$playerEmoji}！",
            "😮 想到一块了！{$playerEmoji} vs {$botEmoji}",
            "🔄 心有灵犀！都是{$playerEmoji}！",
        ];
        $title = $msgs[array_rand($msgs)];
        $streakText = "";
    }
    
    $this->发送("md", null, "<@" . $_uid . "> " . 
        $title . "\n\n"
        . "你：{$playerEmoji}  |  我：{$botEmoji}\n"
        . $streakText,
        $_rps_kb()
    );
    return;
}
