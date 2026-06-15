<?php
// Giảng viên cũng có thể xem và trả lời phản hồi
require_once __DIR__ . '/../includes/functions.php';
kiemTraDangNhap();
if (!in_array($_SESSION['vai_tro'], ['admin', 'giang_vien', 'can_bo'])) chuyenHuong(SITE_URL . '/index.php');

$id = intval($_GET['id'] ?? 0);
global $pdo;

$stmt = $pdo->prepare("
    SELECT ph.*, dm.ten_danh_muc, dm.icon,
           u.ho_ten as ten_nguoi_gui, u.email as email_nguoi_gui, u.ma_sv_gv, u.lop
    FROM phan_hoi ph
    LEFT JOIN danh_muc dm ON ph.danh_muc_id = dm.id
    LEFT JOIN users u ON ph.nguoi_gui_id = u.id
    WHERE ph.id = ?
");
$stmt->execute([$id]);
$ph = $stmt->fetch();
if (!$ph) chuyenHuong(SITE_URL . '/canbo/phan-hoi.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gui_tra_loi'])) {
    $noiDung = trim($_POST['noi_dung_tra_loi'] ?? '');
    if (!empty($noiDung)) {
        traLoi($id, $_SESSION['user_id'], $noiDung);
        flashMessage('success', 'Đã gửi trả lời.');
        chuyenHuong("phan-hoi-chi-tiet.php?id=$id");
    }
}

$traLois = $pdo->prepare("
    SELECT tl.*, u.ho_ten, u.vai_tro FROM tra_loi tl
    JOIN users u ON tl.nguoi_tra_loi_id = u.id
    WHERE tl.phan_hoi_id = ? ORDER BY tl.created_at ASC
");
$traLois->execute([$id]);
$traLois = $traLois->fetchAll();

$pageTitle  = 'Chi tiết phản hồi';
$activeMenu = 'phan_hoi';
include __DIR__ . '/../includes/header.php';
$flash = flashMessage('success');
?>
<?php if ($flash): ?>
  <div class="alert alert-success alert-dhv"><i class="fas fa-check-circle me-2"></i><?= loiXhtmlEntities($flash) ?></div>
<?php endif; ?>

<a href="phan-hoi.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="fas fa-arrow-left me-1"></i>Quay lại</a>

<div class="card-dhv p-4 mb-3">
  <div class="d-flex gap-2 flex-wrap mb-3">
    <span class="badge-trang-thai badge-<?= $ph['trang_thai'] ?>"><?= nhanTrangThai($ph['trang_thai']) ?></span>
    <span class="badge bg-light text-dark"><i class="<?= $ph['icon'] ?> me-1"></i><?= loiXhtmlEntities($ph['ten_danh_muc'] ?? '') ?></span>
    <?php if ($ph['an_danh']): ?><span class="badge bg-dark"><i class="fas fa-user-secret me-1"></i>Ẩn danh</span><?php endif; ?>
  </div>
  <h4 class="fw-800 mb-3 text-primary"><?= loiXhtmlEntities($ph['tieu_de']) ?></h4>
  <div class="feedback-content-box p-4 mb-4" style="background: #f8fafc; border-left: 4px solid var(--primary); border-radius: 4px 12px 12px 4px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02); font-size: 1.05rem; color: #334155; line-height: 1.8; position: relative;">
    <div class="quote-icon" style="position: absolute; right: 20px; top: 15px; font-size: 2.5rem; color: rgba(0, 48, 135, 0.05); pointer-events: none;"><i class="fas fa-quote-right"></i></div>
    <div style="white-space: pre-wrap; font-weight: 500;"><?= trim(loiXhtmlEntities($ph['noi_dung'])) ?></div>
  </div>
  <div class="small text-muted"><i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($ph['created_at'])) ?></div>
</div>

<div class="card-dhv p-4">
  <h6 class="fw-700 mb-3"><i class="fas fa-comments me-2 text-primary"></i>Trao đổi (<?= count($traLois) ?>)</h6>
  <?php foreach ($traLois as $tl): ?>
    <div class="reply-box <?= in_array($tl['vai_tro'],['admin','giang_vien'])?'admin-reply':'' ?> mb-2">
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="avatar-circle" style="width:28px;height:28px;font-size:.7rem"><?= mb_strtoupper(mb_substr($tl['ho_ten'],0,1)) ?></div>
        <strong class="small"><?= loiXhtmlEntities($tl['ho_ten']) ?></strong>
        <?php if (in_array($tl['vai_tro'],['admin','giang_vien'])): ?>
          <span class="badge bg-primary" style="font-size:.65rem">BQL</span>
        <?php endif; ?>
        <span class="reply-meta ms-auto"><?= thoiGianTuongDoi($tl['created_at']) ?></span>
      </div>
      <div class="small" style="white-space:pre-wrap"><?= loiXhtmlEntities($tl['noi_dung']) ?></div>
    </div>
  <?php endforeach; ?>

  <form method="POST" class="mt-3">
    <textarea name="noi_dung_tra_loi" class="form-control mb-2" rows="3" placeholder="Nhập trả lời..." required></textarea>
    <button type="submit" name="gui_tra_loi" class="btn btn-dhv btn-sm"><i class="fas fa-paper-plane me-1"></i>Gửi</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
