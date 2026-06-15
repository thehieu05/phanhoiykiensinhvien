<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('can_bo');

$id      = intval($_GET['id'] ?? 0);
$canBoId = $_SESSION['user_id'];
global $pdo;

$stmt = $pdo->prepare("
    SELECT ph.*, cd.ten_chu_de, cd.icon, lph.ten_loai,
           u.ho_ten as ten_nguoi_gui, u.ma_sv_gv, u.lop, u.email as email_sv,
           tdv.ho_ten as ten_truong_don_vi, dv.ten_don_vi
    FROM phan_hoi ph
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    LEFT JOIN loai_phan_hoi lph ON ph.loai_phan_hoi_id = lph.id
    LEFT JOIN users u ON ph.nguoi_gui_id = u.id
    LEFT JOIN users tdv ON ph.truong_don_vi_id = tdv.id
    LEFT JOIN don_vi dv ON ph.don_vi_xu_ly_id = dv.id
    WHERE ph.id = ? AND ph.can_bo_xu_ly_id = ?
");
$stmt->execute([$id, $canBoId]);
$ph = $stmt->fetch();
if (!$ph) chuyenHuong(SITE_URL . '/canbo/danh-sach.php');

$pdo->prepare("UPDATE phan_hoi SET luot_xem = luot_xem + 1 WHERE id = ?")->execute([$id]);

$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Bắt đầu xử lý
    if (isset($_POST['bat_dau']) && $ph['trang_thai'] === 'da_tiep_nhan') {
        capNhatTrangThai($id, 'dang_xu_ly', $canBoId, 'Cán bộ bắt đầu xử lý');
        chuyenHuong("xuly.php?id=$id&msg=batdau_ok");
    }

    // // Cập nhật trạng thái
    // if (isset($_POST['cap_nhat_tt'])) {
    //     $tsM = $_POST['trang_thai_moi'] ?? '';
    //     $gc  = trim($_POST['ghi_chu_tt'] ?? '');
    //     $allowed = ['da_tiep_nhan','dang_xu_ly','cho_bo_sung'];
    //     if (in_array($tsM, $allowed)) {
    //         capNhatTrangThai($id, $tsM, $canBoId, $gc);
    //         chuyenHuong("xuly.php?id=$id&msg=cap_nhat_ok");
    //     }
    // }

    // Gửi trả lời
    if (isset($_POST['gui_tra_loi'])) {
        $nd = trim($_POST['noi_dung_tra_loi'] ?? '');
        if (empty($nd)) { $msg = 'Vui lòng nhập nội dung trả lời.'; $msgType = 'danger'; }
        elseif (!in_array($ph['trang_thai'], ['dang_xu_ly','da_tiep_nhan'])) {
            $msg = 'Không thể gửi trả lời ở trạng thái hiện tại.'; $msgType = 'warning';
        } else {
            $pdo->prepare("INSERT INTO tra_loi (phan_hoi_id, nguoi_tra_loi_id, noi_dung, loai, trang_thai_duyet, nguoi_duyet_id, ngay_duyet) VALUES (?, ?, ?, 'chinh_thuc', 'da_duyet', ?, NOW())")->execute([$id, $canBoId, $nd, $canBoId]);
            $tlId = $pdo->lastInsertId();

            // Upload file trả lời
            if (!empty($_FILES['file_tra_loi']['name'][0])) {
                foreach ($_FILES['file_tra_loi']['tmp_name'] as $k => $tmp) {
                    if (!empty($tmp)) uploadFile(['name'=>$_FILES['file_tra_loi']['name'][$k],'tmp_name'=>$tmp,'size'=>$_FILES['file_tra_loi']['size'][$k],'error'=>0], $id, $tlId);
                }
            }

            capNhatTrangThai($id, 'da_xu_ly', $canBoId, 'Cán bộ trả lời phản hồi (Tự động duyệt và gửi sinh viên)');

            // Thông báo trưởng đơn vị về câu trả lời đã gửi
            if ($ph['truong_don_vi_id']) {
                themThongBao($ph['truong_don_vi_id'], 'Phản hồi đã được xử lý', "Cán bộ đã trả lời và hoàn thành phản hồi: \"{$ph['tieu_de']}\"", 'cap_nhat_trang_thai', $id);
            }
            chuyenHuong("xuly.php?id=$id&msg=gui_ok");
        }
    }

    // Hủy nhận phân công và trả lại cho Trưởng đơn vị
    if (isset($_POST['huy_xu_ly'])) {
        $lyDo = trim($_POST['ly_do_huy'] ?? '');
        if (empty($lyDo)) {
            $msg = 'Vui lòng nhập lý do hủy nhận phân công.';
            $msgType = 'danger';
        } else {
            // Trả lại cho Trưởng đơn vị: Đổi trạng thái về cho_xu_ly và gỡ can_bo_xu_ly_id
            $success = $pdo->prepare("UPDATE phan_hoi SET trang_thai = 'cho_xu_ly', can_bo_xu_ly_id = NULL, updated_at = NOW() WHERE id = ?")
                ->execute([$id]);
            
            if ($success) {
                ghiLichSu($id, $ph['trang_thai'], 'cho_xu_ly', $canBoId, 'Cán bộ hủy nhận phân công: ' . $lyDo);
                
                // Thông báo cho trưởng đơn vị
                if ($ph['truong_don_vi_id']) {
                    themThongBao(
                        $ph['truong_don_vi_id'],
                        'Cán bộ hủy nhận phân công',
                        "Cán bộ đã hủy nhận phân công xử lý phản hồi: \"{$ph['tieu_de']}\". Lý do: $lyDo",
                        'cap_nhat_trang_thai',
                        $id
                    );
                }
                flashMessage('success', 'Đã hủy nhận phân công và trả lại cho Trưởng đơn vị.');
                chuyenHuong("danh-sach.php");
            } else {
                $msg = 'Không thể cập nhật trạng thái phản hồi.';
                $msgType = 'danger';
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $msgs = ['batdau_ok'=>'Đã bắt đầu xử lý phản hồi.','cap_nhat_ok'=>'Đã cập nhật trạng thái.','gui_ok'=>'Đã gửi câu trả lời và hoàn thành phản hồi thành công.'];
    $msg = $msgs[$_GET['msg']] ?? '';
}

// Lịch sử trao đổi
$traLois = $pdo->prepare("SELECT tl.*, u.ho_ten, u.vai_tro FROM tra_loi tl JOIN users u ON tl.nguoi_tra_loi_id = u.id WHERE tl.phan_hoi_id = ? ORDER BY tl.created_at ASC");
$traLois->execute([$id]);
$traLois = $traLois->fetchAll();

$dinhKems = layDinhKem($id);

// Reload latest status
$ph['trang_thai'] = $pdo->prepare("SELECT trang_thai FROM phan_hoi WHERE id=?")->execute([$id]) ? $pdo->prepare("SELECT trang_thai FROM phan_hoi WHERE id=?")->execute([$id]) : $ph['trang_thai'];
$phFresh = $pdo->prepare("SELECT trang_thai FROM phan_hoi WHERE id=?"); $phFresh->execute([$id]); $trangThaiHT = $phFresh->fetchColumn() ?: $ph['trang_thai'];

$coDuocTraLoi = in_array($trangThaiHT, ['dang_xu_ly','da_tiep_nhan']);

$pageTitle  = 'Xử lý phản hồi #' . $id;
$activeMenu = 'danh_sach';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> mb-3"><i class="fas fa-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?> me-2"></i><?= e($msg) ?></div>
<?php endif; ?>

<div class="d-flex align-items-center gap-2 mb-3">
  <a href="danh-sach.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Quay lại</a>
  <span class="text-muted small">/ <?= e($ph['ma_phan_hoi']??'#'.$id) ?></span>
  <span class="badge bg-<?= mauTrangThai($trangThaiHT) ?> ms-1"><?= nhanTrangThai($trangThaiHT) ?></span>
</div>

<!-- BANNER -->
<?php if ($trangThaiHT === 'da_tiep_nhan'): ?>
<div class="alert alert-primary d-flex align-items-center gap-3 mb-3">
  <i class="fas fa-play-circle fa-2x"></i>
  <div class="flex-grow-1">
    <div class="fw-700">Phản hồi mới được phân công cho bạn</div>
    <?php if ($ph['ghi_chu_phan_cong']): ?>
    <div class="small">Ghi chú: <em>"<?= e($ph['ghi_chu_phan_cong']) ?>"</em></div>
    <?php endif; ?>
  </div>
  <form method="POST"><button type="submit" name="bat_dau" class="btn btn-primary"><i class="fas fa-play me-1"></i>Bắt đầu xử lý</button></form>
</div>
<?php endif; ?>

<?php if ($trangThaiHT === 'da_huy'): ?>
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-3">
  <i class="fas fa-ban fa-2x"></i>
  <div><div class="fw-700">Phản hồi đã hủy</div><div class="small">Lý do: <?= e($ph['ly_do_huy']??'') ?></div></div>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <!-- NỘI DUNG -->
    <div class="card-dhv p-4 mb-3">
      <div class="d-flex gap-2 flex-wrap mb-2">
        <?php if ($ph['ten_chu_de']): ?><span class="badge bg-light text-dark border"><i class="<?= $ph['icon'] ?> me-1"></i><?= e($ph['ten_chu_de']) ?></span><?php endif; ?>
        <?php if ($ph['ten_loai']): ?><span class="badge bg-light text-dark border"><?= e($ph['ten_loai']) ?></span><?php endif; ?>
        <span class="badge bg-<?= mauMucDo($ph['muc_do_uu_tien']) ?>"><?= nhanMucDo($ph['muc_do_uu_tien']) ?></span>
      </div>
      <h4 class="fw-800 mb-3"><?= e($ph['tieu_de']) ?></h4>
      <div class="feedback-content-box p-4 mb-4" style="background: #f8fafc; border-left: 4px solid var(--primary); border-radius: 4px 12px 12px 4px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02); font-size: 1.05rem; color: #334155; line-height: 1.8; position: relative;">
        <div class="quote-icon" style="position: absolute; right: 20px; top: 15px; font-size: 2.5rem; color: rgba(0, 48, 135, 0.05); pointer-events: none;"><i class="fas fa-quote-right"></i></div>
        <div style="white-space: pre-wrap; font-weight: 500;"><?= trim(e($ph['noi_dung'])) ?></div>
      </div>

      <?php if (!empty($dinhKems)): ?>
      <div class="mt-2">
        <div class="small fw-600 text-muted mb-2"><i class="fas fa-paperclip me-1"></i>File đính kèm:</div>
        <?php foreach ($dinhKems as $dk): ?>
        <a href="<?= UPLOAD_URL.$dk['duong_dan'] ?>" target="_blank" class="badge bg-light text-dark border me-1 text-decoration-none">
          <i class="fas fa-file me-1"></i><?= e($dk['ten_goc']) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="d-flex gap-3 small text-muted mt-3 flex-wrap">
        <span><i class="fas fa-user me-1"></i><?= $ph['an_danh'] ? 'Ẩn danh' : e($ph['ten_nguoi_gui']??'N/A') ?></span>
        <span><i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($ph['created_at'])) ?></span>
      </div>
    </div>

    <!-- CẬP NHẬT TRẠNG THÁI -->
    <!-- <?php if (in_array($trangThaiHT, ['dang_xu_ly','da_tiep_nhan'])): ?>
    <div class="card-dhv p-3 mb-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-exchange-alt me-2 text-primary"></i>Cập nhật trạng thái</h6>
      <form method="POST" class="row g-2">
        <div class="col-md-5">
          <select name="trang_thai_moi" class="form-select form-select-sm">
            <option value="da_tiep_nhan" <?= $trangThaiHT==='da_tiep_nhan'?'selected':'' ?>>Đã tiếp nhận</option>
            <option value="dang_xu_ly" <?= $trangThaiHT==='dang_xu_ly'?'selected':'' ?>>Đang xử lý</option>
            <option value="cho_bo_sung">Chờ bổ sung từ sinh viên</option>
          </select>
        </div>
        <div class="col-md-5">
          <input type="text" name="ghi_chu_tt" class="form-control form-control-sm" placeholder="Ghi chú (tùy chọn)">
        </div>
        <div class="col-md-2">
          <button type="submit" name="cap_nhat_tt" class="btn btn-outline-primary btn-sm w-100">Cập nhật</button>
        </div>
      </form>
    </div>
    <?php endif; ?> -->

    <!-- SOẠN TRẢ LỜI -->
    <div class="card-dhv p-4 mb-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-pen me-2 text-primary"></i><?= $coDuocTraLoi ? 'Soạn câu trả lời' : 'Lịch sử trao đổi' ?></h6>

      <?php foreach ($traLois as $tl): ?>
      <div class="mb-3 p-3 rounded border border-<?= $tl['trang_thai_duyet']==='tu_choi'?'danger':($tl['trang_thai_duyet']==='da_duyet'?'success':'warning') ?>">
        <div class="d-flex justify-content-between mb-1 small">
          <strong><?= e($tl['ho_ten']) ?></strong>
          <span class="text-muted"><?= thoiGianTuongDoi($tl['created_at']) ?></span>
        </div>
        <div class="small" style="white-space:pre-wrap"><?= e($tl['noi_dung']) ?></div>
        <?php if ($tl['trang_thai_duyet']==='cho_duyet'): ?><span class="badge bg-warning text-dark mt-1">⏳ Chờ duyệt</span>
        <?php elseif ($tl['trang_thai_duyet']==='da_duyet'): ?><span class="badge bg-success mt-1">✅ Đã duyệt & gửi SV</span>
        <?php elseif ($tl['trang_thai_duyet']==='tu_choi'): ?><span class="badge bg-danger mt-1">❌ Bị từ chối: <?= e($tl['ghi_chu_duyet']??'') ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <?php if ($coDuocTraLoi): 
        $lastRejectedNd = '';
        foreach ($traLois as $tl) {
            if ($tl['nguoi_tra_loi_id'] == $canBoId && $tl['trang_thai_duyet'] === 'tu_choi') {
                $lastRejectedNd = $tl['noi_dung'];
            }
        }
      ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <textarea name="noi_dung_tra_loi" class="form-control" rows="5"
                    placeholder="Nhập câu trả lời đầy đủ, rõ ràng...&#10;&#10;Câu trả lời sẽ được gửi trực tiếp tới sinh viên và hoàn thành phản hồi." required><?= e($lastRejectedNd) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Đính kèm file (tùy chọn)</label>
          <input type="file" name="file_tra_loi[]" class="form-control form-control-sm" multiple>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" name="gui_tra_loi" class="btn btn-dhv">
            <i class="fas fa-paper-plane me-1"></i>Gửi câu trả lời
          </button>
        </div>
        <div class="form-text mt-2"><i class="fas fa-info-circle me-1 text-primary"></i>Câu trả lời sẽ được <strong>gửi trực tiếp tới sinh viên</strong> (Hệ thống tự động duyệt).</div>
      </form>
      <?php endif; ?>
    </div>

    <!-- HỦY XỬ LÝ PHẢN HỒI (HỦY NHẬN PHÂN CÔNG) -->
    <?php if (in_array($trangThaiHT, ['da_tiep_nhan','dang_xu_ly'])): ?>
    <div class="card-dhv p-3" style="border-left:4px solid #dc3545">
      <h6 class="fw-700 mb-2 text-danger"><i class="fas fa-times-circle me-2"></i>Hủy nhận phân công</h6>
      <p class="small text-muted">Nếu không thể xử lý phản hồi này, bạn có thể hủy nhận phân công để trả lại phản hồi này cho Trưởng đơn vị.</p>
      <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy nhận phân công và trả lại phản hồi này cho Trưởng đơn vị không?')">
        <div class="mb-2">
          <textarea name="ly_do_huy" class="form-control form-control-sm" rows="2" placeholder="Lý do hủy nhận phân công..." required></textarea>
        </div>
        <button type="submit" name="huy_xu_ly" class="btn btn-outline-danger btn-sm">
          <i class="fas fa-times me-1"></i>Xác nhận hủy nhận phân công
        </button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- SIDEBAR -->
  <div class="col-lg-4">
    <?php if ($ph['ghi_chu_phan_cong']): ?>
    <div class="card-dhv p-3 mb-3" style="border-left:4px solid #ffc107">
      <h6 class="fw-700 mb-2"><i class="fas fa-clipboard-list me-2 text-warning"></i>Giao việc từ Trưởng ĐV</h6>
      <p class="small mb-1"><?= e($ph['ghi_chu_phan_cong']) ?></p>
      <?php if ($ph['han_xu_ly']): ?>
      <div class="small text-danger fw-bold"><i class="fas fa-calendar me-1"></i>Hạn: <?= date('d/m/Y', strtotime($ph['han_xu_ly'])) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card-dhv p-3 mb-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-user me-2 text-primary"></i>Người gửi phản hồi</h6>
      <?php if ($ph['an_danh']): ?>
        <div class="text-center py-2"><i class="fas fa-user-secret fa-2x text-muted mb-2 d-block"></i><span class="text-muted small">Phản hồi ẩn danh</span></div>
      <?php else: ?>
        <div class="small">
          <div class="fw-600 mb-1"><?= e($ph['ten_nguoi_gui']??'N/A') ?></div>
          <div class="text-muted">MSSV: <?= e($ph['ma_sv_gv']??'N/A') ?></div>
          <div class="text-muted">Lớp: <?= e($ph['lop']??'N/A') ?></div>
          <?php if ($ph['email_sv']): ?>
          <div class="text-muted"><i class="fas fa-envelope me-1"></i><?= e($ph['email_sv']) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card-dhv p-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Thông tin</h6>
      <table class="table table-sm small mb-0">
        <tr><td class="text-muted">Mã PH</td><td><strong><?= e($ph['ma_phan_hoi']??'#'.$id) ?></strong></td></tr>
        <tr><td class="text-muted">Ngày gửi</td><td><?= date('d/m/Y', strtotime($ph['created_at'])) ?></td></tr>
        <tr><td class="text-muted">Mức độ</td><td><span class="badge bg-<?= mauMucDo($ph['muc_do_uu_tien']) ?>"><?= nhanMucDo($ph['muc_do_uu_tien']) ?></span></td></tr>
        <tr><td class="text-muted">Trưởng ĐV</td><td><?= e($ph['ten_truong_don_vi']??'N/A') ?></td></tr>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>