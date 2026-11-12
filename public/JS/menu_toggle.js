"use strict";

document.addEventListener("DOMContentLoaded", function () {
    inizializzaMenuMobile();
});

function inizializzaMenuMobile() {
    const menuToggle = document.querySelector('.menu-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (menuToggle && dropdownMenu) {
        menuToggle.addEventListener('click', () => {
            dropdownMenu.classList.toggle('show');
        });
    }
}