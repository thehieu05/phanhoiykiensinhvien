<?php
require_once __DIR__ . '/../includes/functions.php';

kiemTraDangNhap();

if ($_SESSION['vai_tro'] !== 'sinh_vien') {
    chuyenHuong(SITE_URL . '/index.php');
}

$pageTitle = 'Trang chủ Sinh viên';
$activeMenu = 'dashboard';
$userId = $_SESSION['user_id'];

global $conn;

// Thống kê phản hồi cá nhân
$counts = [];

foreach (
    ['cho_xu_ly','da_tiep_nhan','dang_xu_ly','cho_bo_sung',
     'da_xu_ly','da_huy','tu_choi','cho_duyet_tl'] as $ts
) {

    $stmt = $conn->prepare(
        "SELECT COUNT(*) 
         FROM phan_hoi 
         WHERE nguoi_gui_id = ? 
         AND trang_thai = ?"
    );

    $stmt->bind_param("is", $userId, $ts);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_row();

    $counts[$ts] = $row[0];
}

$tongTai = array_sum($counts);

// 5 phản hồi gần nhất
$stmt = $conn->prepare("
    SELECT ph.id,
           ph.tieu_de,
           ph.trang_thai,
           ph.created_at,
           ph.muc_do_uu_tien,
           cd.ten_chu_de,
           cd.icon
    FROM phan_hoi ph
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    WHERE ph.nguoi_gui_id = ?
    ORDER BY ph.created_at DESC
    LIMIT 5
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$phanHoiMoi = $result->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header text-center py-4" 
     style="
     background:linear-gradient(135deg, #003087, #0056d6);
     border-radius:12px;
     color:white;
     box-shadow:0 4px 12px rgba(0,0,0,0.1); ">
  <h2 class="fw-800 text-white mb-2">Hệ thống Phản hồi Ý kiến Sinh viên</h2>
  <p class="text-white mb-4">Nơi tiếp nhận và xử lý các ý kiến, thắc mắc, đề xuất của sinh viên Đại học Vinh</p>
  <a href="gui-phan-hoi.php" class="btn btn-dhv btn-lg px-5 shadow-sm" style="border-radius:50px">
    <i class="fas fa-paper-plane me-2"></i>Gửi phản hồi ngay
  </a>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4 mt-2">
  <div class="col-6 col-md-3">
    <div class="stat-card blue py-3">
      <div class="icon" style="font-size:2rem"><i class="fas fa-clipboard-list"></i></div>
      <div><div class="value fs-3"><?= $tongTai ?></div><div class="label">Tổng đã gửi</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card orange py-3">
      <div class="icon" style="font-size:2rem"><i class="fas fa-clock"></i></div>
      <div><div class="value fs-3"><?= ($counts['cho_xu_ly']??0)+($counts['da_tiep_nhan']??0) ?></div><div class="label">Chờ xử lý</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card teal py-3">
      <div class="icon" style="font-size:2rem"><i class="fas fa-spinner"></i></div>
      <div><div class="value fs-3"><?= ($counts['dang_xu_ly']??0)+($counts['cho_duyet_tl']??0)+($counts['cho_bo_sung']??0) ?></div><div class="label">Đang xử lý</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card green py-3">
      <div class="icon" style="font-size:2rem"><i class="fas fa-check-circle"></i></div>
      <div><div class="value fs-3"><?= $counts['da_xu_ly']??0 ?></div><div class="label">Đã xử lý</div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- PHẢN HỒI GẦN ĐÂY -->
  <div class="col-lg-8">
    <div class="card-dhv p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-700 mb-0"><i class="fas fa-history me-2 text-primary"></i>Phản hồi gần đây</h6>
        <a href="phan-hoi-cua-toi.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
      </div>
      <?php if (empty($phanHoiMoi)): ?>
      <div class="empty-state py-5 text-center">
        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted">Bạn chưa gửi phản hồi nào.</p>
      </div>
      <?php else: ?>
      <div class="d-flex flex-column gap-2">
        <?php foreach ($phanHoiMoi as $ph): ?>
        <a href="chi-tiet.php?id=<?= $ph['id'] ?>" class="feedback-card <?= $ph['muc_do_uu_tien'] ?> text-decoration-none text-dark d-block">
          <div class="d-flex justify-content-between align-items-center">
            <div class="flex-grow-1 me-3">
              <div class="fw-600 mb-1"><?= e($ph['tieu_de']) ?></div>
              <div class="d-flex gap-2 flex-wrap align-items-center">
                <span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?>" style="font-size:.65rem"><?= nhanTrangThai($ph['trang_thai']) ?></span>
                <?php if ($ph['ten_chu_de']): ?>
                <span class="text-muted" style="font-size:.72rem"><i class="<?= $ph['icon'] ?> me-1"></i><?= e($ph['ten_chu_de']) ?></span>
                <?php endif; ?>
                <span class="text-muted ms-2" style="font-size:.72rem"><i class="fas fa-clock me-1"></i><?= thoiGianTuongDoi($ph['created_at']) ?></span>
              </div>
            </div>
            <i class="fas fa-chevron-right text-muted"></i>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- HƯỚNG DẪN -->
  <div class="col-lg-4">
    <div class="card-dhv p-4 h-100" style="background:#f8f9fa">
      <h6 class="fw-700 mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Quy trình xử lý</h6>
      <div class="timeline">
        <div class="d-flex gap-3 mb-3">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:24px;height:24px;flex-shrink:0;font-size:.75rem">1</div>
          <div><div class="fw-600 small">Gửi phản hồi</div><div class="text-muted" style="font-size:.72rem">Chọn chủ đề, nhập nội dung chi tiết và đính kèm minh chứng.</div></div>
        </div>
        <div class="d-flex gap-3 mb-3">
          <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center fw-bold" style="width:24px;height:24px;flex-shrink:0;font-size:.75rem">2</div>
          <div><div class="fw-600 small">Tiếp nhận & Phân công</div><div class="text-muted" style="font-size:.72rem">Hệ thống chuyển đến đơn vị chuyên trách trong 24h.</div></div>
        </div>
        <div class="d-flex gap-3 mb-3">
          <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold" style="width:24px;height:24px;flex-shrink:0;font-size:.75rem">3</div>
          <div><div class="fw-600 small">Đang xử lý</div><div class="text-muted" style="font-size:.72rem">Cán bộ chuyên môn kiểm tra và xử lý vấn đề (3-5 ngày).</div></div>
        </div>
        <div class="d-flex gap-3">
          <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold" style="width:24px;height:24px;flex-shrink:0;font-size:.75rem">4</div>
          <div><div class="fw-600 small">Đã xử lý & Đánh giá</div><div class="text-muted" style="font-size:.72rem">Nhận kết quả và đánh giá mức độ hài lòng của bạn.</div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
