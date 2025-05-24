import {getParent, insertAfter} from "./helpers/domHelpers";

const errorMessages = {
    default: "Cette valeur semble non valide.",
    required: "Ce champ est requis.",
    email: "Cette valeur n'est pas une adresse email valide.",
    phoneNumber: "Entrez un numéro de téléphone valide."
}

const emailPattern = /[a-z0-9._%+-]+@[a-z0-9.-]+[.][a-z]{2,}$/;

const wrapperClass = 'form__field-wrapper';
const fieldClass = 'form__field';
const validModifier = '_valid';
const invalidModifier = '_error';

const getEventType = (field) => {
    switch (field.nodeName) {
        case "SELECT" : return "change";
        default : return "blur";
    }
}

const FieldValidator = function (field) {
    const _this = this;

    this.field = field;
    this.eventType = getEventType(field)
    this.errorElement = null;
    this.fieldWrapper = null;
    this.fieldWrapperClass = null;

    try {
        this.fieldWrapper = getParent(field, wrapperClass);
        this.fieldWrapperClass = wrapperClass;
    } catch(e) {
        this.fieldWrapper = field;
        this.fieldWrapperClass = fieldClass;
    }

    const validWrapperClass = this.fieldWrapperClass + validModifier;
    const errorWrapperClass = this.fieldWrapperClass + invalidModifier;
    const isEmpty = () => _this.field.value.trim().length <= 0;

    this.getError = () => {
        const validity = _this.field.validity;
        const inputType = _this.field.getAttribute('type');

        const requiredError = _this.field.hasAttribute('required') && isEmpty();
        const emailFormatError = inputType === 'email' && !emailPattern.test(_this.field.value);
        const phoneNumberError = inputType === 'tel' && requiredError;

        if(!requiredError && !emailFormatError && validity.valid) return;

        if(phoneNumberError) {
            return errorMessages.phoneNumber

        } else if (requiredError) {
            return errorMessages.required

        } else if(emailFormatError) {
            return errorMessages.email

        } else {
            return _this.field.dataset.errorMessage ?? errorMessages.default;
        }
    }

    this.showError = (error) => {
        if(_this.errorElement) _this.errorElement.remove();

        _this.fieldWrapper.classList.add(errorWrapperClass);
        _this.errorElement = document.createElement('p');
        _this.errorElement.classList.add('form__error');
        _this.errorElement.innerText = error;
        insertAfter(_this.errorElement, _this.fieldWrapper);
    }

    this.removeError = () => {
        if(_this.errorElement) {
            _this.fieldWrapper.classList.remove(errorWrapperClass);
            _this.errorElement.remove();
        }
    }

    this.validate = () => {
        const error = _this.getError();

        if(!error) {
            if(!isEmpty()) {
                _this.fieldWrapper.classList.add(validWrapperClass);
            }
            _this.removeError();
        } else {
            _this.fieldWrapper.classList.remove(validWrapperClass);
            _this.showError(error);
        }

        return !error;
    }

    this.destroy = () => {
        field.removeEventListener(_this.eventType, _this.validate);
        field.removeEventListener('validate', _this.validate);
    }

    this.init = () => {
        field.addEventListener(_this.eventType, _this.validate);
        field.addEventListener('validate', _this.validate);
    }

    _this.init();
}

export const FormValidator = function (form) {
    const _this = this;

    this.form = form;
    this.submitBtn = form.querySelector('button[type="submit"]');
    this.fieldValidators = [];

    this.isFormValid = () => {
        let isFormValid = true;

        _this.fieldValidators.forEach((fieldValidator) => {
            const isFieldValid = fieldValidator.validate();
            isFormValid = isFormValid && isFieldValid;
        });

        return isFormValid;
    }

    this.onSubmit = (e) => {
        if(e.cancelable) {
            e.preventDefault();

            if(_this.isFormValid()) {
                var clickEvent = new MouseEvent('click', {cancelable: false});
                _this.submitBtn.dispatchEvent(clickEvent);
                _this.submitBtn.setAttribute('disabled', 'disabled');
            }
        }
    }

    this.destroy = () => {
        _this.fieldValidators.forEach((fieldValidator) => fieldValidator.destroy());
        _this.submitBtn.removeEventListener('click', _this.onSubmit);

    }

    this.init = () => {
        const fields = form.querySelectorAll('input, select, textarea');

        fields.forEach((field) => {
            _this.fieldValidators.push(new FieldValidator(field));
        });

        _this.submitBtn.addEventListener('click', _this.onSubmit);
        _this.form.setAttribute('novalidate', "novalidate");
    }

    _this.init();
}

document.addEventListener("DOMContentLoaded", function() {
    const forms = document.querySelectorAll('.js-form_validation');
    forms.forEach((form) => new FormValidator(form));
});