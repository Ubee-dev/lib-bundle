import { parsePhoneNumber, parsePhoneNumberFromString, AsYouType, getCountryCallingCode } from 'libphonenumber-js';

const errorWrapperClass = 'form__field-wrapper_error';
const validWrapperClass = 'form__field-wrapper_valid';
const errorFieldClass = 'form__field_error';
const validFieldClass = 'form__field_valid';

const phoneNumber = {

    countryCode: 'FR',
    number: '',

    $countryCodeFlag: null,
    $callingCode: null,
    $selectCountryCode: null,
    $phoneNumberInputWrapper:null,
    $phoneNumberInput:null,
    $zohoPhoneNumberInput:null,
    $submitBtn: null,

    init: function(){

        const $phoneNumber = document.querySelector('.js-phone-number');

        this.$countryCodeFlag = $phoneNumber.querySelector('.js-phone-number-flag');
        this.$callingCode = this.$countryCodeFlag.querySelector('.js-phone-number-calling-code');
        this.$selectCountryCode = $phoneNumber.querySelector('.js-phone-number-country-select');
        this.$phoneNumberInputWrapper = $phoneNumber.querySelector('.js-phone-number-input-wrapper');
        this.$phoneNumberInput = this.$phoneNumberInputWrapper.querySelector('.js-phone-number-input');
        this.$zohoPhoneNumberInput = $phoneNumber.querySelector('.js-phone-number-zoho');
        this.$submitBtn = document.querySelector('button[type="submit"]');

        this.$selectCountryCode.value = this.countryCode;
        this.updateFlag();
        this.updateCallingCode();
        this.bind();
    },

    bind: function(){

        const _ = this;

        _.$selectCountryCode.addEventListener('change', (e) => {
            _.countryCode = e.target.value;
            _.$zohoPhoneNumberInput.value = phoneNumber.getInternationalPhoneNumber();
            _.$phoneNumberInput.value = _.getAsYouType();
            _.$phoneNumberInput.dispatchEvent(new Event('blur'));

            if(_.number.length){
                _.validate();
            }
            _.updateFlag();
            _.updateCallingCode();
        })

        _.$phoneNumberInput.addEventListener('keyup', (e) => {
            const value = e.target.value;
            const caretPosition = e.target.selectionStart;

            _.number = value;
            e.target.value = _.getAsYouType();

            if( caretPosition < value.length) {
                e.target.setSelectionRange(caretPosition, caretPosition);
            }
        });

        _.$phoneNumberInput.addEventListener('blur', (e) => {
            setTimeout(() => {
                _.number = e.target.value;
                _.$zohoPhoneNumberInput.value = phoneNumber.getInternationalPhoneNumber();
                phoneNumber.validate();

                if(!_.number.length) return;
                e.target.value = phoneNumber.getNationalPhoneNumber();
            }, 0);
        });

        _.$submitBtn.addEventListener('click', () => {
            phoneNumber.validate()
        });
    },

    validate: function () {
        this.$zohoPhoneNumberInput.dispatchEvent(new Event('validate'));

        if( this.$zohoPhoneNumberInput.classList.contains(errorFieldClass)){
            this.$phoneNumberInputWrapper.classList.add(errorWrapperClass);
            this.$phoneNumberInputWrapper.classList.remove(validWrapperClass);
        }else if( this.$zohoPhoneNumberInput.classList.contains(validFieldClass)){
            this.$phoneNumberInputWrapper.classList.add(validWrapperClass);
            this.$phoneNumberInputWrapper.classList.remove(errorWrapperClass);
        }
    },

    getAsYouType: function(){
        return new AsYouType(this.countryCode).input(this.number);
    },

    getInternationalPhoneNumber: function(){
        try {
            const parsedPhoneNumber = parsePhoneNumber(this.number, this.countryCode);
            const phoneNumber = parsePhoneNumberFromString(parsedPhoneNumber.number);
            if(!parsedPhoneNumber.isValid()){
                return '';
            }
            return phoneNumber.format("INTERNATIONAL");
        } catch(e){
            return '';
        }
    },

    getNationalPhoneNumber: function (){
        if(!this.number) return;
        try {
            const parsedPhoneNumber = parsePhoneNumber(this.number, this.countryCode);
            const phoneNumber = parsePhoneNumberFromString(parsedPhoneNumber.number);
            return phoneNumber.format("NATIONAL");
        } catch(e){
            return this.number;
        }
    },

    updateFlag: function(){
        const flagPath = window.location.origin + '/bundles/Khalil1608lib/images/flags/' + this.countryCode + '.svg';
        const countryName = this.$selectCountryCode.querySelector("option[value=" + this.countryCode + "]").innerText;
        const img = this.$countryCodeFlag.querySelector('img');

        img.setAttribute('src', flagPath);
        img.setAttribute('alt', countryName);
    },
    
    updateCallingCode: function() {
        this.$callingCode.innerText = `(+${getCountryCallingCode(this.countryCode)})`;
    }
};

document.addEventListener("DOMContentLoaded", function() {
    if(!!document.querySelector('.js-phone-number')){
        phoneNumber.init();
    }
});