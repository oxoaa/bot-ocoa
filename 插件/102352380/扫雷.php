<?php
/**
 * 扫雷 💣
 * 命令: 扫雷
 */

if (!function_exists("_mineAt")) {
function _mineAt(string $id): string {
    return empty($id) ? "" : "<@" . $id . ">";
}
}

$_dataDir = __DIR__ . "/../../数据";
if (!is_dir($_dataDir)) { @mkdir($_dataDir, 0755, true); }
$_mineFile = $_dataDir . "/sweep.json";

$_mine_read = function($f) {
    $fp = @fopen($f, "r"); if (!$fp) return [];
    flock($fp, LOCK_SH); $c = fread($fp, filesize($f) ?: 1);
    flock($fp, LOCK_UN); fclose($fp);
    return json_decode($c ?: "{}", true) ?: [];
};
$_mine_write = function($f, $d) {
    $fp = @fopen($f, "c"); if (!$fp) return;
    flock($fp, LOCK_EX); ftruncate($fp, 0);
    fwrite($fp, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp); flock($fp, LOCK_UN); fclose($fp);
};

// 生成棋盘图片
$_genBoardImg = function(array $board, array $revealed, int $cols, string $rid): string {
    $cellSize = 80;
    $padding = 4;
    $rows = count($board) / $cols;
    $imgW = $cols * ($cellSize + $padding) + $padding;
    $imgH = $rows * ($cellSize + $padding) + $padding;
    $img = imagecreatetruecolor($imgW, $imgH);

    // 颜色定义
    $bg = imagecolorallocate($img, 30, 30, 30);
    $cellHidden = imagecolorallocate($img, 70, 130, 200);
    $cellRevealed = imagecolorallocate($img, 220, 220, 220);
    $cellMine = imagecolorallocate($img, 255, 80, 80);
    $cellWin = imagecolorallocate($img, 80, 200, 80);
    $textWhite = imagecolorallocate($img, 255, 255, 255);
    $textDark = imagecolorallocate($img, 50, 50, 50);

    // 数字颜色
    $numColors = [
        1 => imagecolorallocate($img, 0, 100, 255),
        2 => imagecolorallocate($img, 0, 150, 0),
        3 => imagecolorallocate($img, 255, 0, 0),
        4 => imagecolorallocate($img, 100, 0, 180),
    ];

    imagefill($img, 0, 0, $bg);

    for ($i = 0; $i < count($board); $i++) {
        $col = $i % $cols;
        $row = intdiv($i, $cols);
        $x = $padding + $col * ($cellSize + $padding);
        $y = $padding + $row * ($cellSize + $padding);

        if (in_array($i, $revealed)) {
            if ($board[$i] === -1) {
                // 雷
                imagefilledrectangle($img, $x, $y, $x + $cellSize, $y + $cellSize, $cellMine);
                imagestring($img, 5, $x + 30, $y + 30, "X", $textWhite);
            } else {
                // 已翻开
                imagefilledrectangle($img, $x, $y, $x + $cellSize, $y + $cellSize, $cellRevealed);
                if ($board[$i] > 0) {
                    $color = $numColors[$board[$i]] ?? $textDark;
                    imagestring($img, 5, $x + 35, $y + 30, (string)$board[$i], $color);
                }
            }
        } else {
            // 未翻开
            imagefilledrectangle($img, $x, $y, $x + $cellSize, $y + $cellSize, $cellHidden);
            // 凸起效果
            imagerectangle($img, $x, $y, $x + $cellSize, $y + $cellSize, imagecolorallocate($img, 100, 160, 230));
        }
    }

    $dir = __DIR__ . "/../../ttt";
    @mkdir($dir, 0755, true);
    $path = "{$dir}/mine_{$rid}.png";
    imagepng($img, $path);
    return "https://bot.ocoa.cn/ttt/mine_{$rid}.png?t=" . time();
};

