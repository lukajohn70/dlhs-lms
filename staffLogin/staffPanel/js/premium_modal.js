/**
 * premium_modal.js
 * Global premium modal utility — self-injects CSS & HTML.
 * Provides:
 *   showPremiumModal(message, title, type)
 *     type: 'info' | 'success' | 'error' | 'warning' (default: 'info')
 *   showPremiumConfirm(message, title, onConfirm, onCancel)
 */
(function () {
    /* ── CSS ────────────────────────────────────────────────── */
    const css = `
/* ---------- Premium Modal Global Styles ---------- */
.pm-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: rgba(10, 12, 20, 0.72);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    align-items: center;
    justify-content: center;
    animation: pmFadeIn .18s ease;
}
.pm-backdrop.active { display: flex; }

@keyframes pmFadeIn  { from { opacity: 0; } to { opacity: 1; } }
@keyframes pmSlideUp { from { opacity: 0; transform: translateY(28px) scale(.96); }
                       to   { opacity: 1; transform: translateY(0)      scale(1);   } }

.pm-card {
    background: linear-gradient(145deg, #1a1d2e, #12141f);
    border: 1px solid rgba(255,255,255,.09);
    border-radius: 20px;
    box-shadow: 0 24px 64px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.04);
    max-width: 440px;
    width: 92%;
    padding: 36px 32px 28px;
    text-align: center;
    animation: pmSlideUp .22s cubic-bezier(.22,1,.36,1);
    color: #e8eaf6;
    font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
    position: relative;
}

.pm-icon-wrap {
    width: 62px; height: 62px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 18px;
    font-size: 26px;
}
.pm-icon-wrap.pm-info    { background: rgba(79,140,255,.18);  color: #4f8cff; border: 1.5px solid rgba(79,140,255,.35); }
.pm-icon-wrap.pm-success { background: rgba(52,211,153,.18);  color: #34d399; border: 1.5px solid rgba(52,211,153,.35); }
.pm-icon-wrap.pm-error   { background: rgba(248,113,113,.18); color: #f87171; border: 1.5px solid rgba(248,113,113,.35); }
.pm-icon-wrap.pm-warning { background: rgba(251,191,36,.18);  color: #fbbf24; border: 1.5px solid rgba(251,191,36,.35); }

.pm-title {
    font-size: 1.18rem;
    font-weight: 700;
    letter-spacing: .3px;
    margin-bottom: 10px;
    color: #fff;
}
.pm-message {
    font-size: .93rem;
    color: #9ba3be;
    line-height: 1.6;
    margin-bottom: 26px;
}
.pm-actions { display: flex; gap: 10px; justify-content: center; }

.pm-btn {
    padding: 10px 28px;
    border: none;
    border-radius: 10px;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .15s, transform .12s;
    letter-spacing: .2px;
    min-width: 90px;
}
.pm-btn:hover  { opacity: .88; transform: translateY(-1px); }
.pm-btn:active { transform: scale(.97); }

.pm-btn-ok      { background: linear-gradient(135deg, #4f8cff, #6a5aff); color: #fff; }
.pm-btn-confirm { background: linear-gradient(135deg, #f87171, #ef4444); color: #fff; }
.pm-btn-cancel  { background: rgba(255,255,255,.08); color: #c0c7e0; border: 1px solid rgba(255,255,255,.12); }
`;

    /* ── Inject CSS ─────────────────────────────────────────── */
    const style = document.createElement('style');
    style.id = 'premium-modal-global-css';
    if (!document.getElementById('premium-modal-global-css')) {
        style.innerHTML = css;
        document.head.appendChild(style);
    }

    /* ── Modal HTML (Alert) ─────────────────────────────────── */
    const alertHTML = `
<div class="pm-backdrop" id="pmAlertBackdrop">
  <div class="pm-card" role="dialog" aria-modal="true" aria-labelledby="pmAlertTitle">
    <div class="pm-icon-wrap pm-info" id="pmAlertIcon"><i class="fa fa-info-circle"></i></div>
    <div class="pm-title" id="pmAlertTitle">Notice</div>
    <div class="pm-message" id="pmAlertMessage">Message here.</div>
    <div class="pm-actions">
      <button class="pm-btn pm-btn-ok" id="pmAlertOkBtn">OK</button>
    </div>
  </div>
</div>`;

    /* ── Modal HTML (Confirm) ───────────────────────────────── */
    const confirmHTML = `
<div class="pm-backdrop" id="pmConfirmBackdrop">
  <div class="pm-card" role="dialog" aria-modal="true" aria-labelledby="pmConfirmTitle">
    <div class="pm-icon-wrap pm-warning" id="pmConfirmIcon"><i class="fa fa-exclamation-triangle"></i></div>
    <div class="pm-title" id="pmConfirmTitle">Confirm</div>
    <div class="pm-message" id="pmConfirmMessage">Are you sure?</div>
    <div class="pm-actions">
      <button class="pm-btn pm-btn-cancel" id="pmConfirmCancelBtn">Cancel</button>
      <button class="pm-btn pm-btn-confirm" id="pmConfirmOkBtn">Confirm</button>
    </div>
  </div>
</div>`;

    /* ── Inject HTML ────────────────────────────────────────── */
    function ensureBody(cb) {
        if (document.body) { cb(); }
        else { document.addEventListener('DOMContentLoaded', cb); }
    }

    ensureBody(function () {
        if (!document.getElementById('pmAlertBackdrop')) {
            document.body.insertAdjacentHTML('beforeend', alertHTML);
        }
        if (!document.getElementById('pmConfirmBackdrop')) {
            document.body.insertAdjacentHTML('beforeend', confirmHTML);
        }

        /* Alert OK button */
        document.getElementById('pmAlertOkBtn').addEventListener('click', function () {
            document.getElementById('pmAlertBackdrop').classList.remove('active');
            if (typeof window._pmAlertCallback === 'function') {
                window._pmAlertCallback();
                window._pmAlertCallback = null;
            }
        });

        /* Confirm buttons */
        document.getElementById('pmConfirmOkBtn').addEventListener('click', function () {
            document.getElementById('pmConfirmBackdrop').classList.remove('active');
            if (typeof window._pmConfirmCallback === 'function') {
                window._pmConfirmCallback(true);
                window._pmConfirmCallback = null;
            }
        });
        document.getElementById('pmConfirmCancelBtn').addEventListener('click', function () {
            document.getElementById('pmConfirmBackdrop').classList.remove('active');
            if (typeof window._pmConfirmCallback === 'function') {
                window._pmConfirmCallback(false);
                window._pmConfirmCallback = null;
            }
        });

        /* Close on backdrop click */
        document.getElementById('pmAlertBackdrop').addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
                window._pmAlertCallback = null;
            }
        });
        document.getElementById('pmConfirmBackdrop').addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
                if (typeof window._pmConfirmCallback === 'function') {
                    window._pmConfirmCallback(false);
                    window._pmConfirmCallback = null;
                }
            }
        });
    });

    /* ── Type → icon / class map ────────────────────────────── */
    const TYPE_MAP = {
        info:    { cls: 'pm-info',    icon: 'fa-info-circle',          title: 'Notice' },
        success: { cls: 'pm-success', icon: 'fa-check-circle',         title: 'Success' },
        error:   { cls: 'pm-error',   icon: 'fa-times-circle',         title: 'Error' },
        warning: { cls: 'pm-warning', icon: 'fa-exclamation-triangle', title: 'Warning' },
    };

    /* ── Public API ─────────────────────────────────────────── */

    /**
     * showPremiumModal(message, title, type, callback)
     * Drop-in replacement for alert().
     * type: 'info' | 'success' | 'error' | 'warning'
     */
    window.showPremiumModal = function (message, title, type, callback) {
        type = type || 'info';
        const map = TYPE_MAP[type] || TYPE_MAP.info;

        const iconEl  = document.getElementById('pmAlertIcon');
        const titleEl = document.getElementById('pmAlertTitle');
        const msgEl   = document.getElementById('pmAlertMessage');

        iconEl.className = 'pm-icon-wrap ' + map.cls;
        iconEl.innerHTML = '<i class="fa ' + map.icon + '"></i>';
        titleEl.textContent = title || map.title;
        msgEl.textContent   = message || '';

        window._pmAlertCallback = callback || null;
        document.getElementById('pmAlertBackdrop').classList.add('active');
    };

    /**
     * showPremiumConfirm(message, title, onConfirm, onCancel)
     * Drop-in replacement for confirm().
     * onConfirm / onCancel are callbacks invoked when the user clicks.
     */
    window.showPremiumConfirm = function (message, title, onConfirm, onCancel) {
        const titleEl = document.getElementById('pmConfirmTitle');
        const msgEl   = document.getElementById('pmConfirmMessage');

        titleEl.textContent = title   || 'Confirm';
        msgEl.textContent   = message || 'Are you sure?';

        window._pmConfirmCallback = function (confirmed) {
            if (confirmed && typeof onConfirm === 'function') onConfirm();
            if (!confirmed && typeof onCancel === 'function') onCancel();
        };
        document.getElementById('pmConfirmBackdrop').classList.add('active');
    };

})();
