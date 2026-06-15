<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('truong_don_vi');

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$tdvId = $_SESSION['user_id'];
$userInfo = layThongTinUser($tdvId);
$donViId = $userInfo['don_vi_id'];
global $pdo;

if (!$id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID phản hồi không hợp lệ.']);
    } else {
        echo '<div class="alert alert-danger">ID phản hồi không hợp lệ.</div>';
    }
    exit;
}

// Xử lý hủy phản hồi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'huy_phan_hoi') {
    header('Content-Type: application/json');
    $lyDo = trim($_POST['ly_do_huy'] ?? '');
    if (empty($lyDo)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập lý do hủy.']);
        exit;
    }

    // Kiểm tra tính hợp lệ và quyền sở hữu phản hồi
    $stmtCheck = $pdo->prepare("SELECT trang_thai FROM phan_hoi WHERE id = ? AND don_vi_xu_ly_id = ?");
    $stmtCheck->execute([$id, $donViId]);
    $currentStatus = $stmtCheck->fetchColumn();

    if (!$currentStatus) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phản hồi hoặc phản hồi không thuộc thẩm quyền của đơn vị bạn.']);
        exit;
    }

    if (in_array($currentStatus, ['da_huy', 'da_xu_ly', 'tu_choi'])) {
        echo json_encode(['success' => false, 'message' => 'Phản hồi này đã được xử lý, hủy hoặc từ chối từ trước.']);
        exit;
    }

    $success = capNhatTrangThai($id, 'da_huy', $tdvId, 'Hủy phản hồi. Lý do: ' . $lyDo);
    if ($success) {
        $stmtUpdate = $pdo->prepare("UPDATE phan_hoi SET ly_do_huy = ?, nguoi_huy_id = ?, ngay_huy = NOW(), updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$lyDo, $tdvId, $id]);
        echo json_encode(['success' => true, 'message' => 'Đã hủy phản hồi thành công.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái phản hồi.']);
    }
    exit;
}

