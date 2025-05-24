import Modal from './modal';

class GalleryModal extends Modal {
    constructor() {
        super("#js-modal_gallery");
    }

    openModal(target) {
        const content = target.getAttribute('data-gallery-zoom-content');
        super.openModal();
        super.updateContent(content);
    }

    closeModal() {
        super.closeModal();
        super.updateContent('');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const galleryModal = new GalleryModal();
    galleryModal.init();
});