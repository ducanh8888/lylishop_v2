# DEPLOYMENT

Lyli Shop có một môi trường production. Production nhận artifact bất biến; host không `git pull` và không chạy Composer để build.

## Quy trình duy nhất

**LOCAL VALIDATE → BUILD → BACKUP IF PRODUCTION STATE WILL CHANGE → DEPLOY → SMOKE TEST → PUBLIC OR ROLLBACK**

1. **Local validate:** chạy validator liên quan trong WSL. Đây là deployment gate; GitHub Actions chỉ cung cấp thông tin và không cần chờ.
2. **Build:** commit/push source, rồi chạy `scripts/build-artifact.sh` từ commit bất biến. Artifact phải có `vendor/`, `web/wp/`, plugins, MU plugins, Botiga và `shop-child`; không có `.env`, uploads, backup, Git hoặc DB dump.
3. **Backup nếu production state thay đổi:** chạy `scripts/production-backup.sh` trên host. Script dùng PHP 8.3 + WP-CLI `db export`, gzip DB và tar uploads; không shell-source `.env` và không in credential.
4. **Deploy:** upload/extract vào `apps/lylishop/releases/<timestamp>`, rồi link `.env` và `web/app/uploads` tới `shared`. Kiểm tra release riêng trước khi đổi `current`.
5. **Smoke test:** dùng `/opt/alt/php83/usr/bin/php /usr/bin/wp --path=<release>/web/wp`; kiểm tra core, theme, MU autoload, `wp lyli`, homepage và các route công khai.
6. **Public hoặc rollback:** đổi `apps/lylishop/current` tới release mới nếu gate đạt. Khi lỗi chức năng/bảo mật thật, trỏ `current` về release trước và restore DB chỉ khi migration DB không tương thích ngược.

## Cấu trúc host

```text
/home/erxwskxohosting/
├── public_html -> apps/lylishop/current/web
└── apps/lylishop/
    ├── releases/<timestamp>/
    ├── shared/.env
    ├── shared/uploads/
    ├── shared/backups/
    └── current -> releases/<timestamp>
```

Production giữ `DISALLOW_FILE_MODS=true` và `DISALLOW_FILE_EDIT=true`. Nội dung/cấu hình cửa hàng chỉnh trong WP Admin; code/plugin/theme đi qua Git, local validation và release artifact.

## Public gate tối thiểu

- HTTPS/Home/Shop/Cart/Checkout/My Account/admin login hoạt động, không có PHP fatal.
- Mobile 375px không tràn ngang rõ ràng.
- `.env`, backup và SQL không public; directory listing tắt.
- Không bật payment chưa cấu hình; không publish nội dung giả.

Trạng thái release, source commit, backup và blocker hiện tại nằm ở `docs/PRODUCTION-STATUS.md`.
