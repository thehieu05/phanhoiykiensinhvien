<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('truong_don_vi');

$pageTitle  = 'Lịch sử phân công';
$activeMenu = 'ls_phan_cong';
$tdvId      = $_SESSION['user_id'];
global $pdo;

$page   = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 15;
$offset  = ($page-1)*$perPage;

$where = ['lpc.nguoi_phan_cong_id = ?']; $params = [$tdvId];
if ($search) { $where[] = '(ph.tieu_de LIKE ? OR u.ho_ten LIKE ?)'; $kw='%'.$search.'%'; $params[]=$kw; $params[]=$kw; }

$wStr = implode(' AND ', $where);
$total = $pdo->prepare("SELECT COUNT(*) FROM lich_su_phan_cong lpc JOIN phan_hoi ph ON lpc.phan_hoi_id=ph.id JOIN users u ON lpc.can_bo_id=u.id WHERE $wStr");
$total->execute($params); $total=$total->fetchColumn();

$stmt = $pdo->prepare("
    SELECT lpc.*, ph.tieu_de, ph.trang_thai, ph.ma_phan_hoi,
           u.ho_ten as ten_can_bo,
           cd.ten_chu_de
    FROM lich_su_phan_cong lpc
    JOIN phan_hoi ph ON lpc.phan_hoi_id = ph.id
    JOIN users u ON lpc.can_bo_id = u.id
    LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
    WHERE $wStr
    ORDER BY lpc.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$records = $stmt->fetchAll();

// --- XỬ LÝ XEM CHI TIẾT PHÂN CÔNG ---
$selectedLPC = intval($_GET['id'] ?? 0);
$detailLPC = null;
$dinhKemsPhanHoi = [];
$dinhKemsAll = [];
$lichSu = [];
$traLois = [];

if ($selectedLPC) {
    $detailStmt = $pdo->prepare("
        SELECT lpc.*, ph.tieu_de, ph.noi_dung, ph.trang_thai, ph.ma_phan_hoi, ph.muc_do_uu_tien, ph.an_danh, ph.id as phan_hoi_id,
               u.ho_ten as ten_can_bo,
               cd.ten_chu_de, lph.ten_loai,
               sv.ho_ten as ten_sinh_vien, sv.lop as lop_sinh_vien, sv.ma_sv_gv as mssv_sinh_vien
        FROM lich_su_phan_cong lpc
        JOIN phan_hoi ph ON lpc.phan_hoi_id = ph.id
        JOIN users u ON lpc.can_bo_id = u.id
        LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
        LEFT JOIN loai_phan_hoi lph ON ph.loai_phan_hoi_id = lph.id
        LEFT JOIN users sv ON ph.nguoi_gui_id = sv.id
        WHERE lpc.id = ? AND lpc.nguoi_phan_cong_id = ?
    ");
    $detailStmt->execute([$selectedLPC, $tdvId]);
    $detailLPC = $detailStmt->fetch();
    
    if ($detailLPC) {
        // Files đính kèm
        $allDinhKems = $pdo->prepare("SELECT * FROM dinh_kem WHERE phan_hoi_id = ? ORDER BY created_at");
        $allDinhKems->execute([$detailLPC['phan_hoi_id']]);
        $dinhKemsAll = $allDinhKems->fetchAll();
        $dinhKemsPhanHoi = array_filter($dinhKemsAll, fn($dk) => is_null($dk['tra_loi_id']));
        
        // Lịch sử trao đổi / Trả lời cán bộ
        $traLoiStmt = $pdo->prepare("
            SELECT tl.*, u.ho_ten, u.vai_tro 
            FROM tra_loi tl 
            JOIN users u ON tl.nguoi_tra_loi_id = u.id 
            WHERE tl.phan_hoi_id = ? 
            ORDER BY tl.created_at ASC
        ");
        $traLoiStmt->execute([$detailLPC['phan_hoi_id']]);
        $traLois = $traLoiStmt->fetchAll();
        
        // Lịch sử trạng thái (Timeline)
        $lsStmt = $pdo->prepare("
            SELECT ls.*, u.ho_ten
            FROM lich_su_trang_thai ls
            LEFT JOIN users u ON ls.nguoi_thay_doi_id = u.id
            WHERE ls.phan_hoi_id = ?
            ORDER BY ls.created_at ASC
        ");
        $lsStmt->execute([$detailLPC['phan_hoi_id']]);
        $lichSu = $lsStmt->fetchAll();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-history me-2"></i>Lịch sử phân công</h2>
  <p class="mb-0">Danh sách toàn bộ lịch sử phân công xử lý phản hồi</p>
</div>

<div class="row g-3">
  <!-- DANH SÁCH LỊCH SỬ PHÂN CÔNG -->
  <div class="<?= $detailLPC ? 'col-lg-7' : 'col-lg-12' ?>">
    <div class="card-dhv p-3 mb-3">
      <form method="GET" class="row g-2 align-items-end">
        <?php if ($selectedLPC): ?>
          <input type="hidden" name="id" value="<?= $selectedLPC ?>">
        <?php endif; ?>
        <div class="col-md-8">
          <input type="text" name="search" class="form-control" placeholder="Tìm theo tiêu đề hoặc tên cán bộ..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button type="submit" class="btn btn-dhv flex-grow-1"><i class="fas fa-search me-1"></i>Tìm kiếm</button>
          <a href="lich-su-phan-cong.php" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>

    <div class="card-dhv p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="small text-muted">Tổng: <strong><?= $total ?></strong> lần phân công</span>
        <a href="phancong.php" class="btn btn-dhv btn-sm"><i class="fas fa-plus me-1"></i>Phân công mới</a>
      </div>

      <?php if (empty($records)): ?>
      <div class="empty-state py-5 text-center">
        <i class="fas fa-history fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted">Chưa có lịch sử phân công.</p>
      </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="table-light">
            <tr>
              <th>Phản hồi</th>
              <th>Người được giao</th>
              <?php if (!$detailLPC): ?>
                <th>Ghi chú</th>
              <?php endif; ?>
              <th>Hạn xử lý</th>
              <th>Trạng thái HT</th>
              <th>Thời gian giao</th>
              <th class="text-center">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r): ?>
            <tr style="cursor:pointer" onclick="window.location.href='?id=<?= $r['id'] ?>&search=<?= urlencode($search) ?>&page=<?= $page ?>'" class="<?= $selectedLPC == $r['id'] ? 'table-primary fw-bold' : '' ?>">
              <td>
                <div class="fw-600"><?= e(mb_substr($r['tieu_de'], 0, $detailLPC ? 35 : 55)) ?><?= mb_strlen($r['tieu_de']) > ($detailLPC ? 35 : 55) ? '...' : '' ?></div>
                <div class="text-muted" style="font-size:.72rem"><?= e($r['ma_phan_hoi']??'') ?><?php if ($r['ten_chu_de']): ?> · <?= e($r['ten_chu_de']) ?><?php endif; ?></div>
              </td>
              <td><strong><?= e($r['ten_can_bo']) ?></strong></td>
              <?php if (!$detailLPC): ?>
                <td class="text-muted"><?= $r['ghi_chu'] ? e(mb_substr($r['ghi_chu'], 0, 60)) : '–' ?></td>
              <?php endif; ?>
              <td class="<?= ($r['han_xu_ly']&&strtotime($r['han_xu_ly'])<time()&&!in_array($r['trang_thai'],['da_xu_ly','da_huy','tu_choi']))?'text-danger fw-bold':'' ?>">
                <?= $r['han_xu_ly'] ? date('d/m/Y', strtotime($r['han_xu_ly'])) : '–' ?>
              </td>
              <td><span class="badge bg-<?= mauTrangThai($r['trang_thai']) ?>"><?= nhanTrangThai($r['trang_thai']) ?></span></td>
              <td class="text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
              <td class="text-center">
                <a href="?id=<?= $r['id'] ?>&search=<?= urlencode($search) ?>&page=<?= $page ?>" class="btn btn-xs btn-outline-primary" onclick="event.stopPropagation();" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-3">
        <?= phanTrang(ceil($total/$perPage), $page, '?'.http_build_query(array_filter(['search'=>$search, 'id'=>$selectedLPC]))) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- CHI TIẾT PHÂN CÔNG (HIỂN THỊ CỘT BÊN PHẢI) -->
  <?php if ($detailLPC): ?>
  <div class="col-lg-5">
    <div class="card-dhv p-4 mb-3" style="border-top:4px solid var(--primary)">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <h5 class="fw-700 mb-1 text-primary"><i class="fas fa-info-circle me-1"></i>Chi tiết phân công</h5>
          <div class="small text-muted">Mã phản hồi: <strong><?= e($detailLPC['ma_phan_hoi'] ?? '#'.$detailLPC['phan_hoi_id']) ?></strong></div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="xemChiTietPhanHoi(<?= $detailLPC['phan_hoi_id'] ?>)" title="Xem chi tiết phản hồi">
            <i class="fas fa-eye me-1"></i>Chi tiết
          </button>
          <a href="?search=<?= urlencode($search) ?>&page=<?= $page ?>" class="btn-close" title="Đóng chi tiết"></a>
        </div>
      </div>

      <!-- 1. THÔNG TIN PHẢN HỒI -->
      <div class="mb-4">
        <h6 class="fw-700 border-bottom pb-2 mb-2 text-secondary"><i class="fas fa-comment-alt me-2"></i>Thông tin phản hồi</h6>
        
        <div class="d-flex gap-2 flex-wrap mb-2">
          <span class="badge bg-<?= mauTrangThai($detailLPC['trang_thai']) ?>"><?= nhanTrangThai($detailLPC['trang_thai']) ?></span>
          <span class="badge bg-light text-dark border"><?= e($detailLPC['ten_chu_de']) ?></span>
          <?php if ($detailLPC['ten_loai']): ?>
            <span class="badge bg-light text-dark border"><?= e($detailLPC['ten_loai']) ?></span>
          <?php endif; ?>
          <span class="badge bg-<?= mauMucDo($detailLPC['muc_do_uu_tien']) ?>"><?= nhanMucDo($detailLPC['muc_do_uu_tien']) ?></span>
        </div>

        <div class="fw-700 mb-1"><?= e($detailLPC['tieu_de']) ?></div>
        <div class="bg-light rounded p-3 mb-2 small" style="max-height:200px; overflow-y:auto; white-space:pre-wrap; line-height:1.6; border-left:3px solid var(--primary)">
          <?= trim(e($detailLPC['noi_dung'])) ?>
        </div>

        <!-- File đính kèm phản hồi -->
        <?php if (!empty($dinhKemsPhanHoi)): ?>
        <div class="mb-2">
          <div class="small fw-600 text-muted mb-1"><i class="fas fa-paperclip me-1"></i>File đính kèm:</div>
          <div class="d-flex flex-wrap gap-1">
            <?php foreach ($dinhKemsPhanHoi as $dk): ?>
            <a href="<?= UPLOAD_URL.$dk['duong_dan'] ?>" target="_blank" class="badge bg-light text-dark border me-1 text-decoration-none">
              <i class="fas fa-file me-1"></i><?= e($dk['ten_goc']) ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="small text-muted mt-2">
          <strong>Người gửi:</strong> 
          <?php if ($detailLPC['an_danh']): ?>
            <span class="text-muted"><i class="fas fa-user-secret me-1"></i>Ẩn danh</span>
          <?php else: ?>
            <?= e($detailLPC['ten_sinh_vien']) ?> (MSSV: <?= e($detailLPC['mssv_sinh_vien']) ?>, Lớp: <?= e($detailLPC['lop_sinh_vien']) ?>)
          <?php endif; ?>
        </div>
      </div>

      <!-- 2. CHI TIẾT GIAO VIỆC -->
      <div class="mb-4">
        <h6 class="fw-700 border-bottom pb-2 mb-2 text-secondary"><i class="fas fa-user-check me-2"></i>Thông tin giao việc</h6>
        <table class="table table-sm small mb-0 table-borderless">
          <tr>
            <td class="text-muted" style="width:120px">Cán bộ xử lý:</td>
            <td><strong><?= e($detailLPC['ten_can_bo']) ?></strong></td>
          </tr>
          <tr>
            <td class="text-muted">Thời gian giao:</td>
            <td><?= date('d/m/Y H:i', strtotime($detailLPC['created_at'])) ?></td>
          </tr>
          <tr>
            <td class="text-muted">Hạn xử lý:</td>
            <td class="<?= ($detailLPC['han_xu_ly']&&strtotime($detailLPC['han_xu_ly'])<time()&&!in_array($detailLPC['trang_thai'],['da_xu_ly','da_huy','tu_choi']))?'text-danger fw-bold':'' ?>">
              <?= $detailLPC['han_xu_ly'] ? date('d/m/Y', strtotime($detailLPC['han_xu_ly'])) : '–' ?>
            </td>
          </tr>
          <tr>
            <td class="text-muted" valign="top">Ghi chú giao việc:</td>
            <td style="white-space:pre-wrap"><?= $detailLPC['ghi_chu'] ? e($detailLPC['ghi_chu']) : '<em>Không có ghi chú</em>' ?></td>
          </tr>
        </table>
      </div>

      <!-- 3. LỊCH SỬ TRAO ĐỔI & TRẢ LỜI CỦA CÁN BỘ -->
      <div class="mb-4">
        <h6 class="fw-700 border-bottom pb-2 mb-2 text-secondary"><i class="fas fa-comments me-2"></i>Trao đổi & Câu trả lời (<?= count($traLois) ?>)</h6>
        <?php if (empty($traLois)): ?>
          <p class="text-muted small">Chưa có trao đổi hoặc câu trả lời nào.</p>
        <?php else: ?>
          <div class="d-flex flex-column gap-2" style="max-height:220px; overflow-y:auto">
            <?php foreach ($traLois as $tl): 
              $dkTraLoi = array_filter($dinhKemsAll, fn($dk)=>$dk['tra_loi_id']==$tl['id']);
            ?>
            <div class="p-2 border rounded bg-white small border-<?= $tl['trang_thai_duyet']==='tu_choi'?'danger':($tl['trang_thai_duyet']==='da_duyet'?'success':'warning') ?>">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <strong><?= e($tl['ho_ten']) ?></strong>
                <span class="text-muted" style="font-size:.7rem"><?= date('d/m H:i', strtotime($tl['created_at'])) ?></span>
              </div>
              <div class="mb-1" style="white-space:pre-wrap"><?= e($tl['noi_dung']) ?></div>
              
              <!-- File đính kèm của câu trả lời này -->
              <?php if (!empty($dkTraLoi)): ?>
              <div class="mb-1">
                <?php foreach ($dkTraLoi as $dk): ?>
                <a href="<?= UPLOAD_URL.$dk['duong_dan'] ?>" target="_blank" class="badge bg-light text-dark border me-1 text-decoration-none" style="font-size:.65rem">
                  <i class="fas fa-paperclip me-1"></i><?= e($dk['ten_goc']) ?>
                </a>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <?php if ($tl['trang_thai_duyet']==='cho_duyet'): ?>
                <span class="badge bg-warning text-dark" style="font-size:.65rem">⏳ Chờ duyệt</span>
              <?php elseif ($tl['trang_thai_duyet']==='da_duyet'): ?>
                <span class="badge bg-success" style="font-size:.65rem">✅ Đã duyệt & gửi SV</span>
              <?php elseif ($tl['trang_thai_duyet']==='tu_choi'): ?>
                <span class="badge bg-danger" style="font-size:.65rem">❌ Bị từ chối: <?= e($tl['ghi_chu_duyet']??'') ?></span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- 4. LỊCH SỬ XỬ LÝ (TIMELINE) -->
      <div>
        <h6 class="fw-700 border-bottom pb-2 mb-2 text-secondary"><i class="fas fa-history me-2"></i>Lịch sử xử lý</h6>
        <div class="timeline small" style="max-height: 180px; overflow-y: auto;">
          <?php foreach ($lichSu as $ls): ?>
          <div class="d-flex gap-2 mb-2">
            <div class="d-flex flex-column align-items-center">
              <div class="rounded-circle bg-<?= mauTrangThai($ls['trang_thai_moi']) ?> d-flex align-items-center justify-content-center" style="width:20px;height:20px;min-width:20px">
                <i class="<?= iconTrangThai($ls['trang_thai_moi']) ?> text-white" style="font-size:.55rem"></i>
              </div>
            </div>
            <div class="small">
              <div class="fw-600" style="font-size:.75rem"><?= nhanTrangThai($ls['trang_thai_moi']) ?></div>
              <?php if ($ls['ghi_chu']): ?>
                <div class="text-muted" style="font-size:.7rem"><?= e($ls['ghi_chu']) ?></div>
              <?php endif; ?>
              <div class="text-muted" style="font-size:.65rem">
                <?= $ls['ho_ten'] ? e($ls['ho_ten']) : 'Hệ thống' ?> · <?= date('d/m/Y H:i', strtotime($ls['created_at'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
