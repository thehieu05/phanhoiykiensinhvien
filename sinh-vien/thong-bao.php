<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraDangNhap();
$userId = $_SESSION['user_id'];
$role   = $_SESSION['vai_tro'];
global $pdo;

// Đánh dấu đã đọc
if (isset($_GET['mark'])) {
    danhDauDaDoc(intval($_GET['mark']), $userId);
    // Redirect về trang liên quan
    $tb = $pdo->prepare("SELECT * FROM thong_bao WHERE id=?"); $tb->execute([intval($_GET['mark'])]); $tb=$tb->fetch();
    if ($tb && $tb['phan_hoi_id']) {
        $prefix = match($role) { 'admin'=>SITE_URL.'/admin','truong_don_vi'=>SITE_URL.'/truongdonvi','can_bo'=>SITE_URL.'/canbo',default=>SITE_URL.'/sinh-vien' };
        $page = match($role) { 'can_bo'=>'xuly.php','truong_don_vi'=>'duyetphanhoi.php','admin'=>'phan-hoi-chi-tiet.php',default=>'chi-tiet.php' };
        chuyenHuong("$prefix/$page?id={$tb['phan_hoi_id']}");
    }
}

// Đánh dấu tất cả đã đọc
if (isset($_GET['mark_all'])) {
    $pdo->prepare("UPDATE thong_bao SET da_doc=1 WHERE nguoi_nhan_id=?")->execute([$userId]);
    chuyenHuong('thong-bao.php');
}

$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page-1)*$perPage;

$total = $pdo->prepare("SELECT COUNT(*) FROM thong_bao WHERE nguoi_nhan_id=?"); $total->execute([$userId]); $total=$total->fetchColumn();
$stmt  = $pdo->prepare("SELECT * FROM thong_bao WHERE nguoi_nhan_id=? ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute([$userId]);
$thongBaos = $stmt->fetchAll();

$pageTitle  = 'Thông báo';
$activeMenu = '';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-center">
    <h2><i class="fas fa-bell me-2"></i>Thông báo</h2>
    <a href="?mark_all=1" class="btn btn-outline-secondary btn-sm"><i class="fas fa-check-double me-1"></i>Đánh dấu tất cả đã đọc</a>
  </div>
</div>

<div class="card-dhv p-3">
  <?php if (empty($thongBaos)): ?>
  <div class="empty-state py-5 text-center"><i class="fas fa-bell-slash fa-3x text-muted mb-3 d-block"></i><p class="text-muted">Chưa có thông báo nào.</p></div>
  <?php else: ?>
  <div class="d-flex flex-column gap-2">
    <?php foreach ($thongBaos as $tb):
      $icons = ['phan_hoi_moi'=>'fas fa-comment-alt text-primary','phan_cong'=>'fas fa-user-check text-success','tra_loi'=>'fas fa-reply text-info','duyet_tra_loi'=>'fas fa-clipboard-check text-warning','cap_nhat_trang_thai'=>'fas fa-sync text-primary','he_thong'=>'fas fa-cog text-secondary'];
      $icon = $icons[$tb['loai']] ?? 'fas fa-bell text-secondary';
    ?>
    <a href="?mark=<?= $tb['id'] ?>" class="d-flex align-items-start gap-3 p-3 rounded border text-decoration-none text-dark <?= $tb['da_doc']?'':'fw-semibold bg-light border-primary' ?>">
      <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;background:rgba(0,48,135,.08)">
        <i class="<?= $icon ?>"></i>
      </div>
      <div class="flex-grow-1">
        <div><?= e($tb['tieu_de']) ?><?php if (!$tb['da_doc']): ?><span class="badge bg-primary ms-2" style="font-size:.65rem">Mới</span><?php endif; ?></div>
        <?php if ($tb['noi_dung']): ?><div class="text-muted small mt-1"><?= e(mb_substr($tb['noi_dung'],0,120)) ?></div><?php endif; ?>
        <div class="text-muted" style="font-size:.72rem;margin-top:.25rem"><i class="fas fa-clock me-1"></i><?= thoiGianTuongDoi($tb['created_at']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="mt-3"><?= phanTrang(ceil($total/$perPage), $page, '?page') ?></div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
