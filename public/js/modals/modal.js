
const Modal = class {
    constructor(selector) {
        this.body = document.querySelector('body');
        this.modal = document.querySelector(selector);
        this.modalLinks = document.querySelectorAll(`[data-targeted-modal="${selector}"]`);
        this.modalContent = document.querySelector(selector + ' .js-modal__content');
        this.closeButton = document.querySelector(selector + ' .js-modal__close');
        this.openModalClassName = 'modal_open';
        this.openModalBodyClassName = 'modal-open';
    }

    updateContent(content) {
        if(!this.modalContent) return;
        this.modalContent.innerHTML = content;
    }

    openModal(target) {
        this.body.classList.add(this.openModalBodyClassName);
        this.modal.classList.add(this.openModalClassName);
    }

    closeModal() {
        this.body.classList.remove(this.openModalBodyClassName);
        this.modal.classList.remove(this.openModalClassName);
    }

    init() {
        if(this.modal) {
            this.closeButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.closeModal();
            });

            if(this.modalLinks.length > 0) {
                this.modalLinks.forEach((modalLink) => {
                    modalLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.openModal(modalLink)
                    });
                });
            }
        }
    }
}

export default Modal;