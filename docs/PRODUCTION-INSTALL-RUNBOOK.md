# PRODUCTION INSTALL RUNBOOK — Lyli Shop

Trình tự **tương lai**, chưa thực thi bước nào. Mỗi bước ghi rõ có cần **xác nhận rõ ràng (explicit authorization)** trước khi chạy hay không. Đây là kế hoạch, không phải log thực thi. Điều kiện tiên quyết trước khi bắt đầu bước 1: `docs/INSTALLATION-PREPARATION.md` ở trạng thái READY FOR CREDENTIAL HANDOFF, và **DNS `lylishop.online` đã được founder xác nhận/xử lý** (hiện đang trỏ Vercel — xem `docs/INSTALLATION-PREPARATION.md`).

| # | Bước | Cần xác nhận rõ ràng? |
|---|---|---|
| 1 | Founder tạo database trong OnePanel (tên DB, user, password, charset `utf8mb4`) | Không áp dụng — founder tự thực hiện, ngoài phạm vi agent |
| 2 | Credential được lưu ngoài repository (trực tiếp trên `commerce-host`, xem `docs/CREDENTIAL-HANDOFF.md`) | Không áp dụng — founder/developer thực hiện thủ công |
| 3 | Agent xác thực kết nối database (chỉ kiểm tra connect thành công/thất bại, không đọc/in giá trị) | **CÓ** — cần xác nhận trước khi chạy, vì đây là lần đầu chạm vào credential thật |
| 4 | Backup web root mặc định hiện tại (`index.htm`) trước khi thay đổi bất cứ gì trong `public_html` | **CÓ** — thao tác ghi đầu tiên lên `public_html` |
| 5 | Tạo cấu trúc `apps/lylishop/{releases,shared}` và `shared/{.env,uploads,logs,backups}` | **CÓ** — tạo thư mục mới trên production |
| 6 | Upload release artifact đã build (từ máy dev/CI) lên `releases/<timestamp>/` | **CÓ** — đưa code thật lên host |
| 7 | Symlink hoặc copy `shared/.env` vào release | **CÓ** — chạm credential thật lần đầu trong release |
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
* DNS: nếu tới thời điểm chạy runbook này mà `lylishop.online` vẫn trỏ ngoài hosting account (ví dụ Vercel như phát hiện ở `docs/INSTALLATION-PREPARATION.md`), phải giải quyết việc này **trước** bước 9 (kích hoạt theme công khai) — kích hoạt site trong khi DNS trỏ sai chỗ không gây hại nhưng cũng không có ý nghĩa kiểm thử thật; cần founder xác nhận DNS đã đúng trước khi coi go-live là hoàn tất.
