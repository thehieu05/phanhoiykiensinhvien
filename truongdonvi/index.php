<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('truong_don_vi');

$pageTitle  = 'Trang chủ Trưởng đơn vị';
$activeMenu = 'dashboard';
$tdvId      = $_SESSION['user_id'];
$userInfo   = layThongTinUser($tdvId);
$donViId    = $userInfo['don_vi_id'];
global $pdo;

// Thống kê
$tongTai = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE don_vi_xu_ly_id = ?"); $tongTai->execute([$donViId]); $tongTai = $tongTai->fetchColumn();
$choXL   = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE don_vi_xu_ly_id = ? AND trang_thai = 'cho_xu_ly'"); $choXL->execute([$donViId]); $choXL = $choXL->fetchColumn();
$dangXL  = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE don_vi_xu_ly_id = ? AND trang_thai IN ('da_tiep_nhan','dang_xu_ly')"); $dangXL->execute([$donViId]); $dangXL = $dangXL->fetchColumn();
$daXL    = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE don_vi_xu_ly_id = ? AND trang_thai = 'da_xu_ly'"); $daXL->execute([$donViId]); $daXL = $daXL->fetchColumn();

// Đang chờ duyệt trả lời
$choDuyetTL = $pdo->prepare("SELECT COUNT(DISTINCT ph.id) FROM phan_hoi ph JOIN tra_loi tl ON tl.phan_hoi_id=ph.id WHERE ph.don_vi_xu_ly_id=? AND tl.loai='noi_bo' AND tl.trang_thai_duyet='cho_duyet' AND ph.trang_thai NOT IN ('da_huy','tu_choi')"); $choDuyetTL->execute([$donViId]); $choDuyetTL = $choDuyetTL->fetchColumn();

// Cần phân công (cho_xu_ly)
$canPhanCong = $pdo->prepare("
    SELECT ph.*, cd.ten_chu_de
    FROM phan_hoi ph
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    WHERE ph.don_vi_xu_ly_id = ? AND ph.trang_thai = 'cho_xu_ly'
    ORDER BY ph.created_at ASC LIMIT 5
");
$canPhanCong->execute([$donViId]); $canPhanCong = $canPhanCong->fetchAll();

// Cán bộ trong đơn vị
$canBos = $pdo->prepare("SELECT u.id, u.ho_ten, COUNT(ph.id) as dang_xu_ly FROM users u LEFT JOIN phan_hoi ph ON ph.can_bo_xu_ly_id = u.id AND ph.trang_thai NOT IN ('da_xu_ly','da_huy','tu_choi') WHERE u.vai_tro='can_bo' AND u.don_vi_id=? GROUP BY u.id ORDER BY u.ho_ten");
$canBos->execute([$donViId]); $canBos = $canBos->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-800 mb-0"><i class="fas fa-building me-2 text-primary"></i>Trưởng đơn vị: <?= e($userInfo['ten_don_vi']??$userInfo['khoa']??'') ?></h5>
    <p class="text-muted small mb-0">Xin chào, <?= e($userInfo['ho_ten']) ?></p>
  </div>
  <a href="thong-ke.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-chart-pie me-1"></i>Xem thống kê chi tiết</a>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card py-3 border border-warning" style="background:#fffcf2">
      <div class="icon text-warning"><i class="fas fa-user-check"></i></div>
      <div><div class="label text-dark fw-bold">Cần phân công</div><div class="value fs-3 text-warning"><?= $choXL ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card py-3 border border-info" style="background:#f0f9ff">
      <div class="icon text-info"><i class="fas fa-clipboard-check"></i></div>
      <div><div class="label text-dark fw-bold">Chờ duyệt trả lời</div><div class="value fs-3 text-info"><?= $choDuyetTL ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card teal py-3">
      <div class="icon"><i class="fas fa-spinner"></i></div>
      <div><div class="label">Đang xử lý</div><div class="value fs-3"><?= $dangXL ?></div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card green py-3">
      <div class="icon"><i class="fas fa-check-circle"></i></div>
      <div><div class="label">Đã xử lý xong</div><div class="value fs-3"><?= $daXL ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- CẦN PHÂN CÔNG -->
  <div class="col-lg-8">
    <div class="card-dhv p-3 h-100 border-top border-4 border-warning">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-700 mb-0"><i class="fas fa-exclamation-circle me-2 text-warning"></i>Phản hồi cần phân công xử lý</h6>
        <a href="phancong.php" class="btn btn-sm btn-outline-warning">Tất cả</a>
      </div>
      <?php if (empty($canPhanCong)): ?>
      <div class="empty-state py-4 text-center">
        <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
        <p class="text-muted small">Tất cả phản hồi đã được phân công.</p>
      </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <tbody>
            <?php foreach ($canPhanCong as $ph): ?>
            <tr>
              <td>
                <div class="fw-600"><?= e(mb_substr($ph['tieu_de'],0,60)) ?></div>
                <div class="text-muted mt-1" style="font-size:.72rem">
                  <span class="badge bg-light text-dark border"><?= e($ph['ten_chu_de']) ?></span> · <?= thoiGianTuongDoi($ph['created_at']) ?>
                </div>
              </td>
              <td><span class="badge bg-<?= mauMucDo($ph['muc_do_uu_tien']) ?>"><?= nhanMucDo($ph['muc_do_uu_tien']) ?></span></td>
              <td class="text-end">
                <div class="d-flex justify-content-end gap-1">
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="xemChiTietPhanHoi(<?= $ph['id'] ?>)" title="Xem chi tiết">
                    <i class="fas fa-eye"></i>
                  </button>
                  <a href="phancong.php?id=<?= $ph['id'] ?>" class="btn btn-sm btn-warning">Phân công</a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- CÁN BỘ -->
  <div class="col-lg-4">
    <div class="card-dhv p-3 h-100">
      <h6 class="fw-700 mb-3"><i class="fas fa-users me-2 text-primary"></i>Cán bộ trong đơn vị</h6>
      <?php if (empty($canBos)): ?>
      <div class="text-muted small">Chưa có cán bộ nào.</div>
      <?php else: ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($canBos as $cb):
          $color = $cb['dang_xu_ly']==0?'success':($cb['dang_xu_ly']<=3?'warning':'danger');
        ?>
        <div class="d-flex align-items-center gap-2">
          <div class="avatar-circle flex-shrink-0" style="width:36px;height:36px;font-size:.8rem"><?= mb_strtoupper(mb_substr($cb['ho_ten'],0,1,'UTF-8')) ?></div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between small">
              <strong class="text-dark"><?= e($cb['ho_ten']) ?></strong>
              <span class="badge bg-<?= $color ?> rounded-pill"><?= $cb['dang_xu_ly'] ?> việc</span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
