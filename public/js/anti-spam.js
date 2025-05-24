function AntiSpam(form) {
    this.form = form;
    this.executionTimeInput = null;
    this.currentTime = 0;
    this.chrono = null;
    this.ghostWrapper = document.getElementById('js-wrapper-as');
    this.init();
}

AntiSpam.prototype = {
    constructor: AntiSpam,
    init: function () {
        this.addGhostField();
        this.addExecutionTimeInput();
        this.addFormEventListener();
        this.form.onsubmit = () => {
            clearInterval(this.chrono);
        };
    },
    addGhostField: function (name) {

        const ghostLabel = document.createElement('label');
        ghostLabel.appendChild(document.createTextNode('Merci de ne pas remplir le champ aussi'));
        ghostLabel.setAttribute('for', 'as_second');

        const ghostInput = document.createElement('input');
        ghostInput.setAttribute('type','text');
        ghostInput.setAttribute('name', 'as_second');
        ghostInput.setAttribute('id', 'as_second');
        ghostInput.setAttribute('pattern','[a-z0-9]*$');
        ghostInput.setAttribute('placeholder','Remarque 2...');
        this.ghostWrapper.appendChild(ghostLabel);
        this.ghostWrapper.appendChild(ghostInput);
    },
    addExecutionTimeInput: function () {
        const executionTimeInput = document.createElement('input');
        executionTimeInput.setAttribute('type','hidden');
        executionTimeInput.setAttribute('name','execution_time');
        this.executionTimeInput = executionTimeInput;
        this.ghostWrapper.appendChild(this.executionTimeInput);
    },
    addFormEventListener: function() {
        const initClock = () => {
            this.initClock();
            ['change','click', 'keyup', "keydown", "keypress"].forEach( evt =>
                this.form.removeEventListener(evt, initClock)
            );
        }
        ['change','click', 'keyup', "keydown", "keypress"].forEach( evt =>
            this.form.addEventListener(evt, initClock)
        );
    },
    initClock: function () {
        this.updateClock();
        this.chrono = window.setInterval(this.updateClock.bind(this), 1000);
    },
    updateClock: function () {
        this.currentTime++;
        this.executionTimeInput.value = this.currentTime;
    },
}

window.onload = function ()
{
    const forms = document.getElementsByClassName('form-with-as');
    if(forms.length > 0) {
        for (let form of forms) {
            new AntiSpam(form);
        }
    }
}