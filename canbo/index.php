<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('can_bo');

$pageTitle  = 'Trang chủ Cán bộ';
$activeMenu = 'dashboard';
$canBoId    = $_SESSION['user_id'];
$userInfo   = layThongTinUser($canBoId);
global $pdo;

// Thống kê
$sMoi = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE can_bo_xu_ly_id=? AND trang_thai='da_tiep_nhan'"); $sMoi->execute([$canBoId]);
$sXL  = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE can_bo_xu_ly_id=? AND trang_thai='dang_xu_ly'"); $sXL->execute([$canBoId]);
$sDX  = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE can_bo_xu_ly_id=? AND trang_thai='da_xu_ly'"); $sDX->execute([$canBoId]);
$sQH  = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE can_bo_xu_ly_id=? AND han_xu_ly<CURDATE() AND trang_thai NOT IN('da_xu_ly','da_huy','tu_choi')"); $sQH->execute([$canBoId]);

// Phản hồi gần nhất được giao
$phanHoiMoi = $pdo->prepare("
    SELECT ph.*, cd.ten_chu_de, cd.icon
    FROM phan_hoi ph
    LEFT JOIN chu_de cd ON ph.chu_de_id=cd.id
    WHERE ph.can_bo_xu_ly_id=? AND ph.trang_thai NOT IN('da_xu_ly','da_huy','tu_choi')
    ORDER BY ph.created_at DESC LIMIT 5
");
$phanHoiMoi->execute([$canBoId]);
$phanHoiMoi = $phanHoiMoi->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-800 mb-0"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Xin chào, <?= e($userInfo['ho_ten']) ?>!</h5>
    <p class="text-muted small mb-0"><?= e($userInfo['ten_don_vi']??$userInfo['khoa']??'Cán bộ xử lý') ?></p>
  </div>
  <a href="danh-sach.php" class="btn btn-dhv btn-sm"><i class="fas fa-inbox me-1"></i>Xem tất cả phản hồi</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card orange">
      <div class="icon"><i class="fas fa-inbox"></i></div>
      <div><div class="label">Mới tiếp nhận</div><div class="value"><?= $sMoi->fetchColumn() ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card blue">
      <div class="icon"><i class="fas fa-spinner"></i></div>
      <div><div class="label">Đang xử lý</div><div class="value"><?= $sXL->fetchColumn() ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card green">
      <div class="icon"><i class="fas fa-check-circle"></i></div>
      <div><div class="label">Đã xử lý</div><div class="value"><?= $sDX->fetchColumn() ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card red">
      <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
      <div><div class="label">Quá hạn</div><div class="value"><?= $sQH->fetchColumn() ?></div></div>
    </div>
  </div>
</div>

<div class="card-dhv p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-700 mb-0"><i class="fas fa-list me-2 text-primary"></i>Phản hồi đang chờ xử lý</h6>
    <a href="danh-sach.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
  </div>
  <?php if (empty($phanHoiMoi)): ?>
  <div class="empty-state py-4 text-center">
    <i class="fas fa-check-double fa-2x text-success mb-2 d-block"></i>
    <p class="text-muted">Tuyệt vời! Không có phản hồi nào cần xử lý.</p>
  </div>
  <?php else: ?>
  <?php foreach ($phanHoiMoi as $ph):
    $quaHan = $ph['han_xu_ly'] && strtotime($ph['han_xu_ly']) < time();
  ?>
  <div class="feedback-card <?= $ph['muc_do_uu_tien'] ?> mb-2 <?= $quaHan?'border-danger border':'' ?>">
    <div class="d-flex justify-content-between align-items-start">
      <div class="flex-grow-1">
        <div class="d-flex gap-2 mb-1 flex-wrap">
          <span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?>"><?= nhanTrangThai($ph['trang_thai']) ?></span>
          <?php if ($ph['ten_chu_de']): ?><span class="badge bg-light text-dark border"><i class="<?= $ph['icon'] ?> me-1"></i><?= e($ph['ten_chu_de']) ?></span><?php endif; ?>
          <?php if ($quaHan): ?><span class="badge bg-danger">⚠ Quá hạn</span><?php endif; ?>
        </div>
        <div class="fw-600"><?= e($ph['tieu_de']) ?></div>
        <div class="small text-muted"><?= thoiGianTuongDoi($ph['created_at']) ?>
          <?php if ($ph['han_xu_ly']): ?> · Hạn: <span class="<?= $quaHan?'text-danger fw-bold':'' ?>"><?= date('d/m/Y', strtotime($ph['han_xu_ly'])) ?></span><?php endif; ?>
        </div>
      </div>
      <a href="xuly.php?id=<?= $ph['id'] ?>" class="btn btn-dhv btn-sm ms-2"><i class="fas fa-tasks me-1"></i>Xử lý</a>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
