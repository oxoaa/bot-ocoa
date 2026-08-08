#!/bin/bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }

BOT_DIR="$(cd "$(dirname "$0")" && pwd)"
BOT_NAME="bot-ocoa"
PHP_BIN=""

echo ""
echo "=========================================="
echo "        QQ Bot 一键部署脚本"
echo "=========================================="
echo ""

# ========== 1. 检测 PHP ==========
log "检测 PHP 环境..."
for p in /www/server/php/85/bin/php /usr/bin/php85 /usr/bin/php; do
    if [ -x "$p" ] && "$p" -r "exit(version_compare(PHP_VERSION,'8.4','>=')?0:1);" 2>/dev/null; then
        PHP_BIN="$p"
        break
    fi
done
[ -z "$PHP_BIN" ] && err "需要 PHP 8.4+，请先安装"

PHP_VER=$("$PHP_BIN" -r "echo PHP_VERSION;")
log "PHP $PHP_VER ($PHP_BIN)"

# 检查 Swoole
"$PHP_BIN" -m 2>/dev/null | grep -qi swoole || err "缺少 Swoole 扩展，请安装: pecl install swoole"
log "Swoole 已安装"

# ========== 2. 修复 PHP disable_functions ==========
INI_FILE=$("$PHP_BIN" --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}')
if [ -n "$INI_FILE" ] && grep -q "disable_functions" "$INI_FILE"; then
    DISABLED=$(grep "^disable_functions" "$INI_FILE" | head -1)
    if echo "$DISABLED" | grep -q "proc_open"; then
        warn "修复 PHP disable_functions..."
        cp "$INI_FILE" "${INI_FILE}.bak"
        sed -i 's/^disable_functions = .*/disable_functions = passthru,system,chroot,chgrp,chown,shell_exec,popen,ini_alter,ini_restore,dl,openlog,syslog,readlink,symlink,popepassthru,imap_open,apache_setenv/' "$INI_FILE"
        log "已从 disable_functions 中移除 proc_open/exec/pcntl_*"
    fi
fi

# ========== 3. 安装依赖 ==========
if [ -f "$BOT_DIR/composer.json" ]; then
    if command -v composer &>/dev/null; then
        log "安装 Composer 依赖..."
        cd "$BOT_DIR" && composer install --no-dev --quiet 2>/dev/null || true
    elif [ -f "$BOT_DIR/vendor/autoload.php" ]; then
        log "vendor 已存在，跳过 composer install"
    else
        warn "未找到 composer，且 vendor 目录不存在，请手动安装依赖"
    fi
fi

# ========== 4. 配置文件 ==========
if [ ! -f "$BOT_DIR/config.json" ]; then
    if [ -f "$BOT_DIR/config.example.json" ]; then
        cp "$BOT_DIR/config.example.json" "$BOT_DIR/config.json"
        warn "已生成 config.json，请编辑填入你的配置："
        echo "    vim $BOT_DIR/config.json"
        echo ""
        read -p "编辑完成后按回车继续..." _
    else
        err "缺少 config.json"
    fi
fi
log "配置文件就绪"

# ========== 5. Nginx ==========
DOMAIN=$(grep -oP '"域名"\s*:\s*"\K[^"]+' "$BOT_DIR/config.json" 2>/dev/null || echo "127.0.0.1")
HTTP_PORT=$(grep -oP '"http端口"\s*:\s*\K[0-9]+' "$BOT_DIR/config.json" 2>/dev/null || echo "8080")

if command -v nginx &>/dev/null; then
    log "检测到 Nginx"
    echo ""
    read -p "是否自动配置 Nginx 反代？(y/N): " SETUP_NGINX
    if [[ "$SETUP_NGINX" =~ ^[Yy]$ ]]; then
        read -p "请输入域名 (如 bot.example.com): " NGINX_DOMAIN
        read -p "SSL 证书路径 (fullchain.pem): " SSL_CERT
        read -p "SSL 私钥路径 (privkey.pem): " SSL_KEY

        NGINX_CONF="/etc/nginx/sites-available/${NGINX_DOMAIN:-bot}.conf"
        # 宝塔面板路径兼容
        [ -d "/www/server/panel/vhost/nginx" ] && NGINX_CONF="/www/server/panel/vhost/nginx/${NGINX_DOMAIN:-bot}.conf"

        cat > "$NGINX_CONF" << NGINXEOF
server {
    listen 80;
    listen 443 ssl;
    http2 on;
    server_name ${NGINX_DOMAIN};

    ssl_certificate    ${SSL_CERT};
    ssl_certificate_key ${SSL_KEY};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    if (\$scheme = http) {
        return 301 https://\$host\$request_uri;
    }

    location / {
        proxy_pass http://127.0.0.1:${HTTP_PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }
}
NGINXEOF

        nginx -t 2>&1 && nginx -s reload 2>/dev/null && log "Nginx 配置完成并重载" || warn "Nginx 配置有误，请手动检查"
    fi
else
    warn "未检测到 Nginx，请自行配置反向代理到 127.0.0.1:${HTTP_PORT}"
fi

# ========== 6. systemd 服务 ==========
echo ""
read -p "是否创建 systemd 服务（开机自启+崩溃重启）？(Y/n): " SETUP_SVC
if [[ ! "$SETUP_SVC" =~ ^[Nn]$ ]]; then
    cat > /etc/systemd/system/${BOT_NAME}.service << SVCEOF
[Unit]
Description=QQ Bot Service
After=network.target

[Service]
Type=simple
WorkingDirectory=${BOT_DIR}
ExecStart=${PHP_BIN} server.php
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
SVCEOF

    systemctl daemon-reload
    systemctl enable ${BOT_NAME}
    log "systemd 服务已创建"
fi

# ========== 7. 管理脚本 ==========
cat > "${BOT_DIR}/bot" << 'MANAGEEOF'
#!/bin/bash
case "$1" in
  start)   systemctl start bot-ocoa && echo "✅ Bot 已启动" ;;
  stop)    systemctl stop bot-ocoa && echo "⏹ Bot 已停止" ;;
  restart) systemctl restart bot-ocoa && echo "🔄 Bot 已重启" ;;
  status)  systemctl status bot-ocoa --no-pager ;;
  log)     journalctl -u bot-ocoa -f --no-pager ;;
  *)       echo "用法: bot {start|stop|restart|status|log}" ;;
esac
MANAGEEOF
chmod +x "${BOT_DIR}/bot"
ln -sf "${BOT_DIR}/bot" /usr/local/bin/bot
log "管理脚本已安装，可用 bot start|stop|restart|status|log"

# ========== 8. 创建运行目录 ==========
mkdir -p "${BOT_DIR}/日志" "${BOT_DIR}/数据/数据库"

# ========== 9. 启动 ==========
echo ""
read -p "是否立即启动 Bot？(Y/n): " START_NOW
if [[ ! "$START_NOW" =~ ^[Nn]$ ]]; then
    systemctl restart ${BOT_NAME}
    sleep 2
    if systemctl is-active --quiet ${BOT_NAME}; then
        log "Bot 启动成功！"
        echo ""
        echo "=========================================="
        echo "  管理命令: bot start|stop|restart|status|log"
        echo "  配置文件: ${BOT_DIR}/config.json"
        echo "=========================================="
        echo ""
    else
        err "Bot 启动失败，运行 bot log 查看日志"
    fi
fi
