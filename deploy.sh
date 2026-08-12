#!/bin/bash
G='\033[0;32m' R='\033[0;31m' N='\033[0m'
ok()  { echo -e "${G}[✓]${N} $1"; }
err() { echo -e "${R}[✗]${N} $1"; exit 1; }

BOT_DIR="/www/wwwroot/bot"
BOT_NAME="bot"
REPO="https://github.com/oxoaa/bot-ocoa.git"

echo ""
echo "  ╔══════════════════════════════╗"
echo "  ║      QQ Bot 一键部署         ║"
echo "  ╚══════════════════════════════╝"
echo ""

# ---- 1. 检测 PHP ----
PHP_BIN=$(command -v php 2>/dev/null)
[ -z "$PHP_BIN" ] && err "未找到 PHP，请先安装 PHP 8.4+ 和 Swoole 扩展"
$PHP_BIN -r "exit(version_compare(PHP_VERSION,'8.4','>=')?0:1);" 2>/dev/null || err "PHP 版本需要 8.4+，当前: $($PHP_BIN -r 'echo PHP_VERSION;')"
ok "PHP $($PHP_BIN -r 'echo PHP_VERSION;') ✓"

$PHP_BIN -m 2>/dev/null | grep -qi swoole || err "缺少 Swoole 扩展"
ok "Swoole ✓"

# ---- 2. 修复 disable_functions ----
INI=$($PHP_BIN --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}' | tr -d '"')
if [ -n "$INI" ] && grep -q "proc_open" "$INI" 2>/dev/null; then
    cp "$INI" "${INI}.bak" 2>/dev/null
    sed -i 's/^disable_functions = .*/disable_functions = passthru,system,chroot,chgrp,chown,shell_exec,popen,ini_alter,ini_restore,dl,openlog,syslog,readlink,symlink,popepassthru,imap_open,apache_setenv/' "$INI"
    ok "disable_functions 已修复"
fi

# ---- 3. 安装 Nginx ----
if ! command -v nginx &>/dev/null; then
    echo "[→] 安装 Nginx..."
    if command -v apt-get &>/dev/null; then
        apt-get update -qq && apt-get install -y nginx
    elif command -v yum &>/dev/null; then
        yum install -y nginx
    elif command -v dnf &>/dev/null; then
        dnf install -y nginx
    fi
    command -v nginx &>/dev/null || err "Nginx 安装失败"
fi
ok "Nginx ✓"

# ---- 4. 安装 Composer ----
if ! command -v composer &>/dev/null; then
    echo "[→] 安装 Composer..."
    curl -sS https://getcomposer.org/installer | $PHP_BIN -- --install-dir=/usr/local/bin --filename=composer
    command -v composer &>/dev/null || err "Composer 安装失败"
fi
ok "Composer ✓"

# ---- 5. 拉代码 ----
if [ -d "$BOT_DIR/.git" ]; then
    cd "$BOT_DIR" && git pull --quiet
    ok "代码已更新"
else
    git clone "$REPO" "$BOT_DIR" || err "克隆失败，请检查 git 和网络"
    ok "代码已部署到 $BOT_DIR"
fi
cd "$BOT_DIR"

# ---- 6. 装依赖 ----
if [ -f vendor/autoload.php ]; then
    ok "依赖已就绪"
else
    composer install --no-dev
fi

# ---- 7. 配置 ----
[ -f config.json ] || cp config.example.json config.json
ok "配置文件就绪"

# ---- 8. systemd ----
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

# ---- 9. 管理命令 ----
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

# ---- 10. 启动 ----
mkdir -p 日志 数据/数据库
systemctl restart ${BOT_NAME}
sleep 2
systemctl is-active --quiet ${BOT_NAME} && ok "Bot 启动成功！" || err "启动失败，运行 bot log 查看原因"

echo ""
echo "  ╔══════════════════════════════════╗"
echo "  ║  部署完成！                        ║"
echo "  ║                                    ║"
echo "  ║  1. vim $BOT_DIR/config.json      ║"
echo "  ║  2. bot restart                    ║"
echo "  ║  3. bot log                        ║"
echo "  ╚══════════════════════════════════╝"
echo ""
