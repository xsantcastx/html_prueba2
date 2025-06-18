document.addEventListener("DOMContentLoaded", function () {
    const navbar = document.querySelector(".navbar");
    let lastScrollY = window.scrollY;

    window.addEventListener("scroll", () => {
        if (window.scrollY > lastScrollY) {
            // Scrolling down - shrink navbar
            navbar.style.height = "40px";
        } else {
            // Scrolling up - expand navbar
            navbar.style.height = "60px";
        }
        lastScrollY = window.scrollY;
    });
});
