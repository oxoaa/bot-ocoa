# QQ Bot Framework

基于 Swoole 的 QQ 机器人框架。

## 一键部署

```bash
curl -fsSL https://raw.githubusercontent.com/oxoaa/bot-ocoa/main/deploy.sh | bash
```

部署完编辑配置：

```bash
vim /www/wwwroot/bot/config.json
```

重启生效：

```bash
bot restart
```

## 管理

```bash
bot start|stop|restart|status|log
```

## 环境要求

- PHP 8.4+ (Swoole)
- Linux (systemd)
- Nginx (可选，用于 SSL 反代)
