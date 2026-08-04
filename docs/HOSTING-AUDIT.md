# HOSTING AUDIT — lylishop.online

**Ngày audit:** 2026-08-03
**Phạm vi:** Đọc-only audit. Không có thay đổi nào được thực hiện trên máy chủ ngoài một symlink thử nghiệm (`~/symlink_test_tmp`) và một file lock thử nghiệm (`/tmp/locktest.$$`), cả hai đã được xóa ngay sau khi kiểm tra.

---

## 0. Ghi chú quan trọng: sai lệch alias SSH

Brief yêu cầu dùng `ssh lyli-prod`. Alias này **không tồn tại** trong `~/.ssh/config` trên máy phát triển. File config chỉ có một entry:

```
Host commerce-host
    HostName onehost-cloudhn032505.000nethost.com
    User erxwskxohosting
    Port 65333
    IdentityFile C:\Users\ADMIN\.ssh\id_ed25519
    IdentitiesOnly yes
```

`User` khớp chính xác với `erxwskxohosting` nêu trong brief, và đây là entry duy nhất trong file config, nên toàn bộ audit này được thực hiện qua alias thật là **`commerce-host`**, không phải `lyli-prod`.

**Hành động đề xuất:** thêm một alias `Host lyli-prod` trỏ cùng cấu hình (hoặc đổi tên entry hiện tại) để khớp với tên dùng trong tài liệu vận hành và script sau này. Chưa tự đổi vì đây là file cấu hình cá nhân của developer trên máy local.

---

## 1. Executive verdict

# **PASS WITH WORKAROUNDS**

Tài khoản hosting hoàn toàn trống (chưa có WordPress, chưa có dữ liệu khách hàng thật — chỉ có `index.htm` mặc định), PHP 8.3 dùng được rõ ràng qua CloudLinux PHP Selector, Composer và WP-CLI chạy được qua PHP 8.3, symlink/rsync/zip hoạt động. Các workaround cần thiết: bổ sung extension PHP còn thiếu (nếu panel cho phép), xác nhận thủ công một số mục qua OnePanel UI, và dùng chiến lược symlink thay vì đổi document root panel-level.

Không có hard blocker nào được phát hiện qua SSH.

---

## 2. Confirmed facts

### 2.1. Tài khoản & filesystem

| Mục | Giá trị |
|---|---|
| User | `erxwskxohosting` (uid 1712, gid 1713) |
| Shell | `/bin/bash`, chạy trong CageFS (CloudLinux jail) |
| Home | `/home/erxwskxohosting` |
| Disk | Filesystem `/dev/md125`, 3.5T tổng, 1.6T khả dụng (dùng chung, không phải quota riêng) |
| Quota lệnh `quota -s` | Không có (không hiển thị qua CLI — cần xem trong OnePanel) |
| Domain chính | `lylishop.online`, `document_root=/home/erxwskxohosting/public_html`, `is_main=true` (xác nhận qua `opcli domains`) |
| Nội dung `public_html` hiện tại | Chỉ có `index.htm` mặc định của hosting — **không có WordPress, không có dữ liệu khách hàng** |
| Thư mục khác | `logs/` (access/error log rỗng cho domain), `lscache/` (quyền `nobody`, không đọc được — dấu hiệu có LiteSpeed cache module ở tầng hệ thống) |
| `domains/`, `httpdocs/`, `private_html/`, `tmp/`, `backup/` | Không tồn tại ở home — cấu trúc panel dùng `public_html` phẳng, không theo kiểu cPanel multi-domain cũ |
| Symlink | Hoạt động (`ln -s public_html symlink_test_tmp` thành công, đã xóa) |
| File locking (`flock`) | Hoạt động |
| rsync | Có, `/usr/bin/rsync` |
| zip / unzip | Có, `/usr/bin/zip`, `/usr/bin/unzip` |
| curl / wget / tar / gzip | Có |
| Shell restriction | CageFS (CloudLinux) — lệnh cơ bản hoạt động bình thường trong jail; không có quyền root/sudo/apt như đã biết trước |
| OnePanel CLI (`opcli`) | Có tại `/usr/local/bin/opcli`, version 3.8.23 — hỗ trợ `domains`, `user_info`, `panel_info`, `tmp_clean` (đọc-only, không có subcommand tạo database) |

