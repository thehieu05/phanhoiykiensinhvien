<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('can_bo');

$pageTitle  = 'Phản hồi được giao';
$activeMenu = 'danh_sach';
$canBoId    = $_SESSION['user_id'];
global $pdo;

$trangThai = $_GET['trang_thai'] ?? '';
$search    = trim($_GET['search'] ?? '');
$page      = max(1, intval($_GET['page'] ?? 1));

$filters = ['can_bo_id' => $canBoId];
if ($trangThai) $filters['trang_thai'] = $trangThai;
if ($search)    $filters['search'] = $search;

$result   = layDanhSachPhanHoi($filters, $page, 15);
$phanHois = $result['data'];

// Đếm theo luồng trạng thái mới
$counts = [];
foreach (['da_phan_cong','dang_xu_ly','cho_bo_sung','da_xu_ly'] as $ts) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE can_bo_xu_ly_id = ? AND trang_thai = ?");
    $s->execute([$canBoId, $ts]);
    $counts[$ts] = $s->fetchColumn();
}
$sQuaHan = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE can_bo_xu_ly_id = ? AND han_xu_ly < CURDATE() AND trang_thai NOT IN ('da_xu_ly','da_huy','tu_choi')");
$sQuaHan->execute([$canBoId]);
$counts['qua_han'] = $sQuaHan->fetchColumn();

$success = flashMessage('success');
$error   = flashMessage('error');
include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle me-2"></i><?= e($success) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
  <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-header">
  <h2><i class="fas fa-inbox me-2"></i>Phản hồi được giao</h2>
  <p class="mb-0">Danh sách phản hồi được Trưởng đơn vị phân công cho bạn</p>
</div>

<!-- STAT CARDS -->
<div class="row g-2 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card orange text-center">
      <div class="value"><?= $counts['da_phan_cong']??0 ?></div>
      <div class="label">Chờ tiếp nhận</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card blue text-center">
      <div class="value"><?= $counts['dang_xu_ly']??0 ?></div>
      <div class="label">Đang xử lý</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card green text-center">
      <div class="value"><?= $counts['da_xu_ly']??0 ?></div>
      <div class="label">Đã xử lý</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card red text-center">
      <div class="value"><?= $counts['qua_han']??0 ?></div>
      <div class="label">Quá hạn</div>
    </div>
  </div>
</div>

