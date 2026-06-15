<?php
include __DIR__ . '/../config/config.php';

// --- XÁC THỰC & PHÂN QUYỀN ---
function dangNhap($usernameOrEmail, $matKhau) {
    global $conn;
    $sql = "SELECT * FROM users WHERE (email = ? OR ma_sv_gv = ?) AND trang_thai = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user && password_verify($matKhau, $user['mat_khau'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['ho_ten']    = $user['ho_ten'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['vai_tro']   = $user['vai_tro'];
        $_SESSION['don_vi_id'] = $user['don_vi_id'];
        return $user;
    }
    return false;
}

function dangXuat() {
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

function kiemTraDangNhap() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/index.php?msg=login_required');
        exit;
    }
}

function kiemTraVaiTro($vaiTro) {
    kiemTraDangNhap();
    $ds = is_array($vaiTro) ? $vaiTro : [$vaiTro];
    if (!in_array($_SESSION['vai_tro'], $ds)) {
        header('Location: ' . SITE_URL . '/index.php?msg=access_denied');
        exit;
    }
}

function layThongTinUser($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.*, dv.ten_don_vi FROM users u LEFT JOIN don_vi dv ON u.don_vi_id = dv.id WHERE u.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// --- TIỆN ÍCH ---
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function loiXhtmlEntities($str) { return e($str); }

function chuyenHuong($url) { header("Location: $url"); exit; }

function flashMessage($key, $msg = null) {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; }
    else { $val = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $val; }
}

function thoiGianTuongDoi($time) {
    $diff = time() - strtotime($time);
    if ($diff < 60) return 'Vừa xong';
    if ($diff < 3600) return floor($diff/60).' phút trước';
    if ($diff < 86400) return floor($diff/3600).' giờ trước';
    if ($diff < 604800) return floor($diff/86400).' ngày trước';
    return date('d/m/Y', strtotime($time));
}

function nhanTrangThai($ts) {
    return [
        'cho_xu_ly'    => 'Chờ xử lý',           // Sinh viên vừa gửi
        'da_phan_cong' => 'Đã phân công xử lý',   // Trưởng đơn vị đã phân công
        'dang_xu_ly'   => 'Đang xử lý',           // Cán bộ đã tiếp nhận
        'cho_bo_sung'  => 'Chờ bổ sung',
        'da_xu_ly'     => 'Đã xử lý',
        'da_huy'       => 'Đã hủy',
        'tu_choi'      => 'Từ chối',
        // legacy
        'da_tiep_nhan' => 'Đã tiếp nhận',
        'cho_duyet'    => 'Chờ xử lý',
        'cho_duyet_tl' => 'Chờ duyệt',
    ][$ts] ?? $ts;
}

function mauTrangThai($ts) {
    return [
        'cho_xu_ly'    => 'warning',    // Vàng – chờ xử lý
        'da_phan_cong' => 'info',       // Xanh nhạt – đã phân công
        'dang_xu_ly'   => 'primary',    // Xanh đậm – đang xử lý
        'cho_bo_sung'  => 'warning',
        'da_xu_ly'     => 'success',
        'da_huy'       => 'secondary',
        'tu_choi'      => 'danger',
        // legacy
        'da_tiep_nhan' => 'info',
        'cho_duyet'    => 'warning',
        'cho_duyet_tl' => 'warning',
    ][$ts] ?? 'secondary';
}

function iconTrangThai($ts) {
    return [
        'cho_xu_ly'    => 'fas fa-clock',
        'da_tiep_nhan' => 'fas fa-inbox',
        'dang_xu_ly'   => 'fas fa-spinner',
        'cho_bo_sung'  => 'fas fa-exclamation-circle',
        'da_xu_ly'     => 'fas fa-check-circle',
        'da_huy'       => 'fas fa-ban',
        'tu_choi'      => 'fas fa-times-circle',
    ][$ts] ?? 'fas fa-circle';
}

