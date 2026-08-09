# QQ Bot Framework

基于 Swoole 的 QQ 机器人框架，一条命令全自动部署。

## 一键部署

```bash
curl -fsSL https://raw.githubusercontent.com/oxoaa/bot-ocoa/main/deploy.sh | bash
```

**脚本自动完成：**
- 检测系统（Ubuntu/Debian/CentOS/RHEL）
- 安装 PHP 8.4+（如果没有）
- 安装 Swoole 扩展（如果没有）
- 安装 Composer（如果没有）
- 安装 Nginx（如果没有）
- 修复 PHP disable_functions 限制
- 拉取代码 + 安装依赖
- 创建 systemd 服务（开机自启、崩溃重启）
- 安装 `bot` 管理命令

## 配置

```bash
vim /www/wwwroot/bot/config.json
bot restart
```

## 管理

```bash
bot start|stop|restart|status|log
```

## 环境要求

- Linux（Ubuntu/Debian/CentOS/RHEL/Rocky/Alma）
- root 权限
