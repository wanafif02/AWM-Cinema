// WAN AFIFUDDIN — Customer frontend , wan afifsini je oi
// ==================================================
// CUSTOMER: MOVIE → SEAT MAP → BOOKING
// ==================================================
$("movies").onclick = async (event) => {
  const button = event.target.closest(".book");
  if (!button) return;
  if (!state.user) {
    toast("Sign in to choose your seats.");
    showAuth();
    return;
  }
  if (state.user.role !== "customer") {
    toast("Use the customer workspace to book seats.");
    return;
  }
  state.selected = state.movies.find(
    (e) => Number(e.id) === Number(button.dataset.id),
  );
  state.selectedSeats = [];
  view("seats");
  await loadSeatMap();
};

async function loadSeatMap() {
  if (!state.selected) return;
  const request = ++seatMapRequest;
  const selectedEventId = Number(state.selected.id);
  $("customerSeats").innerHTML = '<p class="muted loading">Loading seats…</p>';
  $("seatError").textContent = "";
  $("confirmSeats").disabled = true;
  try {
    const r = await api(`events/${selectedEventId}/seats`);
    if (request !== seatMapRequest) return;
    state.seatLayout = r;
    const available = r.seats
      .filter((s) => s.status === "available")
      .map((s) => Number(s.id));
    state.selectedSeats = state.selectedSeats.filter((id) =>
      available.includes(id),
    );
    $("seatMovie").textContent = r.event.title;
    $("seatSubtitle").textContent =
      `${r.venue.name} · ${localTime(r.event.starts_at)} · ${money(r.event.price_cents)} per seat`;
    renderSeatGrid($("customerSeats"), r.seats, true);
    updateSeatSummary();
  } catch (error) {
    if (request !== seatMapRequest) return;
    $("customerSeats").innerHTML = `<p class="error">${esc(error.message)}</p>`;
    state.seatLayout = null;
    state.selectedSeats = [];
    updateSeatSummary();
  }
}

function renderSeatGrid(target, seats, customer) {
  const rows = new Map();
  for (const seat of seats) {
    const row = Number(seat.row_index);
    if (!rows.has(row)) rows.set(row, []);
    rows.get(row).push(seat);
  }
  target.innerHTML = [...rows.entries()]
    .map(
      ([row, items]) =>
        `<div class="seat-row"><span class="row-label">${esc(items[0].label.replace(/\d+$/, ""))}</span><div class="seat-row-buttons">${items
          .map((s) => {
            const selected =
              customer && state.selectedSeats.includes(Number(s.id));
            const status = customer
              ? s.status
              : Number(s.active)
                ? "available"
                : "unavailable";
            return `<button class="seat ${selected ? "selected" : status}" data-id="${Number(s.id)}" aria-label="Seat ${esc(s.label)}: ${selected ? "selected" : status}" aria-pressed="${selected}" ${customer && status !== "available" ? "disabled" : ""}>${esc(s.label)}</button>`;
          })
          .join("")}</div></div>`,
    )
    .join("");
}

function updateSeatSummary() {
  const labels = (state.seatLayout?.seats || [])
    .filter((s) => state.selectedSeats.includes(Number(s.id)))
    .map((s) => s.label);
  $("selectedLabels").textContent = labels.length
    ? `Seats: ${labels.join(", ")}`
    : "Choose 1–10 seats.";
  $("seatPrice").textContent = money(
    labels.length * Number(state.seatLayout?.event.price_cents || 0),
  );
  $("confirmSeats").disabled = !labels.length;
}
$("customerSeats").onclick = (event) => {
  const button = event.target.closest(".seat");
  if (!button || !state.seatLayout) return;
  const id = Number(button.dataset.id);
  if (state.selectedSeats.includes(id))
    state.selectedSeats = state.selectedSeats.filter((x) => x !== id);
  else {
    if (state.selectedSeats.length >= 10) {
      toast("Maximum ten seats per booking.");
      return;
    }
    state.selectedSeats.push(id);
  }
  renderSeatGrid($("customerSeats"), state.seatLayout.seats, true);
  updateSeatSummary();
};
$("seatRefresh").onclick = loadSeatMap;
$("seatBack").onclick = () => {
  ++seatMapRequest;
  state.selectedSeats = [];
  view("movies");
};
$("confirmSeats").onclick = async () => {
  const button = $("confirmSeats");
  button.disabled = true;
  $("seatError").textContent = "";
  try {
    const booking = await api("bookings", "POST", {
      event_id: Number(state.selected.id),
      seat_ids: [...state.selectedSeats],
    });
    toast(`Booking confirmed: ${booking.seat_labels.join(", ")}`);
    state.selectedSeats = [];
    view("bookings");
    loadMovies();
  } catch (error) {
    await loadSeatMap();
    $("seatError").textContent = error.message;
  } finally {
    if (state.seatLayout) updateSeatSummary();
  }
};
