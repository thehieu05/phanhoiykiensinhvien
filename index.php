<?php
include("includes/functions.php");

// Nếu đã đăng nhập -> chuyển hướng
if (!empty($_SESSION['user_id'])) {
    switch ($_SESSION['vai_tro']) {
        case 'admin':      chuyenHuong(SITE_URL . '/admin/'); break;
        case 'truong_don_vi': chuyenHuong(SITE_URL . '/truongdonvi/'); break;
        case 'can_bo': chuyenHuong(SITE_URL . '/canbo/'); break;
        default:           chuyenHuong(SITE_URL . '/sinh-vien/'); break;
    }
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Khớp với name="Username" và name="Password" ở form HTML bên dưới
    $usernameOrEmail = ($_POST['Username'] ?? '');
    $matKhau = $_POST['Password'] ?? '';
    
    if (empty($usernameOrEmail) || empty($matKhau)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập, email và mật khẩu.';
    } else {
        $user = dangNhap($usernameOrEmail, $matKhau);
        if ($user) {
            switch ($user['vai_tro']) {
                case 'admin':      chuyenHuong(SITE_URL . '/admin/'); break;
                case 'truong_don_vi': chuyenHuong(SITE_URL . '/truongdonvi/'); break;
                case 'can_bo': chuyenHuong(SITE_URL . '/canbo/'); break;
                default:           chuyenHuong(SITE_URL . '/sinh-vien/'); break;
            }
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi-vn">
<head>
    <meta charset="utf-8" />
    <title>Đăng nhập - Trường Đại học Vinh</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="assets/favicon.ico" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700italic,700,900,900italic" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">
    <style type="text/css">
        [fuse-cloak],
        .fuse-cloak {
            display: none !important;
        }
        .validation-summary-errors ul li {
            transform: translateY(25%);
        }
    </style>
    <link rel="stylesheet" type="text/css" href="assets/css/main.css">
    <style type="text/css">
        body {
            background: url("assets/img/bg-login.jpg") no-repeat;
            background-size: cover;
            font-family: 'Roboto Condensed', sans-serif;
        }
        /* Style bổ sung cho thông báo lỗi */
        .alert-custom {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
        }
        .alert-danger-custom { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success-custom { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning-custom { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    </style>
    <link type="text/css" rel="stylesheet" href="assets/css/custom.css" />
    </head>
<body class="layout layout-vertical layout-left-navigation layout-above-toolbar layout-above-footer">
    <main>
        <div id="wrapper">
            <div class="content-wrapper">
                
<div class="bg-login clearfix">
    <div class="tn-row">
        <div class="custom-left">
            <div class="bxinfo">
                <h1 class="titu">
                    <span>Trường Đại Học Vinh</span>
                </h1>
                <p class="desinf">
                        <div> Địa chỉ: 182 Lê Duẩn - Thành Phố Vinh - tỉnh Nghệ An </div>
                    <div>
                            <span>Điện thoại: (038)3855452</span>
                            <span>Fax: (038)3855269</span>
                            <span>Email: vinhuni@vinhuni.edu.vn</span>
                    </div>
                </p>
                <p class="desinf">
                    
                </p>
            </div>
        </div>
        <div class="custom-right">
            <div class="bxform">
                <div class="logolg">
                    <div class="tn-logo">
                        <img class="tn-logo__image" src="assets/img/logo-dh-vinh.png" alt="" title="">

                        <div class="tn-logo__text-wrapper">
                            <div class="tn-logo__text-line-1"><span>Trường Đại Học Vinh</span></div>
                            <div class="tn-logo__text-line-2">HỆ THỐNG QUẢN TRỊ ĐẠI HỌC THÔNG MINH - USMART</div>
                        </div>
                    </div>
                </div>
                        
                    <form method="post" class="tn-form-container" action="">
                        
                        <?php if ($error): ?>
                            <div class="alert-custom alert-danger-custom">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_GET['msg'])): ?>
                            <?php if ($_GET['msg'] === 'dang_xuat'): ?>
                                <div class="alert-custom alert-success-custom">
                                    <?php echo "Bạn đã đăng xuất thành công."; ?>
                                </div>
                            <?php elseif ($_GET['msg'] === 'login_required'): ?>
                                <div class="alert-custom alert-warning-custom">
                                    <?php echo "Vui lòng đăng nhập để tiếp tục."; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <input type="hidden" id="ReturnUrl" name="ReturnUrl" value="" />
                        <input type="hidden" data-val="true" data-val-required="The FromApp field is required." id="FromApp" name="FromApp" value="False" />
                        
                        <div class="form-group md-focus tn-form-control">
                            <div class="tn-form-control__icon fa fa-user"></div>

                            <input id="username" autofocus type="text" class="form-control frmcust tn-form-control__input" placeholder="Tên đăng nhập, email" data-val="true" data-val-required="Tên đăng nhập, email hoặc không được để trống" name="Username" />
                        </div>
                        <div class="form-group md-focus tn-form-control">
                            <div class="tn-form-control__icon fa fa-key"></div>

                            <input id="password" type="password" class="form-control frmcust tn-form-control__input tn-form-control__input-password" placeholder="Mật khẩu" data-val="true" data-val-required="Mật khẩu không được để trống" name="Password" />

                            <div class="tn-form-control__icon --password-visibility" onclick="togglePasswordVisibility()"></div>
                        </div>
                        <div class="form-check tn-form-check clearfix">
                            <div class="reml">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" aria-label="Ghi nhớ đăng nhập" />
                                    <span class="checkbox-icon"></span>
                                    <span class="form-check-description">Ghi nhớ đăng nhập</span>
                                </label>
                            </div>
                            <div class="remr">
                                <a href="/Account/ForgotPassword">Quên mật khẩu?</a>
                            </div>
                        </div>
                        <button name="button" value="login" type="submit"
                            class="submit-button btn btn-block btn-secondary my-4 mx-auto tn-submit-button"
                            aria-label="Đăng nhập">
                            Đăng nhập
                        </button>

                        <div class="tn-other-login-methods">

                        </div>
                    </form>
            </div>
        </div>
        </div>
    </div>
<script>
    function togglePasswordVisibility() {
        var x = document.getElementById("password");
        if (x.type === "password") {
            x.type = "text";
        } else {
            x.type = "password";
        }
    }
</script>
            </div>
        </div>
    </main>
</body>
</html>