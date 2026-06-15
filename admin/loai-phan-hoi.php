<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin');

$pageTitle  = 'Quản lý Loại phản hồi';
$activeMenu = 'loai_ph';
global $pdo;

$msg = ''; $msgType = 'success'; $editRow = null;
$filterChuDe = intval($_GET['chu_de_id'] ?? 0);
$chuDes = layDanhSachChuDe(false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act   = $_POST['action'] ?? '';
    $id    = intval($_POST['id'] ?? 0);
    $cdId  = intval($_POST['chu_de_id'] ?? 0);
    $ten   = trim($_POST['ten_loai'] ?? '');
    $mo    = trim($_POST['mo_ta'] ?? '');
    $tt    = intval($_POST['trang_thai'] ?? 1);

    if ($act === 'them') {
        if (!$cdId || empty($ten)) { $msg='Vui lòng chọn chủ đề và nhập tên loại.'; $msgType='danger'; }
        else { $pdo->prepare("INSERT INTO loai_phan_hoi (chu_de_id, ten_loai, mo_ta, trang_thai) VALUES (?,?,?,?)")->execute([$cdId,$ten,$mo,$tt]); flashMessage('success','Đã thêm loại phản hồi.'); chuyenHuong('loai-phan-hoi.php?chu_de_id='.$cdId); }
    } elseif ($act === 'sua') {
        $pdo->prepare("UPDATE loai_phan_hoi SET chu_de_id=?, ten_loai=?, mo_ta=?, trang_thai=? WHERE id=?")->execute([$cdId,$ten,$mo,$tt,$id]);
        flashMessage('success','Đã cập nhật.'); chuyenHuong('loai-phan-hoi.php?chu_de_id='.$cdId);
    } elseif ($act === 'xoa') {
        $pdo->prepare("DELETE FROM loai_phan_hoi WHERE id=?")->execute([$id]);
        flashMessage('success','Đã xóa loại phản hồi.'); chuyenHuong('loai-phan-hoi.php?chu_de_id='.$filterChuDe);
    }
}

if (isset($_GET['edit'])) { $s=$pdo->prepare("SELECT * FROM loai_phan_hoi WHERE id=?"); $s->execute([intval($_GET['edit'])]); $editRow=$s->fetch(); if ($editRow && !$filterChuDe) $filterChuDe=$editRow['chu_de_id']; }

$success = flashMessage('success');
$search  = trim($_GET['search'] ?? '');
$where   = ['1=1']; $params = [];
if ($filterChuDe) { $where[]='lph.chu_de_id=?'; $params[]=$filterChuDe; }
if ($search) { $where[]='lph.ten_loai LIKE ?'; $params[]='%'.$search.'%'; }
$wStr = implode(' AND ', $where);
$loais = $pdo->prepare("SELECT lph.*, cd.ten_chu_de FROM loai_phan_hoi lph JOIN chu_de cd ON lph.chu_de_id=cd.id WHERE $wStr ORDER BY cd.ten_chu_de, lph.ten_loai");
$loais->execute($params); $loais=$loais->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><i class="fas fa-exclamation-circle me-2"></i><?= e($msg) ?></div><?php endif; ?>

<div class="page-header"><h2><i class="fas fa-list-alt me-2"></i>Quản lý Loại phản hồi</h2></div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-dhv p-4">
      <h6 class="fw-700 mb-3"><?= $editRow?'<i class="fas fa-edit me-2 text-warning"></i>Sửa loại':'<i class="fas fa-plus me-2 text-primary"></i>Thêm loại mới' ?></h6>
      <form method="POST">
        <input type="hidden" name="action" value="<?= $editRow?'sua':'them' ?>">
        <?php if ($editRow): ?><input type="hidden" name="id" value="<?= $editRow['id'] ?>"><?php endif; ?>
        <div class="mb-3">
          <label class="form-label small fw-600">Chủ đề <span class="text-danger">*</span></label>
          <select name="chu_de_id" class="form-select" required>
            <option value="">-- Chọn chủ đề --</option>
            <?php foreach ($chuDes as $cd): ?>
            <option value="<?= $cd['id'] ?>" <?= ($editRow['chu_de_id']??$filterChuDe)==$cd['id']?'selected':'' ?>><?= e($cd['ten_chu_de']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Tên loại phản hồi <span class="text-danger">*</span></label>
          <input type="text" name="ten_loai" class="form-control" required value="<?= e($editRow['ten_loai']??'') ?>" placeholder="VD: Nộp học phí">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Mô tả</label>
          <textarea name="mo_ta" class="form-control" rows="2"><?= e($editRow['mo_ta']??'') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Trạng thái</label>
          <select name="trang_thai" class="form-select">
            <option value="1" <?= ($editRow['trang_thai']??1)?'selected':'' ?>>Hoạt động</option>
            <option value="0" <?= ($editRow['trang_thai']??1)==0?'selected':'' ?>>Ẩn</option>
          </select>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-dhv flex-grow-1"><?= $editRow?'Lưu':'Thêm' ?></button>
          <?php if ($editRow): ?><a href="loai-phan-hoi.php?chu_de_id=<?= $filterChuDe ?>" class="btn btn-outline-secondary">Hủy</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card-dhv p-3">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-700 mb-0">Danh sách loại phản hồi (<?= count($loais) ?>)</h6>
        <form method="GET" class="d-flex gap-2">
          <select name="chu_de_id" class="form-select form-select-sm" style="width:180px">
            <option value="">-- Tất cả chủ đề --</option>
            <?php foreach ($chuDes as $cd): ?>
            <option value="<?= $cd['id'] ?>" <?= $filterChuDe==$cd['id']?'selected':'' ?>><?= e($cd['ten_chu_de']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm..." value="<?= e($search) ?>" style="width:140px">
          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="table-light"><tr><th>Tên loại</th><th>Chủ đề</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <?php if (empty($loais)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Không có dữ liệu.</td></tr>
            <?php else: foreach ($loais as $l): ?>
            <tr>
              <td><strong><?= e($l['ten_loai']) ?></strong><?php if ($l['mo_ta']): ?><div class="text-muted" style="font-size:.72rem"><?= e(mb_substr($l['mo_ta'],0,50)) ?></div><?php endif; ?></td>
              <td><span class="badge bg-light text-dark border"><?= e($l['ten_chu_de']) ?></span></td>
              <td><span class="badge bg-<?= $l['trang_thai']?'success':'secondary' ?>"><?= $l['trang_thai']?'Hoạt động':'Ẩn' ?></span></td>
              <td>
                <a href="?edit=<?= $l['id'] ?>&chu_de_id=<?= $l['chu_de_id'] ?>" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                <form method="POST" class="d-inline" onsubmit="return confirm('Xóa loại này?')">
                  <input type="hidden" name="action" value="xoa"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
