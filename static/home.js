document.addEventListener("DOMContentLoaded", () => {

  function updateDateTime() {
    const el = document.getElementById("date-time");
    const now = new Date();
    el.textContent = now.toLocaleDateString("en-US", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      hour12: true
    });
  }

  updateDateTime();
  setInterval(updateDateTime, 60000);

  window.toggleMenu = () => {
    document.getElementById("header").classList.toggle("active");
    document.querySelector(".main-content").classList.toggle("active");
    document.querySelector(".overlay").classList.toggle("active");
  };

  let index = 0;
  const slides = document.querySelectorAll(".slide");
  const dots = document.querySelectorAll(".dot");

  function showSlide(n) {
    index = (n + slides.length) % slides.length;
    slides.forEach(s => s.classList.remove("active"));
    dots.forEach(d => d.classList.remove("active"));
    slides[index].classList.add("active");
    dots[index].classList.add("active");
  }

  window.changeSlide = n => showSlide(index + n);
  window.currentSlide = n => showSlide(n);

  setInterval(() => showSlide(index + 1), 5000);
});
