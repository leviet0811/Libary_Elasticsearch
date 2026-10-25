<?php
use Elasticsearch\Client;
require "vendor/autoload.php";

$hosts = [['host' => '127.0.0.1', 'port' => '9200', 'scheme' => 'http']];
$client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();

$mgs = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index_name = strtolower(trim($_POST['index_name'] ?? ''));
    // Ép tên index không chứa khoảng trắng và ký tự đặc biệt
    $index_name = preg_replace('/[^a-z0-9_-]/', '', $index_name);
    $act = $_POST['act'] ?? '';

    if (!empty($index_name)) {
        if ($act === 'create') {
            if ($client->indices()->exists(['index' => $index_name])) {
                $mgs = '<div class="alert alert-warning">Tủ sách <strong>'.$index_name.'</strong> đã tồn tại!</div>';
            } else {
                $params = [
                    'index' => $index_name,
                    'body' => [
                        'settings' => [
                            'number_of_shards' => 1, 'number_of_replicas' => 0,
                            'analysis' => [
                                'analyzer' => [
                                    'my_analyzer' => [
                                        'type' => 'custom', 'tokenizer' => 'standard', 
                                        'char_filter' => ['html_strip'], 'filter' => ['lowercase', 'stop']
                                    ]
                                ]
                            ]
                        ],
                        'mappings' => [
                            'properties' => [
                                'title' => ['type' => 'text', 'analyzer' => 'my_analyzer'],
                                'content' => ['type' => 'text'],
                                'keywords' => ['type' => 'text'],
                                'cover_image' => ['type' => 'text', 'index' => false] 
                            ]
                        ]
                    ]
                ];
                $client->indices()->create($params);
                $mgs = '<div class="alert alert-success">Khởi tạo thành công tủ sách: <strong>'.$index_name.'</strong></div>';
            }
        } elseif ($act === 'delete') {
            if ($client->indices()->exists(['index' => $index_name])) {
                $client->indices()->delete(['index' => $index_name]);
                $mgs = '<div class="alert alert-danger">Đã đốt cháy tủ sách: <strong>'.$index_name.'</strong></div>';
            }
        }
    } else {
        $mgs = '<div class="alert alert-danger">Tên tủ sách không hợp lệ! Chỉ dùng chữ cái thường, số và dấu gạch ngang.</div>';
    }
}

// Lấy danh sách các Tủ sách hiện có (Bỏ qua các index hệ thống bắt đầu bằng dấu chấm)
$existing_indices = [];
try {
    $mapping = $client->indices()->getMapping();
    foreach (array_keys($mapping) as $idx) {
        if (strpos($idx, '.') !== 0) $existing_indices[] = $idx;
    }
} catch (Exception $e) {}
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header h4 bg-dark text-white"><i class="fas fa-plus-square"></i> Tạo Tủ Sách Mới</div>
            <div class="card-body">
                <?=$mgs?>
                <form method="post" class="form-inline d-flex justify-content-between">
                    <input type="hidden" name="act" value="create">
                    <input type="text" name="index_name" class="form-control flex-grow-1 mr-2" placeholder="Tên tủ sách (vd: van-hoc, khoa-hoc...)" required>
                    <button type="submit" class="btn btn-success px-4"><i class="fas fa-hammer"></i> Khởi tạo</button>
                </form>
                <small class="text-muted mt-2 d-block">* Tên tủ sách viết liền không dấu, không khoảng trắng (vd: sach_van_hoc).</small>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header h5 bg-secondary text-white"><i class="fas fa-list"></i> Các Tủ Sách Hiện Có</div>
            <ul class="list-group list-group-flush">
                <?php if(empty($existing_indices)): ?>
                    <li class="list-group-item text-center text-muted py-4">Chưa có tủ sách nào. Hãy tạo một tủ sách mới!</li>
                <?php else: ?>
                    <?php foreach($existing_indices as $idx): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong><i class="fas fa-folder-open text-warning mr-2"></i> <?= htmlspecialchars($idx) ?></strong>
                            <form method="post" class="m-0" onsubmit="return confirm('Bạn có chắc chắn muốn xóa toàn bộ tủ sách này?');">
                                <input type="hidden" name="act" value="delete">
                                <input type="hidden" name="index_name" value="<?= htmlspecialchars($idx) ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Xóa tủ sách</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>