#!/bin/bash

G='\033[0;32m' R='\033[0;31m' Y='\033[1;33m' N='\033[0m'
ok()  { echo -e "${G}[✓]${N} $1"; }
warn(){ echo -e "${Y}[!]${N} $1"; }
err() { echo -e "${R}[✗]${N} $1"; exit 1; }
run() { echo -e "${Y}[→]${N} $1"; local out; out=$(eval "$1" 2>&1) || { echo "$out" | tail -10; err "执行失败: $1"; }; }

BOT_DIR="/www/wwwroot/bot"
BOT_NAME="bot"
REPO="https://github.com/oxoaa/bot-ocoa.git"

echo ""
echo "  ╔══════════════════════════════╗"
echo "  ║      QQ Bot 一键部署         ║"
echo "  ╚══════════════════════════════╝"
echo ""

# ============ 0. 基础工具 ============
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VER=$VERSION_ID
    ok "系统: $PRETTY_NAME"
else
    err "不支持的操作系统"
fi

if ! command -v git &>/dev/null; then
    warn "安装 git..."
    case "$OS" in
        ubuntu|debian)  apt-get update -qq && apt-get install -y git ;;
        centos|rhel|rocky|almalinux|fedora)
            if command -v dnf &>/dev/null; then dnf install -y git; else yum install -y git; fi ;;
    esac
fi

# ============ 1. PHP 8.4+ ============
PHP_BIN=""
for p in /usr/local/bin/php /usr/bin/php8.4 /usr/bin/php /www/server/php/85/bin/php /www/server/php/84/bin/php; do
    [ -x "$p" ] && "$p" -r "exit(version_compare(PHP_VERSION,'8.4','>=')?0:1);" 2>/dev/null && PHP_BIN="$p" && break
done

if [ -z "$PHP_BIN" ]; then
    warn "未找到 PHP 8.4+，开始安装..."
    case "$OS" in
        ubuntu|debian)
            run "apt-get update -qq"
            run "apt-get install -y software-properties-common"
            run "LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php"
            run "apt-get update -qq"
            # 先装核心包，再装扩展包，分开装方便定位问题
            run "apt-get install -y php8.4-cli php8.4-mbstring php8.4-curl php8.4-xml php8.4-zip php8.4-bcmath"
            apt-get install -y php8.4-sockets php8.4-dev php8.4-pear 2>/dev/null || warn "部分扩展包安装失败，不影响核心功能"
            PHP_BIN=$(command -v php8.4 || command -v php)
            ;;
        centos|rhel|rocky|almalinux|fedora)
            PKG="yum"; command -v dnf &>/dev/null && PKG="dnf"
            run "$PKG install -y epel-release" || true
            run "$PKG install -y https://rpms.remirepo.net/enterprise/remi-release-${VER}.rpm" || true
            run "$PKG module reset php -y" || true
            run "$PKG module enable php:remi-8.4 -y" || true
            run "$PKG install -y php-cli php-mbstring php-xml php-zip php-bcmath php-sockets php-devel php-pear"
            PHP_BIN=$(command -v php)
            ;;
        *)
            err "不支持的系统: $OS，请手动安装 PHP 8.4+"
            ;;
    esac
    [ -z "$PHP_BIN" ] && err "PHP 安装失败，请手动安装 PHP 8.4+"
fi
ok "PHP $($PHP_BIN -r 'echo PHP_VERSION;') ($PHP_BIN)"

# ============ 2. Swoole ============
if ! $PHP_BIN -m 2>/dev/null | grep -qi swoole; then
    warn "安装 Swoole 扩展..."
    case "$OS" in
        ubuntu|debian)  apt-get install -y php8.4-dev >/dev/null 2>&1 || true ;;
        centos|rhel|rocky|almalinux|fedora) $PKG install -y php-devel >/dev/null 2>&1 || true ;;
    esac
    run "printf '\n' | pecl install swoole"
    # 写入 ini
    SCAN_DIR=$($PHP_BIN -r "echo PHP_CONFIG_FILE_SCAN_DIR;" 2>/dev/null)
    if [ -d "$SCAN_DIR" ]; then
        echo "extension=swoole.so" > "$SCAN_DIR/20-swoole.ini"
    else
        INI=$($PHP_BIN --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}' | tr -d '"')
        echo "extension=swoole.so" >> "$INI"
    fi
    $PHP_BIN -m 2>/dev/null | grep -qi swoole || err "Swoole 安装失败"
fi
ok "Swoole ✓"

# ============ 3. 修复 disable_functions ============
INI=$($PHP_BIN --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}' | tr -d '"')
if [ -n "$INI" ] && grep -q "proc_open" "$INI" 2>/dev/null; then
    warn "修复 disable_functions..."
    cp "$INI" "${INI}.bak"
    sed -i 's/^disable_functions = .*/disable_functions = passthru,system,chroot,chgrp,chown,shell_exec,popen,ini_alter,ini_restore,dl,openlog,syslog,readlink,symlink,popepassthru,imap_open,apache_setenv/' "$INI"
    ok "disable_functions 已修复"
else
    ok "disable_functions 无需修复"
fi

# ============ 4. Nginx ============
if ! command -v nginx &>/dev/null; then
    warn "安装 Nginx..."
    case "$OS" in
        ubuntu|debian)  run "apt-get install -y nginx" ;;
        centos|rhel|rocky|almalinux|fedora) run "$PKG install -y nginx" ;;
    esac
fi
ok "Nginx ✓"

# ============ 5. Composer ============
if ! command -v composer &>/dev/null; then
    warn "安装 Composer..."
    run "curl -sS https://getcomposer.org/installer | $PHP_BIN -- --install-dir=/usr/local/bin --filename=composer"
fi
ok "Composer ✓"

# ============ 6. 拉取代码 ============
if [ -d "$BOT_DIR/.git" ]; then
    cd "$BOT_DIR" && git pull --quiet
    ok "代码已更新"
else
    run "git clone $REPO $BOT_DIR"
    ok "代码已部署"
fi
cd "$BOT_DIR"

# ============ 7. 依赖 ============
if [ -f vendor/autoload.php ]; then
    ok "依赖已就绪"
else
    run "composer install --no-dev"
fi

# ============ 8. 配置 ============
[ -f config.json ] || cp config.example.json config.json
ok "配置文件就绪"

# ============ 9. systemd ============
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

# ============ 10. 管理命令 ============
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

# ============ 11. 启动 ============
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
echo "  ║  1. vim $BOT_DIR/config.json  填入配置     ║"
echo "  ║  2. bot restart               重启生效     ║"
echo "  ║  3. bot log                   查看日志     ║"
echo "  ║                                            ║"
echo "  ║  Nginx SSL 反代请自行配置                   ║"
echo "  ╚══════════════════════════════════════════╝"
echo ""
