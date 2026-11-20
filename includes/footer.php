    </main>
</div>

<!-- Universal Accessible Confirmation Modal (Section 6.9) -->
<div class="modal fade" id="lms-confirm-modal" tabindex="-1" aria-labelledby="lms-confirm-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: var(--lms-radius-card);">
      <div class="modal-header border-bottom border-light-subtle px-4 pt-4 pb-3">
        <h5 class="modal-title fs-5 fw-bold text-dark d-flex align-items-center gap-2" id="lms-confirm-title">
          <i class="bi bi-question-circle text-primary"></i> Confirm Action
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3 text-secondary" id="lms-confirm-body" style="font-size: 0.95rem; line-height: 1.5;">
        Are you sure you want to proceed with this action?
      </div>
      <div class="modal-footer border-top-0 px-4 pb-4 pt-2 gap-2">
        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary px-3" id="lms-confirm-btn">Confirm</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container (Section 6.10) -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="lms-toast-container" style="z-index: 1090;"></div>

<footer class="lms-footer py-3 border-top text-center text-muted small">
  <div class="container-fluid px-4">
    <span>&copy; <?= date('Y') ?> LibraryMS &mdash; Modern Library Management System</span>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
