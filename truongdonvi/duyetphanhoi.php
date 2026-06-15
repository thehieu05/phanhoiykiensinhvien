<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('truong_don_vi');

$pageTitle  = 'Duyệt trả lời phản hồi';
$activeMenu = 'duyet_tl';
$tdvId      = $_SESSION['user_id'];
$userInfo   = layThongTinUser($tdvId);
$donViId    = $userInfo['don_vi_id'];
global $pdo;

$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tlId = intval($_POST['tra_loi_id'] ?? 0);
    $act  = $_POST['action'] ?? '';
    $gc   = trim($_POST['ghi_chu'] ?? '');

    // Check ownership
    $tlInfo = $pdo->prepare("SELECT tl.*, ph.id as ph_id, ph.tieu_de, ph.nguoi_gui_id, u.email as email_cb FROM tra_loi tl JOIN phan_hoi ph ON tl.phan_hoi_id=ph.id JOIN users u ON tl.nguoi_tra_loi_id=u.id WHERE tl.id=? AND ph.don_vi_xu_ly_id=?");
    $tlInfo->execute([$tlId, $donViId]);
    $tlInfo = $tlInfo->fetch();

    if ($tlInfo && $tlInfo['trang_thai_duyet'] === 'cho_duyet') {
        $phId = $tlInfo['ph_id'];
        if ($act === 'duyet') {
            $pdo->prepare("UPDATE tra_loi SET trang_thai_duyet='da_duyet', loai='chinh_thuc', nguoi_duyet_id=?, ngay_duyet=NOW() WHERE id=?")->execute([$tdvId, $tlId]);
            capNhatTrangThai($phId, 'da_xu_ly', $tdvId, 'Trưởng đơn vị đã duyệt câu trả lời và hoàn tất xử lý');

            // Copy file đính kèm từ tra_loi sang phan_hoi_id (nếu cần hiển thị công khai)
            // Hiện tại trong db, dinh_kem có tra_loi_id, có thể duyệt trực tiếp.

            flashMessage('success', 'Đã duyệt câu trả lời và chuyển trạng thái phản hồi thành Đã xử lý.');
            chuyenHuong('duyetphanhoi.php');
        } elseif ($act === 'tu_choi') {
            if (empty($gc)) { $msg='Vui lòng nhập lý do từ chối để cán bộ biết sửa.'; $msgType='danger'; }
            else {
                $pdo->prepare("UPDATE tra_loi SET trang_thai_duyet='tu_choi', ghi_chu_duyet=?, nguoi_duyet_id=?, ngay_duyet=NOW() WHERE id=?")->execute([$gc, $tdvId, $tlId]);
                capNhatTrangThai($phId, 'dang_xu_ly', $tdvId, 'Trưởng đơn vị từ chối câu trả lời, yêu cầu sửa: '.$gc);

                themThongBao($tlInfo['nguoi_tra_loi_id'], 'Câu trả lời bị từ chối', "Câu trả lời của bạn cho phản hồi \"{$tlInfo['tieu_de']}\" đã bị từ chối. Lý do: $gc", 'duyet_tra_loi', $phId);
                guiEmail($tlInfo['email_cb'], 'Câu trả lời bị từ chối - Yêu cầu chỉnh sửa', "Câu trả lời của bạn cho phản hồi \"<strong>{$tlInfo['tieu_de']}</strong>\" đã bị Trưởng đơn vị từ chối.<br>Lý do: <strong>$gc</strong><br>Vui lòng đăng nhập hệ thống để chỉnh sửa lại câu trả lời.");

                flashMessage('success', 'Đã từ chối câu trả lời và yêu cầu cán bộ làm lại.');
                chuyenHuong('duyetphanhoi.php');
            }
        }
    }
}

$success = flashMessage('success');

