#!/bin/bash
set -e

G='\033[0;32m' R='\033[0;31m' Y='\033[1;33m' N='\033[0m'
ok()  { echo -e "${G}[✓]${N} $1"; }
warn(){ echo -e "${Y}[!]${N} $1"; }
err() { echo -e "${R}[✗]${N} $1"; exit 1; }

BOT_DIR="/www/wwwroot/bot"
BOT_NAME="bot"
REPO="https://github.com/oxoaa/bot-ocoa.git"

echo ""
echo "  ╔══════════════════════════════╗"
echo "  ║      QQ Bot 一键部署         ║"
echo "  ╚══════════════════════════════╝"
echo ""

# ============================================================
#  第一步：检测系统
# ============================================================
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VER=$VERSION_ID
else
    err "不支持的操作系统"
fi
ok "系统: $PRETTY_NAME"

# ============================================================
#  第二步：安装 PHP 8.4+（如果没有）
# ============================================================
PHP_BIN=""
for p in $(compgen -c php 2>/dev/null | grep -E '^php[0-9.]*$' | sort -u) \
          /www/server/php/85/bin/php /www/server/php/84/bin/php \
          /usr/local/bin/php /usr/bin/php; do
    P=$(command -v "$p" 2>/dev/null || echo "$p")
    [ -x "$P" ] && "$P" -r "exit(version_compare(PHP_VERSION,'8.4','>=')?0:1);" 2>/dev/null && PHP_BIN="$P" && break
done

if [ -z "$PHP_BIN" ]; then
    warn "未找到 PHP 8.4+，开始自动安装..."
    case "$OS" in
        ubuntu|debian)
            apt-get update -qq
            apt-get install -y -qq software-properties-common > /dev/null 2>&1
            add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
            apt-get update -qq
            apt-get install -y -qq php8.4-cli php8.4-mbstring php8.4-curl \
                php8.4-xml php8.4-zip php8.4-bcmath php8.4-sockets \
                php8.4-dev php8.4-pear > /dev/null 2>&1
            PHP_BIN=$(command -v php8.4 || command -v php)
            ;;
        centos|rhel|rocky|almalinux|fedora)
            if command -v dnf &>/dev/null; then PKG=dnf; else PKG=yum; fi
            $PKG install -y epel-release > /dev/null 2>&1 || true
            if [ "$OS" = "centos" ] || [ "$OS" = "rhel" ]; then
                $PKG install -y https://rpms.remirepo.net/enterprise/remi-release-${VER}.rpm > /dev/null 2>&1 || true
                $PKG module reset php -y > /dev/null 2>&1 || true
                $PKG module enable php:remi-8.4 -y > /dev/null 2>&1 || true
            fi
            $PKG install -y php-cli php-mbstring php-xml php-zip php-bcmath php-sockets php-devel php-pear > /dev/null 2>&1
            PHP_BIN=$(command -v php)
            ;;
        *)
            err "不支持的系统: $OS，请手动安装 PHP 8.4+"
            ;;
    esac
    [ -z "$PHP_BIN" ] && err "PHP 安装失败"
fi
ok "PHP $($PHP_BIN -r 'echo PHP_VERSION;') ✓"

# ============================================================
#  第三步：安装 Swoole 扩展（如果没有）
# ============================================================
if ! $PHP_BIN -m 2>/dev/null | grep -qi swoole; then
    warn "安装 Swoole 扩展..."
    $PHP_BIN -m 2>/dev/null | grep -qi dev || {
        # 确保 phpize 存在
        case "$OS" in
            ubuntu|debian) apt-get install -y -qq php8.4-dev > /dev/null 2>&1 ;;
            centos|rhel|rocky|almalinux|fedora) $PKG install -y php-devel > /dev/null 2>&1 ;;
        esac
    }
    printf "\n" | pecl install swoole > /dev/null 2>&1
    # 自动启用扩展
    EXT_DIR=$($PHP_BIN -r "echo ini_get('extension_dir');" 2>/dev/null)
    INI_DIR=$($PHP_BIN -r "echo PHP_CONFIG_FILE_SCAN_DIR;" 2>/dev/null)
    if [ -d "$INI_DIR" ]; then
        echo "extension=swoole.so" > "$INI_DIR/swoole.ini"
    else
        echo "extension=swoole.so" >> $($PHP_BIN --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}' | tr -d '"')
    fi
    $PHP_BIN -m 2>/dev/null | grep -qi swoole || err "Swoole 安装失败"
