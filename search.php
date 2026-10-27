<?php
use Elasticsearch\Client;
require __DIR__ . '/vendor/autoload.php';

$hosts = [['host' => '127.0.0.1', 'port' => '9200', 'scheme' => 'http']];
$client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();

// Lấy danh sách tủ sách
$existing_indices = [];
try {
    $mapping = $client->indices()->getMapping();
    foreach (array_keys($mapping) as $idx) {
        if (strpos($idx, '.') !== 0) $existing_indices[] = $idx;
    }
} catch (Exception $e) {}

$search = $_POST['search'] ?? null;
$target_index = $_POST['target_index'] ?? '*'; 
$rs = null;
$error_msg = null;

if ($search != null) {
    $params = [
        'index' => $target_index, // Tìm theo Tủ đã chọn hoặc Tất cả (*)
        'body' => [
            'size' => 20,
            'query' => [
                'bool' => [
                    'must' => [
                        'multi_match' => [
                            'query' => $search,
                            'fields' => ['title^3', 'keywords^2', 'content'], 
                            'fuzziness' => 'AUTO', 
                            'type' => 'best_fields',
                            'tie_breaker' => 0.3 
                        ]
                    ]
                ]
            ],
            'highlight' => [
                'pre_tags' => ["<mark>"],
                'post_tags' => ["</mark>"],
                'fields' => [
                    'title' => new stdClass(),
                    'keywords' => new stdClass(),
                    'content' => ['fragment_size' => 150, 'number_of_fragments' => 1]
                ]
            ]
        ]
    ];
    
    try {
        $prs = $client->search($params);
        if (isset($prs['hits']['total']['value']) && $prs['hits']['total']['value'] >= 1) {
            $rs = $prs['hits']['hits'];
        }
    } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
        $error_msg = '<div class="alert alert-danger text-center mt-4 shadow-sm">Khu vực bạn chọn tìm kiếm chưa tồn tại hoặc không có dữ liệu!</div>';
    } catch (Exception $e) {
        $error_msg = '<div class="alert alert-danger text-center mt-4 shadow-sm">Lỗi: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    }
}
?>

<div class="text-center mb-5">
    <h2 class="display-4"><i class="fas fa-search"></i> Tra cứu Thư viện</h2>
    <form method="post" class="mt-4">
        <div class="row justify-content-center">
            <div class="col-md-3">
                <select name="target_index" class="form-control form-control-lg shadow-sm bg-light">
                    <option value="*" <?=($target_index == '*') ? 'selected' : ''?>>-- Tất cả Tủ Sách --</option>
                    <?php foreach($existing_indices as $idx): ?>
                        <option value="<?=htmlspecialchars($idx)?>" <?=($target_index == $idx) ? 'selected' : ''?>>
                            Trong tủ: <?=htmlspecialchars($idx)?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex">
                <input name="search" value="<?=htmlspecialchars($search)?>" class="form-control form-control-lg flex-grow-1 shadow-sm" placeholder="Nhập tên sách, tác giả...">
                <button type="submit" class="btn btn-dark btn-lg ml-2 shadow-sm px-4"><i class="fas fa-search"></i> Tìm</button>
            </div>
        </div>
    </form>
</div>

<?php 
if ($error_msg != null): echo $error_msg;
elseif ($rs != null):
?>
    <hr>
    <p class="text-muted">Tìm thấy <strong><?=count($rs)?></strong> cuốn sách.</p>
    <div class="row">
        <?php foreach ($rs as $r):?>
            <?php
                // Escape toàn bộ dữ liệu người dùng để chống XSS, sau đó khôi phục đúng thẻ <mark> do highlight chèn vào
                $restore_mark = fn($s) => str_replace(['<mark>', '</mark>'], ['<mark>', '</mark>'], $s);
                $title = isset($r['highlight']['title'][0])
                    ? $restore_mark(htmlspecialchars($r['highlight']['title'][0], ENT_QUOTES, 'UTF-8'))
                    : htmlspecialchars($r['_source']['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $cover = !empty($r['_source']['cover_image']) ? htmlspecialchars($r['_source']['cover_image'], ENT_QUOTES, 'UTF-8') : 'https://placehold.co/300x400?text=Chua+Co+Anh+Bia';
                $content_snippet = isset($r['highlight']['content'][0])
                    ? $restore_mark(htmlspecialchars($r['highlight']['content'][0], ENT_QUOTES, 'UTF-8'))
                    : htmlspecialchars(substr($r['_source']['content'] ?? 'Chưa có tóm tắt...', 0, 100), ENT_QUOTES, 'UTF-8') . '...';
                $id = htmlspecialchars($r['_id'], ENT_QUOTES, 'UTF-8');
                $index_name_found = htmlspecialchars($r['_index'], ENT_QUOTES, 'UTF-8'); // Nơi chứa cuốn sách được tìm thấy
            ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="position-absolute p-2">
                        <span class="badge badge-primary shadow-sm"><i class="fas fa-folder"></i> <?=$index_name_found?></span>
                    </div>
                    <img src="<?=$cover?>" class="card-img-top p-2" style="height: 320px; object-fit: contain; background: #f8f9fa;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-truncate" title="<?=strip_tags($title)?>"><?=$title?></h5>
                        <p class="card-text text-muted small flex-grow-1"><?=$content_snippet?></p>
                        <button class="btn btn-outline-dark btn-sm mt-auto" data-toggle="modal" data-target="#bookModal<?=$id?>"><i class="fas fa-eye"></i> Chi tiết</button>
                    </div>
                </div>
            </div>

            <!-- Modal Chi Tiết -->
            <div class="modal fade" id="bookModal<?=$id?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title"><i class="fas fa-book"></i> <?=htmlspecialchars($r['_source']['title'] ?? '', ENT_QUOTES, 'UTF-8')?></h5>
                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body row">
                            <div class="col-md-5 text-center">
                                <img src="<?=$cover?>" class="img-fluid shadow-sm border" style="max-height: 400px; object-fit: contain;">
                            </div>
                            <div class="col-md-7">
                                <h6>Mã sách: <span class="badge badge-secondary"><?=$id?></span></h6>
                                <h6>Thuộc tủ: <span class="badge badge-info"><?=$index_name_found?></span></h6>
                                <h6 class="mt-3 text-danger"><i class="fas fa-tags"></i> Từ khóa:</h6>
                                <p><?= implode(', ', array_map(fn($k) => htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8'), (array)($r['_source']['keywords'] ?? ['Không có']))) ?></p>
                                <h6 class="mt-3 text-primary"><i class="fas fa-align-left"></i> Nội dung tóm tắt:</h6>
                                <p class="text-justify" style="white-space: pre-wrap;"><?=htmlspecialchars($r['_source']['content'] ?? 'Chưa có nội dung cập nhật.', ENT_QUOTES, 'UTF-8')?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach;?>
    </div>

    <!-- Script Modal -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

<?php elseif($search != null): ?>
    <div class="alert alert-warning text-center shadow-sm"><i class="fas fa-exclamation-triangle"></i> Rất tiếc, không tìm thấy cuốn sách nào khớp với từ khóa.</div>
<?php endif;?>