$_mine_kb = function(array $board, array $revealed, int $cols, string $rid) {
    $rows = [];
    $row = [];
    for ($i = 0; $i < count($board); $i++) {
        if (in_array($i, $revealed)) {
            $label = ($board[$i] === -1) ? "X" : (($board[$i] > 0) ? (string)$board[$i] : " ");
            $style = 2;
        } else {
            $label = "?";
            $style = 1;
        }
        $row[] = ["id" => "m{$i}", "render_data" => ["label" => $label, "style" => $style],
         "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "mine:{$rid}:{$i}"]];
        if (count($row) >= $cols) { $rows[] = ["buttons" => $row]; $row = []; }
    }
    if (!empty($row)) { $rows[] = ["buttons" => $row]; }
    return ["style" => ["font_size" => "small"], "rows" => $rows];
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
if ($_msg === "扫雷") {
    $size = 25; $cols = 5; $mines = 5;
    $board = array_fill(0, $size, 0);
    $minePositions = [];
    while (count($minePositions) < $mines) {
        $pos = rand(0, $size - 1);
        if (!in_array($pos, $minePositions)) { $minePositions[] = $pos; $board[$pos] = -1; }
    }
    for ($i = 0; $i < $size; $i++) {
        if ($board[$i] === -1) continue;
        $count = 0;
        if ($i > 0 && $board[$i-1] === -1) $count++;
        if ($i < $size - 1 && $board[$i+1] === -1) $count++;
        $board[$i] = $count;
    }

    $rid = "mine_" . $_uid . "_" . time();
    $state = $_mine_read($_mineFile);
    $state[$_src] = ["board" => $board, "revealed" => [], "size" => $size, "cols" => $cols, "mines" => $mines, "rid" => $rid, "status" => "playing"];
    $state["_room_" . $_src] = $_uid;
    $_mine_write($_mineFile, $state);

    $imgUrl = $_genBoardImg($board, [], $cols, $rid);
    $this->发送("md", null,
        "💣 **扫雷**\n\n"
        . "![扫雷 #420px #420px]({$imgUrl})\n\n"
        . _mineAt($_uid) . " 5x5格子 5个雷 点按钮排雷",
        $_mine_kb($board, [], $cols, $rid)
    );
    return;
}

// 按钮回调
if (strpos($_msg, "mine:") === 0) {
    $parts = explode(":", $_msg);
    if (count($parts) < 3) return;
    $rid = $parts[1];
    $param = $parts[2];

    $state = $_mine_read($_mineFile);
    $game = $state[$_src] ?? null;
    $roomOwner = $state["_room_" . $_src] ?? "";
    if ($roomOwner && $roomOwner !== $_uid) {
        $this->发送("md", null, "❌ " . _mineAt($_uid) . " 这不是你的游戏～");
        return;
    }

    if (!$game || ($game["status"] ?? "") !== "playing") {
        $this->发送("md", null, _mineAt($_uid) . " 游戏已结束 「扫雷」");
        return;
    }

    $pos = intval($param);
    $board = $game["board"];
    $revealed = $game["revealed"];
    $cols = $game["cols"];

    if (in_array($pos, $revealed)) return;
    $revealed[] = $pos;

    if ($board[$pos] === -1) {
        // 踩雷
        $allPos = range(0, count($board) - 1);
        $game["revealed"] = $allPos;
        $game["status"] = "lost";
        $state[$_src] = $game;
        $_mine_write($_mineFile, $state);
        $imgUrl = $_genBoardImg($board, $allPos, $cols, $rid);
        $this->发送("md", null,
            "💥 **踩雷了！**\n\n"
            . "![扫雷 #420px #420px]({$imgUrl})\n\n"
            . _mineAt($_uid) . " 「扫雷」",
            $_mine_kb($board, $allPos, $cols, $rid)
        );
        return;
    }

    // 检查胜利
    $safeCells = count($board) - $game["mines"];
    if (count($revealed) >= $safeCells) {
        $game["revealed"] = $revealed;
        $game["status"] = "won";
        $state[$_src] = $game;
        $_mine_write($_mineFile, $state);
        $imgUrl = $_genBoardImg($board, $revealed, $cols, $rid);
        $this->发送("md", null,
            "🎉 **扫雷成功！**\n\n"
            . "![扫雷 #420px #420px]({$imgUrl})\n\n"
            . _mineAt($_uid) . " 「扫雷」",
            $_mine_kb($board, $revealed, $cols, $rid)
        );
        return;
    }

    $game["revealed"] = $revealed;
    $state[$_src] = $game;
    $_mine_write($_mineFile, $state);
    $imgUrl = $_genBoardImg($board, $revealed, $cols, $rid);

    $this->发送("md", null,
        "✅ **安全！** 周围有 {$board[$pos]} 个雷\n\n"
        . "![扫雷 #420px #420px]({$imgUrl})\n\n"
        . _mineAt($_uid) . " 继续排雷",
        $_mine_kb($board, $revealed, $cols, $rid)
    );
    return;
}
