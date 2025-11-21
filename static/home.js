document.addEventListener("DOMContentLoaded", function () {
    
    function updateDateTime() {
        const dateElement = document.getElementById('date-time');
        if (dateElement) {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            };
            dateElement.textContent = now.toLocaleDateString('en-US', options);
        }
    }

    updateDateTime();
    setInterval(updateDateTime, 60000);

    window.toggleMenu = function() {
        const header = document.getElementById('header');
        const mainContent = document.querySelector('.main-content');
        const overlay = document.querySelector('.overlay');

        header.classList.toggle('active');
        mainContent.classList.toggle('active');
        
        if (overlay) {
            overlay.classList.toggle('active');
        }
    };

    let slideIndex = 0;
    const slides = document.getElementsByClassName("slide");
    const dots = document.getElementsByClassName("dot");
    let slideInterval;

    function showSlides(n) {
        if (slides.length === 0) return;

        if (n >= slides.length) { slideIndex = 0; }
        if (n < 0) { slideIndex = slides.length - 1; }

        for (let i = 0; i < slides.length; i++) {
            slides[i].classList.remove("active");
        }
        for (let i = 0; i < dots.length; i++) {
            dots[i].classList.remove("active");
        }

        slides[slideIndex].classList.add("active");
        if (dots.length > 0) {
            dots[slideIndex].classList.add("active");
        }
    }

    window.changeSlide = function(n) {
        clearInterval(slideInterval);
        slideIndex += n;
        showSlides(slideIndex);
        startAutoSlide();
    };

    window.currentSlide = function(n) {
        clearInterval(slideInterval);
        slideIndex = n;
        showSlides(slideIndex);
        startAutoSlide();
    };

    function startAutoSlide() {
        clearInterval(slideInterval);
        slideInterval = setInterval(() => {
            slideIndex++;
            showSlides(slideIndex);
        }, 5000);
    }

    showSlides(slideIndex);
    startAutoSlide();
});