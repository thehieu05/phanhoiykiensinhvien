<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraDangNhap();
if ($_SESSION['vai_tro'] !== 'sinh_vien') chuyenHuong(SITE_URL . '/index.php');

$pageTitle  = 'Lịch sử phản hồi';
$activeMenu = 'lich_su';
$userId     = $_SESSION['user_id'];
global $pdo;

$trangThai = $_GET['trang_thai'] ?? '';
$search    = trim($_GET['search'] ?? '');
$page      = max(1, intval($_GET['page'] ?? 1));

$filters = ['nguoi_gui_id' => $userId];
if ($trangThai) $filters['trang_thai'] = $trangThai;
if ($search)    $filters['search'] = $search;

$result = layDanhSachPhanHoi($filters, $page, 10);
$phanHois = $result['data'];

// Đếm theo trạng thái
$counts = [];
foreach (['cho_xu_ly','da_tiep_nhan','dang_xu_ly','cho_bo_sung','da_xu_ly','da_huy','tu_choi','cho_duyet_tl'] as $ts) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE nguoi_gui_id = ? AND trang_thai = ?");
    $s->execute([$userId, $ts]);
    $counts[$ts] = $s->fetchColumn();
}
$tongTat = array_sum($counts);

$success = flashMessage('success');
include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div>
<?php endif; ?>

<div class="page-header">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <h2><i class="fas fa-clipboard-list me-2"></i>Lịch sử & Trạng thái phản hồi</h2>
      <p class="mb-0">Theo dõi toàn bộ phản hồi bạn đã gửi</p>
    </div>
    <a href="gui-phan-hoi.php" class="btn btn-dhv"><i class="fas fa-plus me-2"></i>Gửi mới</a>
  </div>
</div>

<!-- STAT CARDS -->
<div class="row g-2 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card blue text-center py-2">
      <div class="value"><?= $tongTat ?></div>
      <div class="label">Tổng đã gửi</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card orange text-center py-2">
      <div class="value"><?= ($counts['cho_xu_ly']??0)+($counts['da_tiep_nhan']??0) ?></div>
      <div class="label">Chờ xử lý</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card teal text-center py-2">
      <div class="value"><?= ($counts['dang_xu_ly']??0)+($counts['cho_duyet_tl']??0)+($counts['cho_bo_sung']??0) ?></div>
      <div class="label">Đang xử lý</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card green text-center py-2">
      <div class="value"><?= $counts['da_xu_ly']??0 ?></div>
      <div class="label">Đã xử lý</div>
    </div>
  </div>
</div>

<!-- LỌC -->
<div class="card-dhv p-3 mb-3">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-5">
      <input type="text" name="search" class="form-control" placeholder="Tìm theo tiêu đề, mã phản hồi..." value="<?= e($search) ?>">
    </div>
    <div class="col-md-4">
      <select name="trang_thai" class="form-select">
        <option value="">-- Tất cả trạng thái --</option>
        <?php foreach (['cho_xu_ly','da_tiep_nhan','dang_xu_ly','cho_duyet_tl','cho_bo_sung','da_xu_ly','da_huy','tu_choi'] as $ts): ?>
        <option value="<?= $ts ?>" <?= $trangThai===$ts?'selected':'' ?>><?= nhanTrangThai($ts) ?> (<?= $counts[$ts]??0 ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button type="submit" class="btn btn-dhv flex-grow-1"><i class="fas fa-search me-1"></i>Lọc</button>
      <a href="phan-hoi-cua-toi.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card-dhv p-3">
  <?php if (empty($phanHois)): ?>
  <div class="empty-state py-5 text-center">
    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
    <p class="text-muted"><?= $trangThai || $search ? 'Không tìm thấy phản hồi nào.' : 'Bạn chưa gửi phản hồi nào.' ?></p>
    <?php if (!$trangThai && !$search): ?>
    <a href="gui-phan-hoi.php" class="btn btn-dhv btn-sm">Gửi phản hồi đầu tiên</a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <?php foreach ($phanHois as $ph): ?>
  <div class="feedback-card <?= $ph['muc_do_uu_tien'] ?> mb-2">
    <div class="d-flex justify-content-between align-items-start">
      <div class="flex-grow-1 me-3">
        <div class="d-flex gap-2 mb-1 flex-wrap">
          <span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?>">
            <i class="<?= iconTrangThai($ph['trang_thai']) ?> me-1"></i><?= nhanTrangThai($ph['trang_thai']) ?>
          </span>
          <?php if ($ph['ten_chu_de']): ?>
          <span class="badge bg-light text-dark border"><?= e($ph['ten_chu_de']) ?></span>
          <?php endif; ?>
          <?php if ($ph['muc_do_uu_tien'] === 'khan_cap'): ?>
          <span class="badge bg-danger">Khẩn cấp</span>
          <?php endif; ?>
        </div>
        <div class="fw-600 mb-1"><?= e($ph['tieu_de']) ?></div>
        <div class="small text-muted d-flex gap-3 flex-wrap">
          <span><i class="fas fa-hashtag me-1"></i><?= e($ph['ma_phan_hoi'] ?? '#'.$ph['id']) ?></span>
          <span><i class="fas fa-clock me-1"></i><?= thoiGianTuongDoi($ph['created_at']) ?></span>
          <?php if ($ph['ten_can_bo']): ?>
          <span><i class="fas fa-user-tie me-1"></i><?= e($ph['ten_can_bo']) ?></span>
          <?php endif; ?>
          <?php if ($ph['so_tra_loi'] > 0): ?>
          <span class="text-success"><i class="fas fa-reply me-1"></i><?= $ph['so_tra_loi'] ?> câu trả lời</span>
          <?php endif; ?>
        </div>
      </div>
      <a href="chi-tiet.php?id=<?= $ph['id'] ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-eye me-1"></i>Xem
      </a>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="mt-3">
    <?= phanTrang($result['pages'], $result['page'], '?'.http_build_query(array_filter(['trang_thai'=>$trangThai,'search'=>$search]))) ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
