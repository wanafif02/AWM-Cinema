// AIMAN HAKIM — Administrator frontend #manpunya#jangankacau
// ==================================================
// AIMAN: USER MANAGEMENT
// ==================================================
async function loadUsers() {
  if (state.user?.role !== "admin") {
    view("movies");
    return;
  }
  $("userTable").innerHTML = '<p class="muted loading">Loading accounts…</p>';
  try {
    const rows = [];
    let page = 1;
    while (true) {
      const r = await api(`admin/users?page=${page}&limit=100`);
      rows.push(...r.items);
      if (rows.length >= r.pagination.total) break;
      page++;
    }
    state.userRows = rows;
    $("userTable").innerHTML =
      `<table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Brand</th><th>Actions</th></tr></thead><tbody>${rows.map((u) => `<tr><td>${esc(u.name)}</td><td>${esc(u.email)}</td><td><span class="status">${esc(u.role)}</span></td><td>${esc(u.brand || "All / customer")}</td><td><button class="outline user-edit" data-id="${Number(u.id)}">Edit</button>${Number(u.id) !== Number(state.user.id) ? `<button class="outline danger user-remove" data-id="${Number(u.id)}">Deactivate</button>` : ""}</td></tr>`).join("")}</tbody></table>`;
  } catch (error) {
    $("userTable").innerHTML = `<p class="error">${esc(error.message)}</p>`;
  }
}
function userRoleField() {
  $("userBrandLabel").classList.toggle(
    "hidden",
    !["organiser", "staff"].includes($("userForm").elements.role.value),
  );
}
function editUser(id = null) {
  state.userEditId = id;
  const u = id ? state.userRows.find((u) => Number(u.id) === id) : {};
  const form = $("userForm");
  form.reset();
  form.elements.name.value = u.name || "";
  form.elements.email.value = u.email || "";
  form.elements.role.value = u.role || "customer";
  form.elements.brand.value = u.brand || "AWM";
  form.elements.password.required = !id;
  userRoleField();
  $("userError").textContent = "";
  $("userDialogTitle").textContent = id ? "Edit user" : "Add user";
  $("userDialog").showModal();
}
$("userForm").elements.role.onchange = userRoleField;
$("addUser").onclick = () => editUser();
$("userTable").onclick = async (event) => {
  const edit = event.target.closest(".user-edit"),
    remove = event.target.closest(".user-remove");
  if (edit) editUser(Number(edit.dataset.id));
  if (remove) {
    if (!confirm("Deactivate this user? Historical bookings are retained."))
      return;
    remove.disabled = true;
    try {
      await api(`admin/users/${remove.dataset.id}`, "DELETE");
      toast("User deactivated.");
      loadUsers();
    } catch (error) {
      toast(error.message);
      remove.disabled = false;
    }
  }
};
$("userForm").onsubmit = async (event) => {
  event.preventDefault();
  event.submitter.disabled = true;
  $("userError").textContent = "";
  try {
    const body = Object.fromEntries(new FormData(event.target));
    if (!body.password) delete body.password;
    if (!["organiser", "staff"].includes(body.role)) body.brand = null;
    await api(
      "admin/users" + (state.userEditId ? "/" + state.userEditId : ""),
      state.userEditId ? "PATCH" : "POST",
      body,
    );
    $("userDialog").close();
    toast("User saved.");
    await loadUsers();
  } catch (error) {
    $("userError").textContent = error.message;
  } finally {
    event.submitter.disabled = false;
  }
};
