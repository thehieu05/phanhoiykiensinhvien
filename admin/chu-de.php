<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$pageTitle  = 'Quản lý Chủ đề';
$activeMenu = 'chu_de';
global $pdo;

$msg = ''; $msgType = 'success'; $editRow = null;
$donVis = layDanhSachDonVi();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act    = $_POST['action'] ?? '';
    $id     = intval($_POST['id'] ?? 0);
    $ten    = trim($_POST['ten_chu_de'] ?? '');
    $mo     = trim($_POST['mo_ta'] ?? '');
    $icon   = trim($_POST['icon'] ?? 'fas fa-comment');
    $dvId   = intval($_POST['don_vi_id'] ?? 0) ?: null;
    $tt     = intval($_POST['trang_thai'] ?? 1);

    if ($act === 'them') {
        if (empty($ten)) { $msg='Vui lòng nhập tên chủ đề.'; $msgType='danger'; }
        else {
            $pdo->prepare("INSERT INTO chu_de (ten_chu_de, mo_ta, icon, don_vi_id, trang_thai) VALUES (?,?,?,?,?)")
                ->execute([$ten,$mo,$icon,$dvId,$tt]);
            flashMessage('success','Đã thêm chủ đề.'); chuyenHuong('chu-de.php');
        }
    } elseif ($act === 'sua') {
        $pdo->prepare("UPDATE chu_de SET ten_chu_de=?, mo_ta=?, icon=?, don_vi_id=?, trang_thai=? WHERE id=?")
            ->execute([$ten,$mo,$icon,$dvId,$tt,$id]);
        flashMessage('success','Đã cập nhật chủ đề.'); chuyenHuong('chu-de.php');
    } elseif ($act === 'xoa') {
        $chk=$pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE chu_de_id=?"); $chk->execute([$id]);
        if ($chk->fetchColumn()>0) { $msg='Không thể xóa: đang có phản hồi thuộc chủ đề này.'; $msgType='danger'; }
        else { $pdo->prepare("DELETE FROM chu_de WHERE id=?")->execute([$id]); flashMessage('success','Đã xóa chủ đề.'); chuyenHuong('chu-de.php'); }
    }
}

if (isset($_GET['edit'])) { $s=$pdo->prepare("SELECT * FROM chu_de WHERE id=?"); $s->execute([intval($_GET['edit'])]); $editRow=$s->fetch(); }

$success = flashMessage('success');
$search  = trim($_GET['search'] ?? '');
$w = $search ? "WHERE cd.ten_chu_de LIKE '%".addslashes($search)."%'" : '';
$chuDes = $pdo->query("SELECT cd.*, dv.ten_don_vi, COUNT(ph.id) as so_ph FROM chu_de cd LEFT JOIN don_vi dv ON cd.don_vi_id=dv.id LEFT JOIN phan_hoi ph ON ph.chu_de_id=cd.id $w GROUP BY cd.id ORDER BY cd.ten_chu_de")->fetchAll();

$icons = ['fas fa-dollar-sign','fas fa-graduation-cap','fas fa-home','fas fa-user-lock','fas fa-chalkboard-teacher','fas fa-building','fas fa-users','fas fa-book','fas fa-comment','fas fa-ellipsis-h','fas fa-star','fas fa-heart'];

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-exclamation-circle me-2"></i><?= e($msg) ?></div><?php endif; ?>

