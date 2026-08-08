<?php
/**
 * 找色差 🎨
 * 命令: 找色差
 */

if (!function_exists("_colorAt")) {
function _colorAt(string $id): string {
    return empty($id) ? "" : "<@" . $id . ">";
}
}

$_dataDir = __DIR__ . "/../../数据";
if (!is_dir($_dataDir)) { @mkdir($_dataDir, 0755, true); }
$_colorFile = $_dataDir . "/color_game.json";

$_color_read = function($f) {
    $fp = @fopen($f, "r"); if (!$fp) return [];
    flock($fp, LOCK_SH); $c = fread($fp, filesize($f) ?: 1);
    flock($fp, LOCK_UN); fclose($fp);
    return json_decode($c ?: "{}", true) ?: [];
};
$_color_write = function($f, $d) {
    $fp = @fopen($f, "c"); if (!$fp) return;
    flock($fp, LOCK_EX); ftruncate($fp, 0);
    fwrite($fp, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp); flock($fp, LOCK_UN); fclose($fp);
};

$_genColorImg = function(int $size, int $diff, string $rid): array {
    $cellSize = 120; $padding = 10;
    $imgSize = $size * ($cellSize + $padding) + $padding;
    $img = imagecreatetruecolor($imgSize, $imgSize);
    $baseR = rand(60, 200); $baseG = rand(60, 200); $baseB = rand(60, 200);
    $ansRow = rand(0, $size - 1); $ansCol = rand(0, $size - 1);
    $channel = rand(0, 2); $direction = (rand(0, 1)) ? 1 : -1;
    $diffR = max(0, min(255, $baseR + ($channel === 0 ? $diff * $direction : 0)));
    $diffG = max(0, min(255, $baseG + ($channel === 1 ? $diff * $direction : 0)));
    $diffB = max(0, min(255, $baseB + ($channel === 2 ? $diff * $direction : 0)));
    $bgColor = imagecolorallocate($img, 40, 40, 40);
    imagefill($img, 0, 0, $bgColor);
    $baseColor = imagecolorallocate($img, $baseR, $baseG, $baseB);
    $diffColor = imagecolorallocate($img, $diffR, $diffG, $diffB);
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            $x = $padding + $c * ($cellSize + $padding);
            $y = $padding + $r * ($cellSize + $padding);
            $color = ($r === $ansRow && $c === $ansCol) ? $diffColor : $baseColor;
            $radius = 15;
            imagefilledrectangle($img, $x + $radius, $y, $x + $cellSize - $radius, $y + $cellSize, $color);
            imagefilledrectangle($img, $x, $y + $radius, $x + $cellSize, $y + $cellSize - $radius, $color);
            imagefilledellipse($img, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($img, $x + $cellSize - $radius, $y + $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($img, $x + $radius, $y + $cellSize - $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($img, $x + $cellSize - $radius, $y + $cellSize - $radius, $radius * 2, $radius * 2, $color);
        }
    }
    $dir = __DIR__ . "/../../ttt";
    @mkdir($dir, 0755, true);
    $path = "{$dir}/color_{$rid}.png";
    imagepng($img, $path);
    return ["url" => "https://bot.ocoa.cn/ttt/color_{$rid}.png?t=" . time(), "answer" => $ansRow * $size + $ansCol, "size" => $size];
};

$_color_kb = function(int $size, string $rid) {
    $rows = [];
    $labels = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y"];
    for ($r = 0; $r < $size; $r++) {
        $btns = [];
        for ($c = 0; $c < $size; $c++) {
            $pos = $r * $size + $c;
            $label = $labels[$pos] ?? ($pos + 1);
            $btns[] = ["id" => "c{$pos}", "render_data" => ["label" => $label, "style" => 1],
             "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "color:ans:{$pos}"]];
        }
        $rows[] = ["buttons" => $btns];
    }
    return ["style" => ["font_size" => "small"], "rows" => $rows];
};

$_diff_kb = function() {
    return [
        "style" => ["font_size" => "small"],
        "rows" => [
            ["buttons" => [
                ["id" => "easy", "render_data" => ["label" => "简单 3x3", "style" => 1],
                 "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "color:easy:0"]]
            ]],
            ["buttons" => [
                ["id" => "normal", "render_data" => ["label" => "普通 4x4", "style" => 1],
                 "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "color:normal:0"]]
            ]],
            ["buttons" => [
                ["id" => "hard", "render_data" => ["label" => "困难 5x5", "style" => 1],
                 "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "color:hard:0"]]
            ]]
        ]
    ];
};

$_startGame = function(string $difficulty, string $uid, string $src) use ($_genColorImg, $_color_read, $_color_write, $_colorFile, $_color_kb, $_diff_kb, $_diffName) {
    $diffMap = ["easy" => [3, 60], "normal" => [4, 35], "hard" => [5, 20]];
    [$size, $diff] = $diffMap[$difficulty];
    $rid = "clr_" . $uid . "_" . time();
    $result = $_genColorImg($size, $diff, $rid);
    $state = $_color_read($_colorFile);
    $state[$src] = ["rid" => $rid, "answer" => $result["answer"], "size" => $size, "difficulty" => $difficulty, "created" => time()];
    $state["_room_" . $src] = $uid;
    $_color_write($_colorFile, $state);
    $diffName = ["easy" => "简单", "normal" => "普通", "hard" => "困难"][$difficulty];
    return ["imgUrl" => $result["url"], "size" => $size, "rid" => $rid, "diffName" => $diffName];
};

$_uid = (string)($this->用户ID ?? "");
$_src = (string)($this->来源ID ?? "");
$_msg = '';

if (in_array($this->事件类型, ["C2C_MESSAGE_CREATE", "GROUP_AT_MESSAGE_CREATE", "GROUP_MESSAGE_CREATE"])) {
    $_msg = trim($this->用户信息);
} elseif ($this->事件类型 === "INTERACTION_CREATE" && !empty($this->按钮数据)) {
    $_msg = trim($this->按钮数据);
} else { return; }

// 开始游戏
if ($_msg === "找色差") {
    $this->发送("md", null, 
        "🎨 **找色差**\n\n" . _colorAt($_uid) . " 选择难度：",
        $_diff_kb()
    );
    return;
}

// 按钮回调
if (strpos($_msg, "color:") === 0) {
    $parts = explode(":", $_msg);
    if (count($parts) < 3) return;
    $action = $parts[1];
    $param = intval($parts[2] ?? 0);

    // 选择难度
    if (in_array($action, ["easy", "normal", "hard"])) {
        $result = $_startGame($action, $_uid, $_src);
        $this->发送("md", null, 
            "🎨 **找色差 - {$result['diffName']}**\n\n"
            . "![色差图 #600px #600px]({$result['imgUrl']})\n\n"
            . _colorAt($_uid) . " 找出颜色不同的那个！",
            $_color_kb($result['size'], $result['rid'])
        );
        return;
    }

    // 答案判断
    if ($action === "ans") {
        $state = $_color_read($_colorFile);
        $game = $state[$_src] ?? null;
        if (!$game) {
            $this->发送("md", null, _colorAt($_uid) . " 「找色差」");
            return;
        }
        $roomOwner = $state["_room_" . $_src] ?? "";
        if ($roomOwner && $roomOwner !== $_uid) {
            $this->发送("md", null, "❌ " . _colorAt($_uid) . " 这不是你的游戏～");
            return;
        }

        $answer = $game["answer"];
        $size = $game["size"];
        $difficulty = $game["difficulty"];
        $labels = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y"];
        $ansLabel = $labels[$answer] ?? ($answer + 1);
        $yourLabel = $labels[$param] ?? ($param + 1);

        if ($param === $answer) {
            // 答对，自动新一局
            $newResult = $_startGame($difficulty, $_uid, $_src);
            $this->发送("md", null, 
                "🎉 " . _colorAt($_uid) . " **答对了！**\n\n"
                . "![色差图 #600px #600px]({$newResult['imgUrl']})\n\n"
                . "继续！找出颜色不同的那个 👇",
                $_color_kb($newResult['size'], $newResult['rid'])
            );
        } else {
            // 答错，自动新一局
            $newResult = $_startGame($difficulty, $_uid, $_src);
            $this->发送("md", null, 
                "❌ " . _colorAt($_uid) . " **答错了！**\n\n"
                . "![色差图 #600px #600px]({$newResult['imgUrl']})\n\n"
                . "新一局！找出颜色不同的那个 👇",
                $_color_kb($newResult['size'], $newResult['rid'])
            );
        }
        return;
    }
    return;
}
