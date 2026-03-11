/**
 * notifications.js - Sistema de notificações visuais (toasts)
 * Alias/wrapper para o módulo UI em ui-helper.js
 * Mantido para compatibilidade com templates de autenticação.
 */

// Espera o DOM estar pronto para inicializar
document.addEventListener('DOMContentLoaded', function () {
    // Se o objeto UI (de ui-helper.js) não estiver disponível, cria um fallback simples
    if (typeof window.UI === 'undefined') {
        window.UI = {
            _container: null,

            _getContainer: function () {
                if (!this._container) {
                    this._container = document.createElement('div');
                    this._container.id = 'toast-container';
                    this._container.style.cssText = [
                        'position: fixed',
                        'bottom: 1.5rem',
                        'right: 1.5rem',
                        'z-index: 9999',
                        'display: flex',
                        'flex-direction: column',
                        'gap: 0.5rem',
                    ].join('; ');
                    document.body.appendChild(this._container);
                }
                return this._container;
            },

            showToast: function (message, type) {
                type = type || 'success';
                var container = this._getContainer();

                var toast = document.createElement('div');
                toast.style.cssText = [
                    'padding: 0.75rem 1.25rem',
                    'border-radius: 8px',
                    'font-size: 0.9rem',
                    'font-weight: 500',
                    'color: #fff',
                    'opacity: 0',
                    'transition: opacity 0.3s ease',
                    'background: ' + (type === 'success' ? '#10B981' : '#EF4444'),
                    'box-shadow: 0 4px 12px rgba(0,0,0,0.15)',
                ].join('; ');
                toast.textContent = (type === 'success' ? '✓ ' : '✕ ') + message;
                container.appendChild(toast);

                setTimeout(function () { toast.style.opacity = '1'; }, 10);
                setTimeout(function () {
                    toast.style.opacity = '0';
                    setTimeout(function () { toast.remove(); }, 350);
                }, 3000);
            },

            showError: function (message) { this.showToast(message, 'error'); },
            showSuccess: function (message) { this.showToast(message, 'success'); },

            init: function () { /* já inicializado */ }
        };
    }

    // Procura por mensagens flash PHP renderizadas no DOM e as transforma em toasts
    var flashSuccess = document.querySelector('[data-flash-success]');
    var flashError   = document.querySelector('[data-flash-error]');

    if (flashSuccess && flashSuccess.dataset.flashSuccess) {
        window.UI.showToast(flashSuccess.dataset.flashSuccess, 'success');
    }
    if (flashError && flashError.dataset.flashError) {
        window.UI.showToast(flashError.dataset.flashError, 'error');
    }
});
