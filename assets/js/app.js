/**
 * LibraryMS - Main Application JavaScript
 * Under 15KB unminified, vanilla JS, zero dependencies except Bootstrap 5.3
 */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // Spine Palette (matching PHP helper and CSS tokens)
  const SPINE_PALETTE = ['#3F5BA9', '#2E7D6B', '#8C4A6B', '#A85A17', '#5A6B3A', '#5E548E'];

  // =========================================================================
  // 1. Toast Notification Handler (from PHP flash messages)
  // =========================================================================
  const flashHolder = document.querySelector('.lms-flash-holder');
  const toastContainer = document.getElementById('lms-toast-container');

  if (flashHolder && toastContainer && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
    const flashType = flashHolder.getAttribute('data-flash-type') || 'info';
    const flashMessage = flashHolder.getAttribute('data-flash-message') || '';

    if (flashMessage) {
      const isError = flashType === 'danger' || flashType === 'error';
      const iconClass = isError ? 'bi-exclamation-octagon-fill text-danger' :
                        (flashType === 'success' ? 'bi-check-circle-fill text-success' : 'bi-info-circle-fill text-primary');
      const toastEl = document.createElement('div');
      toastEl.className = 'toast align-items-center border-0';
      toastEl.setAttribute('role', isError ? 'alert' : 'status');
      toastEl.setAttribute('aria-live', isError ? 'assertive' : 'polite');
      toastEl.setAttribute('aria-atomic', 'true');

      toastEl.innerHTML = `
        <div class="d-flex p-2">
          <div class="toast-body d-flex align-items-center gap-2">
            <i class="bi ${iconClass} fs-5"></i>
            <span>${flashMessage}</span>
          </div>
          <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      `;

      toastContainer.appendChild(toastEl);
      const toast = new bootstrap.Toast(toastEl, {
        autohide: !isError,
        delay: 5000
      });
      toast.show();
    }
  }

  // =========================================================================
  // 2. Universal Confirmation Modal (replaces browser confirm())
  // =========================================================================
  const confirmModalEl = document.getElementById('lms-confirm-modal');
  let confirmModalInstance = null;
  let pendingAction = null;

  if (confirmModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    confirmModalInstance = new bootstrap.Modal(confirmModalEl);
    const modalTitle = document.getElementById('lms-confirm-title');
    const modalBody = document.getElementById('lms-confirm-body');
    const modalBtn = document.getElementById('lms-confirm-btn');

    document.addEventListener('click', function (e) {
      const trigger = e.target.closest('[data-confirm], [data-confirm-message]');
      if (!trigger) return;

      // If inside or is a form submit button, intercept
      const tag = trigger.tagName.toLowerCase();
      const href = trigger.getAttribute('href');
      const form = trigger.closest('form');

      const title = trigger.getAttribute('data-confirm-title') || 'Confirm Action';
      const msg = trigger.getAttribute('data-confirm-message') || trigger.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
      const btnText = trigger.getAttribute('data-confirm-button') || 'Confirm';
      const btnStyle = trigger.getAttribute('data-confirm-style') || (msg.toLowerCase().includes('delete') ? 'danger' : 'primary');

      e.preventDefault();
      e.stopPropagation();

      modalTitle.textContent = title;
      modalBody.textContent = msg;
      modalBtn.textContent = btnText;
      modalBtn.className = 'btn btn-' + btnStyle;

      pendingAction = function () {
        if (form && (trigger.type === 'submit' || tag === 'form' || tag === 'button')) {
          // If trigger was a button, add its name/value before submitting if any
          if (trigger.name) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = trigger.name;
            hidden.value = trigger.value;
            form.appendChild(hidden);
          }
          // Remove listener to prevent recursion and submit
          form.submit();
        } else if (href && href !== '#') {
          window.location.href = href;
        }
      };

      confirmModalInstance.show();
    });

    if (modalBtn) {
      modalBtn.addEventListener('click', function () {
        if (typeof pendingAction === 'function') {
          const action = pendingAction;
          pendingAction = null;
          confirmModalInstance.hide();
          action();
        }
      });
    }
  }

  // =========================================================================
  // 3. Filter Bar Selects Auto-Submit
  // =========================================================================
  document.querySelectorAll('.lms-filter-select').forEach(function (select) {
    select.addEventListener('change', function () {
      if (select.form) {
        select.form.submit();
      }
    });
  });

  // =========================================================================
  // 4. Close Mobile Sidebar on Link Click
  // =========================================================================
  const mobileOffcanvasEl = document.getElementById('lmsMobileSidebar');
  if (mobileOffcanvasEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
    const offcanvasInstance = bootstrap.Offcanvas.getInstance(mobileOffcanvasEl) || new bootstrap.Offcanvas(mobileOffcanvasEl);
    mobileOffcanvasEl.querySelectorAll('.lms-nav-link').forEach(function (link) {
      link.addEventListener('click', function () {
        offcanvasInstance.hide();
      });
    });
  }

  // =========================================================================
  // 5. Book Form Live Preview
  // =========================================================================
  const bookForm = document.querySelector('.lms-book-form');
  if (bookForm) {
    const titleInput = bookForm.querySelector('#title');
    const categorySelect = bookForm.querySelector('#category_id');
    const authorSelect = bookForm.querySelector('#author_id');
    const totalCopiesInput = bookForm.querySelector('#total_copies');

    const previewSpine = document.getElementById('previewSpine');
    const previewTitle = document.getElementById('previewTitle');
    const previewAuthor = document.getElementById('previewAuthor');
    const previewPips = document.getElementById('previewPips');

    function updatePreview() {
      const title = (titleInput ? titleInput.value.trim() : '') || 'Untitled Book';
      const firstLetter = title.charAt(0).toUpperCase() || '?';
      const catId = categorySelect ? parseInt(categorySelect.value, 10) || 0 : 0;
      const spineColor = SPINE_PALETTE[Math.abs(catId) % 6];

      let authorName = 'No author selected';
      if (authorSelect && authorSelect.selectedIndex > 0) {
        authorName = authorSelect.options[authorSelect.selectedIndex].text;
      }

      const total = totalCopiesInput ? parseInt(totalCopiesInput.value, 10) || 1 : 1;

      if (previewSpine) {
        previewSpine.style.setProperty('--spine', spineColor);
        previewSpine.innerHTML = `
          <span class="lms-spine__binding"></span>
          <span class="lms-spine__cover">
            <span class="lms-spine__emboss">
              <span class="lms-spine__letter">${firstLetter}</span>
            </span>
          </span>
          <span class="lms-spine__pages"></span>
          <span class="lms-spine__ribbon"></span>
        `;
      }
      if (previewTitle) {
        previewTitle.textContent = title;
      }
      if (previewAuthor) {
        previewAuthor.textContent = authorName;
      }
      if (previewPips) {
        let pipsHtml = '';
        const count = Math.min(Math.max(total, 1), 8);
        for (let i = 0; i < count; i++) {
          pipsHtml += '<span class="lms-pip is-filled"></span>';
        }
        previewPips.innerHTML = pipsHtml;
      }
    }

    if (titleInput) titleInput.addEventListener('input', updatePreview);
    if (categorySelect) categorySelect.addEventListener('change', updatePreview);
    if (authorSelect) authorSelect.addEventListener('change', updatePreview);
    if (totalCopiesInput) totalCopiesInput.addEventListener('input', updatePreview);

    updatePreview();
  }

  // =========================================================================
  // 6. Issue Book Form Live Summary
  // =========================================================================
  const issueForm = document.querySelector('.lms-issue-form');
  if (issueForm) {
    const memberSelect = issueForm.querySelector('#member_id');
    const bookSelect = issueForm.querySelector('#book_id');
    const summaryMember = document.getElementById('summaryMember');
    const summaryBook = document.getElementById('summaryBook');

    function updateIssueSummary() {
      if (summaryMember && memberSelect) {
        if (memberSelect.selectedIndex > 0) {
          summaryMember.textContent = memberSelect.options[memberSelect.selectedIndex].text;
        } else {
          summaryMember.textContent = 'None selected';
        }
      }
      if (summaryBook && bookSelect) {
        if (bookSelect.selectedIndex > 0) {
          summaryBook.textContent = bookSelect.options[bookSelect.selectedIndex].text;
        } else {
          summaryBook.textContent = 'None selected';
        }
      }
    }

    if (memberSelect) memberSelect.addEventListener('change', updateIssueSummary);
    if (bookSelect) bookSelect.addEventListener('change', updateIssueSummary);
    updateIssueSummary();
  }

  // =========================================================================
  // 7. Password Visibility Toggle (Login Page)
  // =========================================================================
  const togglePassBtn = document.getElementById('lms-toggle-password');
  const passInput = document.getElementById('password');
  if (togglePassBtn && passInput) {
    togglePassBtn.addEventListener('click', function () {
      const isPassword = passInput.getAttribute('type') === 'password';
      passInput.setAttribute('type', isPassword ? 'text' : 'password');
      const icon = togglePassBtn.querySelector('i');
      if (icon) {
        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
      }
      togglePassBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
  }

  // =========================================================================
  // 7b. Demo Credentials Autofill Helper
  // =========================================================================
  const demoFillBtn = document.getElementById('lmsDemoFill');
  if (demoFillBtn) {
    demoFillBtn.addEventListener('click', function () {
      const u = document.getElementById('username');
      const p = document.getElementById('password');
      if (u && p) {
        u.value = 'admin';
        p.value = 'password';
        p.focus();
      }
    });
  }

  // =========================================================================
  // 8. Dark Mode Toggle (persisted via localStorage)
  // =========================================================================
  const DARK_KEY = 'lms-theme';
  const root = document.documentElement;

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem(DARK_KEY, theme);
  }

  // On load — restore saved preference, then fall back to system preference
  (function () {
    const saved = localStorage.getItem(DARK_KEY);
    if (saved) {
      applyTheme(saved);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      applyTheme('dark');
    } else {
      applyTheme('light');
    }
  })();

  // Wire up all toggle buttons (sidebar footer + topbar)
  document.querySelectorAll('.lms-theme-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const current = root.getAttribute('data-theme') || 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
    btn.setAttribute('title', 'Toggle dark / light mode');
    btn.setAttribute('aria-label', 'Toggle dark / light mode');
  });

  // Listen for OS-level theme changes
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
      if (!localStorage.getItem(DARK_KEY)) {
        applyTheme(e.matches ? 'dark' : 'light');
      }
    });
  }
});