fi
ok "Swoole 扩展 ✓"

# ============================================================
#  第四步：修复 disable_functions
# ============================================================
INI=$($PHP_BIN --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}' | tr -d '"')
if [ -n "$INI" ] && grep -q "proc_open" "$INI" 2>/dev/null; then
    cp "$INI" "${INI}.bak" 2>/dev/null
    sed -i 's/^disable_functions = .*/disable_functions = passthru,system,chroot,chgrp,chown,shell_exec,popen,ini_alter,ini_restore,dl,openlog,syslog,readlink,symlink,popepassthru,imap_open,apache_setenv/' "$INI"
    ok "disable_functions 已修复"
else
    ok "disable_functions 无需修复"
fi

# ============================================================
#  第五步：安装 Nginx（如果没有）
# ============================================================
if ! command -v nginx &>/dev/null; then
    warn "安装 Nginx..."
    case "$OS" in
        ubuntu|debian) apt-get install -y -qq nginx > /dev/null 2>&1 ;;
        centos|rhel|rocky|almalinux|fedora) $PKG install -y nginx > /dev/null 2>&1 ;;
    esac
fi
ok "Nginx $(nginx -v 2>&1 | awk -F/ '{print $2}') ✓"

# ============================================================
#  第六步：拉取代码
# ============================================================
if [ -d "$BOT_DIR/.git" ]; then
    cd "$BOT_DIR" && git pull --quiet 2>/dev/null
    ok "代码已更新"
else
    git clone --quiet "$REPO" "$BOT_DIR" 2>/dev/null || err "克隆失败，请检查网络"
    ok "代码已部署到 $BOT_DIR"
fi
cd "$BOT_DIR"

# ============================================================
#  第七步：安装依赖
# ============================================================
if [ -f vendor/autoload.php ]; then
    ok "依赖已就绪"
elif command -v composer &>/dev/null; then
    composer install --no-dev --quiet
    ok "Composer 依赖已安装"
else
    # 自动安装 composer
    curl -sS https://getcomposer.org/installer | $PHP_BIN -- --install-dir=/usr/local/bin --filename=composer > /dev/null 2>&1
    composer install --no-dev --quiet
    ok "Composer 已安装，依赖已就绪"
fi

# ============================================================
#  第八步：配置文件
# ============================================================
[ -f config.json ] || cp config.example.json config.json
ok "配置文件就绪"

# ============================================================
#  第九步：systemd 服务
# ============================================================
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

# ============================================================
#  第十步：管理命令
# ============================================================
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

# ============================================================
#  第十一步：启动
# ============================================================
mkdir -p 日志 数据/数据库
systemctl restart ${BOT_NAME}
sleep 2
if systemctl is-active --quiet ${BOT_NAME}; then
    ok "Bot 启动成功！"
else
    err "启动失败，运行 bot log 查看原因"
fi

echo ""
echo "  ╔══════════════════════════════════════════╗"
echo "  ║  部署完成！                                ║"
echo "  ║                                            ║"
echo "  ║  1. 编辑配置: vim $BOT_DIR/config.json    ║"
echo "  ║  2. 重启生效: bot restart                  ║"
echo "  ║  3. 查看日志: bot log                      ║"
echo "  ║                                            ║"
echo "  ║  Nginx SSL 反代请自行配置                   ║"
echo "  ╚══════════════════════════════════════════╝"
echo ""
