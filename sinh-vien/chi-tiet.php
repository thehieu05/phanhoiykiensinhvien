<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraDangNhap();
if ($_SESSION['vai_tro'] !== 'sinh_vien') chuyenHuong(SITE_URL . '/index.php');

$id     = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];
global $pdo;

$stmt = $pdo->prepare("
    SELECT ph.*, cd.ten_chu_de, cd.icon,
           lph.ten_loai,
           u.ho_ten as ten_nguoi_gui,
           cb.ho_ten as ten_can_bo,
           dv.ten_don_vi
    FROM phan_hoi ph
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    LEFT JOIN loai_phan_hoi lph ON ph.loai_phan_hoi_id = lph.id
    LEFT JOIN users u ON ph.nguoi_gui_id = u.id
    LEFT JOIN users cb ON ph.can_bo_xu_ly_id = cb.id
    LEFT JOIN don_vi dv ON ph.don_vi_xu_ly_id = dv.id
    WHERE ph.id = ? AND (ph.nguoi_gui_id = ? OR ph.an_danh = 1)
");
$stmt->execute([$id, $userId]);
$ph = $stmt->fetch();
if (!$ph) chuyenHuong('phan-hoi-cua-toi.php');

// Xử lý đánh giá sao
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['danh_gia'])) {
    $diem = intval($_POST['diem_so'] ?? 0);
    $nxet = trim($_POST['nhan_xet'] ?? '');
    if ($diem >= 1 && $diem <= 5 && $ph['trang_thai'] === 'da_xu_ly') {
        $chk = $pdo->prepare("SELECT id FROM danh_gia WHERE phan_hoi_id = ? AND nguoi_danh_gia_id = ?");
        $chk->execute([$id, $userId]);
        if (!$chk->fetch()) {
            $pdo->prepare("INSERT INTO danh_gia (phan_hoi_id, nguoi_danh_gia_id, diem_so, nhan_xet) VALUES (?,?,?,?)")
                ->execute([$id, $userId, $diem, $nxet]);
            flashMessage('success', 'Cảm ơn bạn đã đánh giá!');
        }
        chuyenHuong("chi-tiet.php?id=$id");
    }
}

