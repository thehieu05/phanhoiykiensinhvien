<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('truong_don_vi');

$pageTitle  = 'Phân công xử lý';
$activeMenu = 'phan_cong';
$tdvId      = $_SESSION['user_id'];
$userInfo   = layThongTinUser($tdvId);
$donViId    = $userInfo['don_vi_id'];
global $pdo;

$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phId  = intval($_POST['phan_hoi_id'] ?? 0);
    $cbId  = intval($_POST['can_bo_id'] ?? 0);
    $gc    = trim($_POST['ghi_chu'] ?? '');
    $han   = $_POST['han_xu_ly'] ?? null;

    if (!$phId || !$cbId) { $msg='Vui lòng chọn phản hồi và cán bộ xử lý.'; $msgType='danger'; }
    else {
        // Cập nhật phan_hoi
        $pdo->prepare("UPDATE phan_hoi SET can_bo_xu_ly_id=?, truong_don_vi_id=?, don_vi_xu_ly_id=?, trang_thai='da_tiep_nhan', ghi_chu_phan_cong=?, ngay_phan_cong=NOW(), han_xu_ly=?, updated_at=NOW() WHERE id=?")
            ->execute([$cbId, $tdvId, $donViId, $gc, $han ?: null, $phId]);

        // Lịch sử phân công
        $pdo->prepare("INSERT INTO lich_su_phan_cong (phan_hoi_id, nguoi_phan_cong_id, can_bo_id, ghi_chu, han_xu_ly) VALUES (?,?,?,?,?)")
            ->execute([$phId, $tdvId, $cbId, $gc, $han ?: null]);

        $cbQuery = $pdo->prepare("SELECT ho_ten FROM users WHERE id=?");
        $cbQuery->execute([$cbId]);
        $tenCanBo = $cbQuery->fetchColumn() ?: '';
        ghiLichSu($phId, 'cho_xu_ly', 'da_tiep_nhan', $tdvId, 'Phân công cho cán bộ: ' . $tenCanBo);

        // Thông báo cán bộ
        $phInfo = $pdo->prepare("SELECT tieu_de FROM phan_hoi WHERE id=?"); $phInfo->execute([$phId]); $phInfo=$phInfo->fetch();
        themThongBao($cbId, 'Phản hồi mới được phân công', "Bạn được phân công xử lý: \"{$phInfo['tieu_de']}\". $gc", 'phan_cong', $phId);

        flashMessage('success', 'Đã phân công thành công!');
        chuyenHuong('phancong.php');
    }
}

$success = flashMessage('success');

// Lọc
$trangThai = $_GET['trang_thai'] ?? 'cho_xu_ly';
$search    = trim($_GET['search'] ?? '');
$page      = max(1, intval($_GET['page'] ?? 1));

$filters = ['don_vi_id' => $donViId];
if ($trangThai) $filters['trang_thai'] = $trangThai;
if ($search) $filters['search'] = $search;

$result   = layDanhSachPhanHoi($filters, $page, 15);
$phanHois = $result['data'];

// Cán bộ trong đơn vị (hoặc tất cả nếu không có donViId)
$where = $donViId ? "WHERE u.vai_tro='can_bo' AND u.don_vi_id=$donViId AND u.trang_thai=1" : "WHERE u.vai_tro='can_bo' AND u.trang_thai=1";
$canBos = $pdo->query("SELECT u.*, COUNT(ph.id) as dang_xu_ly FROM users u LEFT JOIN phan_hoi ph ON ph.can_bo_xu_ly_id=u.id AND ph.trang_thai NOT IN ('da_xu_ly','da_huy','tu_choi') $where GROUP BY u.id ORDER BY u.ho_ten")->fetchAll();

