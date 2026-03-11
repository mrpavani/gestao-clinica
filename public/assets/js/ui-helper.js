/**
 * UI Helper - Custom Toasts and Modals
 */
const UI = {
    toastContainer: null,

    init() {
        if (!document.getElementById('toast-container')) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'toast-container';
            document.body.appendChild(this.toastContainer);
        } else {
            this.toastContainer = document.getElementById('toast-container');
        }

        // Initialize modal container if it doesn't exist
        if (!document.getElementById('ui-modal-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'ui-modal-overlay';
            overlay.className = 'ui-modal-overlay';
            overlay.innerHTML = `
                <div class="ui-modal">
                    <div id="ui-modal-title" class="ui-modal-title"></div>
                    <div id="ui-modal-text" class="ui-modal-text"></div>
                    <div class="ui-modal-actions">
                        <button id="ui-modal-cancel" class="ui-btn ui-btn-cancel">Cancelar</button>
                        <button id="ui-modal-confirm" class="ui-btn ui-btn-danger">Confirmar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            
            document.getElementById('ui-modal-cancel').onclick = () => this.hideModal();
            overlay.onclick = (e) => { if (e.target === overlay) this.hideModal(); };
        }
    },

    showToast(message, type = 'success') {
        this.init();
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = type === 'success' ? '✓' : '✕';
        toast.innerHTML = `<span class="toast-icon">${icon}</span> ${message}`;
        
        this.toastContainer.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    },

    confirmDelete(title, text, onConfirm) {
        this.init();
        const overlay = document.getElementById('ui-modal-overlay');
        document.getElementById('ui-modal-title').textContent = title;
        document.getElementById('ui-modal-text').textContent = text;
        
        const confirmBtn = document.getElementById('ui-modal-confirm');
        confirmBtn.onclick = () => {
            onConfirm();
            this.hideModal();
        };
        
        overlay.classList.add('show');
    },

    hideModal() {
        const overlay = document.getElementById('ui-modal-overlay');
        if (overlay) overlay.classList.remove('show');
    }
};

// Auto-init on load
document.addEventListener('DOMContentLoaded', () => UI.init());

window.UI = UI;
