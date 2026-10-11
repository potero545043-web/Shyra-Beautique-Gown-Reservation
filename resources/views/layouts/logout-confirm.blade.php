<div class="lc-overlay" id="lcOverlay" hidden>
    <div class="lc-box" role="alertdialog" aria-modal="true" aria-labelledby="lcTitle" aria-describedby="lcText">
        <span class="lc-kicker">Shyra Beautique</span>
        <h2 id="lcTitle"><span id="lcTitleMain">Log</span> <em id="lcTitleAccent">out?</em></h2>
        <p id="lcText">Are you sure you want to log out of your account?</p>
        <div class="lc-actions">
            <button type="button" class="lc-btn lc-cancel" id="lcCancel">Stay logged in</button>
            <button type="button" class="lc-btn lc-confirm" id="lcConfirm">Yes, log out</button>
        </div>
    </div>
</div>

<style>
    .lc-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(79, 0, 24, .35);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        animation: lc-fade .2s ease;
    }
    .lc-overlay[hidden] { display: none; }

    .lc-box {
        width: 100%;
        max-width: 420px;
        padding: 32px;
        text-align: center;
        background: #fbf5e3;
        border: 1px solid #d9c089;
        border-radius: 1.5rem;
        box-shadow: 0 25px 70px rgba(79, 0, 24, .3);
        animation: lc-pop .25s ease;
    }
    .lc-kicker {
        display: block;
        margin-bottom: 8px;
        color: #a67c2e;
        font: 600 12px 'DM Sans', sans-serif;
        letter-spacing: .18em;
        text-transform: uppercase;
    }
    .lc-box h2 {
        margin: 0;
        color: #6b0020;
        font: 600 34px/1.1 'Playfair Display', serif;
    }
    .lc-box h2 em { color: #a67c2e; font-weight: 500; }
    .lc-box p {
        margin: 10px 0 24px;
        color: #7a6a60;
        font: 400 15px/1.5 'DM Sans', sans-serif;
    }
    .lc-actions { display: grid; gap: 10px; }
    .lc-btn {
        width: 100%;
        padding: 13px 24px;
        border-radius: 999px;
        font: 600 14px 'DM Sans', sans-serif;
        cursor: pointer;
        transition: background .15s;
    }
    .lc-confirm { background: #6b0020; border: 1px solid #6b0020; color: #fff; }
    .lc-confirm:hover { background: #4f0018; }
    .lc-cancel { background: transparent; border: 1px solid #a67c2e; color: #6b0020; }
    .lc-cancel:hover { background: rgba(166, 124, 46, .1); }
    .lc-btn:focus-visible { outline: 3px solid rgba(107, 0, 32, .35); outline-offset: 2px; }

    @keyframes lc-fade { from { opacity: 0; } to { opacity: 1; } }
    @keyframes lc-pop { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .lc-overlay, .lc-box { animation: none; } }
</style>

<script>
    (() => {
        const overlay = document.getElementById('lcOverlay');
        if (!overlay || overlay.dataset.ready) return;
        overlay.dataset.ready = '1';

        const cancelBtn = document.getElementById('lcCancel');
        const confirmBtn = document.getElementById('lcConfirm');
        const titleMain = document.getElementById('lcTitleMain');
        const titleAccent = document.getElementById('lcTitleAccent');
        const textEl = document.getElementById('lcText');
        let pendingForm = null;
        let lastFocus = null;

        const logoutDefaults = {
            title: 'Log', accent: 'out?',
            text: 'Are you sure you want to log out of your account?',
            yes: 'Yes, log out', no: 'Stay logged in',
        };

        const isLogout = form => form && form.method.toLowerCase() === 'post' && /\/logout\/?$/.test(new URL(form.action, location.href).pathname);

        // Any form can opt in with data-confirm-message="..." (plus optional title / accent / yes / no).
        const configFor = form => {
            if (!form) return null;
            if (form.dataset.confirmed === 'true') return null;
            if (form.dataset.confirmMessage) {
                return {
                    title: form.dataset.confirmTitle || 'Are you',
                    accent: form.dataset.confirmAccent || 'sure?',
                    text: form.dataset.confirmMessage,
                    yes: form.dataset.confirmYes || 'Yes, continue',
                    no: form.dataset.confirmNo || 'Cancel',
                };
            }
            return isLogout(form) ? logoutDefaults : null;
        };

        const open = (form, cfg) => {
            pendingForm = form;
            lastFocus = document.activeElement;
            titleMain.textContent = cfg.title;
            titleAccent.textContent = cfg.accent;
            textEl.textContent = cfg.text;
            confirmBtn.textContent = cfg.yes;
            cancelBtn.textContent = cfg.no;
            overlay.hidden = false;
            cancelBtn.focus();
        };
        const close = () => {
            overlay.hidden = true;
            pendingForm = null;
            if (lastFocus) lastFocus.focus();
        };

        // buttons / links inside the form (capture phase runs before inline onclick handlers)
        document.addEventListener('click', e => {
            if (overlay.contains(e.target) || !e.target.closest('button, a, input[type="submit"]')) return;
            const form = e.target.closest('form');
            const cfg = configFor(form);
            if (!cfg) return;
            e.preventDefault();
            e.stopPropagation();
            open(form, cfg);
        }, true);

        // Enter key / other submit paths
        document.addEventListener('submit', e => {
            const cfg = configFor(e.target);
            if (!cfg) return;
            e.preventDefault();
            open(e.target, cfg);
        }, true);

        confirmBtn.addEventListener('click', () => {
            const form = pendingForm;
            overlay.hidden = true;
            pendingForm = null;
            if (form) {
                form.dataset.confirmed = 'true';
                form.requestSubmit();
            }
        });
        cancelBtn.addEventListener('click', close);
        overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !overlay.hidden) close(); });
    })();
</script>