// Chọn 1 phản hồi cụ thể
$selectedPH = intval($_GET['id'] ?? 0);
$phDetail   = null;
if ($selectedPH) {
    $s = $pdo->prepare("SELECT * FROM phan_hoi WHERE id = ?");
    $s->execute([$selectedPH]);
    $phDetail = $s->fetch();
    if ($phDetail && ($phDetail['don_vi_xu_ly_id'] != $donViId || in_array($phDetail['trang_thai'], ['da_huy', 'da_xu_ly', 'tu_choi']))) {
        $phDetail = null;
        if (!headers_sent()) {
            chuyenHuong('phancong.php');
        } else {
            echo '<script>window.location.href="phancong.php";</script>';
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-exclamation-circle me-2"></i><?= e($msg) ?></div><?php endif; ?>

<div class="page-header">
  <h2><i class="fas fa-user-check me-2"></i>Phân công xử lý</h2>
  <p class="mb-0">Phân công cán bộ xử lý phản hồi của sinh viên</p>
</div>

<div class="row g-3">
  <!-- DANH SÁCH PHẢN HỒI -->
  <div class="col-lg-7">
    <div class="card-dhv p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-700 mb-0"><i class="fas fa-list me-2 text-primary"></i>Phản hồi cần phân công</h6>
      </div>

      <!-- Tab lọc -->
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php foreach (['cho_xu_ly'=>'Chờ xử lý','da_tiep_nhan'=>'Đã tiếp nhận','dang_xu_ly'=>'Đang xử lý','da_huy'=>'Đã hủy'] as $ts=>$lb): ?>
        <a href="?trang_thai=<?= $ts ?>" class="btn btn-sm <?= $trangThai===$ts?'btn-dhv':'btn-outline-secondary' ?>"><?= $lb ?></a>
        <?php endforeach; ?>
      </div>

      <form method="GET" class="mb-3">
        <input type="hidden" name="trang_thai" value="<?= e($trangThai) ?>">
        <div class="input-group">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm tiêu đề, mã..." value="<?= e($search) ?>">
          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
        </div>
      </form>

      <?php if (empty($phanHois)): ?>
      <div class="empty-state py-4 text-center"><i class="fas fa-check-double fa-2x text-success mb-2 d-block"></i><p class="text-muted small">Không có phản hồi nào.</p></div>
      <?php else: ?>
      <div class="d-flex flex-column gap-2">
        <?php foreach ($phanHois as $ph): ?>
        <div class="p-3 border rounded <?= $selectedPH==$ph['id']?'border-primary bg-light':'' ?>" style="cursor:pointer" onclick="chonPH(<?= $ph['id'] ?>, '<?= addslashes(e($ph['tieu_de'])) ?>')">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <div class="fw-600 small"><?= e(mb_substr($ph['tieu_de'],0,70)) ?></div>
              <div class="d-flex gap-2 mt-1 flex-wrap">
                <span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?>" style="font-size:.65rem"><?= nhanTrangThai($ph['trang_thai']) ?></span>
                <?php if ($ph['ten_chu_de']): ?><span class="badge bg-light text-dark border" style="font-size:.65rem"><?= e($ph['ten_chu_de']) ?></span><?php endif; ?>
                <span class="badge bg-<?= mauMucDo($ph['muc_do_uu_tien']) ?>" style="font-size:.65rem"><?= nhanMucDo($ph['muc_do_uu_tien']) ?></span>
              </div>
              <div class="text-muted mt-1" style="font-size:.72rem">
                <?= $ph['an_danh']?'Ẩn danh':e($ph['ten_nguoi_gui']??'') ?> · <?= thoiGianTuongDoi($ph['created_at']) ?>
                <?php if ($ph['ten_can_bo']): ?> · <i class="fas fa-user-tie me-1"></i><?= e($ph['ten_can_bo']) ?><?php endif; ?>
              </div>
            </div>
            <div class="d-flex flex-column gap-1 ms-2">
              <?php if (!in_array($ph['trang_thai'], ['da_huy', 'da_xu_ly', 'tu_choi'])): ?>
                <a href="?id=<?= $ph['id'] ?>&trang_thai=<?= $trangThai ?>" class="btn btn-sm btn-dhv text-nowrap">Phân công</a>
              <?php endif; ?>
              <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" onclick="event.stopPropagation(); xemChiTietPhanHoi(<?= $ph['id'] ?>)" title="Xem chi tiết" style="font-size: 0.72rem; padding: 0.2rem 0.4rem;">
                <i class="fas fa-eye"></i> Chi tiết
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3"><?= phanTrang($result['pages'], $result['page'], '?'.http_build_query(array_filter(['trang_thai'=>$trangThai,'search'=>$search]))) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- FORM PHÂN CÔNG -->
  <div class="col-lg-5">
    <?php if ($phDetail): ?>
    <div class="card-dhv p-4 mb-3" style="border-top:4px solid var(--primary)">
      <h6 class="fw-700 mb-3"><i class="fas fa-user-check me-2 text-primary"></i>Phân công xử lý</h6>
      <div class="bg-light rounded p-3 mb-3 small d-flex justify-content-between align-items-start">
        <div class="pe-2">
          <div class="fw-600 text-dark"><?= e($phDetail['tieu_de']) ?></div>
          <div class="text-muted mt-1"><?= e($phDetail['ma_phan_hoi']??'#'.$phDetail['id']) ?> · <?= date('d/m/Y', strtotime($phDetail['created_at'])) ?></div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary text-nowrap ms-2" onclick="xemChiTietPhanHoi(<?= $phDetail['id'] ?>)">
          <i class="fas fa-eye me-1"></i>Xem chi tiết
        </button>
      </div>
      <form method="POST">
        <input type="hidden" name="phan_hoi_id" value="<?= $phDetail['id'] ?>">
        <div class="mb-3">
          <label class="form-label small fw-600">Cán bộ xử lý <span class="text-danger">*</span></label>
          <?php if (empty($canBos)): ?>
          <div class="alert alert-warning small py-2">Chưa có cán bộ trong đơn vị. Hãy thêm cán bộ trước.</div>
          <?php else: ?>
          <select name="can_bo_id" class="form-select" required>
            <option value="">-- Chọn cán bộ --</option>
            <?php foreach ($canBos as $cb):
              $load = $cb['dang_xu_ly'];
              $icon = $load==0?'🟢':($load<=3?'🟡':'🔴');
            ?>
            <option value="<?= $cb['id'] ?>" <?= $phDetail['can_bo_xu_ly_id']==$cb['id']?'selected':'' ?>>
              <?= $icon ?> <?= e($cb['ho_ten']) ?> (<?= $load ?> đang xử lý)
            </option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Ghi chú phân công</label>
          <textarea name="ghi_chu" class="form-control" rows="3" placeholder="Hướng dẫn, yêu cầu cụ thể..."><?= e($phDetail['ghi_chu_phan_cong']??'') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Hạn xử lý</label>
          <input type="date" name="han_xu_ly" class="form-control" value="<?= e($phDetail['han_xu_ly']??date('Y-m-d', strtotime('+7 days'))) ?>">
        </div>
        <div class="d-flex gap-2 mb-2">
          <button type="submit" class="btn btn-dhv flex-grow-1"><i class="fas fa-user-check me-1"></i>Xác nhận phân công</button>
          <a href="phancong.php" class="btn btn-outline-secondary">Đóng</a>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="card-dhv p-4">
      <div class="empty-state py-4 text-center">
        <i class="fas fa-mouse-pointer fa-2x text-muted mb-3 d-block"></i>
        <p class="text-muted small">Chọn một phản hồi từ danh sách bên trái để phân công xử lý.</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- CÁN BỘ & KHỐI LƯỢNG CÔNG VIỆC -->
    <div class="card-dhv p-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-users me-2 text-primary"></i>Khối lượng cán bộ</h6>
      <?php if (empty($canBos)): ?>
      <p class="text-muted small">Chưa có cán bộ nào trong đơn vị.</p>
      <?php else: ?>
      <?php foreach ($canBos as $cb):
        $pct = min(100, $cb['dang_xu_ly'] * 10);
        $color = $cb['dang_xu_ly']==0?'success':($cb['dang_xu_ly']<=3?'warning':'danger');
      ?>
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="avatar-circle" style="width:30px;height:30px;font-size:.7rem;flex-shrink:0"><?= mb_strtoupper(mb_substr($cb['ho_ten'],0,1,'UTF-8')) ?></div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between small">
            <strong><?= e($cb['ho_ten']) ?></strong>
            <span class="text-<?= $color ?>"><?= $cb['dang_xu_ly'] ?> đang xử lý</span>
          </div>
          <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function chonPH(id, tieu_de) {
  window.location.href = '?id=' + id + '&trang_thai=<?= $trangThai ?>';
}


</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>