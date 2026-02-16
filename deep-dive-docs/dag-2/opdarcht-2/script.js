// Simple smooth scrolling for in-page links
document.querySelectorAll('a[href^="#"]').forEach((link) => {
  link.addEventListener("click", (event) => {
    const href = link.getAttribute("href");
    if (!href || href === "#") return;
    const target = document.querySelector(href);
    if (!target) return;
    event.preventDefault();
    target.scrollIntoView({ behavior: "smooth", block: "start" });
  });
});

// Dark / light theme toggle (saved in localStorage)
const body = document.body;
const themeToggle = document.getElementById("themeToggle");

const storedTheme = window.localStorage.getItem("theme");
if (storedTheme === "dark") {
  body.classList.add("dark");
  if (themeToggle) themeToggle.textContent = "☀";
}

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const isDark = body.classList.toggle("dark");
    themeToggle.textContent = isDark ? "☀" : "☾";
    window.localStorage.setItem("theme", isDark ? "dark" : "light");
  });
}

// Set current year in footer
const yearEl = document.getElementById("year");
if (yearEl) {
  yearEl.textContent = String(new Date().getFullYear());
}

