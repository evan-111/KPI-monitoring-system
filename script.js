// ─────────────────────────────────────────
//  Page navigation labels
// ─────────────────────────────────────────
const pageLabels = {
  dashboard:  'Supervisor Dashboard',
  profiles:   'Sales Assistant Profiles',
  analytics:  'Analytics Dashboard',
  report:     'Performance & Training Report',
  evaluation: 'Performance Evaluation',
  settings:   'Settings'
};

// ─────────────────────────────────────────
//  Navigate between sidebar pages (server-side routing)
// ─────────────────────────────────────────
function navigate(el) {
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  el.classList.add('active');
  const page = el.dataset.page;
  const url = new URL(window.location);
  url.searchParams.set('page', page);
  if (!url.searchParams.has('period')) {
    url.searchParams.set('period', new URL(window.location).searchParams.get('period') || '4');
  }
  window.location.href = url.toString();
}

// ─────────────────────────────────────────
//  Logout handler
// ─────────────────────────────────────────
function handleLogout() {
  if (confirm('Are you sure you want to logout?')) {
    window.location.href = 'logout.php';
  }
}

// ─────────────────────────────────────────
//  Supervisor profile panel
// ─────────────────────────────────────────
function openProfile() {
  document.getElementById('profile-overlay').style.display = 'block';
  document.getElementById('profile-panel').style.display   = 'block';
}

function closeProfile() {
  document.getElementById('profile-overlay').style.display = 'none';
  document.getElementById('profile-panel').style.display   = 'none';
}

function saveProfile() {
  const name  = document.getElementById('pe-name').value.trim();
  const phone = document.getElementById('pe-phone').value.trim();
  const dept  = document.getElementById('pe-dept').value.trim();
  if (!name) { alert('Name is required.'); return; }

  fetch('api/update_profile.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name, phone, department: dept })
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      document.getElementById('pv-name').textContent = name;
      const tn = document.getElementById('topbar-name');
      if (tn) tn.textContent = name;
      const status = document.getElementById('profile-save-status');
      status.textContent = '✓ Profile updated successfully';
      status.style.display = 'block';
      setTimeout(() => { status.style.display = 'none'; }, 3000);
    } else {
      alert('Failed to save: ' + (data.error || 'Unknown error'));
    }
  });
}

function uploadAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const fd = new FormData();
  fd.append('avatar', input.files[0]);
  fetch('api/upload_avatar.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        const newSrc = '/sales_assistant_KPI_monitoring_system/' + data.path + '?t=' + Date.now();
        document.getElementById('profile-avatar-img').src = newSrc;
        const topbarImg = document.querySelector('.avatar-wrap img');
        if (topbarImg) topbarImg.src = newSrc;
      } else {
        alert('Upload failed: ' + (data.error || 'Unknown error'));
      }
    });
}

// ─────────────────────────────────────────
//  Notification panel
// ─────────────────────────────────────────
function toggleNotif() {
  const dd = document.getElementById('notif-dropdown');
  dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
}

function dismissNotif(id) {
  fetch('index.php?action=dismiss_notification&notif_id=' + id)
    .then(function(r) { return r.json(); })
    .then(function() {
      const el = document.getElementById('notif-item-' + id);
      if (el) el.remove();
      updateBadge();
    });
}

function dismissAll() {
  fetch('index.php?action=dismiss_all_notifications')
    .then(function(r) { return r.json(); })
    .then(function() {
      document.getElementById('notif-list').innerHTML =
        '<div style="padding:36px 18px; text-align:center; color:var(--text-muted); font-size:12px;">' +
        '<div style="font-weight:600; color:var(--text-secondary); margin-bottom:4px;">No notifications</div>' +
        '<div>You have no new alerts at this time.</div></div>';
      updateBadge(0);
      const hdr = document.querySelector('#notif-dropdown > div:first-child');
      if (hdr) {
        hdr.innerHTML =
          '<div style="font-size:13px; font-weight:700; color:var(--text-primary);">Notifications</div>' +
          '<span style="background:rgba(34,197,94,0.15); color:#22c55e; font-size:10px; font-weight:700; padding:3px 8px; border-radius:6px; border:1px solid rgba(34,197,94,0.3);">✓ ALL CLEAR</span>';
      }
    });
}

function updateBadge(forceCount) {
  const badge = document.getElementById('notif-badge');
  const list  = document.getElementById('notif-list');
  const count = forceCount !== undefined
    ? forceCount
    : list.querySelectorAll('[id^="notif-item-"]').length;
  if (count > 0) {
    badge.textContent   = count;
    badge.style.display = 'flex';
  } else {
    badge.style.display = 'none';
  }
}

// ─────────────────────────────────────────
//  File input handler
// ─────────────────────────────────────────
function handleFile(e) {
  const file = e.target.files[0];
  if (!file) return;
  alert(`File "${file.name}" selected!\nConnect this to your PHP backend to process the import.`);
}

// ─────────────────────────────────────────
//  DOM-ready listeners
// ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  // Dismiss notification buttons + close dropdown on outside click
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-notif-id]');
    if (btn) {
      e.stopPropagation();
      dismissNotif(parseInt(btn.getAttribute('data-notif-id')));
      return;
    }
    const wrap = document.getElementById('notif-wrap');
    if (wrap && !wrap.contains(e.target)) {
      document.getElementById('notif-dropdown').style.display = 'none';
    }
  });

  // Drag-and-drop on upload zone (if present)
  const zone = document.getElementById('upload-zone');
  if (zone) {
    zone.addEventListener('dragover', e => {
      e.preventDefault();
      zone.style.borderColor = 'var(--accent)';
    });
    zone.addEventListener('dragleave', () => {
      zone.style.borderColor = '';
    });
    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.style.borderColor = '';
      const file = e.dataTransfer.files[0];
      if (file) alert(`Dropped: "${file.name}"`);
    });
  }

});
