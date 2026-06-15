<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('can_bo');

$pageTitle  = 'Lịch sử xử lý';
$activeMenu = 'lich_su';
$canBoId    = $_SESSION['user_id'];
global $pdo;

$page   = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$from   = $_GET['tu_ngay'] ?? '';
$to     = $_GET['den_ngay'] ?? '';

$filters = ['can_bo_id'=>$canBoId, 'trang_thai'=>'da_xu_ly'];
if ($search) $filters['search'] = $search;
if ($from)   $filters['tu_ngay'] = $from;
if ($to)     $filters['den_ngay'] = $to;

$result   = layDanhSachPhanHoi($filters, $page, 15);
$phanHois = $result['data'];

// Tổng thống kê bản thân
$sTong = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE can_bo_xu_ly_id = ?"); $sTong->execute([$canBoId]);
$sDaXL = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE can_bo_xu_ly_id = ? AND trang_thai='da_xu_ly'"); $sDaXL->execute([$canBoId]);
$sTB   = $pdo->prepare("SELECT AVG(dg.diem_so) FROM danh_gia dg JOIN phan_hoi ph ON dg.phan_hoi_id=ph.id WHERE ph.can_bo_xu_ly_id=?"); $sTB->execute([$canBoId]);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-history me-2"></i>Lịch sử xử lý</h2>
  <p class="mb-0">Các phản hồi bạn đã xử lý hoàn tất</p>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card-dhv p-3 text-center">
      <div class="fw-800 fs-2 text-primary"><?= $sTong->fetchColumn() ?></div>
      <div class="text-muted small">Tổng được giao</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card-dhv p-3 text-center">
      <div class="fw-800 fs-2 text-success"><?= $sDaXL->fetchColumn() ?></div>
      <div class="text-muted small">Đã xử lý</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card-dhv p-3 text-center">
      <div class="fw-800 fs-2 text-warning"><?= number_format($sTB->fetchColumn() ?? 0, 1) ?> ⭐</div>
      <div class="text-muted small">Điểm đánh giá TB</div>
    </div>
  </div>
</div>

<div class="card-dhv p-3 mb-3">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Tìm tiêu đề..." value="<?= e($search) ?>"></div>
    <div class="col-md-3"><input type="date" name="tu_ngay" class="form-control" value="<?= e($from) ?>" placeholder="Từ ngày"></div>
    <div class="col-md-3"><input type="date" name="den_ngay" class="form-control" value="<?= e($to) ?>" placeholder="Đến ngày"></div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-dhv flex-grow-1"><i class="fas fa-search"></i></button>
      <a href="lich-su.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
    </div>
  </form>
</div>

<div class="card-dhv p-3">
  <?php if (empty($phanHois)): ?>
  <div class="empty-state py-5 text-center"><i class="fas fa-clipboard-check fa-3x text-muted mb-3 d-block"></i><p class="text-muted">Chưa có phản hồi nào được xử lý.</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light">
        <tr><th>Mã</th><th>Tiêu đề</th><th>Chủ đề</th><th>Kết quả</th><th>Đánh giá</th><th>Ngày XL</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($phanHois as $ph):
          $dg = $pdo->prepare("SELECT diem_so FROM danh_gia WHERE phan_hoi_id=?"); $dg->execute([$ph['id']]); $dg=$dg->fetch();
        ?>
        <tr>
          <td class="text-muted"><?= e($ph['ma_phan_hoi']??'#'.$ph['id']) ?></td>
          <td>
            <div class="fw-600" style="max-width:250px"><?= e(mb_substr($ph['tieu_de'],0,60)) ?></div>
            <div class="text-muted"><?= $ph['an_danh']?'<i>Ẩn danh</i>':e($ph['ten_nguoi_gui']??'') ?></div>
          </td>
          <td><?php if ($ph['ten_chu_de']): ?><span class="badge bg-light text-dark border"><?= e($ph['ten_chu_de']) ?></span><?php endif; ?></td>
          <td><span class="badge bg-success">✅ Đã xử lý</span></td>
          <td>
            <?php if ($dg): ?>
              <?php for($i=1;$i<=5;$i++): ?><i class="fa<?= $i<=$dg['diem_so']?'s':'r' ?> fa-star text-warning" style="font-size:.75rem"></i><?php endfor; ?>
            <?php else: ?><span class="text-muted small">Chưa đánh giá</span><?php endif; ?>
          </td>
          <td class="text-muted"><?= date('d/m/Y', strtotime($ph['updated_at'])) ?></td>
          <td><a href="xuly.php?id=<?= $ph['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-3"><?= phanTrang($result['pages'], $result['page'], '?'.http_build_query(array_filter(['search'=>$search,'tu_ngay'=>$from,'den_ngay'=>$to]))) ?></div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