`opcli panel_info` xác nhận panel hỗ trợ: `php_selector: true`, `nodejs_selector: true`, `python_selector: true`, `mod_lsapi: true`, `mysql_governor: true`, `cagefs: true`, `accelerate_wp: true`.

`php_selector: true` xác nhận: **PHP 8.3 có thể được chọn làm PHP chạy web qua OnePanel** (CloudLinux Selector), không chỉ CLI. Đây trả lời trực tiếp yêu cầu "verify whether web PHP runtime can be selected as PHP 8.3" — có thể, qua panel UI, chưa xác nhận được bước bật thực tế qua SSH (opcli không có subcommand set-php-version).

`mod_lsapi: true` + thư mục `lscache` gợi ý hosting dùng LiteSpeed (hoặc Apache + CloudLinux LSAPI) làm web server tầng dưới. **Cần xác nhận thủ công** (xem mục 3) trước khi chốt chọn WP Super Cache và mã 8.3 của TECH_STACK.md.

### 2.2. PHP 8.3 (`/opt/alt/php83/usr/bin/php`)

```
PHP 8.3.30 (cli) (built: Mar 8 2026)
php.ini: /opt/alt/php83/etc/php.ini
Additional ini: /opt/alt/php83/link/conf/alt_php.ini
```

| Setting | Giá trị |
|---|---|
| memory_limit | 512M — **đạt mục tiêu 512MB của TECH_STACK.md** |
| max_execution_time | 0 (không giới hạn) |
| max_input_time | -1 (không giới hạn) |
| max_input_vars | 3000 |
| upload_max_filesize | 10240M |
| post_max_size | 512M |
| open_basedir | trống (CLI) — cần xác nhận riêng cho web/FPM |
| disable_functions | `system, passthru, shell_exec, escapeshellcmd, dl, show_source, posix_kill, posix_mkfifo, posix_setpgid, posix_setsid, posix_setuid, posix_setgid, posix_seteuid, posix_setegid, posix_uname` |

`proc_open` và `exec` **không** nằm trong danh sách disable_functions — Composer (dùng Symfony Process/`proc_open`) và WP-CLI vẫn hoạt động bình thường.

**Extension đã có:** bcmath, bz2, calendar, ctype, curl, dom, exif, fileinfo, filter, ftp, gd, gettext, hash, iconv, json, mbstring, mysqli, mysqlnd, openssl, pcntl, pcre, PDO, pdo_mysql, pdo_sqlite, Phar, posix, session, SimpleXML, sockets, sqlite3, xml, xmlreader, xmlwriter, xsl, zlib.

**Extension yêu cầu nhưng KHÔNG có trong CLI PHP 8.3:**

| Extension | Có fallback? |
|---|---|
| `zip` | Có — Composer tự dùng binary `/usr/bin/unzip` khi thiếu ZipArchive (xác nhận qua `composer diagnose`: "zip: extension not loaded, unzip present"). WordPress core cũng tự dùng `PclZip` (pure-PHP) khi thiếu ZipArchive, chỉ chậm hơn. |
| `intl` | Không có fallback tương đương đầy đủ, nhưng WordPress/WooCommerce không bắt buộc intl để chạy — chỉ ảnh hưởng một số chuẩn hoá ngôn ngữ nâng cao. |
| `imagick` | Có — GD đã sẵn có, WordPress mặc định fallback sang GD khi thiếu Imagick. |
| `Zend OPcache` | Không có fallback — chỉ ảnh hưởng hiệu năng, không chặn chức năng. |
| `sodium` | Có — WordPress core bundle `sodium_compat` (pure-PHP) tự động khi thiếu ext-sodium. |

→ Không có extension thiếu nào là **hard blocker**. `intl` và `opcache` là ảnh hưởng hiệu năng/chuẩn hoá, nên thử bật qua OnePanel PHP Selector (mục "PHP Extensions") trước khi chấp nhận thiếu vĩnh viễn — **cần kiểm tra thủ công qua panel**, CLI hiện tại không có quyền bật extension.

