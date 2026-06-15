<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$pageTitle  = 'Quản lý Cán bộ';
$activeMenu = 'quan_ly_cb';
global $pdo;

$msg = ''; $msgType = 'success'; $editRow = null;
$donVis = layDanhSachDonVi();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act    = $_POST['action'] ?? '';
    $id     = intval($_POST['id'] ?? 0);
    $ten    = trim($_POST['ho_ten'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $vt     = $_POST['vai_tro'] ?? 'can_bo';
    $ma     = trim($_POST['ma_sv_gv'] ?? '');
    $dvId   = intval($_POST['don_vi_id'] ?? 0) ?: null;
    $sdt    = trim($_POST['so_dien_thoai'] ?? '');
    $tt     = intval($_POST['trang_thai'] ?? 1);
    $mk     = trim($_POST['mat_khau'] ?? '');
    $tuKhoa = ($vt === 'can_bo') ? trim($_POST['tu_khoa'] ?? '') : null;

    if ($act === 'them') {
        if (empty($ten)||empty($email)) { $msg='Vui lòng nhập đầy đủ họ tên và email.'; $msgType='danger'; }
        elseif (empty($mk)) { $msg='Vui lòng nhập mật khẩu cho tài khoản mới.'; $msgType='danger'; }
        else {
            $chk=$pdo->prepare("SELECT id FROM users WHERE email=?"); $chk->execute([$email]);
            if ($chk->fetch()) { $msg='Email này đã được sử dụng.'; $msgType='danger'; }
            else {
                $hash = password_hash($mk, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO users (ho_ten, email, mat_khau, vai_tro, ma_sv_gv, don_vi_id, so_dien_thoai, trang_thai, tu_khoa) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$ten,$email,$hash,$vt,$ma,$dvId,$sdt,$tt,$tuKhoa]);
                flashMessage('success','Đã thêm tài khoản cán bộ.'); chuyenHuong('quanlycanbo.php');
            }
        }
    } elseif ($act === 'sua') {
        $params = [$ten,$email,$vt,$ma,$dvId,$sdt,$tt,$tuKhoa,$id];
        $sql = "UPDATE users SET ho_ten=?, email=?, vai_tro=?, ma_sv_gv=?, don_vi_id=?, so_dien_thoai=?, trang_thai=?, tu_khoa=?";
        if (!empty($mk)) { $sql .= ", mat_khau=?"; $params = [$ten,$email,$vt,$ma,$dvId,$sdt,$tt,$tuKhoa,password_hash($mk,PASSWORD_BCRYPT),$id]; }
        $sql .= " WHERE id=?";
        $pdo->prepare($sql)->execute($params);
        flashMessage('success','Đã cập nhật tài khoản.'); chuyenHuong('quanlycanbo.php');
    } elseif ($act === 'khoa') {
        $pdo->prepare("UPDATE users SET trang_thai = NOT trang_thai WHERE id=?")->execute([$id]);
        flashMessage('success','Đã thay đổi trạng thái tài khoản.'); chuyenHuong('quanlycanbo.php');
    }
}

if (isset($_GET['edit'])) { $s=$pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([intval($_GET['edit'])]); $editRow=$s->fetch(); }

$success = flashMessage('success');
$search  = trim($_GET['search'] ?? '');
$filterVT = $_GET['vai_tro'] ?? '';
$filterDV = intval($_GET['don_vi_id'] ?? 0);
$where   = "WHERE vai_tro IN ('can_bo','truong_don_vi','admin')";
if ($filterVT) $where .= " AND vai_tro = '".addslashes($filterVT)."'";
if ($filterDV) $where .= " AND u.don_vi_id = " . $filterDV;
if ($search) $where .= " AND (ho_ten LIKE '%".addslashes($search)."%' OR email LIKE '%".addslashes($search)."%' OR ma_sv_gv LIKE '%".addslashes($search)."%')";
$users = $pdo->query("SELECT u.*, dv.ten_don_vi FROM users u LEFT JOIN don_vi dv ON u.don_vi_id=dv.id $where ORDER BY u.vai_tro, u.ho_ten")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-exclamation-circle me-2"></i><?= e($msg) ?></div><?php endif; ?>

