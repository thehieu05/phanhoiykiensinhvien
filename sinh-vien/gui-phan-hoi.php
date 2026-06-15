<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraDangNhap();
if ($_SESSION['vai_tro'] !== 'sinh_vien') chuyenHuong(SITE_URL . '/index.php');

$pageTitle  = 'Gửi phản hồi';
$activeMenu = 'gui_ph';
$userId     = $_SESSION['user_id'];
$errors     = [];

global $pdo;
$chuDes = layDanhSachChuDe();

// Xử lý gợi ý AJAX
if (isset($_GET['ajax_goi_y']) && !empty($_GET['q'])) {
    header('Content-Type: application/json');
    echo json_encode(goiYTuDong($_GET['q'], 3));
    exit;
}

// Lấy loại phản hồi theo chủ đề (AJAX)
if (isset($_GET['ajax_loai']) && !empty($_GET['chu_de_id'])) {
    header('Content-Type: application/json');
    echo json_encode(layLoaiTheoChuDe(intval($_GET['chu_de_id'])));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'tieu_de'          => trim($_POST['tieu_de'] ?? ''),
        'noi_dung'         => trim($_POST['noi_dung'] ?? ''),
        'chu_de_id'        => intval($_POST['chu_de_id'] ?? 0),
        'loai_phan_hoi_id' => intval($_POST['loai_phan_hoi_id'] ?? 0),
        'muc_do_uu_tien'   => $_POST['muc_do_uu_tien'] ?? 'trung_binh',
        'an_danh'          => isset($_POST['an_danh']) ? 1 : 0,
    ];

    if (empty($data['tieu_de']))                     $errors[] = 'Vui lòng nhập tiêu đề.';
    if (strlen($data['tieu_de']) < 10)               $errors[] = 'Tiêu đề phải có ít nhất 10 ký tự.';
    if (empty($data['noi_dung']))                    $errors[] = 'Vui lòng nhập nội dung.';
    if (strlen($data['noi_dung']) < 20)              $errors[] = 'Nội dung phải có ít nhất 20 ký tự.';
    if (!$data['chu_de_id'])                         $errors[] = 'Vui lòng chọn chủ đề.';

    if (empty($errors)) {
        $phanHoiId = guiPhanHoi($data, $userId);

        // Upload file đính kèm
        if (!empty($_FILES['dinh_kem']['name'][0])) {
            foreach ($_FILES['dinh_kem']['tmp_name'] as $k => $tmp) {
                if (!empty($tmp)) {
                    $f = [
                        'name'     => $_FILES['dinh_kem']['name'][$k],
                        'tmp_name' => $tmp,
                        'size'     => $_FILES['dinh_kem']['size'][$k],
                        'error'    => $_FILES['dinh_kem']['error'][$k],
                    ];
                    uploadFile($f, $phanHoiId);
                }
            }
        }

        flashMessage('success', 'Phản hồi đã được gửi thành công!');
        chuyenHuong("chi-tiet.php?id=$phanHoiId");
    }
}