### 2.3. Composer

```
Composer version 2.8.10 (2025-07-10), PHP 8.3.30
```

`composer diagnose` qua `/opt/alt/php83/usr/bin/php /usr/local/bin/composer diagnose`:

* Platform: OK
* Git: OK (2.43.7)
* HTTP/HTTPS tới Packagist: OK
* GitHub rate limit: OK
* Disk free space: OK
* Pubkey cho tag/dev verification: **FAIL** (chưa cấu hình — sửa bằng `composer self-update --update-keys`, không quan trọng cho build đầu tiên)
* Composer không phải bản mới nhất (2.8.10 so với 2.10.2) — vẫn thoả yêu cầu "Composer 2.x" của TECH_STACK.md
* `composer audit` báo 8 lỗi bảo mật (CVE) trên chính **bản thân Composer 2.8.10** và `symfony/process` — khuyến nghị self-update lên 2.10.2+ trước khi dùng cho build thật (xem mục 7, Security concerns)

Composer **có thể**: truy cập Packagist, tạo cache dir, tải package (qua unzip fallback), ghi vào project path, chạy trong giới hạn memory/process của hosting. Chưa chạy `composer install` thật (không được yêu cầu ở audit).

### 2.4. WP-CLI

```
/opt/alt/php83/usr/bin/php /usr/bin/wp --info
WP-CLI version: 2.8.1
PHP binary: /opt/alt/php83/usr/bin/php
MySQL binary: /usr/bin/mysql, MariaDB 11.4.10 (client)
```

Hoạt động bình thường qua PHP 8.3.

### 2.5. Database

| Mục | Kết quả |
|---|---|
| MariaDB client | 11.4.10 |
| Kết nối `mysql -h localhost` không mật khẩu | `ERROR 1045: Access denied for user 'erxwskxohosting'@'localhost' (using password: NO)` — **đúng như kỳ vọng**, không có tài khoản MySQL mặc định passwordless |
| Database/user hiện có | Chưa xác định được — cần thông tin từ OnePanel (không có API tạo/liệt kê DB qua `opcli`) |
| Tạo database từ SSH | **Không** — `opcli` không có subcommand database; không tìm thấy `uapi`/`whmapi1`/`mysql -u root` khả dụng. → **Chỉ tạo được qua giao diện OnePanel.** |
| mysqldump | Có, `/usr/bin/mysqldump` (11.4.10-MariaDB) |
| Charset/collation, max connections, remote access | Không xác định được qua SSH vì cần đăng nhập DB thật — **cần kiểm tra thủ công qua OnePanel → Database** sau khi database được tạo |

### 2.6. Cron

| Mục | Kết quả |
|---|---|
| `crontab -l` | Rỗng (chưa có cron job nào) |
| `crontab` binary | `/usr/bin/crontab` — có quyền dùng |
| PHP mặc định trong context cron (`php -v` không full path) | PHP 8.1.34 — **đúng như cảnh báo trong brief, cron mặc định KHÔNG dùng PHP 8.3** |
| Giải pháp | Mọi cron job phải gọi tường minh `/opt/alt/php83/usr/bin/php /path/to/wp ...`, không được dựa vào `php` trần |
| Khoảng thời gian tối thiểu | Không kiểm tra được giới hạn interval tối thiểu của panel qua SSH — cron do panel quản lý lịch, cần xác nhận thủ công (nhiều panel giới hạn tối thiểu 5-15 phút cho account thường) |

### 2.7. SSH / deploy

| Mục | Kết quả |
|---|---|
| SSH qua alias thật (`commerce-host`, xem mục 0) | Hoạt động, public key, không cần mật khẩu |
| scp | Có (`scp` binary phản hồi bình thường) |
| rsync | Có |
| Lệnh SSH chạy lâu | Không bị giới hạn trong các lần test (một số lệnh nhiều bước chạy tuần tự không bị cắt) |
| Symlink switching | Được phép (test thành công) |
| File ownership | File mới tạo bởi `erxwskxohosting` thuộc chính user này; `public_html` sở hữu bởi `erxwskxohosting:nobody` — tương thích với việc web server đọc file do user này ghi |

