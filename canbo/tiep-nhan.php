<?php
require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('can_bo');

$id      = intval($_POST['id'] ?? 0);
$action  = $_POST['action'] ?? '';
$canBoId = $_SESSION['user_id'];
global $pdo;

if (!$id || !in_array($action, ['tiep_nhan', 'tu_choi'])) {
    chuyenHuong(SITE_URL . '/canbo/danh-sach.php');
}

// Kiểm tra phản hồi có thuộc cán bộ này không
$stmt = $pdo->prepare("SELECT * FROM phan_hoi WHERE id = ? AND can_bo_xu_ly_id = ?");
$stmt->execute([$id, $canBoId]);
$ph = $stmt->fetch();

if (!$ph) {
    flashMessage('error', 'Không tìm thấy phản hồi hoặc bạn không có quyền thực hiện.');
    chuyenHuong(SITE_URL . '/canbo/danh-sach.php');
}

if ($action === 'tiep_nhan') {
    // Tiếp nhận → chuyển sang Đang xử lý
    if (in_array($ph['trang_thai'], ['da_tiep_nhan', 'cho_xu_ly', 'da_phan_cong'])) {
        capNhatTrangThai($id, 'dang_xu_ly', $canBoId, 'Cán bộ tiếp nhận và bắt đầu xử lý');
        // Thông báo cho trưởng đơn vị
        if ($ph['truong_don_vi_id']) {
            themThongBao(
                $ph['truong_don_vi_id'],
                'Cán bộ đã tiếp nhận',
                "Cán bộ đã tiếp nhận phản hồi: \"{$ph['tieu_de']}\"",
                'cap_nhat_trang_thai',
                $id
            );
        }
        flashMessage('success', 'Đã tiếp nhận phản hồi và chuyển sang trạng thái Đang xử lý.');
    } else {
        flashMessage('error', 'Phản hồi không ở trạng thái phù hợp để tiếp nhận.');
    }

} elseif ($action === 'tu_choi') {
    $lyDo = trim($_POST['ly_do'] ?? '');
    if (empty($lyDo)) {
        flashMessage('error', 'Vui lòng nhập lý do hủy nhận.');
        chuyenHuong(SITE_URL . '/canbo/danh-sach.php');
    }

    if (in_array($ph['trang_thai'], ['da_tiep_nhan', 'cho_xu_ly', 'dang_xu_ly', 'da_phan_cong'])) {
        // Trả lại cho Trưởng đơn vị: Đổi trạng thái về cho_xu_ly và gỡ can_bo_xu_ly_id
        $pdo->prepare("UPDATE phan_hoi SET trang_thai = 'cho_xu_ly', can_bo_xu_ly_id = NULL, updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
        ghiLichSu($id, $ph['trang_thai'], 'cho_xu_ly', $canBoId, 'Cán bộ hủy nhận phân công: ' . $lyDo);

        // Thông báo cho trưởng đơn vị
        if ($ph['truong_don_vi_id']) {
            themThongBao(
                $ph['truong_don_vi_id'],
                'Cán bộ hủy nhận phân công',
                "Cán bộ đã hủy nhận phân công xử lý phản hồi: \"{$ph['tieu_de']}\". Lý do: $lyDo",
                'cap_nhat_trang_thai',
                $id
            );
        }
        flashMessage('success', 'Đã hủy nhận phân công và trả lại cho Trưởng đơn vị.');
    } else {
        flashMessage('error', 'Phản hồi không ở trạng thái phù hợp để hủy nhận phân công.');
    }
}

chuyenHuong(SITE_URL . '/canbo/danh-sach.php');
