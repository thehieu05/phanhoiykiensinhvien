<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('truong_don_vi');

$pageTitle  = 'Thống kê';
$activeMenu = 'thong_ke';
$tdvId      = $_SESSION['user_id'];
$userInfo   = layThongTinUser($tdvId);
$donViId    = $userInfo['don_vi_id'];
global $pdo;

$tuNgay  = $_GET['tu_ngay'] ?? date('Y-01-01');
$denNgay = $_GET['den_ngay'] ?? date('Y-12-31');

$stats = layThongKe($donViId, $tuNgay, $denNgay);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-chart-pie me-2"></i>Thống kê xử lý</h2>
  <p class="mb-0">Đơn vị: <strong><?= e($userInfo['ten_don_vi'] ?? $userInfo['khoa'] ?? '') ?></strong></p>
</div>

<!-- LỌC THỜI GIAN -->
<div class="card-dhv p-3 mb-4">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small fw-600">Từ ngày</label>
      <input type="date" name="tu_ngay" class="form-control" value="<?= e($tuNgay) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-600">Đến ngày</label>
      <input type="date" name="den_ngay" class="form-control" value="<?= e($denNgay) ?>">
    </div>
    <div class="col-md-4">
      <button type="submit" class="btn btn-dhv"><i class="fas fa-filter me-1"></i>Lọc</button>
      <a href="thong-ke.php" class="btn btn-outline-secondary ms-2">Reset</a>
    </div>
  </form>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'fas fa-inbox','label'=>'Tổng phản hồi','val'=>$stats['tong'],'color'=>'#003087'],
    ['icon'=>'fas fa-clock','label'=>'Chờ xử lý','val'=>$stats['cho_xu_ly'],'color'=>'#ffc107'],
    ['icon'=>'fas fa-spinner','label'=>'Đang xử lý','val'=>$stats['dang_xu_ly'],'color'=>'#0dcaf0'],
    ['icon'=>'fas fa-check-circle','label'=>'Đã xử lý','val'=>$stats['da_xu_ly'],'color'=>'#198754'],
    ['icon'=>'fas fa-exclamation-triangle','label'=>'Quá hạn','val'=>$stats['qua_han'],'color'=>'#dc3545'],
    ['icon'=>'fas fa-ban','label'=>'Đã hủy/Từ chối','val'=>($stats['da_huy']+$stats['tu_choi']),'color'=>'#6c757d'],
  ];
  foreach ($cards as $c):
  ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card-dhv p-3 text-center h-100">
      <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:<?= $c['color'] ?>22">
        <i class="<?= $c['icon'] ?>" style="color:<?= $c['color'] ?>"></i>
      </div>
      <div class="fw-800 fs-4" style="color:<?= $c['color'] ?>"><?= $c['val'] ?></div>
      <div class="text-muted small"><?= $c['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <!-- BIỂU ĐỒ THEO THÁNG -->
  <div class="col-lg-8">
    <div class="card-dhv p-3 h-100">
      <h6 class="fw-700 mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Phản hồi theo tháng</h6>
      <canvas id="chartThang" height="120"></canvas>
    </div>
  </div>
  <!-- BIỂU ĐỒ THEO CHỦ ĐỀ -->
  <div class="col-lg-4">
    <div class="card-dhv p-3 h-100">
      <h6 class="fw-700 mb-3"><i class="fas fa-chart-pie me-2 text-primary"></i>Theo chủ đề</h6>
      <canvas id="chartChuDe" height="200"></canvas>
    </div>
  </div>
</div>

<!-- THỐNG KÊ THEO CÁN BỘ -->
<div class="card-dhv p-3 mb-4">
  <h6 class="fw-700 mb-3"><i class="fas fa-users me-2 text-primary"></i>Hiệu quả xử lý theo cán bộ</h6>
  <?php if (empty($stats['theo_can_bo'])): ?>
  <p class="text-muted small">Chưa có dữ liệu.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle small mb-0">
      <thead class="table-light">
        <tr><th>Cán bộ</th><th>Tổng được giao</th><th>Đã xử lý</th><th>Tỷ lệ</th><th>Tiến độ</th></tr>
      </thead>
      <tbody>
        <?php foreach ($stats['theo_can_bo'] as $cb):
          $rate = $cb['tong'] > 0 ? round($cb['da_xu_ly']/$cb['tong']*100) : 0;
        ?>
        <tr>
          <td><strong><?= e($cb['ho_ten']) ?></strong></td>
          <td><?= $cb['tong'] ?></td>
          <td><?= $cb['da_xu_ly'] ?></td>
          <td><?= $rate ?>%</td>
          <td style="min-width:120px">
            <div class="progress" style="height:8px">
              <div class="progress-bar bg-<?= $rate>=80?'success':($rate>=50?'warning':'danger') ?>" style="width:<?= $rate ?>%"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ĐÁNH GIÁ TRUNG BÌNH -->
<?php if ($stats['danh_gia']['so_danh_gia'] > 0): ?>
<div class="card-dhv p-3">
  <h6 class="fw-700 mb-3"><i class="fas fa-star text-warning me-2"></i>Mức độ hài lòng trung bình</h6>
  <div class="d-flex align-items-center gap-3">
    <div class="text-center">
      <div class="fw-800" style="font-size:3rem;color:#ffc107"><?= number_format($stats['danh_gia']['trung_binh'],1) ?></div>
      <div class="text-muted small">/ 5 sao</div>
    </div>
    <div>
      <?php for($i=1;$i<=5;$i++): ?>
      <i class="fa<?= $i<=$stats['danh_gia']['trung_binh']?'s':'r' ?> fa-star fa-2x text-warning"></i>
      <?php endfor; ?>
      <div class="text-muted small mt-1"><?= $stats['danh_gia']['so_danh_gia'] ?> lượt đánh giá</div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const thangData=<?= json_encode($stats['theo_thang']) ?>;
new Chart(document.getElementById('chartThang'),{
  type:'line',
  data:{labels:thangData.map(d=>d.thang),datasets:[{label:'Phản hồi',data:thangData.map(d=>d.so_luong),borderColor:'#003087',backgroundColor:'rgba(0,48,135,.08)',borderWidth:2.5,tension:.4,fill:true,pointBackgroundColor:'#003087',pointRadius:5}]},
  options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
});
const dmData=<?= json_encode($stats['theo_chu_de']) ?>;
new Chart(document.getElementById('chartChuDe'),{
  type:'doughnut',
  data:{labels:dmData.map(d=>d.ten_chu_de),datasets:[{data:dmData.map(d=>d.so_luong),backgroundColor:['#003087','#e8a000','#198754','#0dcaf0','#dc3545','#6c757d','#6f42c1','#fd7e14'],borderWidth:2}]},
  options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{size:10}}}}}
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
