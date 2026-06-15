<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$pageTitle  = 'Dashboard Quản trị';
$activeMenu = 'dashboard';
global $pdo;

// Thống kê tổng
$tongTai = $pdo->query("SELECT COUNT(*) FROM phan_hoi")->fetchColumn();
$moiNhat = $pdo->query("SELECT COUNT(*) FROM phan_hoi WHERE trang_thai IN ('cho_xu_ly','da_tiep_nhan')")->fetchColumn();
$dangXL  = $pdo->query("SELECT COUNT(*) FROM phan_hoi WHERE trang_thai = 'dang_xu_ly'")->fetchColumn();
$daXL    = $pdo->query("SELECT COUNT(*) FROM phan_hoi WHERE trang_thai = 'da_xu_ly'")->fetchColumn();
$quaHan  = $pdo->query("SELECT COUNT(*) FROM phan_hoi WHERE han_xu_ly < CURDATE() AND trang_thai NOT IN ('da_xu_ly','da_huy','tu_choi')")->fetchColumn();

// Đánh giá
$dgTB    = $pdo->query("SELECT AVG(diem_so) FROM danh_gia")->fetchColumn();
$soDG    = $pdo->query("SELECT COUNT(*) FROM danh_gia")->fetchColumn();

// 5 phản hồi mới nhất
$phanHoiMoi = $pdo->query("
    SELECT ph.*, cd.ten_chu_de, u.ho_ten as ten_nguoi_gui, dv.ten_don_vi
    FROM phan_hoi ph
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    LEFT JOIN users u ON ph.nguoi_gui_id = u.id
    LEFT JOIN don_vi dv ON ph.don_vi_xu_ly_id = dv.id
    ORDER BY ph.created_at DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-800 mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Tổng quan hệ thống</h5>
    <p class="text-muted small mb-0">Thống kê dữ liệu toàn trường</p>
  </div>
  <a href="phan-hoi.php" class="btn btn-dhv btn-sm"><i class="fas fa-list me-1"></i>Tất cả phản hồi</a>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card blue py-3">
      <div class="icon"><i class="fas fa-inbox text-primary"></i></div>
      <div><div class="label">Tổng số</div><div class="value fs-4"><?= $tongTai ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card orange py-3">
      <div class="icon"><i class="fas fa-clock"></i></div>
      <div><div class="label">Đang chờ</div><div class="value fs-4"><?= $moiNhat ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card teal py-3">
      <div class="icon"><i class="fas fa-spinner"></i></div>
      <div><div class="label">Đang xử lý</div><div class="value fs-4"><?= $dangXL ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card green py-3">
      <div class="icon"><i class="fas fa-check-circle"></i></div>
      <div><div class="label">Đã xử lý</div><div class="value fs-4"><?= $daXL ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card bg-danger text-white py-3">
      <div class="icon text-white"><i class="fas fa-exclamation-triangle"></i></div>
      <div><div class="label">Quá hạn</div><div class="value fs-4 text-white"><?= $quaHan ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card bg-warning py-3">
      <div class="icon text-dark"><i class="fas fa-star"></i></div>
      <div><div class="label text-dark" style="opacity:.8">Đánh giá TB</div><div class="value fs-4 text-dark"><?= number_format($dgTB??0,1) ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- DANH SÁCH MỚI NHẤT -->
  <div class="col-lg-8">
    <div class="card-dhv p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-700 mb-0"><i class="fas fa-comments me-2 text-primary"></i>Phản hồi mới nhất</h6>
        <a href="phan-hoi.php" class="btn btn-sm btn-outline-primary">Xem thêm</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="table-light"><tr><th>Tiêu đề / Người gửi</th><th>Chủ đề</th><th>Đơn vị</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($phanHoiMoi as $ph): ?>
            <tr>
              <td>
                <div class="fw-600"><?= e(mb_substr($ph['tieu_de'],0,50)) ?></div>
                <div class="text-muted" style="font-size:.72rem"><?= $ph['an_danh']?'Ẩn danh':e($ph['ten_nguoi_gui']??'') ?> · <?= thoiGianTuongDoi($ph['created_at']) ?></div>
              </td>
              <td><?php if ($ph['ten_chu_de']): ?><span class="badge bg-light text-dark border"><?= e($ph['ten_chu_de']) ?></span><?php endif; ?></td>
              <td class="text-muted"><?= e($ph['ten_don_vi']??'–') ?></td>
              <td><span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?>"><?= nhanTrangThai($ph['trang_thai']) ?></span></td>
              <td><a href="phan-hoi-chi-tiet.php?id=<?= $ph['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- HOẠT ĐỘNG -->
  <div class="col-lg-4">
    <div class="card-dhv p-3 h-100">
      <h6 class="fw-700 mb-3"><i class="fas fa-bolt me-2 text-warning"></i>Truy cập nhanh</h6>
      <?php if ($quaHan > 0): ?>
      <div class="alert alert-danger py-2 px-3 small d-flex align-items-center justify-content-between">
        <div><i class="fas fa-exclamation-circle me-1"></i>Có <strong><?= $quaHan ?></strong> phản hồi quá hạn</div>
        <a href="phan-hoi.php" class="btn btn-sm btn-danger px-2 py-0">Xem</a>
      </div>
      <?php endif; ?>
      <a href="bao-cao.php" class="d-flex align-items-center justify-content-between p-3 bg-light rounded text-decoration-none text-dark mb-2 border border-primary">
        <div>
          <div class="fw-600"><i class="fas fa-chart-pie me-2 text-primary"></i>Báo cáo chi tiết</div>
          <div class="text-muted small mt-1">Xem thống kê theo đơn vị, chủ đề, hiệu suất cán bộ...</div>
        </div>
        <i class="fas fa-arrow-right text-primary"></i>
      </a>
      <a href="quanlycanbo.php" class="d-flex align-items-center justify-content-between p-3 bg-light rounded text-decoration-none text-dark border">
        <div>
          <div class="fw-600"><i class="fas fa-users me-2 text-info"></i>Quản lý tài khoản</div>
          <div class="text-muted small mt-1">Quản lý các tài khoản Trưởng đơn vị, Cán bộ xử lý...</div>
        </div>
        <i class="fas fa-arrow-right text-info"></i>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
