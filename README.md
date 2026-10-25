# Thư Viện Số - Libary Elasticsearch

Web app quản lý và tra cứu sách bằng **Elasticsearch**, viết bằng PHP thuần.

## Tính năng

- **Tạo / xóa index** thư viện trên Elasticsearch kèm analyzer tùy chỉnh (lọc HTML, chuẩn hóa chữ thường, loại stop words)
- **Nhập sách**: tải lên file PDF, tự động render trang đầu làm ảnh bìa (pdf.js) và đánh chỉ mục nội dung
- **Tìm kiếm thông minh**: truy vấn multi_match trên tiêu đề / từ khóa / nội dung với trọng số, hỗ trợ fuzziness (sai chính tả vẫn tìm ra) và highlight kết quả

## Yêu cầu

- PHP >= 8.0
- Composer
- Elasticsearch 7.17.x

## Cài đặt

- Chạy `composer install` để cài thư viện
- Đảm bảo Elasticsearch đang chạy tại `127.0.0.1:9200`
- Chạy `php -S 127.0.0.1:8000` rồi mở trình duyệt truy cập http://127.0.0.1:8000