// Lấy chi tiết phản hồi thuộc đơn vị phụ trách
$stmt = $pdo->prepare("
    SELECT ph.*, cd.ten_chu_de, cd.icon as cd_icon,
           lph.ten_loai,
           u.ho_ten as ten_nguoi_gui, u.email as email_nguoi_gui, u.ma_sv_gv,
           cb.ho_ten as ten_can_bo,
           dv.ten_don_vi
    FROM phan_hoi ph
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    LEFT JOIN loai_phan_hoi lph ON ph.loai_phan_hoi_id = lph.id
    LEFT JOIN users u ON ph.nguoi_gui_id = u.id
    LEFT JOIN users cb ON ph.can_bo_xu_ly_id = cb.id
    LEFT JOIN don_vi dv ON ph.don_vi_xu_ly_id = dv.id
    WHERE ph.id = ? AND ph.don_vi_xu_ly_id = ?
");
$stmt->execute([$id, $donViId]);
$ph = $stmt->fetch();

if (!$ph) {
    echo '<div class="alert alert-danger">Không tìm thấy phản hồi hoặc phản hồi không thuộc thẩm quyền của đơn vị bạn.</div>';
    exit;
}

// File đính kèm của phản hồi và câu trả lời
$dkStmt = $pdo->prepare("SELECT * FROM dinh_kem WHERE phan_hoi_id = ? ORDER BY created_at");
$dkStmt->execute([$id]);
$dinhKems = $dkStmt->fetchAll();

// Lấy tất cả câu trả lời (bao gồm nội bộ, chờ duyệt, bị từ chối)
$traLois = $pdo->prepare("
    SELECT tl.*, u.ho_ten, u.vai_tro, dv.ten_don_vi
    FROM tra_loi tl
    JOIN users u ON tl.nguoi_tra_loi_id = u.id
    LEFT JOIN don_vi dv ON u.don_vi_id = dv.id
    WHERE tl.phan_hoi_id = ?
    ORDER BY tl.created_at ASC
");
$traLois->execute([$id]);
$traLois = $traLois->fetchAll();

// Lịch sử trạng thái
$lichSu = $pdo->prepare("
    SELECT ls.*, u.ho_ten
    FROM lich_su_trang_thai ls
    LEFT JOIN users u ON ls.nguoi_thay_doi_id = u.id
    WHERE ls.phan_hoi_id = ?
    ORDER BY ls.created_at ASC
");
$lichSu->execute([$id]);
$lichSu = $lichSu->fetchAll();
?>

<div class="modal-body py-3">
  <!-- Thẻ thông tin chung -->
  <div class="row g-3 mb-4">
    <div class="col-md-8">
      <div class="d-flex gap-2 mb-2 flex-wrap align-items-center">
        <span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?> px-2 py-1">
          <i class="<?= iconTrangThai($ph['trang_thai']) ?> me-1"></i><?= nhanTrangThai($ph['trang_thai']) ?>
        </span>
        <?php if ($ph['ten_chu_de']): ?>
          <span class="badge bg-light text-dark border"><i class="<?= e($ph['cd_icon'] ?? 'fas fa-tag') ?> me-1"></i><?= e($ph['ten_chu_de']) ?></span>
        <?php endif; ?>
        <?php if ($ph['ten_loai']): ?>
          <span class="badge bg-light text-muted border"><?= e($ph['ten_loai']) ?></span>
        <?php endif; ?>
        <?php if ($ph['muc_do_uu_tien'] === 'khan_cap'): ?>
          <span class="badge bg-danger">Khẩn cấp</span>
        <?php endif; ?>
      </div>
      <h5 class="fw-700 text-dark mb-1"><?= e($ph['tieu_de']) ?></h5>
      <div class="text-muted small">
        <span><i class="fas fa-hashtag me-1"></i>Mã phản hồi: <strong><?= e($ph['ma_phan_hoi'] ?? '#'.$id) ?></strong></span>
        <span class="mx-2">|</span>
        <span><i class="fas fa-clock me-1"></i>Gửi lúc: <?= date('d/m/Y H:i', strtotime($ph['created_at'])) ?></span>
      </div>
    </div>
    <div class="col-md-4 text-md-end">
      <div class="bg-light p-2 rounded text-start text-md-end d-inline-block w-100">
        <div class="small text-muted">Người gửi:</div>
        <div class="fw-bold">
          <?php if ($ph['an_danh']): ?>
            <span class="text-danger"><i class="fas fa-user-secret me-1"></i>Ẩn danh (Sinh viên)</span>
          <?php else: ?>
            <span><i class="fas fa-user me-1"></i><?= e($ph['ten_nguoi_gui']) ?></span>
            <div class="small text-muted fw-normal" style="font-size: 0.75rem;">MSV: <?= e($ph['ma_sv_gv']) ?> · <?= e($ph['email_nguoi_gui']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-3">

  <div class="row g-3">
    <!-- CỘT TRÁI: NỘI DUNG VÀ CÂU TRẢ LỜI -->
    <div class="col-lg-8">
      <!-- Nội dung phản hồi gốc -->
      <div class="card card-body border-0 shadow-none bg-light p-3 mb-4">
        <h6 class="fw-700 text-primary mb-2"><i class="fas fa-quote-left me-2"></i>Nội dung ý kiến:</h6>
        <div class="text-dark whitespace-pre-wrap" style="line-height: 1.6;"><?= nl2br(e($ph['noi_dung'])) ?></div>
        
        <?php if (!empty($dinhKems)): ?>
          <div class="mt-3 pt-3 border-top">
            <div class="small fw-600 mb-1"><i class="fas fa-paperclip me-1"></i>Minh chứng đính kèm:</div>
            <div class="d-flex gap-2 flex-wrap">
              <?php foreach ($dinhKems as $dk): ?>
                <?php if (is_null($dk['tra_loi_id'])): // Đính kèm của phản hồi gốc ?>
                  <a href="<?= UPLOAD_URL . e($dk['duong_dan']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2 text-truncate" style="max-width: 200px;" title="<?= e($dk['ten_goc']) ?>">
                    <i class="fas fa-download me-1"></i><?= e($dk['ten_goc']) ?>
                  </a>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Lịch sử các câu trả lời / thảo luận nội bộ -->
      <h6 class="fw-700 mb-3"><i class="fas fa-comments me-2 text-success"></i>Các phản hồi & trao đổi (<?= count($traLois) ?>)</h6>
      <?php if (empty($traLois)): ?>
        <div class="alert alert-light border small text-muted text-center py-3"><i class="fas fa-info-circle me-1"></i>Chưa có câu trả lời nào được soạn thảo.</div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($traLois as $tl):
            $bg = 'bg-white border';
            $badge = '';
            if ($tl['loai'] === 'noi_bo') {
                if ($tl['trang_thai_duyet'] === 'cho_duyet') {
                    $bg = 'bg-light-warning border border-warning';
                    $badge = '<span class="badge bg-warning text-dark ms-2"><i class="fas fa-clock me-1"></i>Chờ duyệt trả lời</span>';
                } elseif ($tl['trang_thai_duyet'] === 'tu_choi') {
                    $bg = 'bg-light-danger border border-danger';
                    $badge = '<span class="badge bg-danger ms-2"><i class="fas fa-times-circle me-1"></i>Từ chối duyệt</span>';
                } else {
                    $bg = 'bg-light border';
                    $badge = '<span class="badge bg-secondary ms-2">Bản nháp / Nội bộ</span>';
                }
            } else {
                $bg = 'bg-light-success border border-success';
                $badge = '<span class="badge bg-success ms-2"><i class="fas fa-check-circle me-1"></i>Câu trả lời chính thức</span>';
            }
          ?>
            <div class="card p-3 shadow-sm rounded-3 <?= $bg ?>">
              <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                <div class="small">
                  <strong><?= e($tl['ho_ten']) ?></strong>
                  <span class="text-muted">(<?= $tl['vai_tro'] === 'can_bo' ? 'Cán bộ xử lý' : 'Trưởng đơn vị' ?>)</span>
                  <?= $badge ?>
                </div>
                <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($tl['created_at'])) ?></div>
              </div>
              <div class="whitespace-pre-wrap small text-dark" style="line-height: 1.5;"><?= nl2br(e($tl['noi_dung'])) ?></div>
              
              <!-- Nếu bị từ chối, hiển thị ghi chú từ chối -->
              <?php if ($tl['trang_thai_duyet'] === 'tu_choi' && $tl['ghi_chu_duyet']): ?>
                <div class="mt-2 p-2 bg-white rounded border border-danger-subtle small text-danger">
                  <i class="fas fa-exclamation-triangle me-1"></i><strong>Lý do từ chối:</strong> <?= e($tl['ghi_chu_duyet']) ?>
                </div>
              <?php endif; ?>

              <!-- Đính kèm của câu trả lời -->
              <?php
              $filesTL = array_filter($dinhKems, function($dk) use ($tl) { return $dk['tra_loi_id'] == $tl['id']; });
              if (!empty($filesTL)):
              ?>
                <div class="mt-2 pt-2 border-top border-secondary-subtle">
                  <span class="small text-muted me-2"><i class="fas fa-paperclip me-1"></i>File đính kèm:</span>
                  <?php foreach ($filesTL as $dk): ?>
                    <a href="<?= UPLOAD_URL . e($dk['duong_dan']) ?>" target="_blank" class="btn btn-link btn-sm p-0 me-2 text-decoration-none" title="<?= e($dk['ten_goc']) ?>">
                      <i class="fas fa-download me-1"></i><?= e(mb_substr($dk['ten_goc'], 0, 20)) ?>...
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Chức năng Hủy phản hồi -->
      <?php if (!in_array($ph['trang_thai'], ['da_huy', 'da_xu_ly', 'tu_choi'])): ?>
      <div id="cardHuyPhanHoi" class="card card-body border rounded-3 p-3 bg-white mt-4 border-danger-subtle">
        <h6 class="fw-700 text-danger mb-3"><i class="fas fa-ban me-2"></i>Hủy phản hồi ý kiến</h6>
        <form id="formHuyPhanHoi" onsubmit="handleHuyPhanHoi(event, <?= $id ?>)">
          <div class="mb-3">
            <label class="form-label small fw-600 text-muted">Lý do hủy <span class="text-danger">*</span></label>
            <textarea name="ly_do_huy" class="form-control form-control-sm" rows="3" placeholder="Vui lòng nhập lý do hủy..." required></textarea>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-danger btn-sm fw-bold"><i class="fas fa-times-circle me-1"></i>Xác nhận hủy phản hồi</button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- CỘT PHẢI: PHÂN CÔNG & TIẾN TRÌNH -->
    <div class="col-lg-4">
      <!-- Thông tin phân công xử lý -->
      <?php if (!in_array($ph['trang_thai'], ['da_huy', 'tu_choi'])): ?>
      <div class="card card-body border rounded-3 p-3 mb-3 bg-white">
        <h6 class="fw-700 text-dark mb-3"><i class="fas fa-user-tag me-2 text-primary"></i>Phân công xử lý</h6>
        <?php if ($ph['can_bo_xu_ly_id']): ?>
          <div class="small d-flex flex-column gap-2">
            <div><span class="text-muted">Cán bộ xử lý:</span> <strong class="text-dark"><?= e($ph['ten_can_bo']) ?></strong></div>
            <div><span class="text-muted">Hạn xử lý:</span> <strong class="text-danger"><?= $ph['han_xu_ly'] ? date('d/m/Y', strtotime($ph['han_xu_ly'])) : 'Không giới hạn' ?></strong></div>
            <?php if ($ph['ngay_phan_cong']): ?>
              <div><span class="text-muted">Ngày phân công:</span> <span class="text-muted"><?= date('d/m/Y', strtotime($ph['ngay_phan_cong'])) ?></span></div>
            <?php endif; ?>
            <?php if ($ph['ghi_chu_phan_cong']): ?>
              <div class="mt-2 p-2 bg-light rounded text-muted" style="font-size: 0.75rem;">
                <i class="fas fa-info-circle me-1"></i><strong>Chỉ đạo:</strong> <?= e($ph['ghi_chu_phan_cong']) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-light border small text-muted text-center py-2 mb-0"><i class="fas fa-exclamation-triangle me-1"></i>Chưa được phân công cho cán bộ nào.</div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Timeline trạng thái xử lý -->
      <div class="card card-body border rounded-3 p-3 bg-white">
        <h6 class="fw-700 text-dark mb-3"><i class="fas fa-history me-2 text-primary"></i>Nhật ký xử lý</h6>
        <div class="timeline" style="padding-left: 15px;">
          <?php foreach ($lichSu as $ls): ?>
            <div class="mb-3 position-relative" style="border-left: 2px solid #e9ecef; padding-left: 15px; margin-left: -5px;">
              <div class="position-absolute bg-<?= mauTrangThai($ls['trang_thai_moi']) ?> rounded-circle" style="width: 10px; height: 10px; left: -6px; top: 5px;"></div>
              <div class="small fw-bold text-dark"><?= nhanTrangThai($ls['trang_thai_moi']) ?></div>
              <div class="text-muted" style="font-size: 0.7rem;"><?= date('d/m/Y H:i', strtotime($ls['created_at'])) ?></div>
              <?php if ($ls['ghi_chu']): ?>
                <div class="text-muted whitespace-pre-wrap mt-1" style="font-size: 0.72rem;"><?= e($ls['ghi_chu']) ?></div>
              <?php endif; ?>
              <?php if ($ls['ho_ten']): ?>
                <div class="text-muted text-end" style="font-size: 0.68rem; font-style: italic;">Thực hiện bởi: <?= e($ls['ho_ten']) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</div>