function nhanMucDo($m) {
    return ['thap'=>'Thấp','trung_binh'=>'Trung bình','cao'=>'Cao','khan_cap'=>'Khẩn cấp'][$m] ?? $m;
}

function mauMucDo($m) {
    return ['thap'=>'success','trung_binh'=>'info','cao'=>'warning','khan_cap'=>'danger'][$m] ?? 'secondary';
}

function phanTrang($totalPages, $currentPage, $baseUrl) {
    if ($totalPages <= 1) return '';
    $html = '<nav><ul class="pagination justify-content-center mb-0">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= "<li class='page-item $active'><a class='page-link' href='{$baseUrl}&page=$i'>$i</a></li>";
    }
    $html .= '</ul></nav>';
    return $html;
}

// --- CHỦ ĐỀ & LOẠI PHẢN HỒI ---
function layDanhSachChuDe($chiActive = true) {
    global $pdo;
    $where = $chiActive ? 'WHERE cd.trang_thai = 1' : '';
    return $pdo->query("
        SELECT cd.*, dv.ten_don_vi
        FROM chu_de cd
        LEFT JOIN don_vi dv ON cd.don_vi_id = dv.id
        $where ORDER BY cd.ten_chu_de
    ")->fetchAll();
}

function layLoaiTheoChuDe($chuDeId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM loai_phan_hoi WHERE chu_de_id = ? AND trang_thai = 1 ORDER BY ten_loai");
    $stmt->execute([$chuDeId]);
    return $stmt->fetchAll();
}

function layDanhSachDonVi($chiActive = true) {
    global $pdo;
    $where = $chiActive ? 'WHERE trang_thai = 1' : '';
    return $pdo->query("SELECT * FROM don_vi $where ORDER BY ten_don_vi")->fetchAll();
}

// --- PHẢN HỒI ---
function taoMaPhanHoi() {
    global $pdo;
    $nam = date('Y');
    $stt = $pdo->query("SELECT COUNT(*) FROM phan_hoi WHERE YEAR(created_at) = $nam")->fetchColumn() + 1;
    return 'PH' . $nam . str_pad($stt, 4, '0', STR_PAD_LEFT);
}

function timCanBoPhuTrach($tieuDe, $noiDung, $donViId) {
    global $pdo;
    if (!$donViId) return null;
    $stmt = $pdo->prepare("SELECT id, ho_ten, tu_khoa FROM users WHERE vai_tro = 'can_bo' AND don_vi_id = ? AND trang_thai = 1");
    $stmt->execute([$donViId]);
    $canBos = $stmt->fetchAll();

    $text = mb_strtolower($tieuDe . ' ' . $noiDung, 'UTF-8');

    foreach ($canBos as $cb) {
        if (empty($cb['tu_khoa'])) continue;
        // Split by comma
        $kws = array_filter(array_map('trim', explode(',', $cb['tu_khoa'])));
        foreach ($kws as $kw) {
            if (empty($kw)) continue;
            $normalizedKw = mb_strtolower($kw, 'UTF-8');
            // Use substring matching
            if (mb_strpos($text, $normalizedKw) !== false) {
                return [
                    'id' => $cb['id'],
                    'ho_ten' => $cb['ho_ten'],
                    'tu_khoa' => $kw
                ];
            }
        }
    }
    return null;
}

function timTruongDonVi($donViId) {
    global $pdo;
    if (!$donViId) return null;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE vai_tro = 'truong_don_vi' AND don_vi_id = ? AND trang_thai = 1 LIMIT 1");
    $stmt->execute([$donViId]);
    return $stmt->fetchColumn() ?: null;
}

function guiPhanHoi($data, $userId = null) {
    global $pdo;
    $ma = taoMaPhanHoi();
    
    // Lấy đơn vị xử lý từ chủ đề
    $stmtCd = $pdo->prepare("SELECT don_vi_id FROM chu_de WHERE id = ?");
    $stmtCd->execute([$data['chu_de_id']]);
    $donViId = $stmtCd->fetchColumn() ?: null;

    // AI Auto-assignment matching
    $matchedCb = timCanBoPhuTrach($data['tieu_de'], $data['noi_dung'], $donViId);
    $tdvId = timTruongDonVi($donViId);
    
    if ($matchedCb) {
        $canBoXuLyId = $matchedCb['id'];
        $trangThai = 'dang_xu_ly';
        $ngayPhanCong = date('Y-m-d H:i:s');
        $ghiChuPhanCong = 'Hệ thống tự động phân công dựa trên từ khóa: ' . $matchedCb['tu_khoa'];
        $hanXuLy = date('Y-m-d', strtotime('+7 days'));
    } else {
        $canBoXuLyId = null;
        $trangThai = 'cho_xu_ly';
        $ngayPhanCong = null;
        $ghiChuPhanCong = null;
        $hanXuLy = null;
    }

    $stmt = $pdo->prepare("
        INSERT INTO phan_hoi (ma_phan_hoi, tieu_de, noi_dung, chu_de_id, loai_phan_hoi_id, nguoi_gui_id, an_danh, muc_do_uu_tien, don_vi_xu_ly_id, can_bo_xu_ly_id, truong_don_vi_id, trang_thai, ghi_chu_phan_cong, ngay_phan_cong, han_xu_ly)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $ma,
        $data['tieu_de'],
        $data['noi_dung'],
        $data['chu_de_id'] ?: null,
        $data['loai_phan_hoi_id'] ?: null,
        ($data['an_danh'] ?? 0) ? null : $userId,
        ($data['an_danh'] ?? 0) ? 1 : 0,
        $data['muc_do_uu_tien'] ?? 'trung_binh',
        $donViId,
        $canBoXuLyId,
        $tdvId,
        $trangThai,
        $ghiChuPhanCong,
        $ngayPhanCong,
        $hanXuLy
    ]);
    $phanHoiId = $pdo->lastInsertId();

    // Lịch sử
    if ($matchedCb) {
        ghiLichSu($phanHoiId, null, 'cho_xu_ly', null, 'Hệ thống tiếp nhận phản hồi');
        ghiLichSu($phanHoiId, 'cho_xu_ly', 'dang_xu_ly', null, 'Hệ thống tự động phân công cho cán bộ: ' . $matchedCb['ho_ten'] . ' (khớp từ khóa: "' . $matchedCb['tu_khoa'] . '")');
        
        // Thông báo cán bộ phụ trách
        themThongBao($canBoXuLyId, 'Phản hồi tự động phân công', "Bạn được tự động phân công xử lý phản hồi: \"{$data['tieu_de']}\" do khớp từ khóa \"{$matchedCb['tu_khoa']}\".", 'phan_cong', $phanHoiId);
        
        // Đồng thời thông báo cho Trưởng đơn vị biết để theo dõi
        if ($tdvId) {
            themThongBao($tdvId, 'Tự động phân công phản hồi', "Phản hồi mới \"{$data['tieu_de']}\" đã được tự động phân công cho cán bộ: {$matchedCb['ho_ten']}.", 'phan_cong', $phanHoiId);
        }
    } else {
        ghiLichSu($phanHoiId, null, 'cho_xu_ly', $userId, 'Sinh viên gửi phản hồi');
        
        // Thông báo trưởng đơn vị / admin để phân công thủ công
        if ($tdvId) {
            themThongBao($tdvId, 'Phản hồi mới chờ phân công', "Phản hồi mới chờ phân công: \"{$data['tieu_de']}\" (Không khớp từ khóa AI nào)", 'phan_hoi_moi', $phanHoiId);
        }
    }

    // Thông báo cho admin
    $admins = $pdo->query("SELECT id FROM users WHERE vai_tro = 'admin' AND trang_thai = 1")->fetchAll();
    foreach ($admins as $a) {
        themThongBao($a['id'], 'Phản hồi mới', "Phản hồi mới: \"{$data['tieu_de']}\"", 'phan_hoi_moi', $phanHoiId);
    }

    if (!($data['an_danh'] ?? 0) && $userId) {
        $user = layThongTinUser($userId);
        guiEmail($user['email'], 'Xác nhận gửi phản hồi', "Cảm ơn bạn đã gửi phản hồi: <strong>{$data['tieu_de']}</strong>. Mã phản hồi: <strong>$ma</strong>. Chúng tôi sẽ xem xét sớm nhất.");
    }
    return $phanHoiId;
}