// Lấy trả lời chính thức
$traLois = $pdo->prepare("
    SELECT tl.*, u.ho_ten, u.vai_tro, dv.ten_don_vi
    FROM tra_loi tl
    JOIN users u ON tl.nguoi_tra_loi_id = u.id
    LEFT JOIN don_vi dv ON u.don_vi_id = dv.id
    WHERE tl.phan_hoi_id = ? AND tl.loai = 'chinh_thuc' AND tl.trang_thai_duyet = 'da_duyet'
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

// File đính kèm
$dinhKems = layDinhKem($id);

// Đánh giá
$danhGia = $pdo->prepare("SELECT * FROM danh_gia WHERE phan_hoi_id = ? AND nguoi_danh_gia_id = ?");
$danhGia->execute([$id, $userId]);
$danhGia = $danhGia->fetch();

$pageTitle  = 'Chi tiết phản hồi #' . $id;
$activeMenu = 'lich_su';
$success    = flashMessage('success');
include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dhv"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div>
<?php endif; ?>

<div class="d-flex align-items-center gap-2 mb-3">
  <a href="phan-hoi-cua-toi.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Quay lại</a>
  <span class="text-muted small">/ Phản hồi <?= e($ph['ma_phan_hoi'] ?? '#'.$id) ?></span>
</div>

<div class="row g-3">
  <div class="col-lg-8">

    <!-- NỘI DUNG PHẢN HỒI -->
    <div class="card-dhv p-4 mb-3">
      <div class="d-flex gap-2 flex-wrap mb-3">
        <span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?> px-3 py-2">
          <i class="<?= iconTrangThai($ph['trang_thai']) ?> me-1"></i><?= nhanTrangThai($ph['trang_thai']) ?>
        </span>
        <?php if ($ph['ten_chu_de']): ?>
        <span class="badge bg-light text-dark border"><i class="<?= $ph['icon'] ?> me-1"></i><?= e($ph['ten_chu_de']) ?></span>
        <?php endif; ?>
        <?php if ($ph['ten_loai']): ?>
        <span class="badge bg-light text-dark border"><?= e($ph['ten_loai']) ?></span>
        <?php endif; ?>
        <span class="badge bg-<?= mauMucDo($ph['muc_do_uu_tien']) ?>"><?= nhanMucDo($ph['muc_do_uu_tien']) ?></span>
      </div>
      <h4 class="fw-800 mb-3 text-primary"><?= e($ph['tieu_de']) ?></h4>
      <div class="feedback-content-box p-4 mb-4" style="background: #f8fafc; border-left: 4px solid var(--primary); border-radius: 4px 12px 12px 4px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02); font-size: 1.05rem; color: #334155; line-height: 1.8; position: relative;">
        <div class="quote-icon" style="position: absolute; right: 20px; top: 15px; font-size: 2.5rem; color: rgba(0, 48, 135, 0.05); pointer-events: none;"><i class="fas fa-quote-right"></i></div>
        <div style="white-space: pre-wrap; font-weight: 500;"><?= trim(e($ph['noi_dung'])) ?></div>
      </div>
      <div class="small text-muted d-flex gap-3 flex-wrap">
        <span><i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($ph['created_at'])) ?></span>
        <?php if ($ph['ten_can_bo']): ?>
        <span><i class="fas fa-user-tie me-1"></i>Cán bộ: <?= e($ph['ten_can_bo']) ?></span>
        <?php endif; ?>
        <?php if ($ph['ten_don_vi']): ?>
        <span><i class="fas fa-building me-1"></i><?= e($ph['ten_don_vi']) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- FILE ĐÍNH KÈM -->
    <?php if (!empty($dinhKems)): ?>
    <div class="card-dhv p-3 mb-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-paperclip me-2 text-primary"></i>File đính kèm (<?= count($dinhKems) ?>)</h6>
      <div class="row g-2">
        <?php foreach ($dinhKems as $dk): ?>
        <div class="col-12 col-md-6">
          <a href="<?= UPLOAD_URL . $dk['duong_dan'] ?>" target="_blank"
             class="d-flex align-items-center gap-2 p-2 border rounded text-decoration-none text-dark small">
            <i class="fas fa-file-<?= in_array($dk['loai_file'], ['pdf']) ? 'pdf text-danger' : (in_array($dk['loai_file'], ['doc','docx']) ? 'word text-primary' : 'image text-success') ?> fa-lg"></i>
            <div class="overflow-hidden">
              <div class="text-truncate fw-600"><?= e($dk['ten_goc']) ?></div>
              <div class="text-muted"><?= round($dk['kich_thuoc']/1024, 1) ?> KB</div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- CÂU TRẢ LỜI -->
    <?php if (!empty($traLois)): ?>
    <div class="card-dhv p-3 mb-3" style="border-left:4px solid #198754">
      <h6 class="fw-700 mb-3"><i class="fas fa-reply me-2 text-success"></i>Câu trả lời chính thức</h6>
      <?php foreach ($traLois as $tl): ?>
      <div class="bg-light rounded p-3 mb-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <strong><?= e($tl['ho_ten']) ?></strong>
            <?php if ($tl['ten_don_vi']): ?><span class="text-muted small"> – <?= e($tl['ten_don_vi']) ?></span><?php endif; ?>
          </div>
          <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($tl['created_at'])) ?></span>
        </div>
        <div style="white-space:pre-wrap;line-height:1.7"><?= e($tl['noi_dung']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ĐÁNH GIÁ SAO -->
    <?php if ($ph['trang_thai'] === 'da_xu_ly'): ?>
    <div class="card-dhv p-4 mb-3" style="border-top:4px solid #e8a000">
      <h6 class="fw-700 mb-3"><i class="fas fa-star text-warning me-2"></i>Đánh giá kết quả xử lý</h6>
      <?php if ($danhGia): ?>
        <div class="text-center py-2">
          <div class="mb-2">
            <?php for ($i=1;$i<=5;$i++): ?>
              <i class="fas fa-star fa-lg <?= $i<=$danhGia['diem_so'] ? 'text-warning' : 'text-muted' ?>"></i>
            <?php endfor; ?>
          </div>
          <div class="fw-600"><?= $danhGia['diem_so'] ?>/5 sao</div>
          <?php if ($danhGia['nhan_xet']): ?>
          <div class="text-muted small mt-1"><?= e($danhGia['nhan_xet']) ?></div>
          <?php endif; ?>
          <div class="text-success small mt-1"><i class="fas fa-check-circle me-1"></i>Đã đánh giá</div>
        </div>
      <?php else: ?>
        <form method="POST">
          <input type="hidden" name="danh_gia" value="1">
          <div class="text-center mb-3">
            <div class="star-rating mb-2" id="starRating">
              <?php for ($i=1;$i<=5;$i++): ?>
              <i class="far fa-star fa-2x text-warning me-1 star-btn" data-val="<?= $i ?>" style="cursor:pointer"></i>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="diem_so" id="diemSoInput" value="0">
            <div id="starLabel" class="text-muted small">Chọn số sao đánh giá</div>
          </div>
          <div class="mb-3">
            <textarea name="nhan_xet" class="form-control" rows="3" placeholder="Nhận xét (tùy chọn)..."></textarea>
          </div>
          <button type="submit" class="btn btn-warning fw-700 w-100">
            <i class="fas fa-star me-2"></i>Gửi đánh giá
          </button>
        </form>
        <script>
        const labels=['','Rất không hài lòng','Không hài lòng','Bình thường','Hài lòng','Rất hài lòng'];
        document.querySelectorAll('.star-btn').forEach(s=>{
          s.addEventListener('click',function(){
            const v=parseInt(this.dataset.val);
            document.getElementById('diemSoInput').value=v;
            document.getElementById('starLabel').textContent=labels[v];
            document.querySelectorAll('.star-btn').forEach((st,i)=>{
              st.className=`fa-star fa-2x text-warning me-1 star-btn ${i<v?'fas':'far'}`;
            });
          });
        });
        </script>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- CHỜ BỔ SUNG -->
    <?php if ($ph['trang_thai'] === 'cho_bo_sung'): ?>
    <div class="alert alert-warning">
      <i class="fas fa-exclamation-circle me-2"></i>
      <strong>Cần bổ sung thêm thông tin!</strong> Cán bộ xử lý yêu cầu bạn cung cấp thêm thông tin để giải quyết phản hồi này.
    </div>
    <?php endif; ?>

  </div>

  <!-- SIDEBAR -->
  <div class="col-lg-4">
    <!-- TRẠNG THÁI TIMELINE -->
    <div class="card-dhv p-3 mb-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-history me-2 text-primary"></i>Lịch sử xử lý</h6>
      <div class="timeline">
        <?php foreach ($lichSu as $ls): ?>
        <div class="d-flex gap-2 mb-3">
          <div class="d-flex flex-column align-items-center">
            <div class="rounded-circle bg-<?= mauTrangThai($ls['trang_thai_moi']) ?> d-flex align-items-center justify-content-center" style="width:28px;height:28px;min-width:28px">
              <i class="<?= iconTrangThai($ls['trang_thai_moi']) ?> text-white" style="font-size:.65rem"></i>
            </div>
          </div>
          <div class="small">
            <div class="fw-600"><?= nhanTrangThai($ls['trang_thai_moi']) ?></div>
            <?php if ($ls['ghi_chu']): ?>
            <div class="text-muted"><?= e($ls['ghi_chu']) ?></div>
            <?php endif; ?>
            <div class="text-muted" style="font-size:.72rem">
              <?= $ls['ho_ten'] ? e($ls['ho_ten']) : 'Hệ thống' ?>
              · <?= thoiGianTuongDoi($ls['created_at']) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- THÔNG TIN -->
    <div class="card-dhv p-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Thông tin</h6>
      <table class="table table-sm small mb-0">
        <tr><td class="text-muted">Mã</td><td><strong><?= e($ph['ma_phan_hoi'] ?? '#'.$id) ?></strong></td></tr>
        <tr><td class="text-muted">Ngày gửi</td><td><?= date('d/m/Y', strtotime($ph['created_at'])) ?></td></tr>
        <tr><td class="text-muted">Trạng thái</td>
            <td><span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?>"><?= nhanTrangThai($ph['trang_thai']) ?></span></td></tr>
        <?php if ($ph['han_xu_ly']): ?>
        <tr><td class="text-muted">Hạn xử lý</td>
            <td class="<?= strtotime($ph['han_xu_ly'])<time() && !in_array($ph['trang_thai'],['da_xu_ly','da_huy','tu_choi']) ? 'text-danger fw-bold' : '' ?>">
              <?= date('d/m/Y', strtotime($ph['han_xu_ly'])) ?>
            </td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
