/**
 * LibraryMS - Main Application JavaScript
 * Vanilla JS, no dependencies except Bootstrap 5.3.
 */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  const hasBootstrap = typeof bootstrap !== 'undefined';

  // Spine cloth colours: must match spine_tile() in includes/functions.php,
  // which maps a category id to lms-spine--cat-((id % 8) + 1).
  function spineClassFor(categoryId) {
    return 'lms-spine--cat-' + ((Math.abs(categoryId) % 8) + 1);
  }

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  // Sends a confirmed destructive action (delete / return) as a POST request.
  function submitAsPost(url) {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = url;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'csrf_token';
    input.value = csrfToken();
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  }

  // =========================================================================
  // 1. Flash messages: auto-dismiss success notices
  // =========================================================================
  document.querySelectorAll('.lms-flash.alert-success').forEach(function (alert) {
    setTimeout(function () {
      if (hasBootstrap && bootstrap.Alert) {
        bootstrap.Alert.getOrCreateInstance(alert).close();
      }
    }, 6000);
  });

  // =========================================================================
  // 2. Universal confirmation modal (replaces browser confirm())
  // =========================================================================
  const confirmModalEl = document.getElementById('lms-confirm-modal');

  if (confirmModalEl && hasBootstrap && bootstrap.Modal) {
    const confirmModal = new bootstrap.Modal(confirmModalEl);
    const modalTitle = document.getElementById('lms-confirm-title');
    const modalBody = document.getElementById('lms-confirm-body');
    const modalIcon = document.getElementById('lms-confirm-icon');
    const modalBtn = document.getElementById('lms-confirm-btn');
    let pendingAction = null;

    document.addEventListener('click', function (e) {
      const trigger = e.target.closest('[data-confirm], [data-confirm-message]');
      if (!trigger) return;

      e.preventDefault();

      const href = trigger.getAttribute('href');
      const form = trigger.closest('form');
      const msg = trigger.getAttribute('data-confirm-message') || trigger.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
      const style = trigger.getAttribute('data-confirm-style') || (msg.toLowerCase().includes('delete') ? 'danger' : 'primary');

      modalTitle.textContent = trigger.getAttribute('data-confirm-title') || 'Confirm action';
      modalBody.textContent = msg;
      modalBtn.textContent = trigger.getAttribute('data-confirm-button') || 'Confirm';
      modalBtn.className = 'btn btn-' + (style === 'danger' ? 'danger' : 'primary');
      modalIcon.className = 'lms-confirm__icon lms-confirm__icon--' + (style === 'danger' ? 'danger' : 'primary');
      modalIcon.innerHTML = style === 'danger'
        ? '<i class="bi bi-trash3"></i>'
        : '<i class="bi bi-check2-circle"></i>';

      pendingAction = function () {
        if (trigger.getAttribute('data-method') === 'post' && href) {
          submitAsPost(href);
        } else if (form && trigger.tagName === 'BUTTON') {
          form.requestSubmit ? form.requestSubmit() : form.submit();
        } else if (href && href !== '#') {
          window.location.href = href;
        }
      };

      confirmModal.show();
    });

    confirmModalEl.addEventListener('shown.bs.modal', function () {
      modalBtn.focus();
    });

    modalBtn.addEventListener('click', function () {
      if (typeof pendingAction === 'function') {
        const action = pendingAction;
        pendingAction = null;
        modalBtn.disabled = true;
        action();
      }
    });

    confirmModalEl.addEventListener('hidden.bs.modal', function () {
      pendingAction = null;
      modalBtn.disabled = false;
    });
  }

  // =========================================================================
  // 3. Filter bar selects auto-submit
  // =========================================================================
  document.querySelectorAll('.lms-filter-select').forEach(function (select) {
    select.addEventListener('change', function () {
      if (select.form) select.form.submit();
    });
  });

  // =========================================================================
  // 4. Prevent double submission of data-entry forms
  // =========================================================================
  document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
    form.addEventListener('submit', function () {
      form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
        // Defer so the button's own name/value is still submitted.
        setTimeout(function () { btn.disabled = true; }, 0);
      });
    });
  });
  // Re-enable buttons when the page is restored from the back/forward cache.
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
      document.querySelectorAll('button[type="submit"]:disabled').forEach(function (btn) { btn.disabled = false; });
    }
  });

  // =========================================================================
  // 5. Close mobile sidebar on link click
  // =========================================================================
  const mobileOffcanvasEl = document.getElementById('lmsMobileSidebar');
  if (mobileOffcanvasEl && hasBootstrap && bootstrap.Offcanvas) {
    const offcanvasInstance = bootstrap.Offcanvas.getOrCreateInstance(mobileOffcanvasEl);
    mobileOffcanvasEl.querySelectorAll('.lms-nav-link').forEach(function (link) {
      link.addEventListener('click', function () { offcanvasInstance.hide(); });
    });
  }

  // =========================================================================
  // 6. Book form: subcategory filtering + live spine preview
  // =========================================================================
  const bookForm = document.querySelector('.lms-book-form');
  if (bookForm) {
    const titleInput = bookForm.querySelector('#title');
    const categorySelect = bookForm.querySelector('#category_id');
    const subSelect = bookForm.querySelector('#subcategory_id');
    const authorSelect = bookForm.querySelector('#author_id');
    const totalCopiesInput = bookForm.querySelector('#total_copies');

    const previewSpine = document.querySelector('.lms-book-preview-card .lms-spine');
    const previewTitle = document.getElementById('previewTitle');
    const previewAuthor = document.getElementById('previewAuthor');
    const previewPips = document.getElementById('previewPips');
    const onLoan = previewPips ? parseInt(previewPips.getAttribute('data-on-loan') || '0', 10) : 0;

    // Only show subcategories that belong to the chosen category.
    function filterSubcategories() {
      if (!categorySelect || !subSelect) return;
      const catId = categorySelect.value;
      let selectedStillVisible = false;
      Array.prototype.forEach.call(subSelect.options, function (opt) {
        const owner = opt.getAttribute('data-cat');
        if (!owner) return; // placeholder
        const visible = !catId || owner === catId;
        opt.hidden = !visible;
        opt.disabled = !visible;
        if (visible && opt.selected) selectedStillVisible = true;
      });
      if (!selectedStillVisible) subSelect.value = '';
    }

    function updatePreview() {
      const title = (titleInput ? titleInput.value.trim() : '') || 'Untitled book';
      const firstChar = title.charAt(0).toUpperCase();
      const letter = /[A-Z0-9]/i.test(firstChar) ? firstChar : '•';
      const catId = categorySelect ? parseInt(categorySelect.value, 10) || 0 : 0;

      let authorName = 'No author selected';
      if (authorSelect && authorSelect.selectedIndex > 0) {
        authorName = authorSelect.options[authorSelect.selectedIndex].text.trim();
      }

      const total = totalCopiesInput ? Math.max(parseInt(totalCopiesInput.value, 10) || 0, 0) : 1;

      if (previewSpine) {
        previewSpine.className = 'lms-spine lms-spine--lg ' + spineClassFor(catId);
        previewSpine.innerHTML =
          '<span class="lms-spine__binding"></span>' +
          '<span class="lms-spine__cover"><span class="lms-spine__emboss">' +
          '<span class="lms-spine__letter"></span></span></span>' +
          '<span class="lms-spine__pages"></span>' +
          '<span class="lms-spine__ribbon"></span>';
        previewSpine.querySelector('.lms-spine__letter').textContent = letter;
      }
      if (previewTitle) previewTitle.textContent = title;
      if (previewAuthor) previewAuthor.textContent = authorName;
      if (previewPips) {
        const shown = Math.min(total, 10);
        const available = Math.max(total - onLoan, 0);
        previewPips.innerHTML = '';
        for (let i = 0; i < shown; i++) {
          const pip = document.createElement('span');
          pip.className = 'lms-pip' + (i < available ? ' is-filled' : '');
          previewPips.appendChild(pip);
        }
        const label = document.getElementById('previewPipsLabel');
        if (label) {
          label.textContent = total > 0
            ? available + ' of ' + total + (total === 1 ? ' copy' : ' copies') + ' available'
            : 'Enter at least 1 copy';
        }
      }
    }

    if (titleInput) titleInput.addEventListener('input', updatePreview);
    if (categorySelect) categorySelect.addEventListener('change', function () { filterSubcategories(); updatePreview(); });
    if (authorSelect) authorSelect.addEventListener('change', updatePreview);
    if (totalCopiesInput) totalCopiesInput.addEventListener('input', updatePreview);

    filterSubcategories();
    updatePreview();
  }

  // =========================================================================
  // 7. Issue book form: live summary
  // =========================================================================
  const issueForm = document.querySelector('.lms-issue-form');
  if (issueForm) {
    const memberSelect = issueForm.querySelector('#member_id');
    const bookSelect = issueForm.querySelector('#book_id');
    const summaryMember = document.getElementById('summaryMember');
    const summaryBook = document.getElementById('summaryBook');

    function selectedText(select) {
      return select && select.selectedIndex > 0
        ? select.options[select.selectedIndex].text.trim()
        : 'None selected';
    }

    function updateIssueSummary() {
      if (summaryMember) {
        summaryMember.textContent = selectedText(memberSelect);
        summaryMember.classList.toggle('text-muted', !memberSelect || memberSelect.selectedIndex <= 0);
      }
      if (summaryBook) {
        summaryBook.textContent = selectedText(bookSelect);
        summaryBook.classList.toggle('text-muted', !bookSelect || bookSelect.selectedIndex <= 0);
      }
    }

    if (memberSelect) memberSelect.addEventListener('change', updateIssueSummary);
    if (bookSelect) bookSelect.addEventListener('change', updateIssueSummary);
    updateIssueSummary();
  }

  // =========================================================================
  // 8. Password visibility toggle + demo credentials (login page)
  // =========================================================================
  const togglePassBtn = document.getElementById('lms-toggle-password');
  const passInput = document.getElementById('password');
  if (togglePassBtn && passInput) {
    togglePassBtn.addEventListener('click', function () {
      const isPassword = passInput.getAttribute('type') === 'password';
      passInput.setAttribute('type', isPassword ? 'text' : 'password');
      const icon = togglePassBtn.querySelector('i');
      if (icon) icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
      togglePassBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
  }

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
  // 9. Dark mode toggle
  //    The initial theme is applied inline in <head> (no flash). A choice is
  //    only stored once the user toggles, so until then the OS setting wins.
  // =========================================================================
  const THEME_KEY = 'lms-theme';
  const root = document.documentElement;

  function applyTheme(theme, persist) {
    root.setAttribute('data-theme', theme);
    root.setAttribute('data-bs-theme', theme);
    if (persist) {
      try { localStorage.setItem(THEME_KEY, theme); } catch (e) {}
    }
  }

  document.querySelectorAll('.lms-theme-toggle').forEach(function (btn) {
    btn.setAttribute('title', 'Toggle dark / light mode');
    btn.addEventListener('click', function () {
      const current = root.getAttribute('data-theme') || 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark', true);
    });
  });

  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
      let saved = null;
      try { saved = localStorage.getItem(THEME_KEY); } catch (err) {}
      if (!saved) applyTheme(e.matches ? 'dark' : 'light', false);
    });
  }

  // =========================================================================
  // 10. Book catalogue view switcher (list vs grid)
  // =========================================================================
  const viewListBtn = document.getElementById('lmsViewList');
  const viewGridBtn = document.getElementById('lmsViewGrid');
  const listView = document.getElementById('lmsBooksListView');
  const gridView = document.getElementById('lmsBooksGridView');

  if (viewListBtn && viewGridBtn && listView && gridView) {
    const VIEW_KEY = 'lms-books-view';

    function setBookView(mode) {
      const grid = mode === 'grid';
      listView.style.display = grid ? 'none' : '';
      gridView.style.display = grid ? 'grid' : 'none';
      viewGridBtn.classList.toggle('active', grid);
      viewGridBtn.setAttribute('aria-pressed', grid ? 'true' : 'false');
      viewListBtn.classList.toggle('active', !grid);
      viewListBtn.setAttribute('aria-pressed', grid ? 'false' : 'true');
      try { localStorage.setItem(VIEW_KEY, grid ? 'grid' : 'list'); } catch (e) {}
    }

    viewListBtn.addEventListener('click', function () { setBookView('list'); });
    viewGridBtn.addEventListener('click', function () { setBookView('grid'); });

    let savedView = 'list';
    try { savedView = localStorage.getItem(VIEW_KEY) || 'list'; } catch (e) {}
    setBookView(savedView);
  }
});