---

## 3. Unknowns requiring OnePanel or provider support

1. **PHP Selector cho domain `lylishop.online`** — xác nhận panel hỗ trợ `php_selector`, nhưng chưa xác nhận phiên bản PHP thực tế đang áp dụng cho web request (FPM/LSAPI) là bản nào, và liệu có khác với CLI 8.3. Cần vào OnePanel → PHP Selector, chọn 8.3 cho domain.
2. **Module set của PHP-FPM/LSAPI web runtime** — có thể khác CLI. Chủ động **không** dùng cách drop file `phpinfo()` công khai trong `public_html` để giữ an toàn (dù site hiện chưa live) — brief đã cho phép ghi nhận mục này là "manual panel check" thay vì fail.
3. **Bật extension PHP còn thiếu** (`intl`, `opcache`, `sodium`, `zip`) — cần vào OnePanel → PHP Selector → PHP Extensions, kiểm tra danh sách có sẵn để bật hay không.
4. **Web server thật sự (Apache+mod_lsapi hay LiteSpeed thật)** — ảnh hưởng lựa chọn cache plugin (WP Super Cache vs LiteSpeed Cache) theo đúng nguyên tắc TECH_STACK.md §8.3. Cần xác nhận qua panel hoặc hỏi provider.
5. **Tạo Database + user MySQL** — chỉ làm được qua OnePanel UI. Cần charset `utf8mb4`, collation `utf8mb4_unicode_ci` (hoặc `_520_ci`/`_0900_ai_ci` tuỳ MariaDB hỗ trợ), ghi lại host (thường `localhost`), user, và **không commit password**.
6. **Account quota thực tế** — `quota -s` không khả dụng qua CLI; cần xem trong OnePanel (mục "Disk Usage" hoặc tương đương).
7. **Cron minimum interval** và **liệu OnePanel scheduler chạy đúng theo giờ hệ thống VN hay UTC** — cần xem trong giao diện Cron Jobs của panel.
8. **Domain document root re-pointing** — `opcli domains` chỉ đọc, không có subcommand set/update. Chưa xác nhận panel UI có cho đổi document root sang đường dẫn tuỳ ý (ví dụ `~/apps/lylishop/current/web`) hay không. Xem mục 8 (Recommended deployment layout) cho phương án không phụ thuộc vào việc này.
9. ~~Staging subdomain~~ — **không còn áp dụng.** Amendment 2026-08-03: dự án chốt production-only, không có staging domain. Mục này được giữ lại để ghi nhận rằng panel có `accelerate_wp`/multi-feature (chưa xác nhận addon-domain miễn phí), phòng trường hợp quyết định này được xem xét lại trong tương lai — không phải một hạng mục cần kiểm tra tiếp.
10. **Composer self-update quyền ghi** — `/usr/local/bin/composer` nằm ngoài `$HOME`, nhiều khả năng không ghi được bởi user thường (không kiểm tra trực tiếp để tránh side-effect). Nếu cần Composer mới hơn, cân nhắc tải Composer riêng vào `~/bin/composer.phar`.

---

## 4. Hard blockers

**Không có.** Không phát hiện điều kiện nào trong Phase 7 Stop Conditions bị vi phạm dựa trên bằng chứng thu thập được:

* Web PHP 8.3 khả dụng theo panel (`php_selector: true`) — chưa xác nhận *đã bật* nhưng có đường đi rõ ràng.
* Document root có thể giữ an toàn cho Bedrock bằng chiến lược symlink (mục 8) mà không cần panel hỗ trợ đổi doc root.
* Database server (MariaDB 11.4.10) tương thích với WordPress 7.0.2/WooCommerce 10.9.4 theo baseline TECH_STACK.md.
* SSH deploy giữ được uploads/env qua symlink + rsync exclude — khả thi (mục 8).
* Backup/restore path khả thi: `mysqldump` + `rsync`/`tar` có sẵn.
* Không có dữ liệu sống nào có nguy cơ bị ghi đè — tài khoản trống.

