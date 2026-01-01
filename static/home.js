/**
 * slider.js
 * Handles the Homepage Image Slider
 */
document.addEventListener("DOMContentLoaded", () => {
    let index = 0;
    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".dot");

    
    if (slides.length === 0) return;

    function showSlide(n) {
        index = (n + slides.length) % slides.length;
        slides.forEach(s => s.classList.remove("active"));
        dots.forEach(d => d.classList.remove("active"));
        
        if (slides[index]) slides[index].classList.add("active");
        if (dots[index]) dots[index].classList.add("active");
    }

    window.changeSlide = n => showSlide(index + n);
    window.currentSlide = n => showSlide(n);

    // Auto-advance slides every 5 seconds
    setInterval(() => showSlide(index + 1), 5000);
});


