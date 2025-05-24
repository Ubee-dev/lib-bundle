import {isDescendant} from "./helpers/domHelpers";

document.addEventListener('DOMContentLoaded', function() {

    const dropdownToggles = document.querySelectorAll('.js-dropdown-toggle');
    if (!dropdownToggles.length) return;

    const openedToggleClass = 'dropdown__toggle_opened';

    const openDropdown = (toggle) => toggle.classList.add(openedToggleClass);
    const closeDropdown = (toggle) => toggle.classList.remove(openedToggleClass);

    dropdownToggles.forEach((toggle) => {
        //Open/close dropdown on click on toggle
        toggle.addEventListener('click', () => {

            if(toggle.classList.contains(openedToggleClass)) {
                closeDropdown(toggle)
            } else {
                openDropdown(toggle)
            }
        });

        //Close dropdown on click on link
        const toggleLinks = toggle.parentNode.querySelectorAll('a[href]');
        toggleLinks.forEach((link) => {
            link.addEventListener('click', (e) => {
                closeDropdown(toggle)
            })
        })
    });

    //Close dropdown on click out of the dropdown
    document.addEventListener('click', function(e) {
        dropdownToggles.forEach((toggle) => {
            if(!isDescendant(e.target, toggle.parentNode)) {
                closeDropdown(toggle)
            }
        });
    });
});