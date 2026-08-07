# PRODUCTION INSTALL RUNBOOK — Lyli Shop

Trình tự **tương lai**. Mỗi bước ghi rõ có cần **xác nhận rõ ràng (explicit authorization)** trước khi chạy hay không, và trạng thái thực tế tính đến 2026-08-05. Đây vẫn là kế hoạch cho các bước chưa làm, không phải cho phép tự động tiếp tục. Điều kiện tiên quyết trước khi bắt đầu bước 8: DNS đã đúng (✅ xác nhận 2026-08-05) và web PHP là 8.3.x (✅ xác nhận 2026-08-05) — cả hai xem `docs/INSTALLATION-PREPARATION.md`.

**Cập nhật 2026-08-06 — cấp phép một lần (one-time authorization):** founder cấp một lần cho nhiệm vụ triển khai storefront V1 (xem `docs/PRODUCTION-STATUS.md`) bao gồm cả các bước 8–15 của bảng dưới và public cutover có kiểm soát — **không** làm suy yếu chính sách phê duyệt chung cho các lần deploy sau, và **không** tái sử dụng làm ủy quyền cho bất kỳ nhiệm vụ tương lai nào.

| # | Bước | Trạng thái | Cần xác nhận rõ ràng? |
|---|---|---|---|
| 1 | Founder tạo database trong OnePanel (tên DB, user, password, charset `utf8mb4`) | **ĐÃ XONG** (founder, trước phiên 2026-08-05) | Không áp dụng — founder tự thực hiện, ngoài phạm vi agent |
| 2 | Credential được lưu ngoài repository (trực tiếp trên `commerce-host`, xem `docs/CREDENTIAL-HANDOFF.md`) | **ĐÃ XONG** 2026-08-05 — `.env` upload vào `shared/.env`, quyền 600, ngoài `public_html` | Không áp dụng — founder/developer thực hiện thủ công |
| 3 | Agent xác thực kết nối database (chỉ kiểm tra connect thành công/thất bại, không đọc/in giá trị) | **ĐÃ XONG** 2026-08-05 — kết nối PASS, MariaDB 11.4.10, 0 bảng, privilege probe PASS | Đã xác nhận và thực hiện trong phiên credential handoff |
| 4 | Backup web root mặc định hiện tại (`index.htm`) trước khi thay đổi bất cứ gì trong `public_html` | Chưa làm | **CÓ** — thao tác ghi đầu tiên lên `public_html` |
| 5 | Tạo cấu trúc `apps/lylishop/{releases,shared}` và `shared/{.env,uploads,logs,backups}` | **MỘT PHẦN** — `apps/lylishop/shared/` đã tạo (quyền 700) để chứa `.env`; `releases/`, `shared/uploads`, `shared/logs`, `shared/backups` chưa tạo | **CÓ** cho phần còn lại — tạo thư mục mới trên production |
| 6 | Upload release artifact đã build (từ máy dev/CI) lên `releases/<timestamp>/` | Chưa làm | **CÓ** — đưa code thật lên host |
| 7 | Symlink hoặc copy `shared/.env` vào release | Chưa làm (release chưa tồn tại) | **CÓ** — chạm credential thật lần đầu trong release |
| 8 | Cài WordPress qua WP-CLI (`wp core install` + cấu hình locale/timezone/VND) | **CÓ — thao tác không thể đảo ngược dễ dàng, cần xác nhận rõ ràng riêng, không gộp chung với bước khác** |
| 9 | Kích hoạt WooCommerce, Botiga, `shop-child` | **CÓ** — thay đổi trạng thái site hiển thị công khai (một khi domain đã trỏ đúng) |
| 10 | Tạo các trang bắt buộc (Cửa hàng, Giỏ hàng, Thanh toán, Tài khoản, v.v. qua WooCommerce setup) | **CÓ** — nội dung công khai |
| 11 | Cấu hình Classic Cart và Classic Checkout (đã chốt kiến trúc, chỉ còn bật đúng cấu hình) | **CÓ** — ảnh hưởng luồng mua hàng |
| 12 | Chạy kiểm tra tương thích theme/plugin theo `docs/THEME-COMPATIBILITY-GATE.md` | Không cần — read-only, không thay đổi trạng thái site |
| 13 | Chạy health check (`scripts/production-health-check.sh`) | Không cần — read-only |
| 14 | Xoá `index.htm` mặc định **chỉ khi** site mới đã sẵn sàng thay thế | **CÓ — thao tác xoá dữ liệu, luôn cần xác nhận riêng dù là file mặc định của hosting** |
| 15 | Ghi lại quy trình rollback đã dùng thật (không phải bản kế hoạch) vào `docs/BACKUP-RESTORE.md` | Không cần — chỉ là ghi tài liệu |

## Nguyên tắc áp dụng cho toàn bộ runbook

* Không có bước nào trong danh sách trên đã được thực thi ở bất kỳ tác vụ nào tính đến thời điểm viết tài liệu này.
* Mọi bước đánh dấu **CÓ** phải dừng lại và xin xác nhận rõ ràng trước khi chạy, kể cả khi bước trước đó đã được cho phép — không suy ra sự cho phép cho bước tiếp theo.
* Bước 8 (cài WordPress) và bước 14 (xoá `index.htm`) là hai điểm không thể đảo ngược dễ dàng nhất trong toàn runbook — luôn tách riêng, không gộp vào một lần phê duyệt chung với các bước khác.
* Rollback: nếu bất kỳ bước 4–11 thất bại, dùng `scripts/production-rollback.sh` để phục hồi release trước hoặc database từ backup bước 4 — xem `docs/DEPLOYMENT.md` và `docs/BACKUP-RESTORE.md`.
* DNS: **đã xác nhận đúng** kể từ 2026-08-05 (`lylishop.online` → IP hosting) — không còn là điều kiện chặn bước 9. ~~SSL công khai vẫn là self-signed placeholder~~ — **ĐÃ HẾT HIỆU LỰC:** xác nhận 2026-08-06 SSL hợp lệ cho `lylishop.online` (Let's Encrypt/AutoSSL, SAN đúng domain) — xem `docs/ONEPANEL-CHECKLIST.md` mục 1 (đã đánh dấu ĐÃ XONG) và `docs/PRODUCTION-STATUS.md`.
* WP-CLI `--path`: mọi lệnh `wp` trong runbook và trong `scripts/production-deploy.sh` phải trỏ vào thư mục Bedrock `web/` (nơi có `wp-config.php`), **không phải** `web/wp` (chỉ chứa core WordPress) — một lỗi dùng sai `web/wp` đã được tìm thấy và sửa trong `scripts/production-deploy.sh` ngày 2026-08-05 (`docs/INSTALLATION-PREPARATION.md`).