---

## 5. Workarounds

| Vấn đề | Workaround |
|---|---|
| Thiếu `zip` extension | Dựa vào `unzip` binary (Composer tự fallback); không cần hành động thêm trừ khi panel cho bật `zip` |
| Thiếu `opcache`, `intl`, `sodium` | Chấp nhận thiếu ban đầu (có fallback hoặc chỉ ảnh hưởng hiệu năng); thử bật qua PHP Selector khi có quyền truy cập panel |
| Cron mặc định là PHP 8.1 | Luôn gọi cron bằng full path `/opt/alt/php83/usr/bin/php` trong mọi crontab entry |
| Không tạo được DB qua SSH | Tạo DB/user thủ công một lần qua OnePanel, sau đó chỉ đọc credentials qua `.env` — không tự động hoá bước này bằng script |
| Không chắc doc root có đổi được qua panel | Dùng chiến lược symlink: giữ nguyên `public_html` làm document root cố định, biến `public_html` thành **symlink trỏ tới `shared/current-release/web`** (xem mục 8) — không phụ thuộc panel |
| Composer 2.8.10 có CVE đã biết | Trước khi build thật: `php composer.phar self-update` sang tải riêng phiên bản mới (2.10.2+) vào thư mục CI/dev, không dùng bản hệ thống có lỗi bảo mật cho production build |
| `mod_lsapi`/LiteSpeed chưa xác nhận rõ | Mặc định dùng WP Super Cache (an toàn với Apache); nếu xác nhận LiteSpeed thật, đổi sang LiteSpeed Cache theo đúng TECH_STACK.md §8.3 — quyết định hoãn tới khi có xác nhận panel |

---

## 6. Exact commands executed

```bash
# Local
git status; git --version; php -v; composer --version; node -v; npm -v
docker --version; ddev --version; rsync --version; scp -V; tar --version; zip -v
ssh -G lyli-prod
cat ~/.ssh/config

# Remote (via: ssh commerce-host '...')
whoami; id; echo $SHELL; pwd
ls -la ~; quota -s; df -h .; du -sh ~
ls -la ~/domains ~/public_html ~/httpdocs ~/private_html ~/logs ~/tmp ~/backup ~/backups

ln -s public_html symlink_test_tmp && ls -la symlink_test_tmp && readlink symlink_test_tmp && rm -f symlink_test_tmp
( flock -n 200 && echo OK ) 200>/tmp/locktest.$$; rm -f /tmp/locktest.$$
which rsync zip unzip curl wget tar gzip
cat /etc/redhat-release; cagefsctl --version
find ~ -maxdepth 4 -iname "wp-config.php"
find ~ -maxdepth 4 -iname "wp-login.php"

/opt/alt/php83/usr/bin/php -v
/opt/alt/php83/usr/bin/php --ini
/opt/alt/php83/usr/bin/php -r '... ini_get(...) ...'
/opt/alt/php83/usr/bin/php -m

/opt/alt/php83/usr/bin/php /usr/local/bin/composer diagnose
/opt/alt/php83/usr/bin/php /usr/local/bin/composer --version
/opt/alt/php83/usr/bin/php /usr/bin/wp --info

mysql -h localhost -e "SELECT VERSION();"
mysql -e "SHOW VARIABLES LIKE '%max_connections%';"
crontab -l; which crontab; php -v; which mysqldump; mysqldump --version

which uapi whmapi1 cpapi2 onepanel opcli
opcli --help; opcli user_info; opcli domains; opcli panel_info
```

Không có lệnh `rm`, `mv`, `chmod` nào chạy trên file/thư mục **đã tồn tại từ trước**. Duy nhất một symlink và một file lock **tự tạo trong lúc audit** đã được xóa ngay.

---

## 7. Security concerns

