<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Sauté Group') }}</title>

    <!-- Fuentes y estilos -->
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .alert{
            border-radius:10px;
            padding:12px 14px;
            margin:10px 0;
            border:1px solid rgba(0,0,0,.12);
            background:#fff;
            color:#2b2b2b;
            font-weight:600;
        }
        .alert-success{ background:#e9f7ed; border-color:#9ad8ae; color:#1e5a32; }
        .alert-danger,.alert-error{ background:#fdecec; border-color:#f1b4b4; color:#8a1f1f; }
        .alert-warning{ background:#fff7e6; border-color:#f0d196; color:#7a5310; }
        .alert-info{ background:#e9f1fb; border-color:#a8c6ee; color:#184a84; }
        .alert.ok{ background:#e9f7ed; border-color:#9ad8ae; color:#1e5a32; }
        .alert.warn{ background:#fff7e6; border-color:#f0d196; color:#7a5310; }
        .alert.err{ background:#fdecec; border-color:#f1b4b4; color:#8a1f1f; }
        .pd-alerta, .toast{
            border-radius:10px !important;
            padding:12px 14px !important;
            margin:10px 0 !important;
            border:1px solid rgba(0,0,0,.12) !important;
            background:#e9f1fb !important;
            color:#184a84 !important;
            font-weight:600 !important;
        }
        .pd-alerta-ok, .toast.ok, .toast.success{
            background:#e9f7ed !important;
            border-color:#9ad8ae !important;
            color:#1e5a32 !important;
        }
        .pd-alerta-warn{
            background:#fff7e6 !important;
            border-color:#f0d196 !important;
            color:#7a5310 !important;
        }
        .pd-alerta-err, .toast.error{
            background:#fdecec !important;
            border-color:#f1b4b4 !important;
            color:#8a1f1f !important;
        }

        .saute-toast-wrap{
            position:fixed;
            top:18px;
            right:18px;
            z-index:9999;
            display:flex;
            flex-direction:column;
            gap:10px;
            pointer-events:none;
        }
        .saute-toast{
            min-width:280px;
            max-width:420px;
            border-radius:12px;
            padding:12px 14px;
            box-shadow:0 10px 20px rgba(0,0,0,.18);
            border:1px solid rgba(0,0,0,.1);
            background:#fff;
            color:#2a2a2a;
            font-weight:600;
            pointer-events:auto;
            animation:saute-in .18s ease-out;
        }
        .saute-toast.ok{ background:#e9f7ed; border-color:#9ad8ae; color:#1e5a32; }
        .saute-toast.warn{ background:#fff7e6; border-color:#f0d196; color:#7a5310; }
        .saute-toast.err{ background:#fdecec; border-color:#f1b4b4; color:#8a1f1f; }
        .saute-toast.info{ background:#e9f1fb; border-color:#a8c6ee; color:#184a84; }

        .saute-dialog{
            position:fixed;
            inset:0;
            display:none;
            align-items:center;
            justify-content:center;
            background:rgba(0,0,0,.45);
            z-index:10000;
            padding:18px;
        }
        .saute-dialog.show{ display:flex; }
        .saute-dialog-card{
            width:min(460px, 100%);
            background:#fff;
            border-radius:14px;
            box-shadow:0 16px 40px rgba(0,0,0,.25);
            border:1px solid rgba(0,0,0,.08);
            overflow:hidden;
        }
        .saute-dialog-head{
            padding:14px 16px;
            font-weight:800;
            color:#fff;
            background:#b22b27;
        }
        .saute-dialog-body{
            padding:16px;
            color:#333;
            font-weight:500;
            line-height:1.45;
        }
        .saute-dialog-actions{
            display:flex;
            gap:10px;
            justify-content:flex-end;
            padding:0 16px 16px;
            flex-wrap:wrap;
        }
        .saute-btn{
            border:none;
            border-radius:8px;
            padding:9px 14px;
            font-weight:700;
            cursor:pointer;
        }
        .saute-btn.cancel{ background:#888; color:#fff; }
        .saute-btn.ok{ background:#b22b27; color:#fff; }
        .saute-btn.cancel:hover,.saute-btn.ok:hover{ filter:brightness(.95); }

        @keyframes saute-in{
            from{ opacity:0; transform:translateY(-8px); }
            to{ opacity:1; transform:translateY(0); }
        }
    </style>

</head>

<body class="font-sans antialiased" style="background-color: #D7E0DC;">
    <!-- Contenido principal sin navegación superior -->
    <main>
        @yield('content')
    </main>

    @include('layouts.partials.button-final-override')

    <div id="sauteToastWrap" class="saute-toast-wrap" aria-live="polite" aria-atomic="true"></div>
    <div id="sauteDialog" class="saute-dialog" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="saute-dialog-card">
            <div id="sauteDialogTitle" class="saute-dialog-head">Confirmación</div>
            <div id="sauteDialogMessage" class="saute-dialog-body"></div>
            <div class="saute-dialog-actions">
                <button id="sauteDialogCancel" type="button" class="saute-btn cancel">Cancelar</button>
                <button id="sauteDialogOk" type="button" class="saute-btn ok">Confirmar</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            if (window.sauteNotify && window.sauteDialog) return;

            const toastWrap = document.getElementById('sauteToastWrap');
            const dialog = document.getElementById('sauteDialog');
            const dialogTitle = document.getElementById('sauteDialogTitle');
            const dialogMessage = document.getElementById('sauteDialogMessage');
            const dialogCancel = document.getElementById('sauteDialogCancel');
            const dialogOk = document.getElementById('sauteDialogOk');

            function normalizeType(type) {
                if (type === 'success') return 'ok';
                if (type === 'warning') return 'warn';
                if (type === 'error' || type === 'danger') return 'err';
                return type || 'info';
            }

            window.sauteNotify = function (message, type = 'info', timeoutMs = 3600) {
                if (!toastWrap || !message) return;

                const tone = normalizeType(type);
                const item = document.createElement('div');
                item.className = `saute-toast ${tone}`;
                item.textContent = String(message);
                toastWrap.appendChild(item);

                setTimeout(() => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(-6px)';
                    setTimeout(() => item.remove(), 160);
                }, timeoutMs);
            };

            window.sauteDialog = function (message, opts = {}) {
                if (!dialog) return;

                const {
                    title = 'Confirmación',
                    okText = 'Confirmar',
                    cancelText = 'Cancelar',
                    hideCancel = false,
                    onConfirm = null,
                    onCancel = null,
                } = opts;

                dialogTitle.textContent = title;
                dialogMessage.textContent = String(message || '');
                dialogOk.textContent = okText;
                dialogCancel.textContent = cancelText;
                dialogCancel.style.display = hideCancel ? 'none' : 'inline-block';
                dialog.classList.add('show');
                dialog.setAttribute('aria-hidden', 'false');

                const close = () => {
                    dialog.classList.remove('show');
                    dialog.setAttribute('aria-hidden', 'true');
                    dialogOk.onclick = null;
                    dialogCancel.onclick = null;
                };

                dialogOk.onclick = () => {
                    close();
                    if (typeof onConfirm === 'function') onConfirm();
                };
                dialogCancel.onclick = () => {
                    close();
                    if (typeof onCancel === 'function') onCancel();
                };
            };

            window.sauteConfirmSubmit = function (event, message) {
                if (event) event.preventDefault();
                const form = event?.target?.closest('form') || event?.currentTarget;
                window.sauteDialog(message || '¿Deseas continuar?', {
                    onConfirm: () => form && form.submit(),
                });
                return false;
            };

            window.sauteConfirmAction = function (event, message) {
                if (event) event.preventDefault();
                const trigger = event?.currentTarget || event?.target;
                const form = trigger?.form || trigger?.closest('form');
                window.sauteDialog(message || '¿Deseas continuar?', {
                    onConfirm: () => form && form.submit(),
                });
                return false;
            };

            const nativeAlert = window.alert;
            window.alert = function (message) {
                window.sauteNotify(message, 'warning');
                return;
            };
            window._nativeAlert = nativeAlert;
        })();
    </script>
</body>
</html>
