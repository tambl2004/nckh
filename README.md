# 🏬 Quản Lý Kho Hàng Thông Minh

> Hệ thống quản lý kho hàng hiện đại tích hợp mã vạch (Barcode), QR code và RFID, giúp theo dõi hàng tồn kho, quản lý nhập/xuất kho, và tối ưu hóa vận hành kho một cách hiệu quả.

---

## 🚀 Tính năng chính

- 📦 **Quản lý sản phẩm**  
  - Thêm, sửa, xóa thông tin sản phẩm
  - Gắn mã QR / Barcode / RFID cho mỗi sản phẩm

- 🏢 **Quản lý kho hàng**  
  - Theo dõi hàng tồn kho theo thời gian thực  
  - Lưu lịch sử nhập, xuất và luân chuyển hàng hóa  

- 📤 **Quản lý nhập / xuất kho**  
  - Nhập hàng bằng quét mã vạch hoặc RFID  
  - Giao diện nhập xuất thân thiện, hỗ trợ tìm kiếm thông minh  

- 📋 **Báo cáo & thống kê**  
  - Thống kê tồn kho, hàng nhập xuất, hàng sắp hết  
  - Xuất báo cáo dưới dạng Excel / PDF

- 👥 **Quản lý người dùng / phân quyền**  
  - Tài khoản quản trị, nhân viên, phân quyền chức năng rõ ràng  

- 🔐 **Xác thực & bảo mật**  
  - Đăng nhập an toàn, bảo vệ dữ liệu kho bằng cơ chế xác thực

---

## 🖥️ Giao diện người dùng

- Responsive hiện đại với **Bootstrap 5 / Flexbox**
- Thiết kế UI/UX tối ưu cho thao tác kho thực tế
- Hỗ trợ máy quét mã QR / Barcode / RFID trực tiếp

> 📸 *Bạn có thể thêm ảnh demo giao diện ở đây nếu muốn.*

---

## 🛠️ Cài đặt hệ thống

### 1. Yêu cầu hệ thống

- PHP >= 8.0  
- MySQL / MariaDB  
- Node.js & npm (nếu có dùng React/Frontend build)
- Apache / Nginx

### 2. Cài đặt

```bash
# Clone project
git clone https://github.com/tambl2004/nckh.git

# Di chuyển vào thư mục dự án
cd nckh

# Cài đặt backend PHP (nếu cần)
composer install

# Cài đặt frontend (nếu có dùng React/Vue)
npm install
npm run build