<div class="page-header"><h2><i class="fas fa-tags me-2"></i>Quản lý Chủ đề phản hồi</h2></div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-dhv p-4">
      <h6 class="fw-700 mb-3"><?= $editRow ? '<i class="fas fa-edit me-2 text-warning"></i>Sửa chủ đề' : '<i class="fas fa-plus me-2 text-primary"></i>Thêm chủ đề' ?></h6>
      <form method="POST">
        <input type="hidden" name="action" value="<?= $editRow?'sua':'them' ?>">
        <?php if ($editRow): ?><input type="hidden" name="id" value="<?= $editRow['id'] ?>"><?php endif; ?>
        <div class="mb-3">
          <label class="form-label small fw-600">Tên chủ đề <span class="text-danger">*</span></label>
          <input type="text" name="ten_chu_de" class="form-control" required value="<?= e($editRow['ten_chu_de']??'') ?>" placeholder="VD: Học phí & Tài chính">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Icon (Font Awesome)</label>
          <div class="d-flex gap-2">
            <input type="text" name="icon" id="iconInput" class="form-control form-control-sm" value="<?= e($editRow['icon']??'fas fa-comment') ?>">
            <div class="border rounded p-2 d-flex align-items-center" style="min-width:40px;justify-content:center">
              <i id="iconPreview" class="<?= e($editRow['icon']??'fas fa-comment') ?> text-primary"></i>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-1 mt-2">
            <?php foreach ($icons as $ic): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 icon-pick" data-icon="<?= $ic ?>"><i class="<?= $ic ?>"></i></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Đơn vị phụ trách</label>
          <select name="don_vi_id" class="form-select">
            <option value="">-- Chọn đơn vị --</option>
            <?php foreach ($donVis as $dv): ?>
            <option value="<?= $dv['id'] ?>" <?= ($editRow['don_vi_id']??'')==$dv['id']?'selected':'' ?>><?= e($dv['ten_don_vi']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Mô tả</label>
          <textarea name="mo_ta" class="form-control" rows="2"><?= e($editRow['mo_ta']??'') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Trạng thái</label>
          <select name="trang_thai" class="form-select">
            <option value="1" <?= ($editRow['trang_thai']??1)==1?'selected':'' ?>>Hoạt động</option>
            <option value="0" <?= ($editRow['trang_thai']??1)==0?'selected':'' ?>>Tạm ẩn</option>
          </select>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-dhv flex-grow-1"><?= $editRow?'Lưu':'Thêm' ?></button>
          <?php if ($editRow): ?><a href="chu-de.php" class="btn btn-outline-secondary">Hủy</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card-dhv p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-700 mb-0">Danh sách chủ đề (<?= count($chuDes) ?>)</h6>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm kiếm..." value="<?= e($search) ?>" style="width:180px">
          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="table-light"><tr><th>Chủ đề</th><th>Đơn vị</th><th>Phản hồi</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($chuDes as $cd): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <i class="<?= e($cd['icon']) ?> text-primary" style="width:20px"></i>
                  <strong><?= e($cd['ten_chu_de']) ?></strong>
                </div>
                <?php if ($cd['mo_ta']): ?><div class="text-muted ms-4" style="font-size:.72rem"><?= e(mb_substr($cd['mo_ta'],0,60)) ?></div><?php endif; ?>
              </td>
              <td class="text-muted"><?= e($cd['ten_don_vi']??'–') ?></td>
              <td><span class="badge bg-primary"><?= $cd['so_ph'] ?></span></td>
              <td><span class="badge bg-<?= $cd['trang_thai']?'success':'secondary' ?>"><?= $cd['trang_thai']?'Hoạt động':'Ẩn' ?></span></td>
              <td>
                <a href="?edit=<?= $cd['id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                <a href="loai-phan-hoi.php?chu_de_id=<?= $cd['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="Quản lý loại"><i class="fas fa-list-alt"></i></a>
                <form method="POST" class="d-inline" onsubmit="return confirm('Xóa chủ đề?')">
                  <input type="hidden" name="action" value="xoa"><input type="hidden" name="id" value="<?= $cd['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
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
document.querySelectorAll('.icon-pick').forEach(b=>{
  b.addEventListener('click',function(){
    const ic=this.dataset.icon;
    document.getElementById('iconInput').value=ic;
    document.getElementById('iconPreview').className=ic+' text-primary';
  });
});
document.getElementById('iconInput').addEventListener('input',function(){
  document.getElementById('iconPreview').className=this.value+' text-primary';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
