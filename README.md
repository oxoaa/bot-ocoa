# QQ Bot Framework

## 前置条件

- PHP 8.4+（需自行安装）
- Swoole 扩展（需自行安装）
- root 权限

## 一键部署

```bash
curl -fsSL https://raw.githubusercontent.com/oxoaa/bot-ocoa/main/deploy.sh | bash
```

自动完成：检测环境 → 修复 disable_functions → 安装 Nginx/Composer → 拉代码 → 装依赖 → 建服务 → 启动

## 管理

```bash
bot start|stop|restart|status|log
```