$selectedChuDe = intval($_GET['chu_de'] ?? 0);
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-paper-plane me-2"></i>Gửi Phản hồi Ý kiến</h2>
  <p class="mb-0">Ý kiến của bạn giúp nhà trường cải thiện chất lượng đào tạo</p>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-3">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <ul class="mb-0 ps-3"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <!-- GỢI Ý TỰ ĐỘNG -->
    <div id="goiYBox" class="alert alert-info d-none mb-3">
      <div class="fw-700 mb-2"><i class="fas fa-lightbulb me-2"></i>Gợi ý câu trả lời tương tự</div>
      <div id="goiYList"></div>
    </div>

    <div class="card-dhv p-4">
      <form method="POST" enctype="multipart/form-data" id="formGuiPH">

        <!-- BƯỚC 1: Chọn chủ đề -->
        <div class="mb-4">
          <label class="form-label fw-700">1. Chọn chủ đề phản hồi <span class="text-danger">*</span></label>
          <div class="row g-2">
            <?php foreach ($chuDes as $cd): ?>
            <div class="col-6 col-md-4">
              <label class="d-block cursor-pointer h-100">
                <input type="radio" name="chu_de_id" value="<?= $cd['id'] ?>"
                       <?= ($selectedChuDe == $cd['id']) ? 'checked' : '' ?> class="d-none" required>
                <div class="category-option py-2 px-3 rounded border d-flex flex-column align-items-center justify-content-center h-100">
                  <i class="<?= $cd['icon'] ?> mb-2 text-primary fs-5"></i>
                  <div class="small fw-600 text-center"><?= e($cd['ten_chu_de']) ?></div>
                </div>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- BƯỚC 2: Chọn loại -->
        <div class="mb-3" id="loaiBox" style="display:none">
          <label class="form-label fw-700">2. Chọn loại phản hồi</label>
          <select name="loai_phan_hoi_id" id="loaiSelect" class="form-select">
            <option value="">-- Chọn loại (tùy chọn) --</option>
          </select>
        </div>

        <!-- TIÊU ĐỀ -->
        <div class="mb-3">
          <label class="form-label fw-700">3. Tiêu đề <span class="text-danger">*</span></label>
          <input type="text" name="tieu_de" id="tieuDeInput" class="form-control"
                 placeholder="Tóm tắt vấn đề (ít nhất 10 ký tự)"
                 value="<?= e($_POST['tieu_de'] ?? '') ?>" maxlength="255" required>
        </div>

        <!-- NỘI DUNG -->
        <div class="mb-3">
          <label class="form-label fw-700">4. Nội dung chi tiết <span class="text-danger">*</span></label>
          <textarea name="noi_dung" class="form-control" rows="6"
                    placeholder="Mô tả chi tiết vấn đề... (ít nhất 20 ký tự)" required><?= e($_POST['noi_dung'] ?? '') ?></textarea>
          <div class="form-text d-flex justify-content-between">
            <span>Càng chi tiết, chúng tôi càng hỗ trợ tốt hơn.</span>
            <span id="charCount">0 ký tự</span>
          </div>
        </div>

        <!-- ĐÍNH KÈM -->
        <div class="mb-3">
          <label class="form-label fw-700">5. Đính kèm file minh chứng</label>
          <input type="file" name="dinh_kem[]" class="form-control" multiple
                 accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
          <div class="form-text">Cho phép: JPG, PNG, PDF, DOC, DOCX, XLS (tối đa 5MB/file)</div>
        </div>

        <!-- MỨC ĐỘ -->
        <div class="mb-3">
          <label class="form-label fw-700">6. Mức độ ưu tiên</label>
          <select name="muc_do_uu_tien" class="form-select">
            <option value="thap">🟢 Thấp – Góp ý nhỏ, không gấp</option>
            <option value="trung_binh" selected>🟡 Trung bình – Cần xem xét</option>
            <option value="cao">🟠 Cao – Ảnh hưởng đến học tập</option>
            <option value="khan_cap">🔴 Khẩn cấp – Cần xử lý ngay</option>
          </select>
        </div>

        <!-- ẨN DANH -->
        <div class="mb-4 p-3 bg-light rounded border">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="an_danh" id="anDanh"
                   <?= isset($_POST['an_danh']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-600" for="anDanh">
              <i class="fas fa-user-secret me-2 text-secondary"></i>Gửi ẩn danh
            </label>
            <div class="text-muted small mt-1">Thông tin cá nhân sẽ không hiển thị. Bạn sẽ không nhận được email thông báo.</div>
          </div>
        </div>

        <div class="d-flex gap-3">
          <button type="submit" class="btn btn-dhv px-4 fw-700">
            <i class="fas fa-paper-plane me-2"></i>Gửi phản hồi
          </button>
          <a href="index.php" class="btn btn-outline-secondary">Hủy</a>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-dhv p-3 mb-3" style="border-top:4px solid var(--accent)">
      <h6 class="fw-700 mb-2"><i class="fas fa-lightbulb text-warning me-2"></i>Lưu ý</h6>
      <ul class="small text-muted mb-0">
        <li>Phản hồi được xem xét trong 3-5 ngày làm việc.</li>
        <li>Cung cấp thông tin cụ thể để được hỗ trợ tốt nhất.</li>
        <li>Tránh ngôn từ thiếu văn hóa.</li>
        <li>Bạn sẽ nhận email khi phản hồi được xử lý (nếu không ẩn danh).</li>
      </ul>
    </div>
    <div class="card-dhv p-3">
      <h6 class="fw-700 mb-2"><i class="fas fa-tags text-primary me-2"></i>Chủ đề phản hồi</h6>
      <?php foreach ($chuDes as $cd): ?>
      <a href="?chu_de=<?= $cd['id'] ?>" class="d-flex align-items-center gap-2 py-1 text-decoration-none text-dark small">
        <i class="<?= $cd['icon'] ?> text-primary" style="width:16px"></i>
        <?= e($cd['ten_chu_de']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
.category-option{cursor:pointer;border-color:#dee2e6!important;transition:.2s}
.category-option:hover{border-color:var(--primary)!important;background:#f0f4ff}
input[type="radio"]:checked+.category-option{border-color:var(--primary)!important;background:#e8f0fe}
</style>
<script>
const textarea=document.querySelector('textarea[name=noi_dung]'),counter=document.getElementById('charCount');
if(textarea) textarea.addEventListener('input',()=>counter.textContent=textarea.value.length+' ký tự');

// Radio chủ đề -> load loại
document.querySelectorAll('input[name=chu_de_id]').forEach(r=>{
  r.addEventListener('change',()=>{
    document.querySelectorAll('.category-option').forEach(el=>el.style.background='');
    r.nextElementSibling.style.background='#e8f0fe';
    loadLoai(r.value);
  });
  if(r.checked){ r.nextElementSibling.style.background='#e8f0fe'; loadLoai(r.value); }
});

function loadLoai(chuDeId){
  fetch('?ajax_loai=1&chu_de_id='+chuDeId)
    .then(r=>r.json()).then(data=>{
      const sel=document.getElementById('loaiSelect'),box=document.getElementById('loaiBox');
      sel.innerHTML='<option value="">-- Chọn loại (tùy chọn) --</option>';
      data.forEach(l=>sel.innerHTML+=`<option value="${l.id}">${l.ten_loai}</option>`);
      box.style.display=data.length?'':'none';
    });
}

// Gợi ý tự động khi nhập tiêu đề
let timer;
document.getElementById('tieuDeInput')?.addEventListener('input',function(){
  clearTimeout(timer);
  if(this.value.length<5){document.getElementById('goiYBox').classList.add('d-none');return;}
  timer=setTimeout(()=>{
    fetch('?ajax_goi_y=1&q='+encodeURIComponent(this.value))
      .then(r=>r.json()).then(data=>{
        const box=document.getElementById('goiYBox'),list=document.getElementById('goiYList');
        if(!data.length){box.classList.add('d-none');return;}
        list.innerHTML='';
        data.forEach(d=>{
          list.innerHTML+=`<div class="border rounded p-2 mb-2 bg-white small">
            <div class="fw-600">${d.tieu_de}</div>
            <div class="text-muted">${(d.noi_dung_tra_loi||'').substring(0,120)}...</div>
            <div class="d-flex gap-3 mt-1">
              <span class="text-primary"><i class="fas fa-building me-1"></i>${d.ten_chu_de||''}</span>
              <span class="text-muted"><i class="fas fa-clock me-1"></i>${d.updated_at?.substring(0,10)||''}</span>
            </div>
          </div>`;
        });
        box.classList.remove('d-none');
      });
  },600);
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