<!-- LỌC -->
<div class="card-dhv p-3 mb-3">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-5">
      <input type="text" name="search" class="form-control" placeholder="Tìm tiêu đề, mã phản hồi..." value="<?= e($search) ?>">
    </div>
    <div class="col-md-4">
      <select name="trang_thai" class="form-select">
        <option value="">-- Tất cả trạng thái --</option>
        <?php foreach (['da_tiep_nhan'=>'Đã tiếp nhận','dang_xu_ly'=>'Đang xử lý','cho_duyet_tl'=>'Chờ duyệt','cho_bo_sung'=>'Chờ bổ sung từ SV','da_xu_ly'=>'Đã xử lý'] as $ts=>$lb): ?>
        <option value="<?= $ts ?>" <?= $trangThai===$ts?'selected':'' ?>><?= $lb ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button type="submit" class="btn btn-dhv flex-grow-1"><i class="fas fa-search me-1"></i>Lọc</button>
      <a href="danh-sach.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card-dhv p-3">
  <?php if (empty($phanHois)): ?>
  <div class="empty-state py-5 text-center">
    <i class="fas fa-check-double fa-3x text-success mb-3 d-block"></i>
    <p class="text-muted">Không có phản hồi nào cần xử lý.</p>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light">
        <tr>
          <th>Mã</th>
          <th>Tiêu đề / Sinh viên</th>
          <th>Chủ đề</th>
          <th>Mức độ</th>
          <th>Trạng thái</th>
          <th>Hạn xử lý</th>
          <th class="text-center" style="min-width:180px">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($phanHois as $ph):
          $quaHan         = $ph['han_xu_ly'] && strtotime($ph['han_xu_ly']) < time() && !in_array($ph['trang_thai'],['da_xu_ly','da_huy','tu_choi']);
          // Chỉ hiện Tiếp nhận + Từ chối khi phản hồi chưa được tiếp nhận
          $chuaTiepNhan   = in_array($ph['trang_thai'], ['da_phan_cong', 'cho_xu_ly']);
        ?>
        <tr class="<?= $quaHan ? 'table-danger' : '' ?>">
          <td class="text-muted fw-600"><?= e($ph['ma_phan_hoi'] ?? '#'.$ph['id']) ?></td>
          <td>
            <div class="fw-600" style="max-width:220px"><?= e(mb_substr($ph['tieu_de'],0,60)) ?></div>
            <div class="text-muted"><?= $ph['an_danh'] ? '<i>Ẩn danh</i>' : e($ph['ten_nguoi_gui']??'') ?></div>
          </td>
          <td><?php if ($ph['ten_chu_de']): ?><span class="badge bg-light text-dark border"><i class="<?= $ph['icon'] ?> me-1"></i><?= e($ph['ten_chu_de']) ?></span><?php endif; ?></td>
          <td><span class="badge bg-<?= mauMucDo($ph['muc_do_uu_tien']) ?>"><?= nhanMucDo($ph['muc_do_uu_tien']) ?></span></td>
          <td><span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?>"><?= nhanTrangThai($ph['trang_thai']) ?></span></td>
          <td class="<?= $quaHan ? 'text-danger fw-bold' : 'text-muted' ?>">
            <?= $ph['han_xu_ly'] ? date('d/m/Y', strtotime($ph['han_xu_ly'])) : '–' ?>
            <?php if ($quaHan): ?><br><span class="badge bg-danger">Quá hạn</span><?php endif; ?>
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center flex-wrap">

              <?php if ($chuaTiepNhan): ?>
              <!-- Nút Tiếp nhận -->
              <form method="POST" action="tiep-nhan.php" class="d-inline"
                    onsubmit="return confirm('Tiếp nhận phản hồi này và bắt đầu xử lý?')">
                <input type="hidden" name="id" value="<?= $ph['id'] ?>">
                <input type="hidden" name="action" value="tiep_nhan">
                <button type="submit" class="btn btn-sm btn-success" title="Tiếp nhận & bắt đầu xử lý">
                  <i class="fas fa-check me-1"></i>Tiếp nhận
                </button>
              </form>

              <!-- Nút Hủy (mở modal) -->
              <button type="button" class="btn btn-sm btn-danger"
                      title="Hủy nhận phân công từ Trưởng đơn vị"
                      onclick="moModalTuChoi(<?= $ph['id'] ?>, '<?= e(addslashes($ph['tieu_de'])) ?>')">
                <i class="fas fa-times me-1"></i>Hủy
              </button>
              <?php endif; ?>

              <!-- Nút Xem (luôn hiện) -->
              <a href="xuly.php?id=<?= $ph['id'] ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết & xử lý">
                <i class="fas fa-eye me-1"></i>Xem
              </a>

            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-3">
    <?= phanTrang($result['pages'], $result['page'], '?'.http_build_query(array_filter(['trang_thai'=>$trangThai,'search'=>$search]))) ?>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL HỦY NHẬN PHÂN CÔNG -->
<div class="modal fade" id="modalTuChoi" tabindex="-1" aria-labelledby="modalTuChoiLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-700" id="modalTuChoiLabel">
          <i class="fas fa-times-circle text-danger me-2"></i>Hủy nhận phân công
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="tiep-nhan.php">
        <div class="modal-body">
          <input type="hidden" name="id" id="tuChoiId">
          <input type="hidden" name="action" value="tu_choi">
          <div class="alert alert-warning py-2 small mb-3">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Phản hồi: <strong id="tuChoiTieuDe"></strong>
          </div>
          <div>
            <label class="form-label fw-600">Lý do hủy nhận phân công <span class="text-danger">*</span></label>
            <textarea name="ly_do" class="form-control" rows="4"
                      placeholder="Nhập lý do để hủy nhận và trả lại phản hồi này cho Trưởng đơn vị..."
                      required></textarea>
            <div class="form-text mt-1">
              <i class="fas fa-info-circle me-1 text-primary"></i>
              Phản hồi sẽ được gửi trả lại Trưởng đơn vị để phân công cho cán bộ khác.
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fas fa-arrow-left me-1"></i>Huỷ bỏ
          </button>
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-times me-1"></i>Xác nhận hủy
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function moModalTuChoi(id, tieuDe) {
  document.getElementById('tuChoiId').value = id;
  document.getElementById('tuChoiTieuDe').textContent = tieuDe;
  document.querySelector('#modalTuChoi textarea[name="ly_do"]').value = '';
  new bootstrap.Modal(document.getElementById('modalTuChoi')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
