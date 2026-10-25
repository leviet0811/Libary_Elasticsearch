<?php
use Elasticsearch\Client;
require 'vendor/autoload.php';

$hosts = [['host' => '127.0.0.1', 'port' => '9200', 'scheme' => 'http']];
$client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();

// Lấy danh sách tủ sách để đưa vào Dropdown
$existing_indices = [];
try {
    $mapping = $client->indices()->getMapping();
    foreach (array_keys($mapping) as $idx) {
        if (strpos($idx, '.') !== 0) $existing_indices[] = $idx;
    }
} catch (Exception $e) {}

if (isset($_POST['title']) && isset($_POST['id']) && isset($_POST['target_index'])) {
    $params = [
        'index' => $_POST['target_index'], // Lưu vào tủ sách được chọn
        'id'    => $_POST['id'],
        'body'  => [
            'title' => $_POST['title'],
            'content' => $_POST['content'] ?? '',
            'keywords' => explode(',', $_POST['keywords'] ?? ''),
            'cover_image' => $_POST['cover_base64'] ?? '' 
        ]
    ];
    try {
        $client->index($params);
        echo '<div class="alert alert-success shadow-sm"><i class="fas fa-check-circle"></i> Đã cất cuốn sách <strong>' . htmlspecialchars($_POST['title']) . '</strong> vào tủ: <strong>'.$_POST['target_index'].'</strong></div>';
    } catch(Exception $e) {
        echo '<div class="alert alert-danger shadow-sm">Lỗi: ' . $e->getMessage() . '</div>';
    }
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

<?php if(empty($existing_indices)): ?>
    <div class="alert alert-danger text-center p-5 shadow-sm">
        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i><br>
        <h4>Hệ thống chưa có tủ sách nào!</h4>
        <p>Vui lòng qua mục <strong>Tủ Sách (Index)</strong> để khởi tạo ít nhất 1 tủ sách trước khi nhập sách.</p>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header h4 bg-dark text-white"><i class="fas fa-book-medical"></i> Nhập sách mới</div>
                <div class="card-body">
                    <form method="post" id="bookForm">
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-primary"><i class="fas fa-boxes"></i> Chọn Tủ sách để cất:</label>
                            <select name="target_index" class="form-control form-control-lg bg-light" required>
                                <?php foreach($existing_indices as $idx): ?>
                                    <option value="<?=htmlspecialchars($idx)?>">Tủ sách: <?=htmlspecialchars($idx)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="drop_zone" class="form-group border border-primary p-4 rounded text-center" style="border-style: dashed !important; cursor: pointer; transition: 0.3s;">
                            <label for="pdf_upload" class="h5 text-primary m-0" style="cursor: pointer;">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-2"></i><br>
                                Kéo thả hoặc Nhấp vào đây để chọn file PDF
                            </label>
                            <input type="file" id="pdf_upload" accept=".pdf" class="form-control-file d-none">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="font-weight-bold text-muted">Mã sách (ID)</label>
                                <input name="id" id="book_id" class="form-control bg-light" value="<?=time()?>" required readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="font-weight-bold">Tựa sách (Title) <span class="text-danger">*</span></label>
                                <input name="title" id="book_title" class="form-control" required placeholder="Tên sách sẽ tự động điền khi tải PDF...">
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label class="font-weight-bold">Tóm tắt / Nội dung (Content)</label>
                            <textarea name="content" class="form-control" rows="3" placeholder="Nhập tóm tắt..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Từ khóa (Keywords)</label>
                            <input name="keywords" class="form-control" placeholder="Cách nhau bằng dấu phẩy...">
                        </div>

                        <input type="hidden" name="cover_base64" id="cover_base64">
                        <button type="submit" class="btn btn-primary btn-block mt-4 btn-lg shadow-sm"><i class="fas fa-save"></i> Đưa lên kệ sách</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center sticky-top" style="top: 20px;">
                <div class="card-header bg-light text-muted font-weight-bold"><i class="fas fa-image"></i> Ảnh bìa trích xuất</div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center" style="min-height: 400px; background: #f8f9fa;">
                    <canvas id="pdf_canvas" class="img-fluid shadow" style="max-height: 400px; display: none; object-fit: contain;"></canvas>
                    <div id="wait_msg" class="text-muted text-center">
                        <i class="fas fa-file-pdf fa-4x mb-3 text-secondary"></i><br>
                        <span>Chưa có file PDF nào được chọn</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('drop_zone');
        const fileInput = document.getElementById('pdf_upload');
        dropZone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

        ['dragenter', 'dragover'].forEach(eventName => { dropZone.addEventListener(eventName, () => dropZone.style.backgroundColor = '#e9ecef', false); });
        ['dragleave', 'drop'].forEach(eventName => { dropZone.addEventListener(eventName, () => dropZone.style.backgroundColor = 'transparent', false); });

        dropZone.addEventListener('drop', function(e) {
            let dt = e.dataTransfer; let files = dt.files;
            if(files.length > 0) { fileInput.files = files; processFile(files[0]); }
        }, false);

        fileInput.addEventListener('change', function(e) {
            if(this.files.length > 0) { processFile(this.files[0]); }
        });

        function processFile(file) {
            if(file.type !== "application/pdf") { alert("Vui lòng chỉ chọn file định dạng PDF!"); return; }
            document.getElementById('book_title').value = file.name.replace(/\.[^/.]+$/, ""); 
            document.getElementById('wait_msg').innerHTML = '<i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i><br><span class="text-primary font-weight-bold">Đang quét ảnh bìa...</span>';
            document.getElementById('pdf_canvas').style.display = 'none';
            
            var fileReader = new FileReader();
            fileReader.onload = function() {
                var typedarray = new Uint8Array(this.result);
                pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                    pdf.getPage(1).then(function(page) {
                        var scale = 1.5; var viewport = page.getViewport({scale: scale});
                        var canvas = document.getElementById('pdf_canvas');
                        var context = canvas.getContext('2d');
                        canvas.height = viewport.height; canvas.width = viewport.width;
                        page.render({ canvasContext: context, viewport: viewport }).promise.then(function() {
                            document.getElementById('cover_base64').value = canvas.toDataURL('image/jpeg', 0.8);
                            canvas.style.display = 'block'; document.getElementById('wait_msg').style.display = 'none';
                        });
                    }).catch(function() {
                        document.getElementById('wait_msg').innerHTML = '<i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i><br>Lỗi đọc trang PDF';
                    });
                });
            };
            fileReader.readAsArrayBuffer(file);
        }
    </script>
<?php endif; ?>