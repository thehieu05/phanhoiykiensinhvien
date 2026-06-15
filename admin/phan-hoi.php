<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$pageTitle  = 'Quản lý Phản hồi';
$activeMenu = 'phan_hoi';
global $pdo;

$trangThai = $_GET['trang_thai'] ?? '';
$chuDeId   = intval($_GET['chu_de_id'] ?? 0);
$donViId   = intval($_GET['don_vi_id'] ?? 0);
$search    = trim($_GET['search'] ?? '');
$tuNgay    = $_GET['tu_ngay'] ?? '';
$denNgay   = $_GET['den_ngay'] ?? '';
$page      = max(1, intval($_GET['page'] ?? 1));

$filters = [];
if ($trangThai) $filters['trang_thai'] = $trangThai;
if ($chuDeId)   $filters['chu_de_id']  = $chuDeId;
if ($donViId)   $filters['don_vi_id']  = $donViId;
if ($search)    $filters['search']     = $search;
if ($tuNgay)    $filters['tu_ngay']    = $tuNgay;
if ($denNgay)   $filters['den_ngay']   = $denNgay;

$result   = layDanhSachPhanHoi($filters, $page, 20);
$phanHois = $result['data'];
$chuDes   = layDanhSachChuDe(false);
$donVis   = layDanhSachDonVi();

// Counts
$counts = [];
foreach (['cho_xu_ly','da_tiep_nhan','dang_xu_ly','cho_bo_sung','da_xu_ly','da_huy','tu_choi','cho_duyet_tl'] as $ts) {
    $s=$pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE trang_thai=?"); $s->execute([$ts]); $counts[$ts]=$s->fetchColumn();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-comments me-2"></i>Quản lý Phản hồi</h2>
  <p class="mb-0">Tổng: <strong><?= $result['total'] ?></strong> phản hồi</p>
</div>

<!-- STATUS TABS -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <a href="phan-hoi.php" class="btn btn-sm <?= !$trangThai?'btn-dhv':'btn-outline-secondary' ?>">Tất cả (<?= array_sum($counts) ?>)</a>
  <?php 
  $displayCounts = [
      'cho_xu_ly'  => ($counts['cho_xu_ly']??0) + ($counts['da_tiep_nhan']??0),
      'dang_xu_ly' => ($counts['dang_xu_ly']??0) + ($counts['cho_bo_sung']??0) + ($counts['cho_duyet_tl']??0),
      'da_xu_ly'   => $counts['da_xu_ly']??0,
      'tu_choi'    => $counts['tu_choi']??0,
      'da_huy'     => $counts['da_huy']??0,
  ];
  foreach (['cho_xu_ly'=>'Chờ xử lý','dang_xu_ly'=>'Đang xử lý','da_xu_ly'=>'Đã xử lý','tu_choi'=>'Từ chối','da_huy'=>'Đã hủy'] as $ts=>$lb): ?>
  <a href="?trang_thai=<?= $ts ?>" class="btn btn-sm <?= $trangThai===$ts?'btn-dhv':'btn-outline-secondary' ?>">
    <?= $lb ?> (<?= $displayCounts[$ts]??0 ?>)
  </a>
  <?php endforeach; ?>
</div>

<!-- LỌC -->
<div class="card-dhv p-3 mb-3">
  <form method="GET">
    <?php if ($trangThai): ?><input type="hidden" name="trang_thai" value="<?= e($trangThai) ?>"><?php endif; ?>
    <div class="row g-2">
      <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm tiêu đề, mã, SV..." value="<?= e($search) ?>"></div>
      <div class="col-md-2">
        <select name="chu_de_id" class="form-select form-select-sm">
          <option value="">-- Chủ đề --</option>
          <?php foreach ($chuDes as $cd): ?><option value="<?= $cd['id'] ?>" <?= $chuDeId==$cd['id']?'selected':'' ?>><?= e($cd['ten_chu_de']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="don_vi_id" class="form-select form-select-sm">
          <option value="">-- Đơn vị --</option>
          <?php foreach ($donVis as $dv): ?><option value="<?= $dv['id'] ?>" <?= $donViId==$dv['id']?'selected':'' ?>><?= e($dv['ten_don_vi']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><input type="date" name="tu_ngay" class="form-control form-control-sm" value="<?= e($tuNgay) ?>" placeholder="Từ ngày"></div>
      <div class="col-md-2"><input type="date" name="den_ngay" class="form-control form-control-sm" value="<?= e($denNgay) ?>" placeholder="Đến ngày"></div>
      <div class="col-md-1 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-dhv"><i class="fas fa-search"></i></button>
        <a href="phan-hoi.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
      </div>
    </div>
  </form>
</div>

<!-- DANH SÁCH -->
<div class="card-dhv p-3">
  <?php if (empty($phanHois)): ?>
  <div class="empty-state py-5 text-center"><i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i><p class="text-muted">Không có phản hồi nào.</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light">
        <tr><th>Mã</th><th>Tiêu đề / Người gửi</th><th>Chủ đề</th><th>Đơn vị / Cán bộ</th><th>Trạng thái</th><th>Thời gian</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($phanHois as $ph): $quaHan=$ph['han_xu_ly']&&strtotime($ph['han_xu_ly'])<time()&&!in_array($ph['trang_thai'],['da_xu_ly','da_huy','tu_choi']); ?>
        <tr class="<?= $quaHan?'table-warning':'' ?>">
          <td class="fw-600 text-primary"><?= e($ph['ma_phan_hoi']??'#'.$ph['id']) ?></td>
          <td>
            <div class="fw-600" style="max-width:220px"><?= e(mb_substr($ph['tieu_de'],0,60)) ?></div>
            <div class="text-muted"><?= $ph['an_danh']?'<i class="fas fa-user-secret me-1"></i>Ẩn danh':e($ph['ten_nguoi_gui']??'') ?></div>
          </td>
          <td><?php if ($ph['ten_chu_de']): ?><span class="badge bg-light text-dark border"><i class="<?= $ph['icon'] ?> me-1"></i><?= e($ph['ten_chu_de']) ?></span><?php endif; ?>
              <?php if ($ph['ten_loai']): ?><div class="text-muted mt-1" style="font-size:.7rem"><?= e($ph['ten_loai']) ?></div><?php endif; ?></td>
          <td class="text-muted"><?= e($ph['ten_don_vi']??'–') ?><?php if ($ph['ten_can_bo']): ?><br><span><?= e($ph['ten_can_bo']) ?></span><?php endif; ?></td>
          <td>
            <span class="badge bg-<?= mauTrangThai($ph['trang_thai']) ?>"><?= nhanTrangThai($ph['trang_thai']) ?></span>
            <?php if ($quaHan): ?><br><span class="badge bg-danger mt-1">Quá hạn</span><?php endif; ?>
            <?php if ($ph['muc_do_uu_tien']==='khan_cap'): ?><br><span class="badge bg-danger mt-1">Khẩn cấp</span><?php endif; ?>
          </td>
          <td class="text-muted"><?= thoiGianTuongDoi($ph['created_at']) ?></td>
          <td><a href="phan-hoi-chi-tiet.php?id=<?= $ph['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-3"><?= phanTrang($result['pages'], $result['page'], '?'.http_build_query(array_filter(['trang_thai'=>$trangThai,'chu_de_id'=>$chuDeId?:null,'don_vi_id'=>$donViId?:null,'search'=>$search,'tu_ngay'=>$tuNgay,'den_ngay'=>$denNgay]))) ?></div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
