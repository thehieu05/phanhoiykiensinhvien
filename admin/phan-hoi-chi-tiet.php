<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$id = intval($_GET['id'] ?? 0);
global $pdo;

$stmt = $pdo->prepare("
    SELECT ph.*, cd.ten_chu_de, cd.icon,
           u.ho_ten as ten_nguoi_gui, u.email as email_nguoi_gui, u.ma_sv_gv, u.lop
    FROM phan_hoi ph
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    LEFT JOIN users u ON ph.nguoi_gui_id = u.id
    WHERE ph.id = ?
");
$stmt->execute([$id]);
$ph = $stmt->fetch();
if (!$ph) { chuyenHuong(SITE_URL . '/admin/phan-hoi.php'); }

// Cập nhật lượt xem
$pdo->prepare("UPDATE phan_hoi SET luot_xem = luot_xem + 1 WHERE id = ?")->execute([$id]);


// Lấy danh sách trả lời
$traLois = $pdo->prepare("
    SELECT tl.*, u.ho_ten, u.vai_tro
    FROM tra_loi tl JOIN users u ON tl.nguoi_tra_loi_id = u.id
    WHERE tl.phan_hoi_id = ?
    ORDER BY tl.created_at ASC
");
$traLois->execute([$id]);
$traLois = $traLois->fetchAll();

$pageTitle  = 'Chi tiết phản hồi #' . $id;
$activeMenu = 'phan_hoi';
include __DIR__ . '/../includes/header.php';

$flash = flashMessage('success');
?>

<?php if ($flash): ?>
  <div class="alert alert-success alert-dhv mb-3"><i class="fas fa-check-circle me-2"></i><?= loiXhtmlEntities($flash) ?></div>
<?php endif; ?>

<div class="d-flex align-items-center gap-2 mb-3">
  <a href="phan-hoi.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Quay lại</a>
  <span class="text-muted small">/ Phản hồi #<?= $id ?></span>
</div>

<div class="row g-3">
  <!-- CHI TIẾT -->
  <div class="col-lg-8">
    <div class="card-dhv p-4 mb-3">
      <div class="d-flex gap-2 flex-wrap mb-3">
        <span class="badge-trang-thai badge-<?= $ph['trang_thai'] ?>"><?= nhanTrangThai($ph['trang_thai']) ?></span>
        <span class="badge bg-light text-dark"><i class="<?= $ph['icon'] ?> me-1"></i><?= loiXhtmlEntities($ph['ten_chu_de'] ?? 'N/A') ?></span>
        <span class="badge bg-secondary">Mức độ: <?= nhanMucDo($ph['muc_do_uu_tien']) ?></span>
        <?php if ($ph['an_danh']): ?>
          <span class="badge bg-dark"><i class="fas fa-user-secret me-1"></i>Ẩn danh</span>
        <?php endif; ?>
      </div>
      <h4 class="fw-800 mb-3 text-primary"><?= loiXhtmlEntities($ph['tieu_de']) ?></h4>
      <div class="feedback-content-box p-4 mb-4" style="background: #f8fafc; border-left: 4px solid var(--primary); border-radius: 4px 12px 12px 4px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02); font-size: 1.05rem; color: #334155; line-height: 1.8; position: relative;">
        <div class="quote-icon" style="position: absolute; right: 20px; top: 15px; font-size: 2.5rem; color: rgba(0, 48, 135, 0.05); pointer-events: none;"><i class="fas fa-quote-right"></i></div>
        <div style="white-space: pre-wrap; font-weight: 500;"><?= trim(loiXhtmlEntities($ph['noi_dung'])) ?></div>
      </div>
      <div class="d-flex gap-4 small text-muted flex-wrap">
        <span><i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($ph['created_at'])) ?></span>
        <span><i class="fas fa-eye me-1"></i><?= $ph['luot_xem'] ?> lượt xem</span>
        <span><i class="fas fa-reply me-1"></i><?= count($traLois) ?> trả lời</span>
      </div>
    </div>

    <!-- TRẢ LỜI -->
    <div class="card-dhv p-4 mb-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-comments me-2 text-primary"></i>Trao đổi (<?= count($traLois) ?>)</h6>
      <?php if (empty($traLois)): ?>
        <div class="empty-state py-3"><i class="fas fa-comment-slash d-block mb-2"></i>Chưa có trả lời nào</div>
      <?php else: ?>
        <?php foreach ($traLois as $tl): ?>
          <div class="reply-box <?= in_array($tl['vai_tro'], ['admin','giang_vien']) ? 'admin-reply' : '' ?> mb-2">
            <div class="d-flex align-items-center gap-2 mb-1">
              <div class="avatar-circle" style="width:28px;height:28px;font-size:.7rem">
                <?= mb_strtoupper(mb_substr($tl['ho_ten'], 0, 1)) ?>
              </div>
              <strong class="small"><?= loiXhtmlEntities($tl['ho_ten']) ?></strong>
              <?php if (in_array($tl['vai_tro'], ['admin','giang_vien'])): ?>
                <span class="badge bg-primary" style="font-size:.65rem">BQL</span>
              <?php endif; ?>
              <span class="reply-meta ms-auto"><?= thoiGianTuongDoi($tl['created_at']) ?></span>
            </div>
            <div class="small" style="white-space:pre-wrap"><?= loiXhtmlEntities($tl['noi_dung']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>

  <!-- SIDEBAR ACTIONS -->
  <div class="col-lg-4">
    <!-- Thông tin người gửi -->
    <div class="card-dhv p-3 mb-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-user me-2 text-primary"></i>Người gửi</h6>
      <?php if ($ph['an_danh']): ?>
        <div class="text-center py-2">
          <i class="fas fa-user-secret fa-2x text-muted mb-2 d-block"></i>
          <span class="text-muted">Phản hồi ẩn danh</span>
        </div>
      <?php else: ?>
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="avatar-circle"><?= mb_strtoupper(mb_substr($ph['ten_nguoi_gui'] ?? '?', 0, 1)) ?></div>
          <div>
            <div class="fw-600 small"><?= loiXhtmlEntities($ph['ten_nguoi_gui'] ?? 'N/A') ?></div>
            <div class="text-muted" style="font-size:.75rem"><?= loiXhtmlEntities($ph['email_nguoi_gui'] ?? '') ?></div>
          </div>
        </div>
        <div class="small text-muted">
          <div><i class="fas fa-id-badge me-2"></i>MSSV: <?= loiXhtmlEntities($ph['ma_sv_gv'] ?? 'N/A') ?></div>
          <div><i class="fas fa-users me-2"></i>Lớp: <?= loiXhtmlEntities($ph['lop'] ?? 'N/A') ?></div>
        </div>
      <?php endif; ?>
    </div>


    <!-- Thông tin chi tiết -->
    <div class="card-dhv p-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Thông tin</h6>
      <div class="small">
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">ID phản hồi</span><strong>#<?= $ph['id'] ?></strong>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Ngày gửi</span>
          <strong><?= date('d/m/Y', strtotime($ph['created_at'])) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Cập nhật lần cuối</span>
          <strong><?= date('d/m/Y', strtotime($ph['updated_at'])) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Mức độ ưu tiên</span>
          <strong><?= nhanMucDo($ph['muc_do_uu_tien']) ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
