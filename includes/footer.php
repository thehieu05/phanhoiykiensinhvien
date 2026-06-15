
</div><!-- end main-content -->
</div><!-- end d-flex -->

<?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'truong_don_vi'): ?>
<!-- Reusable Feedback Detail Modal for Truong Don Vi -->
<div class="modal fade" id="feedbackDetailModal" tabindex="-1" aria-labelledby="feedbackDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: none; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;">
      <div class="modal-header bg-light py-3" style="border-bottom: 1px solid #dee2e6; flex-shrink: 0;">
        <h5 class="modal-title fw-bold text-dark" id="feedbackDetailModalLabel">
          <i class="fas fa-info-circle text-primary me-2"></i>Chi tiết phản hồi ý kiến
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div id="feedbackDetailModalContent" style="overflow-y: auto; flex-grow: 1;">
        <!-- Loaded via AJAX -->
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
          </div>
          <p class="text-muted mt-2 small">Đang tải chi tiết phản hồi...</p>
        </div>
      </div>
      <div class="modal-footer bg-light" style="border-top: 1px solid #dee2e6; flex-shrink: 0;">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
function xemChiTietPhanHoi(id) {
    // Show modal
    var element = document.getElementById('feedbackDetailModal');
    var myModal = bootstrap.Modal.getOrCreateInstance(element);
    myModal.show();
    
    // Reset loader
    document.getElementById('feedbackDetailModalContent').innerHTML = `
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
          </div>
          <p class="text-muted mt-2 small">Đang tải chi tiết phản hồi...</p>
        </div>`;
    
    // Fetch content via AJAX
    fetch('<?= SITE_URL ?>/truongdonvi/ajax-chi-tiet.php?id=' + id)
        .then(response => response.text())
        .then(html => {
            document.getElementById('feedbackDetailModalContent').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('feedbackDetailModalContent').innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>Đã xảy ra lỗi khi tải dữ liệu. Vui lòng thử lại.
                </div>`;
            console.error(err);
        });
}

function cuonDenFormHuy() {
    const card = document.getElementById('cardHuyPhanHoi');
    if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const txt = card.querySelector('textarea[name="ly_do_huy"]');
        if (txt) {
            txt.focus();
            txt.style.borderColor = '#dc3545';
            txt.style.boxShadow = '0 0 0 0.25rem rgba(220, 53, 69, 0.25)';
            setTimeout(() => {
                txt.style.borderColor = '';
                txt.style.boxShadow = '';
            }, 2000);
        }
    }
}

function handleHuyPhanHoi(event, id) {
    event.preventDefault();
    const form = event.target;
    const lyDo = form.querySelector('[name="ly_do_huy"]').value.trim();
    if (!lyDo) {
        alert('Vui lòng nhập lý do hủy.');
        return;
    }
    if (!confirm('Bạn có chắc chắn muốn hủy phản hồi này?')) {
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang xử lý...';

    const formData = new FormData();
    formData.append('action', 'huy_phan_hoi');
    formData.append('id', id);
    formData.append('ly_do_huy', lyDo);

    fetch('<?= SITE_URL ?>/truongdonvi/ajax-chi-tiet.php?id=' + id, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Mạng không ổn định hoặc có lỗi hệ thống.');
        }
        return response.json();
    })
    .then(res => {
        if (res.success) {
            alert(res.message);
            location.reload();
        } else {
            alert(res.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        alert(err.message || 'Đã xảy ra lỗi kết nối. Vui lòng thử lại.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
</script>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
