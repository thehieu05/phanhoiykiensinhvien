<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$pageTitle  = 'Báo cáo & Thống kê';
$activeMenu = 'bao_cao';
global $pdo;

$tuNgay  = $_GET['tu_ngay'] ?? date('Y-01-01');
$denNgay = $_GET['den_ngay'] ?? date('Y-12-31');
$donViId = intval($_GET['don_vi_id'] ?? 0) ?: null;
$donVis  = layDanhSachDonVi();
$stats   = layThongKe($donViId, $tuNgay, $denNgay);

// Đánh giá sinh viên
$danhGias = $pdo->query("
    SELECT dg.diem_so, dg.nhan_xet, dg.created_at,
           u.ho_ten as ten_sv, u.ma_sv_gv,
           ph.tieu_de, ph.ma_phan_hoi,
           cb.ho_ten as ten_can_bo
    FROM danh_gia dg
    JOIN users u ON dg.nguoi_danh_gia_id=u.id
    JOIN phan_hoi ph ON dg.phan_hoi_id=ph.id
    LEFT JOIN users cb ON ph.can_bo_xu_ly_id=cb.id
    ORDER BY dg.created_at DESC LIMIT 20
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-chart-bar me-2"></i>Báo cáo & Thống kê</h2>
  <p class="mb-0">Thống kê toàn hệ thống</p>
</div>

<!-- BỘ LỌC -->
<div class="card-dhv p-3 mb-4">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small fw-600">Đơn vị</label>
      <select name="don_vi_id" class="form-select">
        <option value="">-- Toàn hệ thống --</option>
        <?php foreach ($donVis as $dv): ?>
        <option value="<?= $dv['id'] ?>" <?= $donViId==$dv['id']?'selected':'' ?>><?= e($dv['ten_don_vi']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-600">Từ ngày</label>
      <input type="date" name="tu_ngay" class="form-control" value="<?= e($tuNgay) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-600">Đến ngày</label>
      <input type="date" name="den_ngay" class="form-control" value="<?= e($denNgay) ?>">
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-dhv"><i class="fas fa-filter me-1"></i>Lọc</button>
      <a href="bao-cao.php" class="btn btn-outline-secondary ms-2">Reset</a>
    </div>
  </form>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['label'=>'Tổng','val'=>$stats['tong'],'icon'=>'fas fa-inbox','color'=>'blue'],
    ['label'=>'Chờ xử lý','val'=>$stats['cho_xu_ly'],'icon'=>'fas fa-clock','color'=>'orange'],
    ['label'=>'Đang xử lý','val'=>$stats['dang_xu_ly'],'icon'=>'fas fa-spinner','color'=>'teal'],
    ['label'=>'Đã xử lý','val'=>$stats['da_xu_ly'],'icon'=>'fas fa-check-circle','color'=>'green'],
    ['label'=>'Quá hạn','val'=>$stats['qua_han'],'icon'=>'fas fa-exclamation','color'=>'red'],
  ];
  foreach ($cards as $c):
  ?>
  <div class="col-6 col-md-4 col-xl">
    <div class="stat-card <?= $c['color'] ?>">
      <div class="icon"><i class="<?= $c['icon'] ?>"></i></div>
      <div><div class="label"><?= $c['label'] ?></div><div class="value"><?= $c['val'] ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card-dhv p-3 h-100">
      <h6 class="fw-700 mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Phản hồi theo tháng</h6>
      <canvas id="chartThang" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-dhv p-3 h-100">
      <h6 class="fw-700 mb-3"><i class="fas fa-chart-pie me-2 text-primary"></i>Theo trạng thái</h6>
      <canvas id="chartTT" height="200"></canvas>
    </div>
  </div>
</div>

<!-- THEO CHỦ ĐỀ -->
<div class="card-dhv p-3 mb-4">
  <h6 class="fw-700 mb-3"><i class="fas fa-tags me-2 text-primary"></i>Thống kê theo chủ đề</h6>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light"><tr><th>Chủ đề</th><th>Số lượng</th><th>Tỷ lệ</th></tr></thead>
      <tbody>
        <?php foreach ($stats['theo_chu_de'] as $cd): $pct = $stats['tong']>0 ? round($cd['so_luong']/$stats['tong']*100) : 0; ?>
        <tr>
          <td><i class="<?= e($cd['icon']??'fas fa-tag') ?> me-2 text-primary"></i><strong><?= e($cd['ten_chu_de']) ?></strong></td>
          <td><?= $cd['so_luong'] ?></td>
          <td style="min-width:150px">
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div></div>
              <span><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- THEO CÁN BỘ -->
<div class="card-dhv p-3 mb-4">
  <h6 class="fw-700 mb-3"><i class="fas fa-users me-2 text-primary"></i>Hiệu quả theo cán bộ</h6>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light"><tr><th>Cán bộ</th><th>Tổng</th><th>Đã XL</th><th>Tỷ lệ</th></tr></thead>
      <tbody>
        <?php foreach ($stats['theo_can_bo'] as $cb):
          $rate = $cb['tong']>0 ? round($cb['da_xu_ly']/$cb['tong']*100) : 0;
        ?>
        <tr>
          <td><strong><?= e($cb['ho_ten']) ?></strong></td>
          <td><?= $cb['tong'] ?></td>
          <td><?= $cb['da_xu_ly'] ?></td>
          <td><div class="progress" style="height:6px;min-width:80px"><div class="progress-bar bg-<?= $rate>=80?'success':($rate>=50?'warning':'danger') ?>" style="width:<?= $rate ?>%"></div></div><span class="ms-1"><?= $rate ?>%</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ĐÁNH GIÁ SINH VIÊN -->
<div class="card-dhv p-3" id="danh-gia-sv">
  <h6 class="fw-700 mb-3"><i class="fas fa-star text-warning me-2"></i>Đánh giá của sinh viên</h6>
  <?php if ($stats['danh_gia']['so_danh_gia'] > 0): ?>
  <div class="d-flex align-items-center gap-4 mb-4 p-3 bg-light rounded">
    <div class="text-center">
      <div class="fw-800" style="font-size:3rem;color:#ffc107"><?= number_format($stats['danh_gia']['trung_binh'],1) ?></div>
      <div class="text-muted small">/ 5 sao</div>
    </div>
    <div>
      <?php for ($i=1;$i<=5;$i++): ?><i class="fa<?= $i<=$stats['danh_gia']['trung_binh']?'s':'r' ?> fa-star fa-2x text-warning me-1"></i><?php endfor; ?>
      <div class="text-muted small mt-1"><?= $stats['danh_gia']['so_danh_gia'] ?> lượt đánh giá</div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (empty($danhGias)): ?>
  <p class="text-muted small">Chưa có đánh giá nào.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light"><tr><th>Phản hồi</th><th>Sinh viên</th><th>Cán bộ XL</th><th>Đánh giá</th><th>Nhận xét</th><th>Ngày</th></tr></thead>
      <tbody>
        <?php foreach ($danhGias as $dg): ?>
        <tr>
          <td><div class="fw-600" style="max-width:180px"><?= e(mb_substr($dg['tieu_de'],0,50)) ?></div><div class="text-muted"><?= e($dg['ma_phan_hoi']??'') ?></div></td>
          <td><?= e($dg['ten_sv']) ?><div class="text-muted"><?= e($dg['ma_sv_gv']??'') ?></div></td>
          <td><?= e($dg['ten_can_bo']??'–') ?></td>
          <td><?php for($i=1;$i<=5;$i++): ?><i class="fa<?= $i<=$dg['diem_so']?'s':'r' ?> fa-star text-warning"></i><?php endfor; ?><span class="ms-1"><?= $dg['diem_so'] ?>/5</span></td>
          <td class="text-muted"><?= $dg['nhan_xet'] ? e(mb_substr($dg['nhan_xet'],0,60)) : '–' ?></td>
          <td class="text-muted"><?= date('d/m/Y', strtotime($dg['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const thangData=<?= json_encode($stats['theo_thang']) ?>;
new Chart(document.getElementById('chartThang'),{type:'line',data:{labels:thangData.map(d=>d.thang),datasets:[{label:'Phản hồi',data:thangData.map(d=>d.so_luong),borderColor:'#003087',backgroundColor:'rgba(0,48,135,.08)',borderWidth:2.5,tension:.4,fill:true,pointBackgroundColor:'#003087',pointRadius:5}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});

const ttData=[
  {label:'Chờ xử lý',val:<?= $stats['cho_xu_ly'] ?>},
  {label:'Đang xử lý',val:<?= $stats['dang_xu_ly'] ?>},
  {label:'Đã xử lý',val:<?= $stats['da_xu_ly'] ?>},
  {label:'Từ chối/Hủy',val:<?= $stats['tu_choi']+$stats['da_huy'] ?>},
];
new Chart(document.getElementById('chartTT'),{type:'doughnut',data:{labels:ttData.map(d=>d.label),datasets:[{data:ttData.map(d=>d.val),backgroundColor:['#ffc107','#0dcaf0','#198754','#dc3545'],borderWidth:2}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
