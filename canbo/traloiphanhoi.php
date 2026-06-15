<?php
// =====================================================
// CÁN BỘ: Phản hồi được phân công + Soạn trả lời
// File: can-bo/phan-hoi.php  (danh sách)
//       can-bo/phan-hoi-chi-tiet.php (xử lý)
// =====================================================

// ===== DANH SÁCH: can-bo/phan-hoi.php =====
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('can_bo');

$pageTitle  = 'Phản hồi của tôi';
$activeMenu = 'phan_hoi';
global $pdo;
$canBoId = $_SESSION['user_id'];

// Bộ lọc
$filters = [
    'trang_thai' => $_GET['trang_thai'] ?? '',
    'search'     => $_GET['search'] ?? '',
];
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$whereArr = ["ph.can_bo_xu_ly_id = ?"];
$params   = [$canBoId];

if ($filters['trang_thai']) {
    $whereArr[] = "ph.trang_thai = ?";
    $params[]   = $filters['trang_thai'];
}
if ($filters['search']) {
    $whereArr[] = "(ph.tieu_de LIKE ? OR ph.noi_dung LIKE ?)";
    $params[]   = "%{$filters['search']}%";
    $params[]   = "%{$filters['search']}%";
}
$whereSQL = implode(' AND ', $whereArr);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi ph WHERE $whereSQL");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$listStmt = $pdo->prepare("
    SELECT ph.*, dm.ten_danh_muc, dm.icon,
           u.ho_ten as ten_nguoi_gui,
           tdv.ho_ten as ten_truong_don_vi,
           (SELECT COUNT(*) FROM tra_loi tl WHERE tl.phan_hoi_id = ph.id AND tl.loai != 'noi_bo') as so_tra_loi
    FROM phan_hoi ph
    LEFT JOIN danh_muc dm ON ph.danh_muc_id = dm.id
    LEFT JOIN users u ON ph.nguoi_gui_id = u.id
    LEFT JOIN users tdv ON ph.truong_don_vi_id = tdv.id
    WHERE $whereSQL
    ORDER BY
        FIELD(ph.trang_thai,'da_phan_cong','dang_xu_ly','cho_duyet_tl','da_xu_ly','tu_choi'),
        ph.muc_do_uu_tien DESC, ph.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$listStmt->execute($params);
$phanHois = $listStmt->fetchAll();

