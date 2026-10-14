// SHARED TEAM CODE — verified role login and workspace selection.
// ==================================================
// ROLE ENTRY AND WORKSPACE THEMES
// ==================================================
let entryRole = "customer";
state.selectedSeats = [];
state.seatLayout = null;
state.userRows = [];
state.userEditId = null;
let seatMapRequest = 0;

function afterLogin() {
  if (state.user.role === "organiser" || state.user.role === "staff") {
    BRAND = state.user.brand;
    $("brandScope").innerHTML = `<option>${esc(BRAND)}</option>`;
  } else {
    $("brandScope").innerHTML = "<option>AWM</option>";
  }
  $("brandScope").value = BRAND;
  updateAccount();
  if (state.user.role === "admin") view("users");
  else if (manager()) view("operations");
  else view("movies");
  loadMovies();
}

$("brandScope").onchange = () => {
  BRAND = $("brandScope").value;
  state.page = 1;
  state.bookingPage = 1;
  state.selectedSeats = [];
  ++seatMapRequest;
  view(
    !state.user || state.user.role === "customer"
      ? "movies"
      : state.user.role === "admin"
        ? "users"
        : "seatManagement",
  );
  loadMovies();
};

// THREE LOGIN SECTIONS — preview selection never grants server permissions.
function chooseWelcomeRole(role) {
  entryRole = role;
  document.body.dataset.mode = role;
  document
    .querySelectorAll(".role-choice")
    .forEach((b) => b.classList.toggle("active", b.dataset.role === role));
  const help = {
    customer: "Customer · Find a film, choose seats and manage your tickets.",
    admin:
      "Aiman · Administrator access to users, screenings, halls, tickets and seats.",
    organiser:
      "Mat Fairuz · Staff access to ticket desk, hall activity and seat management.",
  };
  $("welcomeRoleHelp").textContent = help[role];
  $("welcomeRegister").classList.toggle("hidden", role !== "customer");
  $("welcomeError").textContent = "";
}

document
  .querySelectorAll(".role-choice")
  .forEach((b) => (b.onclick = () => chooseWelcomeRole(b.dataset.role)));
$("welcomeRegister").onclick = () => {
  state.registering = true;
  authMode();
  $("authDialog").showModal();
};
$("browseMovies").onclick = () => {
  view("movies");
  loadMovies();
};
$("welcomeForm").onsubmit = async (event) => {
  event.preventDefault();
  event.submitter.disabled = true;
  $("welcomeError").textContent = "";
  try {
    const form = new FormData(event.target);
    const r = await api("auth/login", "POST", {
      email: form.get("email"),
      password: form.get("password"),
    });
    const actual =
      r.user.role === "admin"
        ? "admin"
        : r.user.role === "customer"
          ? "customer"
          : "organiser";
    if (actual !== entryRole)
      throw new Error(
        "This account belongs to another section. Select " +
          (actual === "organiser" ? "Staff" : actual) +
          " and sign in again.",
      );
    state.token = r.access_token;
    state.user = r.user;
    event.target.reset();
    afterLogin();
    toast("Welcome, " + state.user.name);
  } catch (error) {
    $("welcomeError").textContent = error.message;
  } finally {
    event.submitter.disabled = false;
  }
};