1. **Composer 2.8.10 có 8 CVE đã công bố** (bao gồm 2 mức "high": path traversal ghi file ngoài `vendor/`, command injection qua Perforce source) — không dùng bản này để chạy `composer install` cho production build cho tới khi self-update hoặc dùng Composer version mới hơn ở máy build/CI.
2. **`open_basedir` trống ở CLI** — cần xác nhận riêng cho PHP-FPM/LSAPI web runtime; nếu web runtime cũng không giới hạn `open_basedir`, cần đánh giá thêm rủi ro traversal giữa các site khác trên cùng server (multi-tenant shared hosting).
3. **Không có 2FA/MFA nào đang bảo vệ chính tài khoản SSH/OnePanel** — nằm ngoài phạm vi audit code nhưng đáng lưu ý cho việc vận hành sau này.
4. **`.bash_history` của user hiện chỉ có 7 byte** — tài khoản gần như mới tinh, phù hợp với kết luận "chưa có website nào được cài".
5. Không lưu, không log bất kỳ mật khẩu hay secret nào trong quá trình audit này — mọi lệnh MySQL chạy không kèm mật khẩu và nhận lỗi truy cập đúng như kỳ vọng.

---

## 8. Recommended deployment layout

Vì chưa xác nhận được panel có cho đổi document root tuỳ ý hay không (mục 3.8), nhưng **symlink đã xác nhận hoạt động**, đề xuất mô hình release/symlink kinh điển, "gắn" vào document root cố định bằng cách biến `public_html` chính nó thành symlink:

```text
/home/erxwskxohosting/
├── public_html -> apps/lylishop/current/web      # domain root cố định do panel set 1 lần duy nhất, KHÔNG cần đổi trong panel về sau
└── apps/lylishop/
    ├── releases/
    │   ├── 20260803120000/
    │   │   └── web/                              # Bedrock web/ root của release này
    │   └── 20260810090000/
    ├── shared/
    │   ├── .env
    │   ├── uploads/                              # web/app/uploads thật, symlink từ mỗi release
    │   ├── logs/
    │   └── backups/
    └── current -> releases/20260810090000
```

* Mỗi lần deploy: upload release mới vào `releases/<timestamp>/`, symlink `shared/uploads`, `shared/.env` vào đúng vị trí Bedrock cần bên trong release đó, sau đó đổi `current` sang release mới, và **chỉ đổi `public_html` một lần duy nhất** lúc go-live đầu tiên (từ thư mục thật hiện tại sang symlink trỏ `current/web`).
* Rollback = trỏ lại `current` về release cũ, không cần đụng tới `public_html`.
* Vì đây là bước ghi đè `public_html` hiện tại (đang chỉ chứa `index.htm` mặc định) — **phải backup nó trước** (dù chỉ là file mặc định của hosting) và thực hiện ở Phase 6 (deploy production đầu tiên — không có staging, xem `docs/DEPLOYMENT.md`), không phải bây giờ.

Không cần panel hỗ trợ set document root tuỳ ý — cách này hoạt động trên bất kỳ shared hosting nào cho phép symlink, đã xác nhận đúng ở tài khoản này.

---

## 9. Recommended backup strategy

* **Database:** `mysqldump` hằng ngày (đã xác nhận có binary) → nén → lưu vào `shared/backups/db/` → đồng bộ off-site (rsync xuống máy dev, hoặc gắn vào UpdraftPlus theo TECH_STACK.md §8.4 khi WordPress đã cài).
* **Uploads:** `rsync -a shared/uploads/` hằng ngày/hằng tuần xuống nơi lưu off-site, tách biệt với backup code.
* **Trước mỗi deploy:** bắt buộc dump DB + tar uploads trước khi chạy migration, lưu trong `shared/backups/pre-deploy/<timestamp>/`.
* **Restore test định kỳ:** phục hồi bản mới nhất lên một database/thư mục kiểm thử riêng trên chính hosting này, hoặc lên local DDEV (dự án production-only, không có staging domain — Amendment 2026-08-03) để kiểm chứng backup dùng được.
* Repository (Git) **không** thay thế database backup — đúng nguyên tắc PLAN.md §12.

---

## 10. Bedrock — dùng được không thay đổi?

**Có, không cần sửa Bedrock**, với hai điều chỉnh vận hành (không phải sửa mã nguồn Bedrock):

