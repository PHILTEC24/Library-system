    </main>

    <footer class="lms-footer">
        <span>&copy; <?= date('Y') ?> LibraryMS &mdash; Modern Library Management System</span>
    </footer>
</div>

<!-- Universal Accessible Confirmation Modal -->
<div class="modal fade" id="lms-confirm-modal" tabindex="-1" aria-labelledby="lms-confirm-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered lms-confirm-dialog">
    <div class="modal-content">
      <div class="modal-body lms-confirm">
        <span class="lms-confirm__icon" id="lms-confirm-icon" aria-hidden="true">
          <i class="bi bi-question-lg"></i>
        </span>
        <h2 class="lms-confirm__title" id="lms-confirm-title">Confirm action</h2>
        <p class="lms-confirm__body" id="lms-confirm-body">Are you sure you want to proceed with this action?</p>
      </div>
      <div class="modal-footer lms-confirm__footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="lms-confirm-btn">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
