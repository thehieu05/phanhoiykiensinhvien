<?php
$userId   = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['vai_tro'] ?? '';
$hoTen    = $_SESSION['ho_ten'] ?? '';
$soThongBaoChuaDoc = $userId ? demThongBaoChuaDoc($userId) : 0;

$prefix = match($userRole) {
    'admin'         => SITE_URL . '/admin',
    'truong_don_vi' => SITE_URL . '/truongdonvi',
    'can_bo'        => SITE_URL . '/canbo',
    default         => SITE_URL . '/sinh-vien',
};

$parts  = explode(' ', trim($hoTen));
$avatar = mb_strtoupper(mb_substr(end($parts), 0, 1, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Hệ thống Phản hồi') ?> – ĐH Vinh</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar-dhv">
  <button class="btn-hamburger" id="sidebarToggle"><i class="fas fa-bars"></i></button>
  <a class="navbar-brand" href="<?= $prefix ?>/">
    <img src="../assets/img/logo-dh-vinh.png" alt="ĐH Vinh">
    <div class="brand-text">
      <span class="brand-name">TRƯỜNG ĐẠI HỌC VINH</span>
      <span class="brand-sub">Hệ thống USMART</span>
    </div>
  </a>
  <span class="hoc-ky-label"><?= date('Y')-1 ?> – <?= date('Y') ?>/Học kỳ 2.1</span>
  <div class="navbar-search">
    <input type="text" placeholder="Nhập từ khóa" id="navSearch">
    <button class="search-btn" type="button"><i class="fas fa-search"></i></button>
  </div>
  <div class="navbar-right">
    <div class="dropdown">
      <button class="nav-icon-btn" data-bs-toggle="dropdown" title="Thông báo">
        <i class="fas fa-bell"></i>
        <?php if ($soThongBaoChuaDoc > 0): ?>
          <span class="badge-notif"><?= $soThongBaoChuaDoc ?></span>
        <?php endif; ?>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" style="width:320px;max-height:380px;overflow-y:auto">
        <li><h6 class="dropdown-header fw-bold">Thông báo</h6></li>
        <?php
        $thongBaos = layThongBao($userId, 6);
        if (empty($thongBaos)):
        ?>
          <li><span class="dropdown-item-text text-muted small">Chưa có thông báo</span></li>
        <?php else: foreach ($thongBaos as $tb): ?>
          <li>
            <a class="dropdown-item <?= $tb['da_doc'] ? '' : 'fw-semibold bg-light' ?> small py-2"
               href="<?= $prefix ?>/thong-bao.php?mark=<?= $tb['id'] ?>">
              <div><?= e($tb['tieu_de']) ?></div>
              <div class="text-muted" style="font-size:.71rem"><?= thoiGianTuongDoi($tb['created_at']) ?></div>
            </a>
          </li>
        <?php endforeach; endif; ?>
        <li><hr class="dropdown-divider m-0"></li>
        <li><a class="dropdown-item text-center small py-2 text-primary" href="<?= $prefix ?>/thong-bao.php">Xem tất cả</a></li>
      </ul>
    </div>
    <div class="dropdown">
      <button class="btn-user" data-bs-toggle="dropdown">
        <div class="avatar-circle"><?= $avatar ?></div>
        <span class="user-name d-none d-md-inline"><?= e($hoTen) ?></span>
        <i class="fas fa-chevron-down fa-xs" style="opacity:.7"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header"><?= e($hoTen) ?></h6></li>
        <li><a class="dropdown-item" href="<?= $prefix ?>/profile.php"><i class="fas fa-user me-2 text-primary"></i>Hồ sơ</a></li>
        <li><hr class="dropdown-divider m-0"></li>
        <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="d-flex">
<div class="sidebar" id="sidebar">

<?php if ($userRole === 'admin'): ?>
  <div class="sidebar-label">Tổng quan</div>
  <a href="<?= $prefix ?>/" class="nav-link <?= ($activeMenu==='dashboard')?'active':'' ?>"><i class="fas fa-home"></i> Trang chủ</a>

  <div class="sidebar-label">Phản hồi</div>
  <a href="<?= $prefix ?>/phan-hoi.php" class="nav-link <?= ($activeMenu==='phan_hoi')?'active':'' ?>"><i class="fas fa-comments"></i> Danh sách phản hồi</a>

  <div class="sidebar-label">Danh mục</div>
  <a href="<?= $prefix ?>/don-vi.php" class="nav-link <?= ($activeMenu==='don_vi')?'active':'' ?>"><i class="fas fa-building"></i> Đơn vị phụ trách</a>
  <a href="<?= $prefix ?>/chu-de.php" class="nav-link <?= ($activeMenu==='chu_de')?'active':'' ?>"><i class="fas fa-tags"></i> Chủ đề phản hồi</a>
  <a href="<?= $prefix ?>/loai-phan-hoi.php" class="nav-link <?= ($activeMenu==='loai_ph')?'active':'' ?>"><i class="fas fa-list-alt"></i> Loại phản hồi</a>

  <div class="sidebar-label">Tài khoản</div>
  <a href="<?= $prefix ?>/quanlycanbo.php" class="nav-link <?= ($activeMenu==='quan_ly_cb')?'active':'' ?>"><i class="fas fa-users"></i> Quản lý cán bộ</a>

  <div class="sidebar-label">Báo cáo</div>
  <a href="<?= $prefix ?>/bao-cao.php" class="nav-link <?= ($activeMenu==='bao_cao')?'active':'' ?>"><i class="fas fa-chart-bar"></i> Thống kê & Báo cáo</a>
  <a href="<?= $prefix ?>/danh-gia-sv.php" class="nav-link <?= ($activeMenu==='danh_gia_sv')?'active':'' ?>"><i class="fas fa-star"></i> Đánh giá sinh viên</a>

<?php elseif ($userRole === 'truong_don_vi'): ?>
  <div class="sidebar-label">Tổng quan</div>
  <a href="<?= $prefix ?>/" class="nav-link <?= ($activeMenu==='dashboard')?'active':'' ?>"><i class="fas fa-home"></i> Trang chủ</a>

  <div class="sidebar-label">Quản lý</div>
  <a href="<?= $prefix ?>/phancong.php" class="nav-link <?= ($activeMenu==='phan_cong')?'active':'' ?>"><i class="fas fa-user-check"></i> Phân công xử lý</a>
  <a href="<?= $prefix ?>/duyetphanhoi.php" class="nav-link <?= ($activeMenu==='duyet_tl')?'active':'' ?>"><i class="fas fa-clipboard-check"></i> Duyệt trả lời</a>

  <div class="sidebar-label">Theo dõi</div>
  <a href="<?= $prefix ?>/thong-ke.php" class="nav-link <?= ($activeMenu==='thong_ke')?'active':'' ?>"><i class="fas fa-chart-pie"></i> Thống kê</a>
  <a href="<?= $prefix ?>/lich-su-phan-cong.php" class="nav-link <?= ($activeMenu==='ls_phan_cong')?'active':'' ?>"><i class="fas fa-history"></i> Lịch sử phân công</a>

<?php elseif ($userRole === 'can_bo'): ?>
  <div class="sidebar-label">Tổng quan</div>
  <a href="<?= $prefix ?>/" class="nav-link <?= ($activeMenu==='dashboard')?'active':'' ?>"><i class="fas fa-home"></i> Trang chủ</a>

  <div class="sidebar-label">Phản hồi</div>
  <a href="<?= $prefix ?>/danh-sach.php" class="nav-link <?= ($activeMenu==='danh_sach')?'active':'' ?>"><i class="fas fa-inbox"></i> Phản hồi được giao</a>
  <a href="<?= $prefix ?>/lich-su.php" class="nav-link <?= ($activeMenu==='lich_su')?'active':'' ?>"><i class="fas fa-history"></i> Lịch sử xử lý</a>

<?php else: /* sinh_vien */ ?>
  <div class="sidebar-label">Phản hồi</div>
  <a href="<?= $prefix ?>/gui-phan-hoi.php" class="nav-link <?= ($activeMenu==='gui_ph')?'active':'' ?>"><i class="fas fa-paper-plane"></i> Gửi ý kiến phản hồi</a>
  <a href="<?= $prefix ?>/phan-hoi-cua-toi.php" class="nav-link <?= ($activeMenu==='lich_su')?'active':'' ?>"><i class="fas fa-clipboard-list"></i> Lịch sử & Trạng thái</a>

<?php endif; ?>

  <div class="sidebar-bottom-search">
    <div class="sb-search-wrap">
      <input type="text" placeholder="Tìm kiếm chức năng">
      <button class="sb-search-btn" type="button"><i class="fas fa-search"></i></button>
    </div>
  </div>
</div>

<div class="main-content flex-grow-1">
<script>
(function(){
  var btn=document.getElementById('sidebarToggle');
  if(btn) btn.addEventListener('click',function(){ document.body.classList.toggle('sidebar-toggled'); });
})();
</script>