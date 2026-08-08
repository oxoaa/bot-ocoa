<?php

use function Swoole\Coroutine\Http\get;

if (preg_match('/^天气查询\s*(.*)$/', $this->用户信息, $match)) {
    $city = trim($match[1] ?? '');

    if (empty($city)) {
        $this->发送('文本', '请在指令后面输入城市名称
例如：天气查询 北京');
        return;
    }

    $url = 'https://api.suol.cc/v1/tq_tips.php?type=weather&n=1&msg=' . urlencode($city);
    $response = get($url);
    $json = json_decode((string)$response->getBody(), true);

    if (($json['code'] ?? 0) !== 200 || empty($json['data'])) return;

    $current = $json['data']['current'];
    $forecast = $json['data']['forecast'];

    $summary = "🌦️ 天气查询：{$current['city']}\r" .
        "> 日期：{$current['date']}\r" .
        "> 天气：{$current['weather']}，{$current['temp']}℃，{$current['wind']}\r" .
        "> 湿度：{$current['humidity']} ｜ AQI：{$current['aqi']}\r" .
        "> 更新时间：{$current['updateTime']}\r\r";

    $currentTable = "### 🌤 当前天气\r" .
        "| 城市 | 日期 | 天气 | 温度 | 风向 | 湿度 | AQI | 更新时间 |\r" .
        "|----|----|----|----|----|----|----|----|\r" .
        "| {$current['city']} | {$current['date']} | {$current['weather']} | {$current['temp']}℃ | {$current['wind']} | {$current['humidity']} | {$current['aqi']} | {$current['updateTime']} |\r\r";

    $forecastTable = "### 📅 未来天气预报\r" .
        "| 日期 | 星期 | 最高温 | 最低温 | 白天风向 | 夜间风向 |\r" .
        "|----|----|----|----|----|----|\r";

    foreach ($forecast as $day) {
        $forecastTable .= "| {$day['date']} | {$day['dayName']} | {$day['tempHigh']}℃ | {$day['tempLow']}℃ | {$day['dayWind']} | {$day['nightWind']} |\r";
    }

    $md = $summary . $currentTable . $forecastTable;

    $this->发送('md', null, $md);
}
