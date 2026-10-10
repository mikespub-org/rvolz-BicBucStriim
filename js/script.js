/**
 * BicBucStriim - Modern Vanilla Client Script (ES5 Compatible)
 * Lightweight, zero-dependency script for progressive enhancement
 */

(function () {
  'use strict';

  function onReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
    return match ? decodeURIComponent(match[3]) : null;
  }

  function setCookie(name, val, days) {
    var expires = '';
    if (days) {
      var d = new Date();
      d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
      expires = '; expires=' + d.toUTCString();
    }
    document.cookie = name + '=' + encodeURIComponent(val) + expires + '; path=/';
  }

  function ajax(opts) {
    var xhr = new XMLHttpRequest();
    xhr.open(opts.type || 'GET', opts.url, true);
    if (opts.data && !(opts.data instanceof FormData) && typeof opts.data === 'object') {
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      var params = [];
      for (var k in opts.data) {
        if (opts.data.hasOwnProperty(k)) {
          params.push(encodeURIComponent(k) + '=' + encodeURIComponent(opts.data[k]));
        }
      }
      opts.data = params.join('&');
    }
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        if (xhr.status >= 200 && xhr.status < 300) {
          var resp = xhr.responseText;
          try { resp = JSON.parse(xhr.responseText); } catch (e) {}
          if (opts.success) opts.success(resp);
        } else {
          if (opts.error) opts.error(xhr);
        }
      }
    };
    xhr.send(opts.data || null);
  }

  function showFlash(msg, isError) {
    var flash = document.getElementById('flash');
    if (!flash) return;
    flash.innerHTML = '<p class="' + (isError ? 'error' : 'success') + '">' + msg + '</p>';
  }

  onReady(function () {
    // -------------------------------------------------------------
    // 1. Drawer / Side Panels & Backdrop
    // -------------------------------------------------------------
    var backdrop = document.createElement('div');
    backdrop.className = 'bbs-drawer-backdrop';
    document.body.appendChild(backdrop);

    function closeAllPanels() {
      var panels = document.querySelectorAll('.bbs-panel, section[data-role="panel"]');
      for (var i = 0; i < panels.length; i++) {
        panels[i].classList.remove('open');
      }
      backdrop.classList.remove('open');
    }

    function openPanel(panelId) {
      closeAllPanels();
      var panel = document.getElementById(panelId);
      if (panel) {
        panel.classList.add('open');
        backdrop.classList.add('open');
        var input = panel.querySelector('input[type="search"], input[type="text"]');
        if (input) {
          setTimeout(function () { input.focus(); }, 100);
        }
      }
    }

    backdrop.addEventListener('click', closeAllPanels);

    // Option panel toggles from header search button
    var showOptionBtns = document.querySelectorAll('[id^="show_"][id$="_options"]');
    for (var i = 0; i < showOptionBtns.length; i++) {
      showOptionBtns[i].addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = this.getAttribute('href');
        if (targetId && targetId.indexOf('#') === 0) {
          openPanel(targetId.substring(1));
        }
      });
    }

    // Generic panel links (e.g. author metadata edit panels)
    var panelLinks = document.querySelectorAll('a[href^="#author-md"]');
    for (var j = 0; j < panelLinks.length; j++) {
      panelLinks[j].addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = this.getAttribute('href').substring(1);
        openPanel(targetId);
      });
    }

    // -------------------------------------------------------------
    // 2. Send to Kindle
    // -------------------------------------------------------------
    var kindleBtn = document.getElementById('kindleButton');
    var kindlePopup = document.getElementById('kindlePopup');
    var kindleForm = document.getElementById('ajax-form');
    var kindleEmailInput = document.getElementById('kindleEmail');

    if (kindleEmailInput) {
      var savedEmail = (window.localStorage && localStorage.getItem('kindle_email')) || getCookie('kindle_email');
      if (savedEmail) {
        kindleEmailInput.value = savedEmail;
      }
    }

    if (kindleBtn && kindlePopup) {
      kindleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        kindlePopup.classList.add('open');
        backdrop.classList.add('open');
        if (kindleEmailInput) kindleEmailInput.focus();
      });
    }

    var kindleCancel = kindlePopup ? kindlePopup.querySelector('a[data-rel="back"], .bbs-modal-close') : null;
    if (kindleCancel) {
      kindleCancel.addEventListener('click', function (e) {
        e.preventDefault();
        kindlePopup.classList.remove('open');
        backdrop.classList.remove('open');
      });
    }

    if (kindleForm) {
      kindleForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var submitBtn = document.getElementById('ajax-form-button');
        var email = kindleEmailInput ? kindleEmailInput.value.trim() : '';
        if (submitBtn) submitBtn.disabled = true;

        if (window.localStorage) localStorage.setItem('kindle_email', email);
        setCookie('kindle_email', email, 365);

        ajax({
          type: 'POST',
          url: kindleForm.getAttribute('action'),
          data: { email: email },
          success: function (data) {
            if (kindlePopup) kindlePopup.classList.remove('open');
            backdrop.classList.remove('open');
            if (kindleBtn) {
              kindleBtn.value = typeof data === 'string' ? data : (data.msg || 'Sent!');
              kindleBtn.disabled = true;
            }
          },
          error: function (xhr) {
            var msgEl = document.querySelector('#ajax-message span#msg');
            if (msgEl) {
              msgEl.style.display = 'block';
              setTimeout(function () {
                if (submitBtn) submitBtn.disabled = false;
                msgEl.style.display = 'none';
              }, 2500);
            } else if (submitBtn) {
              submitBtn.disabled = false;
            }
          }
        });
      });
    }

    // -------------------------------------------------------------
    // 3. Admin: Users Management
    // -------------------------------------------------------------
    var newUserForm = document.getElementById('newuserform');
    if (newUserForm) {
      newUserForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var nameInput = document.getElementById('newuser_name');
        var passInput = document.getElementById('newuser_password');
        var root = newUserForm.getAttribute('data-proot') || '';

        ajax({
          type: 'POST',
          url: root + '/admin/users/',
          data: {
            username: nameInput ? nameInput.value : '',
            password: passInput ? passInput.value : ''
          },
          success: function (data) {
            showFlash(data.msg || 'User added', false);
            if (data.user) {
              var userList = document.getElementById('users');
              if (userList) {
                var li = document.createElement('li');
                li.className = 'bbs-list-item';
                li.setAttribute('data-user', data.user.id);
                li.innerHTML = '<a class="bbs-list-link" href="' + root + '/admin/users/' + data.user.id + '/"><h2>' + data.user.username + '</h2></a>' +
                               '<a class="bbs-btn bbs-btn-danger bbs-btn-mini user_delete" href="#" data-user="' + data.user.id + '">Delete</a>';
                userList.insertBefore(li, userList.firstChild);
                bindUserDelete(li.querySelector('.user_delete'));
              }
            }
            if (nameInput) nameInput.value = '';
            if (passInput) passInput.value = '';
          },
          error: function (xhr) {
            showFlash(xhr.responseText || 'Error adding user', true);
          }
        });
      });
    }

    var deleteModal = document.getElementById('delete');
    var deleteUserConfirmBtn = document.getElementById('delete_user');
    var pendingUserId = null;

    function bindUserDelete(btn) {
      if (!btn) return;
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        pendingUserId = this.getAttribute('data-user');
        if (deleteModal) {
          deleteModal.classList.add('open');
          backdrop.classList.add('open');
        } else if (confirm('Delete user?')) {
          doDeleteUser(pendingUserId);
        }
      });
    }

    var userDeleteBtns = document.querySelectorAll('.user_delete');
    for (var u = 0; u < userDeleteBtns.length; u++) {
      bindUserDelete(userDeleteBtns[u]);
    }

    function doDeleteUser(userId) {
      var root = (deleteUserConfirmBtn && deleteUserConfirmBtn.getAttribute('data-proot')) || '';
      ajax({
        type: 'DELETE',
        url: root + '/admin/users/' + userId + '/',
        success: function (data) {
          var li = document.querySelector('li[data-user="' + userId + '"]');
          if (li) li.parentNode.removeChild(li);
          if (deleteModal) deleteModal.classList.remove('open');
          backdrop.classList.remove('open');
          showFlash(data.msg || 'User deleted', false);
        },
        error: function (xhr) {
          if (deleteModal) deleteModal.classList.remove('open');
          backdrop.classList.remove('open');
          showFlash(xhr.responseText || 'Error deleting user', true);
        }
      });
    }

    if (deleteUserConfirmBtn) {
      deleteUserConfirmBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (pendingUserId) doDeleteUser(pendingUserId);
      });
    }

    // -------------------------------------------------------------
    // 4. Admin: Single User Edit
    // -------------------------------------------------------------
    var userForm = document.getElementById('userform');
    if (userForm) {
      userForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var root = userForm.getAttribute('data-proot') || '';
        var userId = userForm.getAttribute('data-user');
        var payload = {
          username: document.getElementById('edituser_name') ? document.getElementById('edituser_name').value : '',
          password: document.getElementById('edituser_password') ? document.getElementById('edituser_password').value : '',
          languages: document.getElementById('edituser_languages') ? document.getElementById('edituser_languages').value : '',
          tags: document.getElementById('edituser_tags') ? document.getElementById('edituser_tags').value : '',
          role: document.getElementById('edituser_role') ? document.getElementById('edituser_role').value : '0'
        };

        ajax({
          type: 'PUT',
          url: root + '/admin/users/' + userId + '/',
          data: payload,
          success: function (data) {
            showFlash(data.msg || 'Changes saved', false);
          },
          error: function (xhr) {
            showFlash(xhr.responseText || 'Error updating user', true);
          }
        });
      });
    }

    // -------------------------------------------------------------
    // 5. Admin: ID Templates
    // -------------------------------------------------------------
    var editTemplateModal = document.getElementById('edit');
    var templateForm = document.getElementById('templateform');
    var templateRows = document.querySelectorAll('.template');

    for (var t = 0; t < templateRows.length; t++) {
      templateRows[t].addEventListener('click', function (e) {
        e.preventDefault();
        var name = this.querySelector('.template-name') ? this.querySelector('.template-name').textContent.trim() : '';
        var label = this.querySelector('.template-label') ? this.querySelector('.template-label').textContent.trim() : '';
        var url = this.querySelector('.template-uri') ? this.querySelector('.template-uri').textContent.trim() : '';

        var nameInput = document.getElementById('name');
        var labelInput = document.getElementById('label');
        var templateInput = document.getElementById('template');

        if (nameInput) nameInput.value = name;
        if (labelInput) labelInput.value = label;
        if (templateInput) templateInput.value = url;

        if (editTemplateModal) {
          editTemplateModal.classList.add('open');
          backdrop.classList.add('open');
        }
      });
    }

    if (templateForm) {
      templateForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var name = document.getElementById('name') ? document.getElementById('name').value : '';
        var label = document.getElementById('label') ? document.getElementById('label').value : '';
        var url = document.getElementById('template') ? document.getElementById('template').value : '';
        var root = templateForm.getAttribute('data-proot') || '';

        ajax({
          type: 'PUT',
          url: root + '/admin/idtemplates/' + encodeURIComponent(name) + '/',
          data: { name: name, label: label, url: url },
          success: function (data) {
            if (editTemplateModal) editTemplateModal.classList.remove('open');
            backdrop.classList.remove('open');
            var row = document.querySelector('a.template[data-template="' + data.template.name + '"]');
            if (row) {
              var uriEl = row.querySelector('.template-uri');
              var lblEl = row.querySelector('.template-label');
              if (uriEl) uriEl.textContent = data.template.val || '';
              if (lblEl) lblEl.textContent = data.template.label || '';
            }
            showFlash(data.msg || 'Template saved', false);
          },
          error: function (xhr) {
            if (editTemplateModal) editTemplateModal.classList.remove('open');
            backdrop.classList.remove('open');
            showFlash(xhr.responseText || 'Error saving template', true);
          }
        });
      });
    }

    var clearTemplateBtns = document.querySelectorAll('.idtemplate_clear');
    for (var c = 0; c < clearTemplateBtns.length; c++) {
      clearTemplateBtns[c].addEventListener('click', function (e) {
        e.preventDefault();
        var templateName = this.getAttribute('data-template');
        var root = this.getAttribute('data-proot') || '';
        ajax({
          type: 'DELETE',
          url: root + '/admin/idtemplates/' + encodeURIComponent(templateName) + '/',
          success: function (data) {
            var row = document.querySelector('a.template[data-template="' + templateName + '"]');
            if (row) {
              var uriEl = row.querySelector('.template-uri');
              var lblEl = row.querySelector('.template-label');
              if (uriEl) uriEl.textContent = '';
              if (lblEl) lblEl.textContent = '';
            }
            showFlash(data.msg || 'Template cleared', false);
          },
          error: function (xhr) {
            showFlash(xhr.responseText || 'Error clearing template', true);
          }
        });
      });
    }

    // -------------------------------------------------------------
    // 6. Author Details Metadata
    // -------------------------------------------------------------
    var delImageBtn = document.getElementById('delete-image');
    if (delImageBtn) {
      delImageBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var root = this.getAttribute('data-proot') || '';
        var author = this.getAttribute('data-author');
        ajax({
          type: 'DELETE',
          url: root + '/metadata/authors/' + author + '/thumbnail/',
          success: function (data) {
            var img = document.getElementById('author-thumbnail-pic');
            if (img) img.parentNode.removeChild(img);
            showFlash(data.msg || 'Image deleted', false);
            closeAllPanels();
          },
          error: function (xhr) {
            showFlash(xhr.responseText || 'Error deleting image', true);
          }
        });
      });
    }

    var newLinkForm = document.getElementById('author-link-new');
    if (newLinkForm) {
      newLinkForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var descInput = document.getElementById('link-description');
        var urlInput = document.getElementById('link-url');
        var url = newLinkForm.getAttribute('action');

        ajax({
          type: 'POST',
          url: url,
          data: {
            label: descInput ? descInput.value : '',
            url: urlInput ? urlInput.value : ''
          },
          success: function (data) {
            var list = document.getElementById('author-links-list');
            var editList = document.getElementById('author-links-edit');
            if (data.link) {
              if (list) {
                var li1 = document.createElement('li');
                li1.className = 'author-link link-' + data.link.id;
                li1.innerHTML = '<a class="bbs-btn bbs-btn-mini" target="_blank" rel="external" href="' + data.link.url + '">' + data.link.label + '</a>';
                list.insertBefore(li1, list.firstChild);
              }
              if (editList) {
                var li2 = document.createElement('li');
                li2.className = 'bbs-list-item link-' + data.link.id;
                li2.innerHTML = '<a href="' + data.link.url + '">' + data.link.label + '</a> ' +
                                '<a href="#" class="bbs-btn bbs-btn-danger bbs-btn-mini author-link-delete" data-link="' + data.link.id + '">Delete</a>';
                editList.insertBefore(li2, editList.firstChild);
                bindLinkDelete(li2.querySelector('.author-link-delete'));
              }
            }
            if (descInput) { descInput.value = ''; descInput.focus(); }
            if (urlInput) urlInput.value = '';
            showFlash(data.msg || 'Link added', false);
          },
          error: function (xhr) {
            showFlash(xhr.responseText || 'Error adding link', true);
          }
        });
      });
    }

    function bindLinkDelete(btn) {
      if (!btn) return;
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var linkId = this.getAttribute('data-link');
        var authorId = this.getAttribute('data-author') || (delImageBtn && delImageBtn.getAttribute('data-author'));
        var root = this.getAttribute('data-proot') || (delImageBtn && delImageBtn.getAttribute('data-proot')) || '';
        ajax({
          type: 'DELETE',
          url: root + '/metadata/authors/' + authorId + '/links/' + linkId + '/',
          success: function (data) {
            var items = document.querySelectorAll('.link-' + linkId);
            for (var k = 0; k < items.length; k++) {
              items[k].parentNode.removeChild(items[k]);
            }
            showFlash(data.msg || 'Link deleted', false);
          },
          error: function (xhr) {
            showFlash(xhr.responseText || 'Error deleting link', true);
          }
        });
      });
    }

    var linkDeleteBtns = document.querySelectorAll('.author-link-delete');
    for (var l = 0; l < linkDeleteBtns.length; l++) {
      bindLinkDelete(linkDeleteBtns[l]);
    }
  });
})();
