<?php
    $page = $_GET['page'] ?? '';

    // Đổi toàn bộ thuật ngữ sang Thư viện
    $menuitems = [
         'managelibrary' => 'Tủ sách (Index)',
         'book' => 'Nhập Sách mới',
         'search' => 'Tra cứu Sách',
    ];
?>
<html>
    <head>
        <title>Thư Viện Số - Elasticsearch</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
        <!-- Thêm FontAwesome để có icon đẹp -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            body { background-color: #f8f9fa; }
            .navbar { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="collapse navbar-collapse" id="navbarNav">
                <a class="navbar-brand" href="/elastic-php/"><i class="fas fa-book-reader mr-2"></i>Thư Viện Số</a>
                <ul class="navbar-nav ml-auto">
                    <?php foreach ($menuitems as $url => $label):?>
                        <?php $activeclass = ($page == $url) ? 'active font-weight-bold' : ''; ?>
                        <li class="nav-item">
                            <a class="nav-link <?=$activeclass?>" href="/elastic-php/?page=<?=$url?>"><?=$label?></a>
                        </li>
                    <?php endforeach;?>
                </ul>
            </div>
        </nav>

        <div class="container mt-4">
            <?php if ($page == ''):?>
                <div class="jumbotron text-center bg-white shadow-sm">
                    <h1 class="display-4 text-dark"><i class="fas fa-library"></i> Chào mừng đến Thư Viện</h1>
                    <p class="lead">Hệ thống tra cứu sách tốc độ cao sử dụng lõi Elasticsearch.</p>
                </div>
            <?php else:?>
                <?php include $page.'.php'; ?>
            <?php endif;?>
        </div>
    </body>
</html>