// === Tema Manual ===
const toggleBtn = document.getElementById("toggleTheme");
const body = document.body;

// Cek preferensi sebelumnya
if (localStorage.getItem("theme") === "light") {
  body.classList.add("light");
  toggleBtn.textContent = "🌙";
} else {
  toggleBtn.textContent = "☀️";
}

// Tombol toggle
toggleBtn.addEventListener("click", () => {
  body.classList.toggle("light");
  if (body.classList.contains("light")) {
    toggleBtn.textContent = "🌙";
    localStorage.setItem("theme", "light");
  } else {
    toggleBtn.textContent = "☀️";
    localStorage.setItem("theme", "dark");
  }
});

// === Efek Loading ===
window.addEventListener("load", () => {
  const loading = document.getElementById("loading-screen");
  setTimeout(() => {
    loading.classList.add("hidden");
  }, 1500); // loading 1.5 detik
});


