<!-- =====================================================
     FILE: can-bo/phan-hoi-xu-ly.php
     Cán bộ xem chi tiết và soạn trả lời (chờ trưởng ĐV duyệt)
===================================================== -->
<?php
/* Lưu file riêng, nội dung bên dưới là cho can-bo/phan-hoi-xu-ly.php */

require_once __DIR__ . '/../includes/functions.php';
kiemTraVaiTro('can_bo');

$id      = intval($_GET['id'] ?? 0);
$canBoId = $_SESSION['user_id'];
global $pdo;

// Chỉ cho xem phản hồi được phân công cho mình
$stmt = $pdo->prepare("
    SELECT ph.*, dm.ten_danh_muc, dm.icon,
           u.ho_ten as ten_nguoi_gui, u.email as email_nguoi_gui, u.ma_sv_gv, u.lop,
           tdv.ho_ten as ten_truong_don_vi
    FROM phan_hoi ph
    LEFT JOIN danh_muc dm ON ph.danh_muc_id = dm.id
    LEFT JOIN users u ON ph.nguoi_gui_id = u.id
    LEFT JOIN users tdv ON ph.truong_don_vi_id = tdv.id
    WHERE ph.id = ? AND ph.can_bo_xu_ly_id = ?
");
$stmt->execute([$id, $canBoId]);
$ph = $stmt->fetch();
if (!$ph) { chuyenHuong(SITE_URL . '/can-bo/phan-hoi.php'); }

$pdo->prepare("UPDATE phan_hoi SET luot_xem = luot_xem + 1 WHERE id = ?")->execute([$id]);

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bắt đầu xử lý
    if (isset($_POST['bat_dau'])) {
        if ($ph['trang_thai'] === 'da_phan_cong') {
            $pdo->prepare("UPDATE phan_hoi SET trang_thai = 'dang_xu_ly', updated_at = NOW() WHERE id = ?")->execute([$id]);
            $pdo->prepare("INSERT INTO lich_su_trang_thai (phan_hoi_id, trang_thai_cu, trang_thai_moi, nguoi_thay_doi_id) VALUES (?,?,?,?)")
                ->execute([$id, 'da_phan_cong', 'dang_xu_ly', $canBoId]);
            chuyenHuong("phan-hoi-xu-ly.php?id=$id");
        }
    }

    // Soạn và gửi trả lời (chờ trưởng ĐV duyệt)
    if (isset($_POST['gui_tra_loi']) && !empty(trim($_POST['noi_dung_tra_loi']))) {
        if (!in_array($ph['trang_thai'], ['dang_xu_ly', 'da_phan_cong'])) {
            $msg = 'Phản hồi này không ở trạng thái có thể trả lời.';
            $msgType = 'danger';
        } else {
            $nd = trim($_POST['noi_dung_tra_loi']);
            // Lưu trả lời, trạng thái = cho_duyet (chờ trưởng ĐV duyệt)
            $pdo->prepare("
                INSERT INTO tra_loi (phan_hoi_id, nguoi_tra_loi_id, noi_dung, loai, trang_thai_duyet)
                VALUES (?, ?, ?, 'noi_bo', 'cho_duyet')
            ")->execute([$id, $canBoId, $nd]);

            // Cập nhật trạng thái phản hồi
            $pdo->prepare("UPDATE phan_hoi SET trang_thai = 'cho_duyet_tl', updated_at = NOW() WHERE id = ?")->execute([$id]);
            $pdo->prepare("INSERT INTO lich_su_trang_thai (phan_hoi_id, trang_thai_cu, trang_thai_moi, nguoi_thay_doi_id, ghi_chu) VALUES (?,?,?,?,?)")
                ->execute([$id, $ph['trang_thai'], 'cho_duyet_tl', $canBoId, 'Cán bộ đã soạn câu trả lời, chờ trưởng ĐV duyệt']);

            // Thông báo cho trưởng đơn vị
            if ($ph['truong_don_vi_id']) {
                $pdo->prepare("INSERT INTO thong_bao (nguoi_nhan_id, tieu_de, noi_dung, loai, phan_hoi_id) VALUES (?,?,?,?,?)")
                    ->execute([
                        $ph['truong_don_vi_id'],
                        'Câu trả lời chờ duyệt',
                        "Cán bộ đã soạn câu trả lời cho phản hồi \"" . $ph['tieu_de'] . "\". Vui lòng xem và duyệt.",
                        'duyet_tra_loi', $id
                    ]);
            }
            chuyenHuong("phan-hoi-xu-ly.php?id=$id&msg=gui_ok");
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'gui_ok') {
    $msg = 'Đã gửi câu trả lời. Đang chờ trưởng đơn vị duyệt.';
}

// Lấy lịch sử trao đổi (bao gồm ghi chú nội bộ)
$traLois = $pdo->prepare("
    SELECT tl.*, u.ho_ten, u.vai_tro
    FROM tra_loi tl
    JOIN users u ON tl.nguoi_tra_loi_id = u.id
    WHERE tl.phan_hoi_id = ?
    ORDER BY tl.created_at ASC
");
$traLois->execute([$id]);
$traLois = $traLois->fetchAll();

$pageTitle  = 'Xử lý phản hồi #' . $id;
$activeMenu = 'phan_hoi';
include __DIR__ . '/../includes/header.php';
?>