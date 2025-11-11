//opening
document.getElementById('header').style.display = 'none';
window.addEventListener('load', () => {
  setTimeout(() => {
    document.getElementById('intro').style.display = 'none';
    document.getElementById('header').style.display = 'block';
  }, 5000);
});
