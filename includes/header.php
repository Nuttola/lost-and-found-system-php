<?php
// includes/header.php
require_once __DIR__ . '/../config/db_connect.php'; 

// กำหนดหัวข้อหน้าเว็บ (Title)
$page_title = $page_title ?? "ระบบแจ้งของหาย-ตามหาของ มหาวิทยาลัย"; 
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
    <style>
        /* สไตล์เพิ่มเติมเพื่อให้ดูทันสมัย */
        body {
            background-color: #f8f9fa; /* Light grey background */
        }
        .navbar-brand {
            font-weight: 700;
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://picsum.photos/1200/400?random=1');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL; ?>">Lost & Found <small>UMT</small></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="<?= BASE_URL; ?>index.php">หน้าหลัก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL; ?>report_lost.php">แจ้งของหาย</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL; ?>report_found.php">แจ้งของที่เก็บได้</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto"> <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            สวัสดี, <?= htmlspecialchars($_SESSION['fullname']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            
                            <li><a class="dropdown-item" href="<?= BASE_URL; ?>user_dashboard.php">แดชบอร์ดของฉัน</a></li>
                            
                            <?php if ($_SESSION['user_role'] >= 2): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL; ?>admin/dashboard.php">ระบบจัดการหลังบ้าน</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <li><a class="dropdown-item" href="<?= BASE_URL; ?>logout.php">ออกจากระบบ</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL; ?>login.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-light ms-2" href="<?= BASE_URL; ?>register.php">ลงทะเบียน</a>
                    </li>
                <?php endif; ?>
            </ul>

            </ul>
        </div>
    </div> 
</nav>

<main class="py-5"> <div class="container"> 