1. Document root không được panel set trực tiếp vào `releases/<id>/web` → dùng symlink `public_html -> current/web` như mục 8, hoàn toàn tương thích cấu trúc chuẩn của Bedrock.
2. Mọi lệnh WP-CLI/Composer trên host phải gọi tường minh qua `/opt/alt/php83/usr/bin/php` (bao gồm cả trong cron) vì PHP CLI mặc định là 8.1.

---

## 11. Release directories & symlink rollback

**Khả thi và được khuyến nghị** — đã xác nhận thực nghiệm rằng symlink hoạt động đúng trên filesystem này (`ln -s`, `readlink` thành công). Mô hình `releases/` + `current` symlink ở mục 8 dùng được nguyên bản.

---

## 12. Composer chạy ở đâu?

**Khuyến nghị: máy dev hoặc CI, không chạy trên shared host cho build thường xuyên.**

Lý do:
* Máy dev Windows hiện **chưa có PHP/Composer cài local** (xác nhận ở mục local audit) — cần cài đặt trước khi build local được, hoặc dùng CI hoàn toàn.
* Host **có thể** chạy Composer (đã xác nhận `composer diagnose` OK qua PHP 8.3), nên **có thể dùng làm phương án dự phòng một lần** (ví dụ bootstrap ban đầu) nhưng không nên là quy trình chính — Composer 2.8.10 trên host có CVE đã biết, và chạy build nặng lặp lại trên tài nguyên shared hosting không tối ưu.
* → **Kết hợp:** CI (GitHub Actions) chạy `composer install --no-dev`, build asset, tạo artifact → upload lên host qua rsync/scp. Host chỉ chạy `wp` (WP-CLI) để migrate/config sau khi artifact đã có sẵn `vendor/` và `web/wp/`.

---

## 13. Staging — SUPERSEDED (Amendment 2026-08-03)

Nội dung gốc của mục này (giữ bên dưới cho lịch sử) khảo sát ba lựa chọn staging vì tại thời điểm audit, quyết định hạ tầng chưa được chốt. Founder sau đó đã quyết định: **production-only, không có staging domain/database/deploy/promotion workflow.** Local development (DDEV) và automated checks (CI) là cổng kiểm tra duy nhất trước production — xem `docs/DEPLOYMENT.md`. Mục 3 item 9 ở trên (staging subdomain) không còn là hạng mục cần xác nhận qua panel.

Nội dung khảo sát gốc (lịch sử, không còn là hướng đi hiện hành):

1. ~~Tốt nhất nếu panel cho phép: subdomain thật, ví dụ `staging.lylishop.online`, database + `.env` riêng.~~
2. ~~Nếu không có subdomain: thư mục riêng dưới `apps/lylishop-staging/` với DB riêng.~~
3. ~~Staging chạy local trên DDEV trước khi đẩy lên production.~~

Lựa chọn 3 ở trên vẫn có giá trị nhưng nay được mô tả lại đúng bản chất: đó là **local development**, không phải "staging" — không có domain/database staging tương ứng trên hosting.

---

## 14. Đối chiếu PLAN.md vs TECH_STACK.md — mâu thuẫn cần xác nhận

Theo yêu cầu "explicitly report any contradictions... do not resolve contradictions silently", các điểm sau **chưa được tự ý giải quyết**:

1. **Bundle & Advanced Coupon: bắt buộc hay tùy chọn?**
   PLAN.md §9.3 xếp "Bundle" và "Advanced discount" vào **Tier B — Theo yêu cầu shop** (chỉ cài khi shop yêu cầu). TECH_STACK.md §5.2 lại liệt kê WooCommerce Product Bundles và Smart Coupons là **"Bắt buộc"** trong bảng "Plugin thương mại bắt buộc theo phạm vi", và đưa cả hai vào khối `VIETNAM COMMERCE` cố định ở §19 (không nằm trong khối `OPTIONAL / FEATURE FLAG` như Affiliate). **Cần xác nhận:** shop cụ thể này đã chốt cần bundle + advanced coupon (nên TECH_STACK đúng), hay đây là default quá tay so với PLAN?