// Thống kê nhanh của cán bộ
$myStats = $pdo->prepare("
    SELECT
        SUM(trang_thai IN ('da_phan_cong','dang_xu_ly')) as dang_xu_ly,
        SUM(trang_thai = 'cho_duyet_tl') as cho_duyet_tl,
        SUM(trang_thai = 'da_xu_ly') as da_xu_ly,
        COUNT(*) as tong
    FROM phan_hoi WHERE can_bo_xu_ly_id = ?
");
$myStats->execute([$canBoId]);
$myStats = $myStats->fetch();

// Kiểm tra thông báo chưa đọc
$soTBChuaDoc = $pdo->prepare("SELECT COUNT(*) FROM thong_bao WHERE nguoi_nhan_id = ? AND da_doc = 0");
$soTBChuaDoc->execute([$canBoId]);
$soTBChuaDoc = $soTBChuaDoc->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-tasks me-2"></i>Phản hồi được phân công</h2>
  <p class="mb-0">Danh sách các phản hồi trưởng đơn vị giao cho bạn xử lý</p>
</div>

<!-- THỐNG KÊ NHANH -->
<div class="row g-2 mb-3">
  <div class="col-4">
    <div class="stat-card blue" style="cursor:pointer" onclick="location.href='?trang_thai=dang_xu_ly'">
      <div class="icon"><i class="fas fa-spinner"></i></div>
      <div><div class="label">Đang xử lý</div><div class="value"><?= $myStats['dang_xu_ly'] ?></div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-card orange" style="cursor:pointer" onclick="location.href='?trang_thai=cho_duyet_tl'">
      <div class="icon"><i class="fas fa-hourglass-half"></i></div>
      <div><div class="label">Chờ duyệt TL</div><div class="value"><?= $myStats['cho_duyet_tl'] ?></div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-card green">
      <div class="icon"><i class="fas fa-check-circle"></i></div>
      <div><div class="label">Đã xử lý</div><div class="value"><?= $myStats['da_xu_ly'] ?></div></div>
    </div>
  </div>
</div>

<!-- FILTER -->
<div class="card-dhv p-3 mb-3">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-5">
      <input type="text" name="search" class="form-control" placeholder="🔍 Tìm kiếm..."
             value="<?= loiXhtmlEntities($filters['search']) ?>">
    </div>
    <div class="col-md-4">
      <select name="trang_thai" class="form-select">
        <option value="">Tất cả</option>
        <option value="da_phan_cong"  <?= $filters['trang_thai']==='da_phan_cong'?'selected':''  ?>>📋 Đã phân công (chưa xử lý)</option>
        <option value="dang_xu_ly"   <?= $filters['trang_thai']==='dang_xu_ly'?'selected':''   ?>>🔄 Đang xử lý</option>
        <option value="cho_duyet_tl" <?= $filters['trang_thai']==='cho_duyet_tl'?'selected':'' ?>>⏳ Chờ trưởng ĐV duyệt</option>
        <option value="da_xu_ly"     <?= $filters['trang_thai']==='da_xu_ly'?'selected':''     ?>>✅ Hoàn thành</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button type="submit" class="btn btn-dhv flex-grow-1"><i class="fas fa-search me-1"></i>Lọc</button>
      <a href="phan-hoi.php" class="btn btn-outline-secondary"><i class="fas fa-redo"></i></a>
    </div>
  </form>
</div>

<div class="mb-2 small text-muted">Tìm thấy <strong><?= $total ?></strong> phản hồi</div>

<!-- DANH SÁCH -->
<?php if (empty($phanHois)): ?>
  <div class="card-dhv p-5 text-center">
    <i class="fas fa-clipboard-check fa-3x text-muted mb-3 d-block"></i>
    <p class="text-muted">Chưa có phản hồi nào được phân công cho bạn.</p>
  </div>
<?php else: ?>
  <?php foreach ($phanHois as $ph): ?>
  <div class="feedback-card <?= $ph['muc_do_uu_tien'] ?> mb-2">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <?php
          $tsMap = [
            'da_phan_cong' => ['primary','📋 Mới phân công'],
            'dang_xu_ly'   => ['info',   '🔄 Đang xử lý'],
            'cho_duyet_tl' => ['warning','⏳ Chờ duyệt TL'],
            'da_xu_ly'     => ['success','✅ Hoàn thành'],
            'tu_choi'      => ['danger', '❌ Từ chối'],
          ];
          [$tsCol, $tsLbl] = $tsMap[$ph['trang_thai']] ?? ['secondary', $ph['trang_thai']];
          ?>
          <span class="badge bg-<?= $tsCol ?>"><?= $tsLbl ?></span>
          <?php if ($ph['muc_do_uu_tien'] === 'khan_cap'): ?>
            <span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>Khẩn cấp</span>
          <?php endif; ?>
          <span class="badge bg-light text-dark"><?= loiXhtmlEntities($ph['ten_danh_muc'] ?? 'N/A') ?></span>
        </div>

        <h6 class="fw-700 mb-1">
          <a href="phan-hoi-xu-ly.php?id=<?= $ph['id'] ?>" class="text-decoration-none text-dark">
            <?= loiXhtmlEntities($ph['tieu_de']) ?>
          </a>
        </h6>
        <p class="text-muted small mb-1"><?= loiXhtmlEntities(mb_substr($ph['noi_dung'], 0, 110)) ?>...</p>
        <div class="small text-muted d-flex gap-3 flex-wrap">
          <span><i class="fas fa-clock me-1"></i><?= thoiGianTuongDoi($ph['created_at']) ?></span>
          <?php if ($ph['han_xu_ly']): ?>
            <span class="text-<?= strtotime($ph['han_xu_ly']) < time() ? 'danger fw-bold' : 'warning' ?>">
              <i class="fas fa-calendar me-1"></i>Hạn: <?= date('d/m/Y', strtotime($ph['han_xu_ly'])) ?>
            </span>
          <?php endif; ?>
          <span><i class="fas fa-reply me-1"></i><?= $ph['so_tra_loi'] ?> trả lời</span>
        </div>
      </div>
      <a href="phan-hoi-xu-ly.php?id=<?= $ph['id'] ?>" class="btn btn-dhv btn-sm">
        <i class="fas fa-<?= in_array($ph['trang_thai'], ['da_phan_cong']) ? 'play' : 'eye' ?> me-1"></i>
        <?= $ph['trang_thai'] === 'da_phan_cong' ? 'Bắt đầu' : 'Xử lý' ?>
      </a>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>&trang_thai=<?= urlencode($filters['trang_thai']) ?>&search=<?= urlencode($filters['search']) ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
