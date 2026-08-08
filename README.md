# QQ Bot Framework

基于 Swoole 的 QQ 机器人框架，支持官方 QQBot 和 NapCat。

## 一键部署

```bash
git clone https://github.com/YOUR_NAME/bot-ocoa.git
cd bot-ocoa
bash deploy.sh
```

脚本会自动完成：
- 检测 PHP 8.4+ 和 Swoole
- 修复 PHP `disable_functions` 限制
- 安装 Composer 依赖
- 配置 Nginx 反向代理（可选）
- 创建 systemd 服务（开机自启、崩溃重启）
- 安装 `bot` 管理命令

## 配置

编辑 `config.json`（首次运行会从 `config.example.json` 自动生成）：

```json
{
  "域名": "127.0.0.1",
  "http端口": 8080,
  "超级管理员": ["你的QQ_ID"],
  "框架": {
    "QQBOT": [
      { "appid": 0, "secret": "你的AppSecret", "sandbox": false }
    ]
  }
}
```

## 管理

```bash
bot start    # 启动
bot stop     # 停止
bot restart  # 重启
bot status   # 查看状态
bot log      # 实时日志
```

## 架构

```
客户端 → Nginx (443 SSL) → Bot (127.0.0.1:8080 HTTP)
```

- Bot 只监听本地 HTTP，不处理 SSL
- Nginx 负责 SSL 终结和反向代理
- systemd 管理进程生命周期

## 环境要求

- PHP >= 8.4
- Swoole 扩展
- Nginx
- Linux (systemd)
