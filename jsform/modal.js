// Modal and Alert Helper Functions

class Modal {
    constructor(id) {
        this.modal = document.getElementById(id) || this.createModal(id);
        this.setupCloseHandlers();
    }

    createModal(id) {
        const modal = document.createElement('div');
        modal.id = id;
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2></h2>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer"></div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    setupCloseHandlers() {
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.hide();
            }
        });
    }

    show(title, body, buttons = []) {
        this.modal.querySelector('.modal-header h2').textContent = title;
        const bodyEl = this.modal.querySelector('.modal-body');
        
        if (typeof body === 'string') {
            bodyEl.innerHTML = body;
        } else {
            bodyEl.innerHTML = '';
            bodyEl.appendChild(body);
        }

        const footerEl = this.modal.querySelector('.modal-footer');
        footerEl.innerHTML = '';
        
        buttons.forEach((btn) => {
            const button = document.createElement('button');
            button.className = `modal-btn ${btn.class || 'btn-secondary'}`;
            button.textContent = btn.text;
            button.onclick = btn.onclick || (() => this.hide());
            footerEl.appendChild(button);
        });

        this.modal.classList.add('show');
    }

    hide() {
        this.modal.classList.remove('show');
    }
}

// Alert Helper
class Alert {
    static show(type, message, duration = 5000) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        const container = document.body.querySelector('.alert-container') || 
            (() => {
                const c = document.createElement('div');
                c.className = 'alert-container';
                c.style.position = 'fixed';
                c.style.top = '20px';
                c.style.right = '20px';
                c.style.zIndex = '999';
                c.style.maxWidth = '400px';
                document.body.appendChild(c);
                return c;
            })();

        container.appendChild(alert);

        if (duration > 0) {
            setTimeout(() => {
                alert.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => alert.remove(), 300);
            }, duration);
        }

        return alert;
    }

    static success(message) {
        return this.show('success', message);
    }

    static error(message) {
        return this.show('error', message);
    }

    static warning(message) {
        return this.show('warning', message);
    }

    static info(message) {
        return this.show('info', message);
    }
}

// Timer Helper
class Timer {
    constructor(element, seconds, onComplete = null) {
        this.element = element;
        this.seconds = seconds;
        this.onComplete = onComplete;
        this.intervalId = null;
    }

    start() {
        this.update();
        this.intervalId = setInterval(() => {
            this.seconds--;
            if (this.seconds <= 0) {
                this.stop();
                if (this.onComplete) this.onComplete();
                return;
            }
            this.update();
        }, 1000);
    }

    update() {
        const minutes = Math.floor(this.seconds / 60);
        const seconds = this.seconds % 60;
        const displayTime = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (this.element) {
            this.element.textContent = displayTime;
            
            // Add warning class when less than 1 minute
            if (this.seconds <= 60) {
                this.element.classList.add('warning');
            }
        }
    }

    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
    }
}
