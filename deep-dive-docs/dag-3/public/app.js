const apiBase = "api.php";

function showMessage(elementId, message, isError = false) {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.textContent = message;
  el.style.color = isError ? "red" : "inherit";
}

async function apiRequest(action, data = {}) {
  const response = await fetch(`${apiBase}?action=${encodeURIComponent(action)}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
    credentials: "same-origin",
  });

  const json = await response.json();
  if (!response.ok) {
    throw new Error(json.error || "Onbekende fout");
  }
  return json;
}

function updateUIForUser(user) {
  const authStatus = document.getElementById("auth-status");
  const streamerSection = document.getElementById("streamer-section");
  const viewerSection = document.getElementById("viewer-section");
  const authSection = document.getElementById("auth-section");
  const userInfo = document.getElementById("user-info");
  const logoutBtn = document.getElementById("logout-btn");

  if (!user) {
    authStatus.textContent = "Niet ingelogd.";
    userInfo.textContent = "Niet ingelogd";
    streamerSection.hidden = true;
    viewerSection.hidden = true;
    authSection.hidden = false;
    logoutBtn.hidden = true;
    return;
  }

  authStatus.textContent = `Ingelogd als ${user.display_name} (${user.role}) – user ID: ${user.id}`;
  userInfo.textContent = `${user.display_name} (${user.role}) – ID ${user.id}`;
  streamerSection.hidden = user.role !== "streamer";
  viewerSection.hidden = user.role !== "viewer";
  authSection.hidden = true;
  logoutBtn.hidden = false;
}

async function fetchCurrentUser() {
  try {
    const data = await apiRequest("me");
    updateUIForUser(data.user);
    if (data.user?.role === "streamer") {
      await loadStreamerStreams();
    }
  } catch (e) {
    // ignore
  }
}

async function loadStreamerStreams() {
  try {
    const data = await apiRequest("my_streams");
    const tbody = document.querySelector("#streamer-streams-table tbody");
    tbody.innerHTML = "";
    data.streams.forEach((s) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `<td>${s.title}</td><td>${s.start_time_utc}</td><td>${s.end_time_utc}</td><td>${s.platform}</td>`;
      tbody.appendChild(tr);
    });
  } catch (e) {
    console.error(e);
  }
}

async function loadSchedule() {
  try {
    const data = await apiRequest("my_schedule");
    renderScheduleGrid(data.streams);
  } catch (e) {
    console.error(e);
  }
}

function renderScheduleGrid(streams = []) {
  const tbody = document.querySelector("#schedule-table tbody");
  if (!tbody) return;

  tbody.innerHTML = "";

  const byDate = new Map();
  streams.forEach((s) => {
    const dateKey = (s.start_time_utc || "").split("T")[0] || "Onbekend";
    if (!byDate.has(dateKey)) byDate.set(dateKey, []);
    byDate.get(dateKey).push(s);
  });

  const today = new Date();
  for (let i = 0; i < 7; i++) {
    const d = new Date(today);
    d.setDate(today.getDate() + i);
    const dateKey = d.toISOString().split("T")[0];
    const dayStreams = byDate.get(dateKey) || [];

    if (dayStreams.length === 0) {
      const tr = document.createElement("tr");
      tr.innerHTML = `<td>${dateKey}</td>
        <td colspan="5" style="opacity:0.6;">Geen streams gepland</td>`;
      tbody.appendChild(tr);
      continue;
    }

    dayStreams.forEach((s, idx) => {
      const tr = document.createElement("tr");
      const dateCell = idx === 0 ? dateKey : "";
      tr.innerHTML = `<td>${dateCell}</td>
        <td>${s.streamer_name}</td>
        <td>${s.title}</td>
        <td>${s.start_time_utc}</td>
        <td>${s.end_time_utc}</td>
        <td>${s.platform}</td>
        <td><a href="${s.url}" target="_blank">link</a></td>`;
      tbody.appendChild(tr);
    });
  }
}

window.addEventListener("DOMContentLoaded", () => {
  const registerForm = document.getElementById("register-form");
  const loginForm = document.getElementById("login-form");
  const createStreamForm = document.getElementById("create-stream-form");
  const followForm = document.getElementById("follow-form");
  const refreshScheduleBtn = document.getElementById("refresh-schedule");
  const searchStreamerForm = document.getElementById("search-streamer-form");
  const logoutBtn = document.getElementById("logout-btn");

  if (logoutBtn) {
    logoutBtn.addEventListener("click", async () => {
      try {
        await apiRequest("logout");
        updateUIForUser(null);
        renderScheduleGrid([]);
      } catch (e) {
        console.error(e);
      }
    });
  }

  registerForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(registerForm);
    const payload = Object.fromEntries(formData.entries());
    try {
      const res = await apiRequest("register", payload);
      showMessage("auth-status", "Registratie geslaagd, je kunt nu inloggen.");
      registerForm.reset();
    } catch (err) {
      showMessage("auth-status", err.message, true);
    }
  });

  loginForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(loginForm);
    const payload = Object.fromEntries(formData.entries());
    try {
      const res = await apiRequest("login", payload);
      updateUIForUser(res.user);
      await fetchCurrentUser();
    } catch (err) {
      showMessage("auth-status", err.message, true);
    }
  });

  createStreamForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(createStreamForm);
    const payload = Object.fromEntries(formData.entries());
    try {
      await apiRequest("create_stream", payload);
      createStreamForm.reset();
      await loadStreamerStreams();
    } catch (err) {
      alert(err.message);
    }
  });

  followForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(followForm);
    const payload = Object.fromEntries(formData.entries());
    payload.streamer_id = Number(payload.streamer_id);
    try {
      await apiRequest("follow", payload);
      followForm.reset();
      alert("Streamer gevolgd.");
    } catch (err) {
      alert(err.message);
    }
  });

  searchStreamerForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(searchStreamerForm);
    const payload = Object.fromEntries(formData.entries());
    const resultsContainer = document.getElementById("search-results");
    if (!resultsContainer) return;

    resultsContainer.innerHTML = "Zoeken...";
    try {
      const res = await apiRequest("search_streamers", payload);
      const list = document.createElement("div");
      if (!res.streamers.length) {
        list.textContent = "Geen streamers gevonden.";
      } else {
        res.streamers.forEach((s) => {
          const row = document.createElement("div");
          row.className = "schedule-stream";
          const label = document.createElement("span");
          label.innerHTML = `<strong>${s.display_name}</strong> (ID ${s.id})`;
          const btn = document.createElement("button");
          btn.type = "button";
          btn.textContent = "Volgen";
          btn.addEventListener("click", async () => {
            try {
              await apiRequest("follow", { streamer_id: s.id });
              alert(`Je volgt nu ${s.display_name}.`);
            } catch (err) {
              alert(err.message);
            }
          });
          row.appendChild(label);
          row.appendChild(document.createTextNode(" "));
          row.appendChild(btn);
          list.appendChild(row);
        });
      }
      resultsContainer.innerHTML = "";
      resultsContainer.appendChild(list);
    } catch (err) {
      resultsContainer.textContent = err.message;
    }
  });

  refreshScheduleBtn?.addEventListener("click", async () => {
    await loadSchedule();
  });

  renderScheduleGrid([]);
  fetchCurrentUser();
});