$choDuyet = $pdo->prepare("
    SELECT tl.*, ph.ma_phan_hoi, ph.tieu_de, ph.muc_do_uu_tien,
           cb.ho_ten as ten_can_bo, cd.ten_chu_de
    FROM tra_loi tl
    JOIN phan_hoi ph ON tl.phan_hoi_id = ph.id
    JOIN users cb ON tl.nguoi_tra_loi_id = cb.id
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    WHERE ph.don_vi_xu_ly_id = ? AND tl.loai = 'noi_bo' AND tl.trang_thai_duyet = 'cho_duyet'
      AND ph.trang_thai NOT IN ('da_huy', 'da_xu_ly', 'tu_choi')
    ORDER BY tl.created_at ASC
");
$choDuyet->execute([$donViId]);
$choDuyet = $choDuyet->fetchAll();

$selectedTL = intval($_GET['id'] ?? 0);
$detailTL = null;
if ($selectedTL) {
    foreach ($choDuyet as $tl) { if ($tl['id'] == $selectedTL) { $detailTL = $tl; break; } }
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-exclamation-circle me-2"></i><?= e($msg) ?></div><?php endif; ?>

<div class="page-header">
  <h2><i class="fas fa-clipboard-check me-2"></i>Duyệt trả lời</h2>
  <p class="mb-0">Duyệt câu trả lời của cán bộ trước khi gửi chính thức cho sinh viên</p>
</div>

<div class="row g-3">
  <!-- DANH SÁCH CHỜ DUYỆT -->
  <div class="col-lg-5">
    <div class="card-dhv p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-700 mb-0"><i class="fas fa-clock me-2 text-warning"></i>Đang chờ duyệt (<?= count($choDuyet) ?>)</h6>
      </div>

      <?php if (empty($choDuyet)): ?>
      <div class="empty-state py-4 text-center">
        <i class="fas fa-check-double fa-2x text-success mb-2 d-block"></i>
        <p class="text-muted small">Không có câu trả lời nào cần duyệt.</p>
      </div>
      <?php else: ?>
      <div class="d-flex flex-column gap-2">
        <?php foreach ($choDuyet as $tl): ?>
        <a href="?id=<?= $tl['id'] ?>" class="feedback-card text-decoration-none text-dark d-block border <?= $selectedTL==$tl['id']?'border-primary bg-light':'' ?>">
          <div class="fw-600 small"><?= e($tl['tieu_de']) ?></div>
          <div class="text-muted mt-1" style="font-size:.72rem">
            <?= e($tl['ten_chu_de']) ?> · <i class="fas fa-user-tie ms-1 me-1"></i><?= e($tl['ten_can_bo']) ?>
          </div>
          <div class="text-muted mt-1" style="font-size:.7rem"><i class="fas fa-calendar-alt me-1"></i>Soạn lúc: <?= date('d/m H:i', strtotime($tl['created_at'])) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- CHI TIẾT DUYỆT -->
  <div class="col-lg-7">
    <?php if ($detailTL):
      // Lấy toàn bộ thông tin chi tiết phản hồi để hiển thị trong modal
      $phStmt = $pdo->prepare("
          SELECT ph.*, cd.ten_chu_de, lph.ten_loai,
                 sv.ho_ten as ten_sinh_vien, sv.lop as lop_sinh_vien, sv.ma_sv_gv as mssv_sinh_vien
          FROM phan_hoi ph
          LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
          LEFT JOIN loai_phan_hoi lph ON ph.loai_phan_hoi_id = lph.id
          LEFT JOIN users sv ON ph.nguoi_gui_id = sv.id
          WHERE ph.id = ?
      ");
      $phStmt->execute([$detailTL['phan_hoi_id']]);
      $phDetail = $phStmt->fetch();

      // Files đính kèm
      $allDinhKems = $pdo->prepare("SELECT * FROM dinh_kem WHERE phan_hoi_id = ? ORDER BY created_at");
      $allDinhKems->execute([$detailTL['phan_hoi_id']]);
      $dinhKemsAll = $allDinhKems->fetchAll();
      
      $dinhKemsPhanHoi = array_filter($dinhKemsAll, fn($dk) => is_null($dk['tra_loi_id']));
      $dkTraLoi = array_filter($dinhKemsAll, fn($dk) => $dk['tra_loi_id'] == $detailTL['id']);

      // Lịch sử trạng thái (Timeline)
      $lsStmt = $pdo->prepare("
          SELECT ls.*, u.ho_ten
          FROM lich_su_trang_thai ls
          LEFT JOIN users u ON ls.nguoi_thay_doi_id = u.id
          WHERE ls.phan_hoi_id = ?
          ORDER BY ls.created_at ASC
      ");
      $lsStmt->execute([$detailTL['phan_hoi_id']]);
      $lichSu = $lsStmt->fetchAll();

      // Các câu trả lời khác
      $traLoiStmt = $pdo->prepare("
          SELECT tl.*, u.ho_ten, u.vai_tro 
          FROM tra_loi tl 
          JOIN users u ON tl.nguoi_tra_loi_id = u.id 
          WHERE tl.phan_hoi_id = ? 
          ORDER BY tl.created_at ASC
      ");
      $traLoiStmt->execute([$detailTL['phan_hoi_id']]);
      $allTraLois = $traLoiStmt->fetchAll();
    ?>
    <div class="card-dhv p-4 mb-3" style="border-top:4px solid var(--primary)">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h6 class="fw-700 mb-1">Nội dung phản hồi từ Sinh viên</h6>
          <div class="small text-muted"><?= e($detailTL['ma_phan_hoi']) ?></div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="xemChiTietPhanHoi(<?= $detailTL['phan_hoi_id'] ?>)">
          <i class="fas fa-eye me-1"></i>Xem chi tiết
        </button>
      </div>
      <div class="bg-light rounded p-3 mb-4 small" style="max-height:150px;overflow-y:auto;white-space:pre-wrap;line-height:1.6;border-left:3px solid var(--primary)">
        <?php
        $phGoc=$pdo->prepare("SELECT noi_dung FROM phan_hoi WHERE id=?"); $phGoc->execute([$detailTL['phan_hoi_id']]);
        echo trim(e($phGoc->fetchColumn()));
        ?>
      </div>

      <h6 class="fw-700 mb-3 text-success"><i class="fas fa-reply me-2"></i>Câu trả lời của cán bộ (<?= e($detailTL['ten_can_bo']) ?>)</h6>
      <div class="border border-success rounded p-3 mb-3 bg-white" style="white-space:pre-wrap"><?= e($detailTL['noi_dung']) ?></div>

      <?php if (!empty($dkTraLoi)): ?>
      <div class="mb-3">
        <div class="small fw-600 text-muted mb-2"><i class="fas fa-paperclip me-1"></i>File đính kèm trả lời:</div>
        <?php foreach ($dkTraLoi as $dk): ?>
        <a href="<?= UPLOAD_URL.$dk['duong_dan'] ?>" target="_blank" class="badge bg-light text-dark border me-1 text-decoration-none"><i class="fas fa-file me-1"></i><?= e($dk['ten_goc']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <hr>

      <form method="POST">
        <input type="hidden" name="tra_loi_id" value="<?= $detailTL['id'] ?>">
        <div class="mb-3">
          <label class="form-label small fw-600">Ghi chú duyệt / Lý do từ chối</label>
          <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Nhập lý do nếu bạn từ chối câu trả lời này..."></textarea>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" name="action" value="duyet" class="btn btn-success flex-grow-1 fw-700" onclick="return confirm('Duyệt câu trả lời này và đóng phản hồi?')"><i class="fas fa-check-circle me-1"></i>Duyệt & Chuyển cho SV</button>
          <button type="submit" name="action" value="tu_choi" class="btn btn-danger flex-grow-1 fw-700" onclick="return confirmTuChoi()"><i class="fas fa-times-circle me-1"></i>Từ chối & Yêu cầu sửa</button>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="card-dhv p-4 h-100 d-flex flex-column align-items-center justify-content-center">
      <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
      <p class="text-muted">Chọn một câu trả lời ở danh sách bên trái để xem và duyệt.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function confirmTuChoi() {
    var gc = document.querySelector('textarea[name="ghi_chu"]').value.trim();
    if (gc === '') {
        alert('Vui lòng nhập lý do từ chối để cán bộ biết sửa.');
        document.querySelector('textarea[name="ghi_chu"]').focus();
        return false;
    }
    return confirm('Từ chối câu trả lời này và yêu cầu cán bộ chỉnh sửa lại?');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>