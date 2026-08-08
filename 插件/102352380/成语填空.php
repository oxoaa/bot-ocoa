
if (!function_exists("_gameAt")) {
function _gameAt(string $id): string {
    return empty($id) ? "" : "<@" . $id . ">";
}
}
<?php
/**
 * 成语填空 📝
 * 命令: 成语填空
 * 给一个成语缺一个字，四选一
 */

$_dataDir = __DIR__ . "/../../数据";
if (!is_dir($_dataDir)) { @mkdir($_dataDir, 0755, true); }
$_cyFile = $_dataDir . "/cytk.json";

$_cytk_read = function($f) {
    $fp = @fopen($f, "r"); if (!$fp) return [];
    flock($fp, LOCK_SH); $c = fread($fp, filesize($f) ?: 1);
    flock($fp, LOCK_UN); fclose($fp);
    return json_decode($c ?: "{}", true) ?: [];
};
$_cytk_write = function($f, $d) {
    $fp = @fopen($f, "c"); if (!$fp) return;
    flock($fp, LOCK_EX); ftruncate($fp, 0);
    fwrite($fp, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp); flock($fp, LOCK_UN); fclose($fp);
};

$_idioms = [
    "一心一意","一帆风顺","一举两得","一目了然","一鸣惊人","三心二意","三顾茅庐",
    "四面八方","五颜六色","五花八门","七上八下","八仙过海","九牛一毛","十全十美",
    "百发百中","百折不挠","千变万化","千军万马","千方百计","万众一心","万无一失",
    "大公无私","大器晚成","小心翼翼","心花怒放","心旷神怡","口若悬河","手舞足蹈",
    "目不转睛","耳目一新","龙飞凤舞","马到成功","花言巧语","风和日丽","风调雨顺",
    "山清水秀","水到渠成","天长地久","人山人海","自以为是","不约而同","有目共睹",
    "无所不能","前仆后继","后来居上","东张西望","东山再起","春暖花开","秋高气爽",
    "日积月累","月明星稀","光明正大","高瞻远瞩","长驱直入","好逸恶劳","美中不足",
    "真才实学","开门见山","成竹在胸","出人头地","入木三分","生龙活虎","死里逃生",
    "老当益壮","多才多艺","先见之明","名正言顺","言传身教","行云流水","知足常乐",
    "难能可贵","动人心弦","快马加鞭","急中生智","进退两难","得寸进尺","理所当然",
    "情投意合","意气风发","志在四方","气壮山河","神机妙算","形影不离","声东击西",
    "文质彬彬","才高八斗","画龙点睛","金玉良言","铁面无私","刀光剑影","火冒三丈",
    "鸟语花香","鱼目混珠","狼心狗肺","狐假虎威","鹤立鸡群","鹏程万里","凤毛麟角",
    "龙马精神","虎头蛇尾","鸡犬不宁","牛鬼蛇神","鼠目寸光","蛇鼠一窝","虎视眈眈"
];

$_uid = (string)($this->用户ID ?? "");
$_src = (string)($this->来源ID ?? "");
$_msg = '';

if (in_array($this->事件类型, ["C2C_MESSAGE_CREATE", "GROUP_AT_MESSAGE_CREATE", "GROUP_MESSAGE_CREATE"])) {
    $_msg = trim($this->用户信息);
} elseif ($this->事件类型 === "INTERACTION_CREATE" && !empty($this->按钮数据)) {
    $_msg = trim($this->按钮数据);
} else { return; }

$_genQuestion = function() use ($_idioms) {
    $idiom = $_idioms[array_rand($_idioms)];
    $chars = mb_str_split($idiom);
    $blankPos = rand(0, 3);
    $answer = $chars[$blankPos];

    // Generate wrong options
    $allChars = [];
    foreach ($_idioms as $id) { foreach (mb_str_split($id) as $c) { $allChars[] = $c; } }
    $wrong = [];
    while (count($wrong) < 3) {
        $c = $allChars[array_rand($allChars)];
        if ($c !== $answer && !in_array($c, $wrong)) { $wrong[] = $c; }
    }

    $options = $wrong;
    array_splice($options, rand(0, 3), 0, $answer);
    shuffle($options);

    $display = $chars;
    $display[$blankPos] = "＿";

    return ["idiom" => $idiom, "display" => implode("", $display), "answer" => $answer, "options" => $options, "blankPos" => $blankPos];
};

$_cytk_kb = function(array $q, string $rid) {
    $rows = [];
    $btns = [];
    foreach ($q["options"] as $i => $opt) {
        $btns[] = ["id" => "o{$i}", "render_data" => ["label" => $opt, "style" => 1],
         "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "cytk:{$rid}:{$opt}"]];
    }
    $rows[] = ["buttons" => $btns];
    $rows[] = ["buttons" => [
        ["id" => "n", "render_data" => ["label" => "🔄 继续成语填空", "style" => 1],
         "action" => ["type" => 1, "permission" => ["type" => 2], "data" => "cytk:{$rid}:new"]]
    ]];
    return ["style" => ["font_size" => "small"], "rows" => $rows];
};

if ($_msg === "成语填空") {
    $q = $_genQuestion();
    $rid = "cytk_" . $_uid . "_" . time();
    $state = $_cytk_read($_cyFile);
    $state[$_src] = ["answer" => $q["answer"], "idiom" => $q["idiom"], "rid" => $rid];
    $state["_room_" . $_src] = $_uid;
    $_cytk_write($_cyFile, $state);

    $this->发送("md", null,
        "📝 **成语填空**\n\n"
        . "补全成语：**{$q['display']}**\n\n"
        . "<@" . $_uid . "> 选一个字！",
        $_cytk_kb($q, $rid)
    );
    return;
}

if (strpos($_msg, "cytk:") === 0) {
    $parts = explode(":", $_msg);
    if (count($parts) < 3) return;
    $rid = $parts[1];
    $choice = $parts[2];

    $state = $_cytk_read($_cyFile);
    $game = $state[$_src] ?? null;
    $roomOwner = $state["_room_" . $_src] ?? "";
    if ($roomOwner && $roomOwner !== $_uid) {
        $this->发送("md", null, "<@" . $_uid . "> " . "❌ 这不是你的游戏～");
        return;
    }

    if ($choice === "new" || !$game) {
        $q = $_genQuestion();
        $rid = "cytk_" . $_uid . "_" . time();
        $state[$_src] = ["answer" => $q["answer"], "idiom" => $q["idiom"], "rid" => $rid];
        $state["_room_" . $_src] = $_uid;
        $_cytk_write($_cyFile, $state);
        $this->发送("md", null,
            "📝 **成语填空**\n\n补全成语：**{$q['display']}**\n\n<@" . $_uid . "> 选一个字！",
            $_cytk_kb($q, $rid)
        );
        return;
    }

    $answer = $game["answer"];
    $idiom = $game["idiom"];

    if ($choice === $answer) {
        unset($state[$_src]);
        $_cytk_write($_cyFile, $state);
        $this->发送("md", null,
            "🎉 **答对了！**\n\n完整成语：**{$idiom}**\n\n「成语填空」挑战！",
            $_cytk_kb(["options" => ["A","B","C","D"], "answer" => ""], $rid)
        );
    } else {
        $this->发送("md", null,
            "❌ **答错了！**\n\n再想想看～",
            $_cytk_kb(["options" => [$choice, $answer, "想", "想"], "answer" => $answer], $rid)
        );
    }
    return;
}