function uploadFile($file, $phanHoiId, $traLoiId = null) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) return false;
    global $pdo;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx'];
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;

    $dir = UPLOAD_DIR . $phanHoiId . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tenFile = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $duongDan = $dir . $tenFile;
    if (!move_uploaded_file($file['tmp_name'], $duongDan)) return false;

    $stmt = $pdo->prepare("INSERT INTO dinh_kem (phan_hoi_id, tra_loi_id, ten_file, ten_goc, duong_dan, loai_file, kich_thuoc) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$phanHoiId, $traLoiId, $tenFile, $file['name'], $phanHoiId.'/'.$tenFile, $ext, $file['size']]);
    return $pdo->lastInsertId();
}

function layDinhKem($phanHoiId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM dinh_kem WHERE phan_hoi_id = ? AND tra_loi_id IS NULL ORDER BY created_at");
    $stmt->execute([$phanHoiId]);
    return $stmt->fetchAll();
}

function ghiLichSu($phanHoiId, $cu, $moi, $userId, $ghiChu = '') {
    global $pdo;
    $pdo->prepare("INSERT INTO lich_su_trang_thai (phan_hoi_id, trang_thai_cu, trang_thai_moi, nguoi_thay_doi_id, ghi_chu) VALUES (?,?,?,?,?)")
        ->execute([$phanHoiId, $cu, $moi, $userId, $ghiChu]);
}

