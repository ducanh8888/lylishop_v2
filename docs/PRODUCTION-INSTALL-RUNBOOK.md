# PRODUCTION INSTALL RUNBOOK

WordPress đã được cài và public cutover đã hoàn tất. Không chạy lại cài đặt, không reset/empty database và không lặp lại các phase cài đặt lịch sử.

Mọi thay đổi tiếp theo dùng quy trình duy nhất trong `docs/DEPLOYMENT.md`:

**LOCAL VALIDATE → BUILD → BACKUP IF PRODUCTION STATE WILL CHANGE → DEPLOY → SMOKE TEST → PUBLIC OR ROLLBACK**

Thông tin thực tế về source commit, active/rollback release, backup, nội dung và commerce blockers nằm tại `docs/PRODUCTION-STATUS.md`.

## Kiểm tra runtime quan trọng

- PHP CLI: `/opt/alt/php83/usr/bin/php`.
- WP-CLI: `/usr/bin/wp --path=apps/lylishop/current/web/wp`.
- Bedrock plugin roots: `web/app/plugins` và `web/app/mu-plugins`.
- Shared data: release `.env` → `shared/.env`; release `web/app/uploads` → `shared/uploads`.
- GitHub Actions là thông tin bổ sung; WSL/local validation là deployment gate.

Chỉ rollback khi có lỗi chức năng hoặc bảo mật thật. Không rollback vì CI đang chờ, cảnh báo advisory, thiếu browser automation hoặc lỗi thẩm mỹ.
