#!/bin/bash
set -e

# 颜色
G='\033[0;32m' R='\033[0;31m' Y='\033[1;33m' N='\033[0m'
ok()  { echo -e "${G}[✓]${N} $1"; }
err() { echo -e "${R}[✗]${N} $1"; exit 1; }

BOT_DIR="/www/wwwroot/bot"
BOT_NAME="bot"

echo ""
echo "  ╔══════════════════════════════╗"
echo "  ║      QQ Bot 一键部署         ║"
echo "  ╚══════════════════════════════╝"
echo ""

# ---- 1. 检测环境 ----
PHP_BIN=""
for p in /www/server/php/85/bin/php /usr/bin/php85 /usr/local/bin/php /usr/bin/php; do
    [ -x "$p" ] && "$p" -r "exit(version_compare(PHP_VERSION,'8.4','>=')?0:1);" 2>/dev/null && PHP_BIN="$p" && break
done
[ -z "$PHP_BIN" ] && err "需要 PHP 8.4+"
$PHP_BIN -m 2>/dev/null | grep -qi swoole || err "缺少 Swoole 扩展"
ok "PHP $($PHP_BIN -r 'echo PHP_VERSION;') + Swoole ✓"

# ---- 2. 修复 disable_functions ----
INI=$($PHP_BIN --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}')
if [ -n "$INI" ] && grep -q "proc_open" "$INI"; then
    cp "$INI" "${INI}.bak" 2>/dev/null
    sed -i 's/^disable_functions = .*/disable_functions = passthru,system,chroot,chgrp,chown,shell_exec,popen,ini_alter,ini_restore,dl,openlog,syslog,readlink,symlink,popepassthru,imap_open,apache_setenv/' "$INI"
    ok "PHP disable_functions 已修复"
fi

# ---- 3. 拉代码 ----
REPO="https://github.com/oxoaa/bot-ocoa.git"
if [ -d "$BOT_DIR/.git" ]; then
    cd "$BOT_DIR" && git pull --quiet 2>/dev/null
    ok "代码已更新"
else
    git clone --quiet "$REPO" "$BOT_DIR" 2>/dev/null || err "克隆失败，请检查网络"
    ok "代码已克隆到 $BOT_DIR"
fi
cd "$BOT_DIR"

# ---- 4. 依赖 ----
if [ -f vendor/autoload.php ]; then
    ok "vendor 已存在"
elif command -v composer &>/dev/null; then
    composer install --no-dev --quiet
    ok "Composer 依赖已安装"
else
    err "无 vendor 且无 composer，请手动安装"
fi

# ---- 5. 配置 ----
[ -f config.json ] || cp config.example.json config.json
ok "配置文件就绪"

# ---- 6. systemd ----
cat > /etc/systemd/system/${BOT_NAME}.service << EOF
[Unit]
Description=QQ Bot
After=network.target

[Service]
Type=simple
WorkingDirectory=${BOT_DIR}
ExecStart=${PHP_BIN} server.php
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable ${BOT_NAME} --quiet 2>/dev/null
ok "systemd 服务已就绪"

# ---- 7. 管理命令 ----
cat > /usr/local/bin/bot << 'MANAGE'
#!/bin/bash
case "$1" in
  start)   systemctl start bot && echo "✅ 启动成功" ;;
  stop)    systemctl stop bot && echo "⏹ 已停止" ;;
  restart) systemctl restart bot && echo "🔄 已重启" ;;
  status)  systemctl status bot --no-pager ;;
  log)     journalctl -u bot -f --no-pager ;;
  *)       echo "bot start|stop|restart|status|log" ;;
esac
MANAGE
chmod +x /usr/local/bin/bot
ok "管理命令已安装"

# ---- 8. 启动 ----
mkdir -p 日志 数据/数据库
systemctl restart ${BOT_NAME}
sleep 2
if systemctl is-active --quiet ${BOT_NAME}; then
    ok "Bot 启动成功！"
else
    err "启动失败，运行 bot log 查看原因"
fi

echo ""
echo "  ╔══════════════════════════════════════╗"
echo "  ║  部署完成！                           ║"
echo "  ║                                      ║"
echo "  ║  编辑配置: vim $BOT_DIR/config.json  ║"
echo "  ║  管理命令: bot start|stop|restart     ║"
echo "  ║  查看日志: bot log                    ║"
echo "  ╚══════════════════════════════════════╝"
echo ""
