<?php /* تحديث إشعارات الطالب تلقائياً (Real-Time) — يُضمَّن في base.php للطلاب المسجلين فقط */ ?>
<script>
(function () {
    'use strict';

    var POLL_INTERVAL = 30000; // كل 30 ثانية
    var API_URL = <?= json_js(url('students:notifications_api')) ?>;
    var ALL_URL = <?= json_js(url('students:notifications_list')) ?>;
    var ICON_URL = <?= json_js(asset('images/logo.svg')) ?>;
    var lastUnread = <?= (int) $student_profile->get_unread_notifications_count() ?>;
    var timer = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    function truncateWords(text, n) {
        var words = String(text || '').split(/\s+/);
        return words.length > n ? words.slice(0, n).join(' ') + ' …' : String(text || '');
    }

    function setBadges(count) {
        document.querySelectorAll('.notifications-badge, .notifications-count').forEach(function (b) {
            b.textContent = count;
            b.style.display = count > 0 ? '' : 'none';
        });
    }

    function renderList(items) {
        var menu = document.querySelector('.notifications-menu');
        if (!menu) { return; }
        menu.querySelectorAll('.notif-item').forEach(function (li) { li.remove(); });
        var empty = menu.querySelector('.notif-empty');
        var anchor = menu.querySelector('.notif-empty') || menu.querySelector('.notif-footer-divider');
        items.forEach(function (n) {
            var li = document.createElement('li');
            li.className = 'notif-item';
            li.innerHTML =
                '<a href="' + esc(n.link || ALL_URL) + '" style="white-space: normal; padding: 10px 15px;' +
                (n.is_read ? '' : ' background: #f0f8ff; border-inline-start: 3px solid #3498db;') + '">' +
                '<div style="display: flex; gap: 10px;"><div style="flex-shrink: 0;">' +
                '<i class="fa ' + esc(n.icon) + '" style="color: ' + esc(n.color) + '; font-size: 20px;"></i></div>' +
                '<div style="flex: 1;"><strong style="display: block; font-size: 13px; color: #2c3e50;">' + esc(n.title) +
                (n.is_read ? '' : ' <span class="badge badge-primary" style="font-size: 9px;">' + <?= js_t('جديد') ?> + '</span>') + '</strong>' +
                '<small style="display: block; color: #7f8c8d; margin-top: 3px;">' + esc(truncateWords(n.message, 10)) + '</small>' +
                '<small style="display: block; color: #95a5a6; margin-top: 3px;"><i class="fa fa-clock-o"></i> ' + <?= js_t('{time} مضت') ?>.replace('{time}', esc(n.time_ago)) + '</small>' +
                '</div></div></a>';
            menu.insertBefore(li, anchor);
        });
        if (empty) { empty.style.display = items.length ? 'none' : ''; }
    }

    function beep() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 800;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.5);
        } catch (e) { /* المتصفح لا يدعم الصوت */ }
    }

    function desktopNotify(n) {
        if (!n || !('Notification' in window)) { return; }
        var show = function () {
            var notification = new Notification(n.title, {body: n.message, icon: ICON_URL, tag: 'student-notification-' + n.id});
            notification.onclick = function () {
                window.focus();
                if (n.link) { window.location.href = n.link; }
                notification.close();
            };
            setTimeout(function () { notification.close(); }, 5000);
        };
        if (Notification.permission === 'granted') {
            show();
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(function (p) { if (p === 'granted') { show(); } });
        }
    }

    function poll() {
        fetch(API_URL, {headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { return; }
                setBadges(data.unread_count);
                if (data.unread_count > lastUnread) {
                    beep();
                    desktopNotify(data.notifications[0]);
                    var badge = document.querySelector('.notifications-badge');
                    if (badge) {
                        badge.classList.add('new-notification');
                        setTimeout(function () { badge.classList.remove('new-notification'); }, 2000);
                    }
                }
                renderList(data.notifications);
                lastUnread = data.unread_count;
            })
            .catch(function (err) { console.error(<?= js_t('خطأ في جلب الإشعارات:') ?>, err); });
    }

    function start() {
        timer = setInterval(poll, POLL_INTERVAL);
    }

    window.addEventListener('beforeunload', function () { if (timer) { clearInterval(timer); } });
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
<style>
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }
    .notifications-badge.new-notification {
        animation: pulse 0.5s ease 3;
        background: #e74c3c;
        box-shadow: 0 0 10px rgba(231, 76, 60, 0.8);
    }
</style>
