#!/usr/bin/env bash
# بيئة اختبار كود الكتاب في صندوق العمل (ليست للقارئ): PHP 8.0 + MySQL 5.7 من حزم npm.
#   bash tools/devenv.sh            تثبيت PHP و MySQL في /tmp (مرة بعد كل إعادة تشغيل)
#   /tmp/tools/bin/php ...          PHP مع الإضافات اللازمة
#   تشغيل MySQL (عملية دائمة):      bash tools/devenv.sh mysqld
set -euo pipefail
T=/tmp/tools; M=/tmp/mysql57
S=$M/package/server
OPT="--no-defaults --explicit_defaults_for_timestamp --basedir=$S --datadir=$M/data/db --lc-messages-dir=$S/share/mysql --innodb-use-native-aio=0 --log_syslog=0"

if [ "${1:-}" = "mysqld" ]; then
  exec env LD_LIBRARY_PATH=$M/lib $S/mysqld $OPT --console --tmpdir=$M/tmp --socket=$M/mysql.sock \
    --port=3306 --bind-address=127.0.0.1 --secure-file-priv=$M/tmp --character-set-server=utf8mb4
fi

if [ ! -x $T/php80/package/native/php/php ]; then
  mkdir -p $T/php80 && cd $T/php80
  curl -sL https://registry.npmjs.org/@libphp/amazon-linux-2-v80/-/amazon-linux-2-v80-0.0.21.tgz | tar xz
fi
mkdir -p $T/bin
cat > $T/bin/php <<'EOF'
#!/usr/bin/env bash
P=/tmp/tools/php80/package/native
EXT=""
for e in mbstring mysqlnd pdo pdo_mysql mysqli fileinfo zip ctype iconv; do
  [ -f $P/php/modules/$e.so ] && EXT="$EXT -d extension=$e"
done
exec env LD_LIBRARY_PATH=$P/lib $P/php/php -n -d extension_dir=$P/php/modules $EXT \
  -d date.timezone=Africa/Algiers -d memory_limit=512M -d upload_max_filesize=20M -d post_max_size=25M "$@"
EOF
chmod +x $T/bin/php

if [ ! -x $S/mysqld ]; then
  mkdir -p $M/lib $M/data $M/tmp && cd $M
  curl -sL https://registry.npmjs.org/mysql-server-5.7-lin-x64/-/mysql-server-5.7-lin-x64-1.0.0.tgz | tar xz
  printf '#include <errno.h>\nint io_setup(){return -ENOSYS;}int io_destroy(){return -ENOSYS;}int io_submit(){return -ENOSYS;}int io_cancel(){return -ENOSYS;}int io_getevents(){return -ENOSYS;}\n' > lib/aio.c
  gcc -shared -fPIC -o lib/libaio.so.1 lib/aio.c -Wl,-soname,libaio.so.1
fi
if [ ! -d $M/data/db/mysql ]; then
  env LD_LIBRARY_PATH=$M/lib $S/mysqld $OPT --initialize-insecure 2>&1 | tail -1
fi
$T/bin/php -v | head -1
