import Modal from "./modal";
import {FormValidator} from "../form";

class DynamicModal extends Modal {
    constructor() {
        super('#js-modal_dynamic');
        this.error = null;
        this.formValidator = null;
        this.url = null;
    }

    loadContent(method, url, data, callback) {
        const self = this;
        let xhr = new XMLHttpRequest();
        xhr.open(method, url);
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                self.updateContent(xhr.response);
                if (callback) callback();

                if(xhr.status === 202) {
                    self.clearModalCache();
                }
            } else {
                self.handleError(xhr);
            }
        };
        xhr.onerror = self.handleError;
        xhr.send(data);
    }

    handleError(e) {
        if(!this.error) {
            this.error = document.createElement('div');
            this.error.innerHTML = this.modalContent.getAttribute('data-error-template');
        }

        this.clearModalCache();
        this.modalContent.appendChild(this.error);
    }

    clearModalCache() {
        this.url = null;
    }

    initForm() {
        const form = this.modalContent.querySelector('form');
        const method = form.getAttribute('method');
        const url = form.getAttribute('action');

        // (un)bind validation
        if (this.formValidator) {
            this.formValidator.destroy();
        }

        if (form) {
            this.formValidator = new FormValidator(form);

            form.addEventListener('submit', async(e) => {
                e.preventDefault();

                var data = new FormData(form);
                this.loadContent(method, url, data);
            });
        }
    }

    async openModal(target) {
        super.openModal(target);

        const title = target.getAttribute('data-modal-title');
        const url  = target.getAttribute('data-modal-href');

        this.modal.querySelector('.js-modal_dynamic__title').innerText = title;

        if(this.url !== url) {
            this.url = url;

            this.loadContent('GET', url, null, () => {
                this.initForm();
            });
        }
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const dynamicModal = new DynamicModal();
    dynamicModal.init();
});