<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$pageTitle  = 'Quản lý đơn vị';
$activeMenu = 'don_vi';
global $pdo;

$msg = ''; $msgType = 'success';
$editRow = null;

// XỬ LÝ FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act  = $_POST['action'] ?? '';
    $id   = intval($_POST['id'] ?? 0);
    $ten  = trim($_POST['ten_don_vi'] ?? '');
    $ma   = trim($_POST['ma_don_vi'] ?? '');
    $mo   = trim($_POST['mo_ta'] ?? '');
    $email= trim($_POST['email_lien_he'] ?? '');
    $sdt  = trim($_POST['so_dien_thoai'] ?? '');

    if ($act === 'them') {
        if (empty($ten)) { $msg = 'Vui lòng nhập tên đơn vị.'; $msgType='danger'; }
        else {
            $pdo->prepare("INSERT INTO don_vi (ten_don_vi, ma_don_vi, mo_ta, email_lien_he, so_dien_thoai) VALUES (?,?,?,?,?)")
                ->execute([$ten,$ma,$mo,$email,$sdt]);
            flashMessage('success', 'Đã thêm đơn vị thành công.');
            chuyenHuong('don-vi.php');
        }
    } elseif ($act === 'sua') {
        if (empty($ten)) { $msg='Tên đơn vị không được để trống.'; $msgType='danger'; }
        else {
            $pdo->prepare("UPDATE don_vi SET ten_don_vi=?, ma_don_vi=?, mo_ta=?, email_lien_he=?, so_dien_thoai=? WHERE id=?")
                ->execute([$ten,$ma,$mo,$email,$sdt,$id]);
            flashMessage('success', 'Đã cập nhật đơn vị.');
            chuyenHuong('don-vi.php');
        }
    } elseif ($act === 'xoa') {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE don_vi_id=?"); $chk->execute([$id]);
        if ($chk->fetchColumn() > 0) { $msg='Không thể xóa: đang có cán bộ thuộc đơn vị này.'; $msgType='danger'; }
        else {
            $pdo->prepare("DELETE FROM don_vi WHERE id=?")->execute([$id]);
            flashMessage('success', 'Đã xóa đơn vị.');
            chuyenHuong('don-vi.php');
        }
    }
}

if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM don_vi WHERE id=?"); $s->execute([intval($_GET['edit'])]); $editRow=$s->fetch();
}

$success = flashMessage('success');
$search  = trim($_GET['search'] ?? '');
$where   = $search ? "WHERE ten_don_vi LIKE '%".addslashes($search)."%' OR ma_don_vi LIKE '%".addslashes($search)."%'" : '';
$donVis  = $pdo->query("SELECT dv.*, COUNT(u.id) as so_can_bo FROM don_vi dv LEFT JOIN users u ON u.don_vi_id=dv.id $where GROUP BY dv.id ORDER BY dv.ten_don_vi")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-exclamation-circle me-2"></i><?= e($msg) ?></div><?php endif; ?>

<div class="page-header">
  <h2><i class="fas fa-building me-2"></i>Quản lý Đơn vị phụ trách</h2>
</div>

<div class="row g-3">
  <!-- FORM THÊM/SỬA -->
  <div class="col-lg-4">
    <div class="card-dhv p-4">
      <h6 class="fw-700 mb-3"><?= $editRow ? '<i class="fas fa-edit me-2 text-warning"></i>Sửa đơn vị' : '<i class="fas fa-plus me-2 text-primary"></i>Thêm đơn vị mới' ?></h6>
      <form method="POST">
        <input type="hidden" name="action" value="<?= $editRow ? 'sua' : 'them' ?>">
        <?php if ($editRow): ?><input type="hidden" name="id" value="<?= $editRow['id'] ?>"><?php endif; ?>
        <div class="mb-3">
          <label class="form-label small fw-600">Tên đơn vị <span class="text-danger">*</span></label>
          <input type="text" name="ten_don_vi" class="form-control" required value="<?= e($editRow['ten_don_vi']??'') ?>" placeholder="VD: Phòng Đào tạo">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Mã đơn vị</label>
          <input type="text" name="ma_don_vi" class="form-control" value="<?= e($editRow['ma_don_vi']??'') ?>" placeholder="VD: PDT">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Email liên hệ</label>
          <input type="email" name="email_lien_he" class="form-control" value="<?= e($editRow['email_lien_he']??'') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Số điện thoại</label>
          <input type="text" name="so_dien_thoai" class="form-control" value="<?= e($editRow['so_dien_thoai']??'') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Mô tả</label>
          <textarea name="mo_ta" class="form-control" rows="3"><?= e($editRow['mo_ta']??'') ?></textarea>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-dhv flex-grow-1"><?= $editRow ? 'Lưu thay đổi' : 'Thêm đơn vị' ?></button>
          <?php if ($editRow): ?><a href="don-vi.php" class="btn btn-outline-secondary">Hủy</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- DANH SÁCH -->
  <div class="col-lg-8">
    <div class="card-dhv p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-700 mb-0">Danh sách đơn vị (<?= count($donVis) ?>)</h6>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm kiếm..." value="<?= e($search) ?>" style="width:200px">
          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
        </form>
      </div>
      <?php if (empty($donVis)): ?>
      <div class="empty-state py-4 text-center"><p class="text-muted">Chưa có đơn vị nào.</p></div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="table-light">
            <tr><th>Đơn vị</th><th>Mã</th><th>Email</th><th>Cán bộ</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($donVis as $dv): ?>
            <tr>
              <td>
                <div class="fw-600"><?= e($dv['ten_don_vi']) ?></div>
                <?php if ($dv['mo_ta']): ?><div class="text-muted" style="font-size:.72rem"><?= e(mb_substr($dv['mo_ta'],0,50)) ?></div><?php endif; ?>
              </td>
              <td><span class="badge bg-light text-dark border"><?= e($dv['ma_don_vi']??'–') ?></span></td>
              <td class="text-muted"><?= $dv['email_lien_he'] ? e($dv['email_lien_he']) : '–' ?></td>
              <td><span class="badge bg-primary"><?= $dv['so_can_bo'] ?></span></td>
              <td>
                <a href="?edit=<?= $dv['id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                <form method="POST" class="d-inline" onsubmit="return confirm('Xóa đơn vị này?')">
                  <input type="hidden" name="action" value="xoa">
                  <input type="hidden" name="id" value="<?= $dv['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
