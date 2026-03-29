<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}
$worker_id = (int)($_GET['id'] ?? 0);
if (!$worker_id) {
    header('Location: workers.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Worker Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/worker_profile.css">
</head>
<body>
  <div class="shell" id="app">
    <div id="ml">
      <div class="ml-wrap">
        <div class="ml-box"><svg viewBox="0 0 54 54" fill="none"><path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white" /><circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)" /></svg></div>
        <div class="ml-name">Home<span>Ease</span></div>
      </div>
    </div>

    <div class="screen active" style="overflow-y:auto; padding-bottom: 90px;">
        <div id="wpContent">
            <div style="padding:40px;text-align:center;color:var(--txt-muted);"><i class="bi bi-arrow-clockwise" style="animation:spin 1s linear infinite;display:inline-block;font-size:24px;"></i><br>Loading profile...</div>
        </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    initTheme();
    
    const workerId = <?= $workerId = (int)$_GET['id']; echo $workerId; ?>;
    
    async function loadWorkerProfile() {
        try {
            const res = await fetch('api/workers_api.php?action=profile&id=' + workerId);
            const data = await res.json();
            
            const content = document.getElementById('wpContent');
            
            if(!data.success) {
                content.innerHTML = `<div style="padding:40px;text-align:center;color:#ef4444;"><i class="bi bi-exclamation-circle" style="font-size:32px;"></i><br>${data.message}</div>`;
                return;
            }
            
            const p = data.provider;
            const reviews = data.reviews || [];
            
            const isVerified = p.is_verified == 1;
            const imgSrc = p.profile_image || `https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=FDECC8&color=F5A623&size=128`;
            
            const isAvailable = p.availability === 'available' && p.status === 'active';
            
            let html = `
                <div class="w-hdr">
                    <div class="back-btn" onclick="history.back()"><i class="bi bi-arrow-left"></i></div>
                    <img src="${imgSrc}" class="wp-avatar" alt="${p.name}">
                    <div class="wp-name">${p.name} ${isVerified ? '<i class="bi bi-patch-check-fill" style="color:#10b981;font-size:16px;" title="Verified Professional"></i>' : ''}</div>
                    <div class="wp-role">${p.specialty}</div>
                    
                    <div class="wp-stats">
                        <div class="wp-stat-item">
                            <div class="wp-stat-val">⭐ ${parseFloat(p.rating || 5).toFixed(1)}</div>
                            <div class="wp-stat-lbl">Rating</div>
                        </div>
                        <div class="wp-stat-item">
                            <div class="wp-stat-val">${p.jobs_done}</div>
                            <div class="wp-stat-lbl">Jobs Done</div>
                        </div>
                    </div>
                </div>
                
                <div class="wp-section">
                    <div class="wp-section-ttl">About</div>
                    <div style="background:var(--card);border-radius:16px;padding:16px;border:1px solid var(--border-col);font-size:13px;color:var(--txt-secondary);line-height:1.6;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <i class="bi bi-person-badge" style="color:var(--teal);font-size:16px;"></i> Member since ${new Date(p.created_at).getFullYear()}
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-check-circle-fill" style="color:${isVerified ? '#10b981' : 'var(--txt-muted)'};font-size:16px;"></i> ${isVerified ? 'Background verified and documents authenticated.' : 'Unverified professional.'}
                        </div>
                    </div>
                </div>
                
                <div class="wp-section">
                    <div class="wp-section-ttl">Recent Reviews (${reviews.length})</div>
            `;
            
            if (reviews.length === 0) {
                html += `<div style="text-align:center;padding:20px;color:var(--txt-muted);font-size:13px;background:var(--card);border-radius:16px;border:1px dashed var(--border-col);">No reviews yet. Be the first to book and review!</div>`;
            } else {
                html += reviews.map(r => {
                    const stars = '★'.repeat(r.rating) + '☆'.repeat(5-r.rating);
                    return `
                    <div class="wp-review-card">
                        <div class="wp-review-hdr">
                            <div>
                                <div class="wp-review-user">${r.user_name || 'Anonymous User'}</div>
                                <div class="wp-review-stars">${stars}</div>
                            </div>
                            <div class="wp-review-date">${new Date(r.created_at).toLocaleDateString()}</div>
                        </div>
                        ${r.comment ? `<div class="wp-review-comment">"${r.comment}"</div>` : ''}
                    </div>`;
                }).join('');
            }
            
            html += `</div>`;
            
            html += `
            <div class="wp-book-footer">
                <button class="wp-book-btn" ${!isAvailable ? 'disabled' : ''} onclick="goPage('booking_form.php?svc=${encodeURIComponent(p.specialty)}&newbooking=1')">
                    <i class="bi bi-calendar-check"></i> ${isAvailable ? 'Book ' + p.name : 'Currently Unavailable'}
                </button>
            </div>
            `;
            
            content.innerHTML = html;
            
        } catch(e) {
            document.getElementById('wpContent').innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;"><i class="bi bi-wifi-off" style="font-size:32px;"></i><br>Connection error.</div>';
        }
    }
    
    loadWorkerProfile();
  </script>
</body>
</html>