<div class="page-header"><h2><i class="fas fa-users me-2"></i>Quản lý Tài khoản Cán bộ</h2></div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-dhv p-4">
      <h6 class="fw-700 mb-3"><?= $editRow?'<i class="fas fa-edit me-2 text-warning"></i>Sửa tài khoản':'<i class="fas fa-plus me-2 text-primary"></i>Thêm tài khoản' ?></h6>
      <form method="POST">
        <input type="hidden" name="action" value="<?= $editRow?'sua':'them' ?>">
        <?php if ($editRow): ?><input type="hidden" name="id" value="<?= $editRow['id'] ?>"><?php endif; ?>
        <div class="mb-2">
          <label class="form-label small fw-600">Họ và tên <span class="text-danger">*</span></label>
          <input type="text" name="ho_ten" class="form-control form-control-sm" required value="<?= e($editRow['ho_ten']??'') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control form-control-sm" required value="<?= e($editRow['email']??'') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Mật khẩu <?= $editRow?'(để trống nếu không đổi)':'' ?> <?= !$editRow?'<span class="text-danger">*</span>':'' ?></label>
          <input type="password" name="mat_khau" class="form-control form-control-sm" <?= !$editRow?'required':'' ?> placeholder="<?= $editRow?'Để trống giữ nguyên MK cũ':'' ?>">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Vai trò</label>
          <select name="vai_tro" class="form-select form-select-sm">
            <option value="can_bo" <?= ($editRow['vai_tro']??'can_bo')==='can_bo'?'selected':'' ?>>Cán bộ xử lý</option>
            <option value="truong_don_vi" <?= ($editRow['vai_tro']??'')==='truong_don_vi'?'selected':'' ?>>Trưởng đơn vị</option>
            <option value="admin" <?= ($editRow['vai_tro']??'')==='admin'?'selected':'' ?>>Quản trị viên</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Đơn vị</label>
          <select name="don_vi_id" class="form-select form-select-sm">
            <option value="">-- Chọn đơn vị --</option>
            <?php foreach ($donVis as $dv): ?>
            <option value="<?= $dv['id'] ?>" <?= ($editRow['don_vi_id']??'')==$dv['id']?'selected':'' ?>><?= e($dv['ten_don_vi']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2" id="tuKhoaGroup" style="display: <?= ($editRow['vai_tro']??'can_bo')==='can_bo'?'block':'none' ?>;">
          <label class="form-label small fw-600">Từ khóa phụ trách (cách nhau bằng dấu phẩy)</label>
          <input type="text" name="tu_khoa" class="form-control form-control-sm" value="<?= e($editRow['tu_khoa']??'') ?>" placeholder="Ví dụ: học phí, học bổng, ký túc xá">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Mã cán bộ</label>
          <input type="text" name="ma_sv_gv" class="form-control form-control-sm" value="<?= e($editRow['ma_sv_gv']??'') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Số điện thoại</label>
          <input type="text" name="so_dien_thoai" class="form-control form-control-sm" value="<?= e($editRow['so_dien_thoai']??'') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Trạng thái</label>
          <select name="trang_thai" class="form-select form-select-sm">
            <option value="1" <?= ($editRow['trang_thai']??1)==1?'selected':'' ?>>Hoạt động</option>
            <option value="0" <?= ($editRow['trang_thai']??1)==0?'selected':'' ?>>Bị khóa</option>
          </select>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-dhv flex-grow-1 btn-sm"><?= $editRow?'Lưu':'Thêm tài khoản' ?></button>
          <?php if ($editRow): ?><a href="quanlycanbo.php" class="btn btn-outline-secondary btn-sm">Hủy</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card-dhv p-3">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-700 mb-0">Danh sách tài khoản (<?= count($users) ?>)</h6>
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
          <select name="vai_tro" class="form-select form-select-sm" style="width:140px">
            <option value="">-- Tất cả vai trò --</option>
            <option value="admin" <?= $filterVT==='admin'?'selected':'' ?>>Admin</option>
            <option value="truong_don_vi" <?= $filterVT==='truong_don_vi'?'selected':'' ?>>Trưởng ĐV</option>
            <option value="can_bo" <?= $filterVT==='can_bo'?'selected':'' ?>>Cán bộ</option>
          </select>
          <select name="don_vi_id" class="form-select form-select-sm" style="width:170px">
            <option value="">-- Tất cả đơn vị --</option>
            <?php foreach ($donVis as $dv): ?>
            <option value="<?= $dv['id'] ?>" <?= $filterDV==$dv['id']?'selected':'' ?>><?= e($dv['ten_don_vi']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm tên, email, mã..." value="<?= e($search) ?>" style="width:150px">
          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="table-light"><tr><th>Họ tên</th><th>Vai trò</th><th>Đơn vị</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <div class="fw-600"><?= e($u['ho_ten']) ?></div>
                <div class="text-muted" style="font-size:.72rem"><?= e($u['email']) ?></div>
                <?php if ($u['ma_sv_gv']): ?><div class="text-muted" style="font-size:.72rem">Mã: <?= e($u['ma_sv_gv']) ?></div><?php endif; ?>
                <?php if ($u['vai_tro'] === 'can_bo'): ?>
                  <div class="text-primary mt-1" style="font-size:.72rem" title="Từ khóa AI phụ trách">
                    <i class="fas fa-key me-1"></i>Từ khóa: <?= e($u['tu_khoa'] ?: 'Chưa có') ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php $vtMap=['admin'=>['danger','Quản trị viên'],'truong_don_vi'=>['primary','Trưởng ĐV'],'can_bo'=>['info','Cán bộ']];
                [$vc,$vl]=$vtMap[$u['vai_tro']]??['secondary',$u['vai_tro']]; ?>
                <span class="badge bg-<?= $vc ?>"><?= $vl ?></span>
              </td>
              <td class="text-muted"><?= e($u['ten_don_vi']??'–') ?></td>
              <td>
                <span class="badge bg-<?= $u['trang_thai']?'success':'danger' ?>">
                  <?= $u['trang_thai']?'Hoạt động':'Bị khóa' ?>
                </span>
              </td>
              <td>
                <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                <form method="POST" class="d-inline" onsubmit="return confirm('<?= $u['trang_thai']?'Khóa':'Mở khóa' ?> tài khoản này?')">
                  <input type="hidden" name="action" value="khoa"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-<?= $u['trang_thai']?'secondary':'success' ?>" title="<?= $u['trang_thai']?'Khóa':'Mở khóa' ?>">
                    <i class="fas fa-<?= $u['trang_thai']?'lock':'unlock' ?>"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelector('select[name="vai_tro"]').addEventListener('change', function() {
    var group = document.getElementById('tuKhoaGroup');
    if (this.value === 'can_bo') {
        group.style.display = 'block';
    } else {
        group.style.display = 'none';
        group.querySelector('input').value = '';
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>