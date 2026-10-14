// SHARED TEAM CODE — API, navigation, movie list, bookings and screening/hall CRUD.
"use strict";
// Change this to the host PC's API URL only when connecting to a shared LAN backend.
const API_BASE = new URL("../api/index.php", document.currentScript.src).href;
let BRAND = "AWM";
const $ = (id) => document.getElementById(id);
const state = {
  token: "",
  user: null,
  movies: [],
  halls: [],
  page: 1,
  bookingPage: 1,
  selected: null,
  registering: false,
  manageType: "events",
  editId: null,
  managerRows: [],
  editType: null,
};
const esc = (value) =>
  String(value ?? "").replace(
    /[&<>"']/g,
    (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        c
      ],
  );
const money = (cents) =>
  new Intl.NumberFormat("en-MY", { style: "currency", currency: "MYR" }).format(
    Number(cents) / 100,
  );
const localTime = (value) =>
  new Date(
    value.replace(" ", "T") + (/(Z|[+-]\d{2}:\d{2})$/.test(value) ? "" : "Z"),
  ).toLocaleString("en-MY", {
    dateStyle: "medium",
    timeStyle: "short",
    timeZone: "Asia/Kuala_Lumpur",
  });
async function api(route, method = "GET", body) {
  const url = new URL(API_BASE);
  const [path, qs] = route.split("?");
  url.searchParams.set("route", path);
  if (qs) new URLSearchParams(qs).forEach((v, k) => url.searchParams.set(k, v));
  const response = await fetch(url, {
    method,
    headers: {
      "Content-Type": "application/json",
      ...(state.token ? { Authorization: `Bearer ${state.token}` } : {}),
    },
    ...(body === undefined ? {} : { body: JSON.stringify(body) }),
  });
  let result;
  try {
    result = await response.json();
  } catch {
    throw new Error(
      "The API did not return JSON. Start Apache/MySQL and check the project folder.",
    );
  }
  if (!response.ok || !result.success) {
    if (response.status === 401) {
      state.token = "";
      state.user = null;
      updateAccount();
    }
    throw new Error(
      result.error?.message || `Request failed (${response.status})`,
    );
  }
  return result.data;
}
function toast(message) {
  $("toast").textContent = message;
  $("toast").classList.remove("hidden");
  clearTimeout(toast.timer);
  toast.timer = setTimeout(() => $("toast").classList.add("hidden"), 5000);
}
function manager() {
  return (
    state.user &&
    ["admin", "organiser", "staff"].includes(state.user.role) &&
    (state.user.role === "admin" || state.user.brand === BRAND)
  );
}
function updateAccount() {
  $("userLabel").textContent = state.user ? state.user.name : "";
  $("loginBtn").classList.toggle("hidden", !!state.user);
  $("logoutBtn").classList.toggle("hidden", !state.user);
  document
    .querySelectorAll(".manager")
    .forEach((x) => x.classList.toggle("hidden", !manager()));
  document
    .querySelectorAll(".admin-only")
    .forEach((x) => x.classList.toggle("hidden", state.user?.role !== "admin"));
  document.body.dataset.mode = state.user
    ? state.user.role === "customer"
      ? "customer"
      : state.user.role === "admin"
        ? "admin"
        : "organiser"
    : entryRole;
  document.body.classList.toggle("signed-out", !state.user);
  const ticketNav = document.querySelector('[data-view="bookings"]');
  if (ticketNav)
    ticketNav.textContent = manager() ? "Ticket desk" : "My tickets";
}
function view(name) {
  document.querySelectorAll(".view").forEach((x) => x.classList.add("hidden"));
  $(name + "View").classList.remove("hidden");
  document
    .querySelectorAll(".nav")
    .forEach((x) => x.classList.toggle("active", x.dataset.view === name));
  if (name === "bookings") loadBookings();
  if (name === "manage") loadManagement();
  if (name === "users") loadUsers();
  if (name === "seatManagement") loadSeatManagement();
  if (name === "operations") loadOperations();
}
function paging(target, info, change) {
  target.innerHTML = "";
  if (info.total <= info.limit) return;
  const prev = document.createElement("button"),
    next = document.createElement("button"),
    label = document.createElement("span");
  prev.textContent = "← Previous";
  next.textContent = "Next →";
  prev.className = next.className = "outline";
  prev.disabled = info.page <= 1;
  next.disabled = info.page * info.limit >= info.total;
  label.textContent = `Page ${info.page} of ${Math.ceil(info.total / info.limit)}`;
  prev.onclick = () => change(info.page - 1);
  next.onclick = () => change(info.page + 1);
  target.append(prev, label, next);
}
let movieRequest = 0;
async function loadMovies() {
  const request = ++movieRequest;
  $("movies").innerHTML =
    '<div class="empty loading">Finding your next movie night…</div>';
  try {
    const query = new URLSearchParams({
      brand: BRAND,
      page: state.page,
      limit: 12,
      search: $("search").value.trim(),
      sort: $("sort").value,
    });
    const result = await api("events?" + query);
    if (request !== movieRequest) return;
    state.movies = result.items;
    $("movieCount").textContent = `${result.pagination.total} screenings`;
    $("movies").innerHTML = result.items.length
      ? result.items
          .map(
            (e, i) =>
              `<article class="movie-card"><div class="poster poster-${i % 6}">${posterMarkup(e.title)}<span class="poster-badge">${esc(e.genre)} · ${esc(BRAND)}</span><div class="poster-title">${esc(e.title)}</div></div><div class="card-body"><h3>${esc(e.title)}</h3><div class="card-meta"><span>${esc(e.genre)}</span><span>·</span><span>${Number(e.duration_minutes)} min</span><span>·</span><span>${Number(e.available_tickets)} available</span></div><div class="card-time">${esc(localTime(e.starts_at))}<br>Hall #${Number(e.venue_id)} · ${esc(BRAND)}</div><div class="card-bottom"><div class="price">${money(e.price_cents)}<small>per ticket</small></div><button class="gold book" data-id="${Number(e.id)}" ${Number(e.available_tickets) < 1 || new Date(e.starts_at.replace(" ", "T") + "Z") <= new Date() ? "disabled" : ""}>Book tickets ↗</button></div></div></article>`,
          )
          .join("")
      : '<div class="empty">No screenings found. Try another movie title.</div>';
    paging($("moviePaging"), result.pagination, (p) => {
      state.page = p;
      loadMovies();
    });
  } catch (e) {
    if (request !== movieRequest) return;
    $("movies").innerHTML =
      `<div class="empty"><strong>Could not load screenings</strong><p>${esc(e.message)}</p><p>Import database/awm_cinema.sql in phpMyAdmin, then press Refresh.</p></div>`;
  }
}
function showAuth() {
  state.registering = false;
  authMode();
  $("authDialog").showModal();
}
function authMode() {
  const register = state.registering;
  $("authTitle").textContent = register ? "Create an account" : "Sign in";
  $("nameField").classList.toggle("hidden", !register);
  $("authForm").elements.name.required = register;
  $("authForm").querySelector("[type=submit]").textContent = register
    ? "Create account"
    : "Sign in";
  $("switchAuth").textContent = register
    ? "Already a member? Sign in"
    : "New here? Create an account";
  $("authError").textContent = "";
}
$("switchAuth").onclick = () => {
  state.registering = !state.registering;
  authMode();
};
$("authForm").onsubmit = async (ev) => {
  ev.preventDefault();
  const button = ev.submitter;
  button.disabled = true;
  $("authError").textContent = "";
  try {
    const form = new FormData(ev.target);
    const credentials = {
      email: form.get("email"),
      password: form.get("password"),
    };
    if (state.registering)
      await api("auth/register", "POST", {
        ...credentials,
        name: form.get("name"),
      });
    const result = await api("auth/login", "POST", credentials);
    state.token = result.access_token;
    state.user = result.user;
    updateAccount();
    $("authDialog").close();
    ev.target.reset();
    toast(`Welcome, ${state.user.name}`);
    afterLogin();
  } catch (e) {
    $("authError").textContent = e.message;
  } finally {
    button.disabled = false;
  }
};
$("loginBtn").onclick = showAuth;
$("logoutBtn").onclick = () => {
  state.user = null;
  state.token = "";
  state.selectedSeats = [];
  chooseWelcomeRole("customer");
  updateAccount();
  BRAND = "AWM";
  $("brandScope").innerHTML = "<option>AWM</option>";
  $("brandScope").value = BRAND;
  view("welcome");
  loadMovies();
  toast("Signed out");
};
async function loadBookings() {
  if (!state.user) {
    $("bookingList").innerHTML =
      '<div class="empty">Sign in to see your tickets.</div>';
    $("bookingPaging").innerHTML = "";
    return;
  }
  $("bookingList").innerHTML =
    '<div class="empty loading">Loading your tickets…</div>';
  try {
    const r = await api(
      `bookings?brand=${BRAND}&page=${state.bookingPage}&limit=10&order=desc`,
    );
    $("bookingList").innerHTML = r.items.length
      ? r.items
          .map(
            (b) =>
              `<article class="ticket"><div><span class="status ${esc(b.status)}">${esc(b.status.toUpperCase())}</span><h3>${esc(b.title)}</h3><p>${esc(localTime(b.starts_at))} · ${esc(b.venue_name)}</p><p>${Number(b.quantity)} tickets · ${money(b.total_cents)} · Seats: ${esc((b.seat_labels || []).join(", "))}</p><p class="reference">Reference: ${esc(b.reference)}</p></div><div class="ticket-actions">${b.status === "confirmed" ? `<button class="gold qr" data-id="${Number(b.id)}">View ticket QR</button><button class="outline cancel" data-id="${Number(b.id)}">Cancel</button>` : '<span class="muted small">Booking cancelled</span>'}</div></article>`,
          )
          .join("")
      : '<div class="empty">No bookings for this cinema yet. Your next movie night is waiting.</div>';
    paging($("bookingPaging"), r.pagination, (p) => {
      state.bookingPage = p;
      loadBookings();
    });
  } catch (e) {
    $("bookingList").innerHTML = `<div class="empty">${esc(e.message)}</div>`;
  }
}
$("bookingList").onclick = async (ev) => {
  const cancel = ev.target.closest(".cancel"),
    qr = ev.target.closest(".qr");
  if (cancel) {
    if (
      !confirm("Cancel this booking? The tickets will become available again.")
    )
      return;
    cancel.disabled = true;
    try {
      await api("bookings/" + cancel.dataset.id, "PATCH", {
        status: "cancelled",
      });
      toast("Booking cancelled.");
      loadBookings();
      loadMovies();
    } catch (e) {
      toast(e.message);
      cancel.disabled = false;
    }
  }
  if (qr) {
    $("qrContent").innerHTML =
      '<p class="muted loading">Generating your QR ticket…</p>';
    $("qrDialog").showModal();
    try {
      const ticket = await api(`bookings/${qr.dataset.id}/qr`);
      $("qrContent").innerHTML =
        `<img src="${esc(ticket.image_data_url)}" alt="Demo booking QR ticket"><p class="reference">${esc(ticket.reference)}</p>`;
    } catch (e) {
      $("qrContent").innerHTML =
        `<p class="error">${esc(e.message)}</p><p class="muted small">Close this window and try again. Your booking remains saved.</p>`;
    }
  }
};
$("reloadBookings").onclick = loadBookings;
async function allPages(resource) {
  let items = [],
    page = 1;
  while (true) {
    const r = await api(`${resource}?brand=${BRAND}&page=${page}&limit=100`);
    items.push(...r.items);
    if (items.length >= r.pagination.total) return items;
    page++;
  }
}
async function loadManagement() {
  if (!manager()) {
    view("movies");
    return;
  }
  $("management").innerHTML =
    '<div class="empty loading">Loading cinema workspace…</div>';
  try {
    const resource = state.manageType;
    state.managerRows = await allPages(resource);
    const rows = state.managerRows;
    $("management").innerHTML = rows.length
      ? `<table><thead><tr><th>${resource === "events" ? "Screening" : "Hall"}</th><th>${resource === "events" ? "Time (MYT)" : "Location"}</th><th>Capacity</th><th>Actions</th></tr></thead><tbody>${rows.map((r) => `<tr><td>${esc(r.title || r.name)}<br><small class="muted">#${Number(r.id)} · ${esc(BRAND)}</small></td><td>${esc(resource === "events" ? localTime(r.starts_at) : r.location)}</td><td>${Number(r.capacity)}${resource === "events" ? `<br><small class="muted">${Number(r.available_tickets)} available</small>` : ""}</td><td><button class="outline edit" data-id="${Number(r.id)}">Edit</button><button class="outline danger remove" data-id="${Number(r.id)}">Remove</button></td></tr>`).join("")}</tbody></table>`
      : '<div class="empty">Nothing here yet. Add your first ' +
        (resource === "events" ? "screening" : "hall") +
        ".</div>";
  } catch (e) {
    $("management").innerHTML = `<div class="empty">${esc(e.message)}</div>`;
  }
}
const input = (name, label, type = "text", value = "", extra = "") =>
  `<label>${label}<input name="${name}" type="${type}" value="${esc(value)}" required ${extra}></label>`;
function toInputDate(value) {
  if (!value) return "";
  const d = new Date(value.replace(" ", "T") + "Z");
  const myt = new Date(d.getTime() + 8 * 3600000);
  return myt.toISOString().slice(0, 16);
}
async function edit(resource, id = null) {
  state.editType = resource;
  state.editId = id;
  const r = id ? state.managerRows.find((r) => Number(r.id) === id) : {};
  $("editTitle").textContent =
    (id ? "Edit " : "Add ") +
    (resource === "events" ? "screening" : "cinema hall");
  $("editError").textContent = "";
  if (resource === "venues")
    $("editFields").innerHTML =
      input("name", "Hall name", "text", r.name || "", 'maxlength="200"') +
      input(
        "location",
        "Location",
        "text",
        r.location || "",
        'maxlength="200"',
      ) +
      input(
        "capacity",
        "Hall capacity",
        "number",
        r.capacity || 80,
        'min="1" max="1000"',
      );
  else {
    try {
      state.halls = await allPages("venues");
    } catch (e) {
      toast(e.message);
      return;
    }
    if (!state.halls.length) {
      toast("Add a hall for this cinema first.");
      return;
    }
    $("editFields").innerHTML =
      input("title", "Movie title", "text", r.title || "", 'maxlength="200"') +
      input(
        "genre",
        "Genre",
        "text",
        r.genre || "Adventure",
        'maxlength="80"',
      ) +
      input(
        "duration_minutes",
        "Movie duration (minutes)",
        "number",
        r.duration_minutes || 120,
        'min="1" max="600"',
      ) +
      `<label>Cinema hall<select name="venue_id">${state.halls.map((v) => `<option value="${Number(v.id)}" ${Number(r.venue_id) === Number(v.id) ? "selected" : ""}>${esc(v.name)} (${Number(v.capacity)} seats)</option>`).join("")}</select></label>` +
      input(
        "starts_at",
        "Start time (Malaysia)",
        "datetime-local",
        toInputDate(r.starts_at),
      ) +
      input(
        "ends_at",
        "End time (Malaysia)",
        "datetime-local",
        toInputDate(r.ends_at),
      ) +
      input(
        "capacity",
        "Tickets available for sale",
        "number",
        r.capacity || 50,
        'min="1" max="1000"',
      ) +
      input(
        "price",
        "Ticket price (RM)",
        "number",
        r.price_cents !== undefined ? Number(r.price_cents) / 100 : 20,
        'min="0" max="1000" step="0.01"',
      );
  }
  $("editDialog").showModal();
}
$("editForm").onsubmit = async (ev) => {
  ev.preventDefault();
  ev.submitter.disabled = true;
  $("editError").textContent = "";
  try {
    const form = Object.fromEntries(new FormData(ev.target));
    form.brand = BRAND;
    form.capacity = Number(form.capacity);
    if (state.editType === "events") {
      form.venue_id = Number(form.venue_id);
      form.duration_minutes = Number(form.duration_minutes);
      form.price_cents = Math.round(Number(form.price) * 100);
      delete form.price;
      form.starts_at += ":00+08:00";
      form.ends_at += ":00+08:00";
    }
    await api(
      state.editType + (state.editId ? "/" + state.editId : ""),
      state.editId ? "PUT" : "POST",
      form,
    );
    $("editDialog").close();
    state.manageType = state.editType;
    toast("Changes saved.");
    loadManagement();
    loadMovies();
  } catch (e) {
    $("editError").textContent = e.message;
  } finally {
    ev.submitter.disabled = false;
  }
};
$("newVenue").onclick = () => edit("venues");
$("newEvent").onclick = () => edit("events");
$("showEvents").onclick = () => {
  state.manageType = "events";
  loadManagement();
};
$("showVenues").onclick = () => {
  state.manageType = "venues";
  loadManagement();
};
$("management").onclick = async (ev) => {
  const e = ev.target.closest(".edit"),
    r = ev.target.closest(".remove");
  if (e) edit(state.manageType, Number(e.dataset.id));
  if (r) {
    if (
      !confirm(
        "Remove this " +
          (state.manageType === "events" ? "screening" : "hall") +
          "? Historical data is retained.",
      )
    )
      return;
    r.disabled = true;
    try {
      await api(state.manageType + "/" + r.dataset.id, "DELETE");
      toast("Removed successfully.");
      loadManagement();
      loadMovies();
    } catch (e) {
      toast(e.message);
      r.disabled = false;
    }
  }
};
document
  .querySelectorAll("[data-close]")
  .forEach((b) => (b.onclick = () => $(b.dataset.close).close()));
document
  .querySelectorAll("[data-view]")
  .forEach((b) => (b.onclick = () => view(b.dataset.view)));
$("refresh").onclick = loadMovies;
$("sort").onchange = () => {
  state.page = 1;
  loadMovies();
};
let searchTimer;
$("search").oninput = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    state.page = 1;
    loadMovies();
  }, 350);
};
