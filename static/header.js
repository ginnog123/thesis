document.addEventListener("DOMContentLoaded", () => {
    
    // 1. Update Date and Time
    function updateDateTime() {
    const el = document.getElementById("date-time");
    if (!el) return;

    const now = new Date();
    el.textContent = now.toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true
    });
}

updateDateTime();
setInterval(updateDateTime, 1000);

    // 2. Sidebar Toggle Logic
    window.toggleMenu = () => {
        const header = document.getElementById("header");
        const mainContent = document.querySelector(".main-content");
        const overlay = document.querySelector(".overlay");

        if (header) header.classList.toggle("active");
        if (mainContent) mainContent.classList.toggle("active");
        if (overlay) overlay.classList.toggle("active");
    };
});