function capNhatTrangThai($phanHoiId, $trangThaiMoi, $userId, $ghiChu = '') {
    global $pdo;
    $ph = $pdo->prepare("SELECT * FROM phan_hoi WHERE id = ?");
    $ph->execute([$phanHoiId]);
    $phanHoi = $ph->fetch();
    if (!$phanHoi) return false;

    $pdo->prepare("UPDATE phan_hoi SET trang_thai = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$trangThaiMoi, $phanHoiId]);
    ghiLichSu($phanHoiId, $phanHoi['trang_thai'], $trangThaiMoi, $userId, $ghiChu);

    if ($phanHoi['nguoi_gui_id']) {
        $label = nhanTrangThai($trangThaiMoi);
        themThongBao($phanHoi['nguoi_gui_id'], 'Cập nhật phản hồi', "Phản hồi \"{$phanHoi['tieu_de']}\" đã chuyển sang: $label", 'cap_nhat_trang_thai', $phanHoiId);
        $user = layThongTinUser($phanHoi['nguoi_gui_id']);
        guiEmail($user['email'], 'Cập nhật trạng thái phản hồi', "Phản hồi của bạn đã được cập nhật sang: <strong>$label</strong>");
    }
    return true;
}

function goiYTuDong($tieuDe, $limit = 3) {
    global $pdo;
    $kw = '%' . trim($tieuDe) . '%';
    $stmt = $pdo->prepare("
        SELECT ph.id, ph.tieu_de, ph.noi_dung, ph.trang_thai,
               cd.ten_chu_de,
               tl.noi_dung AS noi_dung_tra_loi,
               u.ho_ten AS ten_can_bo,
               ph.updated_at
        FROM phan_hoi ph
        LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
        LEFT JOIN tra_loi tl ON tl.phan_hoi_id = ph.id AND tl.loai = 'chinh_thuc' AND tl.trang_thai_duyet = 'da_duyet'
        LEFT JOIN users u ON tl.nguoi_tra_loi_id = u.id
        WHERE ph.trang_thai = 'da_xu_ly'
          AND (ph.tieu_de LIKE ? OR ph.noi_dung LIKE ?)
        ORDER BY ph.updated_at DESC
        LIMIT ?
    ");
    $stmt->execute([$kw, $kw, $limit]);
    return $stmt->fetchAll();
}

function layDanhSachPhanHoi($filters = [], $page = 1, $perPage = 15) {
    global $pdo;
    $where = ['1=1']; $params = [];

    if (!empty($filters['trang_thai'])) { $where[] = 'ph.trang_thai = ?'; $params[] = $filters['trang_thai']; }
    if (!empty($filters['chu_de_id']))  { $where[] = 'ph.chu_de_id = ?'; $params[] = $filters['chu_de_id']; }
    if (!empty($filters['nguoi_gui_id'])) { $where[] = 'ph.nguoi_gui_id = ?'; $params[] = $filters['nguoi_gui_id']; }
    if (!empty($filters['can_bo_id']))  { $where[] = 'ph.can_bo_xu_ly_id = ?'; $params[] = $filters['can_bo_id']; }
    if (!empty($filters['don_vi_id']))  { $where[] = 'ph.don_vi_xu_ly_id = ?'; $params[] = $filters['don_vi_id']; }
    if (!empty($filters['truong_don_vi_id'])) { $where[] = 'ph.truong_don_vi_id = ?'; $params[] = $filters['truong_don_vi_id']; }
    if (!empty($filters['search'])) {
        $where[] = '(ph.tieu_de LIKE ? OR ph.noi_dung LIKE ? OR ph.ma_phan_hoi LIKE ?)';
        $kw = '%'.$filters['search'].'%';
        $params[] = $kw; $params[] = $kw; $params[] = $kw;
    }
    if (!empty($filters['tu_ngay'])) { $where[] = 'DATE(ph.created_at) >= ?'; $params[] = $filters['tu_ngay']; }
    if (!empty($filters['den_ngay'])) { $where[] = 'DATE(ph.created_at) <= ?'; $params[] = $filters['den_ngay']; }

    $whereStr = implode(' AND ', $where);
    $offset = ($page - 1) * $perPage;

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi ph WHERE $whereStr");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT ph.*, cd.ten_chu_de, cd.icon,
               lph.ten_loai,
               u.ho_ten as ten_nguoi_gui,
               cb.ho_ten as ten_can_bo,
               dv.ten_don_vi,
               (SELECT COUNT(*) FROM tra_loi tl WHERE tl.phan_hoi_id = ph.id AND tl.loai='chinh_thuc' AND tl.trang_thai_duyet='da_duyet') as so_tra_loi
        FROM phan_hoi ph
        LEFT JOIN chu_de cd ON ph.chu_de_id = cd.id
        LEFT JOIN loai_phan_hoi lph ON ph.loai_phan_hoi_id = lph.id
        LEFT JOIN users u ON ph.nguoi_gui_id = u.id
        LEFT JOIN users cb ON ph.can_bo_xu_ly_id = cb.id
        LEFT JOIN don_vi dv ON ph.don_vi_xu_ly_id = dv.id
        WHERE $whereStr
        ORDER BY ph.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);

    return ['data'=>$stmt->fetchAll(), 'total'=>$total, 'pages'=>ceil($total/$perPage), 'page'=>$page];
}

// --- THÔNG BÁO ---
function themThongBao($nguoiNhanId, $tieuDe, $noiDung, $loai = 'he_thong', $phanHoiId = null) {
    global $pdo;
    $pdo->prepare("INSERT INTO thong_bao (nguoi_nhan_id, tieu_de, noi_dung, loai, phan_hoi_id) VALUES (?,?,?,?,?)")
        ->execute([$nguoiNhanId, $tieuDe, $noiDung, $loai, $phanHoiId]);
}

function demThongBaoChuaDoc($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM thong_bao WHERE nguoi_nhan_id = ? AND da_doc = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function layThongBao($userId, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM thong_bao WHERE nguoi_nhan_id = ? ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function danhDauDaDoc($thongBaoId, $userId) {
    global $pdo;
    $pdo->prepare("UPDATE thong_bao SET da_doc = 1 WHERE id = ? AND nguoi_nhan_id = ?")->execute([$thongBaoId, $userId]);
}

// --- EMAIL ---
function guiEmail($to, $subject, $body) {
    $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $full = "<html><body style='font-family:Arial;'><div style='max-width:600px;margin:0 auto;'>
        <div style='background:#003087;color:#fff;padding:20px;border-radius:8px 8px 0 0;text-align:center;'>
            <h2>🎓 Trường Đại học Vinh</h2><p>Hệ thống Phản hồi Ý kiến Sinh viên</p>
        </div>
        <div style='background:#fff;padding:24px;border-radius:0 0 8px 8px;border:1px solid #eee;'>
            <h3>$subject</h3><p>$body</p>
            <hr><p style='color:#999;font-size:11px;'>Email tự động, vui lòng không trả lời.</p>
        </div></div></body></html>";
    @mail($to, $subject, $full, $headers);
}

// --- THỐNG KÊ ---
function layThongKe($donViId = null, $tuNgay = null, $denNgay = null) {
    global $pdo;
    $where = ['1=1']; $params = [];
    if ($donViId) { $where[] = 'ph.don_vi_xu_ly_id = ?'; $params[] = $donViId; }
    if ($tuNgay)  { $where[] = 'DATE(ph.created_at) >= ?'; $params[] = $tuNgay; }
    if ($denNgay) { $where[] = 'DATE(ph.created_at) <= ?'; $params[] = $denNgay; }
    $w = implode(' AND ', $where);

    $stats = [];
    foreach (['cho_xu_ly','da_tiep_nhan','dang_xu_ly','cho_bo_sung','da_xu_ly','da_huy','tu_choi'] as $ts) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi ph WHERE $w AND ph.trang_thai = ?");
        $s->execute(array_merge($params, [$ts]));
        $stats[$ts] = $s->fetchColumn();
    }
    $s = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi ph WHERE $w");
    $s->execute($params);
    $stats['tong'] = $s->fetchColumn();

    // Quá hạn
    $s2 = $pdo->prepare("SELECT COUNT(*) FROM phan_hoi ph WHERE $w AND ph.han_xu_ly < CURDATE() AND ph.trang_thai NOT IN ('da_xu_ly','da_huy','tu_choi')");
    $s2->execute($params);
    $stats['qua_han'] = $s2->fetchColumn();

    // Theo chủ đề
    $s3 = $pdo->prepare("SELECT cd.ten_chu_de, cd.icon, COUNT(ph.id) as so_luong FROM chu_de cd LEFT JOIN phan_hoi ph ON ph.chu_de_id = cd.id AND $w GROUP BY cd.id ORDER BY so_luong DESC");
    $s3->execute($params);
    $stats['theo_chu_de'] = $s3->fetchAll();

    // Theo tháng
    $s4 = $pdo->prepare("SELECT DATE_FORMAT(ph.created_at,'%m/%Y') as thang, COUNT(*) as so_luong FROM phan_hoi ph WHERE $w AND ph.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(ph.created_at,'%Y-%m') ORDER BY ph.created_at ASC");
    $s4->execute($params);
    $stats['theo_thang'] = $s4->fetchAll();

    // Theo cán bộ
    $s5 = $pdo->prepare("SELECT u.ho_ten, COUNT(ph.id) as tong, SUM(ph.trang_thai='da_xu_ly') as da_xu_ly FROM users u LEFT JOIN phan_hoi ph ON ph.can_bo_xu_ly_id = u.id AND $w WHERE u.vai_tro = 'can_bo' GROUP BY u.id ORDER BY tong DESC");
    $s5->execute($params);
    $stats['theo_can_bo'] = $s5->fetchAll();

    // Đánh giá trung bình
    $s6 = $pdo->prepare("SELECT AVG(dg.diem_so) as trung_binh, COUNT(dg.id) as so_danh_gia FROM danh_gia dg JOIN phan_hoi ph ON dg.phan_hoi_id = ph.id WHERE $w");
    $s6->execute($params);
    $stats['danh_gia'] = $s6->fetch();

    return $stats;
}
