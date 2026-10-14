// MAT FAIRUZ — Staff #matkedahbuatsni oi
// ==================================================
// MAT: SEAT STUDIO
// ==================================================
async function loadSeatManagement() {
  if (!manager()) {
    view("movies");
    return;
  }
  $("organiserSeats").innerHTML =
    '<p class="muted loading">Loading cinema halls…</p>';
  try {
    const previous = $("seatVenue").value;
    const halls = await allPages("venues");
    $("seatVenue").innerHTML = halls
      .map(
        (v) =>
          `<option value="${Number(v.id)}">${esc(v.name)} · ${Number(v.capacity)} seats</option>`,
      )
      .join("");
    if (halls.some((v) => String(v.id) === previous))
      $("seatVenue").value = previous;
    if (!halls.length) {
      $("organiserSeats").innerHTML =
        '<p class="muted">Add a cinema hall in Screenings & halls first.</p>';
      $("generateSeats").disabled = true;
      return;
    }
    await loadHallLayout();
  } catch (error) {
    $("organiserSeats").innerHTML =
      `<p class="error">${esc(error.message)}</p>`;
  }
}
async function loadHallLayout() {
  const id = Number($("seatVenue").value);
  if (!id) return;
  $("organiserSeats").innerHTML =
    '<p class="muted loading">Loading layout…</p>';
  try {
    const r = await api(`seats?venue_id=${id}`);
    state.hallLayout = r.items;
    $("generateSeats").disabled = !!r.items.length;
    if (!r.items.length)
      $("organiserSeats").innerHTML =
        '<p class="muted">No layout yet. Generate default layout to create seats.</p>';
    else renderSeatGrid($("organiserSeats"), r.items, false);
  } catch (error) {
    $("organiserSeats").innerHTML =
      `<p class="error">${esc(error.message)}</p>`;
  }
}
$("seatVenue").onchange = loadHallLayout;
$("seatManagerRefresh").onclick = loadSeatManagement;
$("generateSeats").onclick = async () => {
  $("generateSeats").disabled = true;
  try {
    await api("seats", "POST", { venue_id: Number($("seatVenue").value) });
    toast("Layout generated.");
    await loadHallLayout();
  } catch (error) {
    toast(error.message);
    $("generateSeats").disabled = false;
  }
};
$("organiserSeats").onclick = async (event) => {
  const button = event.target.closest(".seat");
  if (!button) return;
  const seat = state.hallLayout.find(
    (s) => Number(s.id) === Number(button.dataset.id),
  );
  const active = Number(seat.active) ? 0 : 1;
  if (!confirm(`${active ? "Reopen" : "Block"} seat ${seat.label}?`)) return;
  button.disabled = true;
  try {
    await api(`seats/${seat.id}`, "PATCH", { active });
    toast(`Seat ${seat.label} ${active ? "available" : "blocked"}.`);
    await loadHallLayout();
  } catch (error) {
    toast(error.message);
    button.disabled = false;
  }
};

// MAT FAIRUZ — ongoing halls and ticket summaries.
async function loadOperations() {
  if (!manager()) {
    view("movies");
    return;
  }
  $("hallActivity").innerHTML =
    '<div class="empty loading">Loading hall activity…</div>';
  try {
    const r = await api("operations/halls");
    const ongoing = r.items.filter((h) => h.status === "ongoing").length;
    const blocked = r.items.reduce((n, h) => n + Number(h.blocked_seats), 0);
    const sold = r.items.reduce((n, h) => n + Number(h.tickets_sold), 0);
    $("operationStats").innerHTML = [
      ["Halls", r.items.length],
      ["Ongoing", ongoing],
      ["Tickets in ongoing shows", sold],
      ["Blocked seats", blocked],
    ]
      .map(
        ([label, value]) =>
          `<div class="stat"><span>${label}</span><strong>${value}</strong></div>`,
      )
      .join("");
    $("hallActivity").innerHTML = r.items
      .map(
        (h) =>
          `<article class="hall-card"><div class="section-heading"><h3>${esc(h.name)}</h3><span class="status ${h.status === "ongoing" ? "ongoing" : ""}">${h.status.toUpperCase()}</span></div><h2>${esc(h.current?.title || "Ready for the next story")}</h2><p class="muted">${h.current ? "Ends " + esc(localTime(h.current.ends_at)) : "No screening currently running"}</p><div class="hall-metrics"><span>${Number(h.active_seats)} active seats</span><span>${Number(h.blocked_seats)} blocked</span><span>${Number(h.tickets_sold)} tickets sold</span></div><p class="small muted">Next: ${h.next ? esc(h.next.title) + " · " + esc(localTime(h.next.starts_at)) : "No scheduled screening"}</p></article>`,
      )
      .join("");
  } catch (error) {
    $("hallActivity").innerHTML =
      `<div class="empty error">${esc(error.message)}</div>`;
  }
}
$("refreshOperations").onclick = loadOperations;