2. **Cart/Checkout: Block hay Classic?**
   PLAN.md §2.2 và §8.3 liệt kê rõ "Dùng: WooCommerce Cart block. WooCommerce Checkout block." như phần reuse mặc định. TECH_STACK.md §4.1 **chốt dùng Classic Cart/Checkout cho V1**, với lý do plugin Việt Nam (Vietnam Store Toolkit, SePay) chưa công bố rõ tương thích Blocks. Đây là lý do kỹ thuật hợp lý, nhưng đảo ngược trực tiếp một quyết định đã nêu trong PLAN. **Cần xác nhận:** người chốt PLAN đồng ý với override này của TECH_STACK.

3. **"Một plugin Security/hardening" (Tier A) có được thỏa mãn không?**
   PLAN.md §9.3 Tier A yêu cầu một plugin "Security hoặc hardening" như một hạng mục bắt buộc độc lập. TECH_STACK.md §9.3 chủ động **không cài security suite** (không Wordfence/Sucuri/AIOWPS), thay bằng tổ hợp WP 2FA + Simple History + hosting-level hardening. **Cần xác nhận:** tổ hợp này được xem là đã thỏa Tier A của PLAN, hay PLAN vẫn kỳ vọng một plugin hardening chuyên dụng riêng.

Các điểm **không** phải mâu thuẫn (TECH_STACK chỉ đang giải quyết một quyết định PLAN cố ý để mở):
* Theme nền: PLAN §6.2 để ngỏ giữa Block theme và Storefront; TECH_STACK §3.1 chốt Storefront — đây là quyết định hợp lệ, không phải xung đột.
* RMA: PLAN §8.5 dùng ngôn ngữ điều kiện ("nếu cần"); TECH_STACK §11.1 xác định điều kiện chưa đạt nên chưa cài — nhất quán.

---

## 15. Bảng đối chiếu tiêu chí Stop Condition (Phase 7)

| Điều kiện dừng | Trạng thái |
|---|---|
| Web PHP không chạy được PHP 8.3 | Không xác nhận là true — `php_selector: true`, cần bật qua panel |
| Document root không thể an toàn cho Bedrock | Không đúng — giải quyết được bằng symlink |
| Database server không tương thích | Không đúng — MariaDB 11.4.10 tương thích |
| Thiếu extension PHP không thể bật | Chưa xác nhận — cần kiểm tra PHP Selector UI, có fallback cho hầu hết |
| SSH deploy không giữ được uploads/env | Không đúng — rsync + symlink giải quyết được |
| Không có backup/restore path | Không đúng — mysqldump + rsync sẵn có |
| Dữ liệu sống có nguy cơ bị ghi đè | Không — tài khoản trống |
| Thiếu plugin trả phí/license | Chưa đánh giá trong audit này (nằm ngoài phạm vi SSH — cần xác nhận license SePay, Product Bundles, Smart Coupons, Affiliate riêng) |
| PLAN.md/TECH_STACK.md mâu thuẫn chưa giải quyết | **Có 3 điểm nêu ở mục 14** — cần xác nhận trước khi cấu hình plugin/cart thật, nhưng không chặn việc scaffold repository |

**Kết luận:** Không có Stop Condition nào bị vi phạm để chặn hoàn toàn Phase 5 (scaffold repository). Ba mâu thuẫn ở mục 14 nên được xác nhận **trước khi cấu hình plugin Bundle/Coupon và trước khi build trang Cart/Checkout thật**, nhưng không chặn việc tạo cấu trúc repository, composer.json, mu-plugin skeleton, hay viết script.

Deploy production thật (Phase 6, lên host — không có staging, xem Amendment mục 13) **chưa nên bắt đầu** cho tới khi mục 3.1, 3.4, 3.5 (PHP Selector, DB tạo qua panel, xác nhận web server) được xác nhận qua OnePanel, và theme đã vượt qua `docs/THEME-COMPATIBILITY-GATE.md` (lựa chọn theme — Botiga Free — đã chốt 2026-08-04 ở `docs/THEME-DECISION.md`, nhưng gate tương thích chưa chạy).
