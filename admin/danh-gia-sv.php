<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$pageTitle  = 'Đánh giá từ sinh viên';
$activeMenu = 'danh_gia_sv';
global $pdo;

$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page-1)*$perPage;

$search  = trim($_GET['search'] ?? '');
$diemSo  = intval($_GET['diem_so'] ?? 0);

$where = ['1=1']; $params = [];
if ($search) { $where[] = '(u.ho_ten LIKE ? OR ph.tieu_de LIKE ? OR cb.ho_ten LIKE ?)'; $kw='%'.$search.'%'; $params[]=$kw; $params[]=$kw; $params[]=$kw; }
if ($diemSo > 0) { $where[] = 'dg.diem_so = ?'; $params[] = $diemSo; }

$wStr = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM danh_gia dg JOIN users u ON dg.nguoi_danh_gia_id=u.id JOIN phan_hoi ph ON dg.phan_hoi_id=ph.id LEFT JOIN users cb ON ph.can_bo_xu_ly_id=cb.id WHERE $wStr");
$total->execute($params); $total=$total->fetchColumn();

$stmt = $pdo->prepare("
    SELECT dg.*, u.ho_ten as ten_sv, u.ma_sv_gv, u.lop,
           ph.tieu_de, ph.ma_phan_hoi,
           cb.ho_ten as ten_can_bo, dv.ten_don_vi
    FROM danh_gia dg
    JOIN users u ON dg.nguoi_danh_gia_id=u.id
    JOIN phan_hoi ph ON dg.phan_hoi_id=ph.id
    LEFT JOIN users cb ON ph.can_bo_xu_ly_id=cb.id
    LEFT JOIN don_vi dv ON ph.don_vi_xu_ly_id=dv.id
    WHERE $wStr
    ORDER BY dg.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$danhGias = $stmt->fetchAll();

// Tổng quan
$avg = $pdo->query("SELECT AVG(diem_so) FROM danh_gia")->fetchColumn() ?? 0;
$counts = [];
for($i=1;$i<=5;$i++){ $s=$pdo->prepare("SELECT COUNT(*) FROM danh_gia WHERE diem_so=?"); $s->execute([$i]); $counts[$i]=$s->fetchColumn(); }
$totalDG = array_sum($counts);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-star me-2"></i>Đánh giá từ Sinh viên</h2>
  <p class="mb-0">Xem và phân tích các đánh giá mức độ hài lòng về kết quả xử lý phản hồi</p>
</div>

<!-- TỔNG QUAN -->
<div class="card-dhv p-4 mb-4">
  <div class="row align-items-center">
    <div class="col-md-3 text-center border-end">
      <div class="fw-800" style="font-size:3.5rem;color:#ffc107;line-height:1"><?= number_format($avg, 1) ?></div>
      <div class="mb-2">
        <?php for($i=1;$i<=5;$i++): ?><i class="fa<?= $i<=$avg?'s':'r' ?> fa-star fa-lg text-warning"></i><?php endfor; ?>
      </div>
      <div class="text-muted small"><?= $totalDG ?> lượt đánh giá</div>
    </div>
    <div class="col-md-9 px-4">
      <?php for($i=5;$i>=1;$i--): $pct = $totalDG>0 ? round($counts[$i]/$totalDG*100) : 0; ?>
      <div class="d-flex align-items-center gap-3 mb-2">
        <div style="width:40px" class="small fw-600"><?= $i ?> <i class="fas fa-star text-warning"></i></div>
        <div class="progress flex-grow-1" style="height:8px"><div class="progress-bar bg-warning" style="width:<?= $pct ?>%"></div></div>
        <div style="width:40px" class="small text-muted text-end"><?= $counts[$i] ?></div>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- LỌC & DANH SÁCH -->
<div class="card-dhv p-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-700 mb-0">Danh sách chi tiết (<?= $total ?>)</h6>
    <form method="GET" class="d-flex gap-2">
      <select name="diem_so" class="form-select form-select-sm" style="width:140px">
        <option value="">-- Tất cả số sao --</option>
        <?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>" <?= $diemSo==$i?'selected':'' ?>><?= $i ?> sao</option><?php endfor; ?>
      </select>
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm SV, cán bộ, phản hồi..." value="<?= e($search) ?>" style="width:200px">
      <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
      <?php if ($search||$diemSo): ?><a href="danh-gia-sv.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a><?php endif; ?>
    </form>
  </div>

  <?php if (empty($danhGias)): ?>
  <div class="empty-state py-5 text-center"><p class="text-muted">Không tìm thấy đánh giá nào.</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light"><tr><th>Đánh giá</th><th>Sinh viên</th><th>Phản hồi</th><th>Người xử lý</th><th>Thời gian</th></tr></thead>
      <tbody>
        <?php foreach ($danhGias as $dg): ?>
        <tr>
          <td style="min-width:180px">
            <div class="mb-1 text-warning">
              <?php for($i=1;$i<=5;$i++): ?><i class="fa<?= $i<=$dg['diem_so']?'s':'r' ?> fa-star"></i><?php endfor; ?>
            </div>
            <?php if ($dg['nhan_xet']): ?><div class="text-muted" style="white-space:pre-wrap"><?= e($dg['nhan_xet']) ?></div><?php endif; ?>
          </td>
          <td>
            <div class="fw-600"><?= e($dg['ten_sv']) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= e($dg['ma_sv_gv']??'') ?> <?= $dg['lop']?" - ".e($dg['lop']):'' ?></div>
          </td>
          <td>
            <a href="phan-hoi-chi-tiet.php?id=<?= $dg['phan_hoi_id'] ?>" class="text-decoration-none fw-600"><?= e(mb_substr($dg['tieu_de'],0,50)) ?>...</a>
            <div class="text-muted mt-1" style="font-size:.72rem"><?= e($dg['ma_phan_hoi']) ?></div>
          </td>
          <td>
            <div class="fw-600"><?= e($dg['ten_can_bo']??'–') ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= e($dg['ten_don_vi']??'') ?></div>
          </td>
          <td class="text-muted"><?= date('d/m/Y H:i', strtotime($dg['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-3"><?= phanTrang(ceil($total/$perPage), $page, '?'.http_build_query(array_filter(['search'=>$search,'diem_so'=>$diemSo?:null]))) ?></div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
