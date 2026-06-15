<?php
// =====================================================
// ADMIN: Quản lý Danh mục Phản hồi
// File: admin/danh-muc.php
// =====================================================
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('admin'); // Chỉ admin được vào

$pageTitle  = 'Quản lý Danh mục';
$activeMenu = 'danh_muc';

global $pdo;

// Xử lý CRUD
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'them' || $action === 'sua') {
        $ten       = trim($_POST['ten_danh_muc'] ?? '');
        $mo_ta     = trim($_POST['mo_ta'] ?? '');
        $icon      = trim($_POST['icon'] ?? 'fas fa-comment');
        $don_vi    = trim($_POST['don_vi_xu_ly'] ?? '');
        $trang_thai = intval($_POST['trang_thai'] ?? 1);

        if ($ten === '') {
            $msg = 'Vui lòng nhập tên danh mục.';
            $msgType = 'danger';
        } elseif ($action === 'them') {
            $pdo->prepare("INSERT INTO danh_muc (ten_danh_muc, mo_ta, icon, don_vi_xu_ly, trang_thai) VALUES (?,?,?,?,?)")
                ->execute([$ten, $mo_ta, $icon, $don_vi, $trang_thai]);
            $msg = 'Đã thêm danh mục thành công.';
        } else {
            $id = intval($_POST['id']);
            $pdo->prepare("UPDATE danh_muc SET ten_danh_muc=?, mo_ta=?, icon=?, don_vi_xu_ly=?, trang_thai=? WHERE id=?")
                ->execute([$ten, $mo_ta, $icon, $don_vi, $trang_thai, $id]);
            $msg = 'Đã cập nhật danh mục.';
        }
    }

    if ($action === 'xoa') {
        $id = intval($_POST['id']);
        // Kiểm tra có phản hồi đang dùng không
        $count = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi WHERE danh_muc_id = ?");
        $count->execute([$id]);
        if ($count->fetchColumn() > 0) {
            $msg = 'Không thể xoá: Danh mục đang có phản hồi liên kết.';
            $msgType = 'warning';
        } else {
            $pdo->prepare("DELETE FROM danh_muc WHERE id = ?")->execute([$id]);
            $msg = 'Đã xoá danh mục.';
        }
    }
}

$danhMucs = $pdo->query("SELECT * FROM danh_muc ORDER BY id DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2><i class="fas fa-tags me-2"></i>Quản lý Danh mục Phản hồi</h2>
  <p class="mb-0">Thêm, sửa, xoá các danh mục phân loại phản hồi sinh viên</p>
</div>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> alert-dhv mb-3">
    <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : ($msgType === 'danger' ? 'times-circle' : 'exclamation-triangle') ?> me-2"></i>
    <?= loiXhtmlEntities($msg) ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <!-- FORM THÊM MỚI -->
  <div class="col-lg-4">
    <div class="card-dhv p-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-plus-circle me-2 text-primary"></i>Thêm danh mục mới</h6>
      <form method="POST">
        <input type="hidden" name="action" value="them">
        <div class="mb-2">
          <label class="form-label small fw-600">Tên danh mục <span class="text-danger">*</span></label>
          <input type="text" name="ten_danh_muc" class="form-control" placeholder="VD: Chất lượng giảng dạy" required>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Đơn vị xử lý mặc định</label>
          <input type="text" name="don_vi_xu_ly" class="form-control" placeholder="VD: Khoa CNTT">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Icon (Font Awesome)</label>
          <input type="text" name="icon" class="form-control" value="fas fa-comment" placeholder="fas fa-comment">
          <div class="form-text">Xem icon tại <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com</a></div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-600">Mô tả</label>
          <textarea name="mo_ta" class="form-control" rows="2" placeholder="Mô tả ngắn..."></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-600">Trạng thái</label>
          <select name="trang_thai" class="form-select">
            <option value="1">✅ Hoạt động</option>
            <option value="0">🔒 Ẩn</option>
          </select>
        </div>
        <button type="submit" class="btn btn-dhv w-100"><i class="fas fa-save me-1"></i>Thêm danh mục</button>
      </form>
    </div>
  </div>

  <!-- DANH SÁCH -->
  <div class="col-lg-8">
    <div class="card-dhv p-3">
      <h6 class="fw-700 mb-3"><i class="fas fa-list me-2 text-primary"></i>Danh sách danh mục (<?= count($danhMucs) ?>)</h6>
      <div class="table-responsive">
        <table class="table table-dhv table-hover mb-0">
          <thead>
            <tr>
              <th style="width:40px">#</th>
              <th>Tên danh mục</th>
              <th>Đơn vị xử lý</th>
              <th>Trạng thái</th>
              <th style="width:110px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($danhMucs as $i => $dm): ?>
            <tr>
              <td class="text-muted small"><?= $dm['id'] ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <i class="<?= loiXhtmlEntities($dm['icon']) ?> text-primary"></i>
                  <div>
                    <div class="fw-600"><?= loiXhtmlEntities($dm['ten_danh_muc']) ?></div>
                    <?php if ($dm['mo_ta']): ?>
                      <div class="text-muted small"><?= loiXhtmlEntities(mb_substr($dm['mo_ta'], 0, 50)) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="small text-muted"><?= loiXhtmlEntities($dm['don_vi_xu_ly'] ?? '—') ?></td>
              <td>
                <?php if ($dm['trang_thai']): ?>
                  <span class="badge bg-success">Hoạt động</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Ẩn</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <button class="btn btn-outline-primary btn-sm" title="Sửa"
                    onclick="moModalSua(<?= htmlspecialchars(json_encode($dm), ENT_QUOTES) ?>)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <form method="POST" onsubmit="return confirm('Xoá danh mục này?')">
                    <input type="hidden" name="action" value="xoa">
                    <input type="hidden" name="id" value="<?= $dm['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Xoá">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($danhMucs)): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Chưa có danh mục nào.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- MODAL SỬA -->
<div class="modal fade" id="modalSua" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-700"><i class="fas fa-edit me-2"></i>Sửa danh mục</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="sua">
          <input type="hidden" name="id" id="sua_id">
          <div class="mb-2">
            <label class="form-label small fw-600">Tên danh mục <span class="text-danger">*</span></label>
            <input type="text" name="ten_danh_muc" id="sua_ten" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-600">Đơn vị xử lý mặc định</label>
            <input type="text" name="don_vi_xu_ly" id="sua_don_vi" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label small fw-600">Icon (Font Awesome)</label>
            <input type="text" name="icon" id="sua_icon" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label small fw-600">Mô tả</label>
            <textarea name="mo_ta" id="sua_mo_ta" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-600">Trạng thái</label>
            <select name="trang_thai" id="sua_trang_thai" class="form-select">
              <option value="1">✅ Hoạt động</option>
              <option value="0">🔒 Ẩn</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
          <button type="submit" class="btn btn-dhv"><i class="fas fa-save me-1"></i>Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function moModalSua(dm) {
  document.getElementById('sua_id').value = dm.id;
  document.getElementById('sua_ten').value = dm.ten_danh_muc;
  document.getElementById('sua_don_vi').value = dm.don_vi_xu_ly || '';
  document.getElementById('sua_icon').value = dm.icon || 'fas fa-comment';
  document.getElementById('sua_mo_ta').value = dm.mo_ta || '';
  document.getElementById('sua_trang_thai').value = dm.trang_thai;
  new bootstrap.Modal(document.getElementById('modalSua')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>