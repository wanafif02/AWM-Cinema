<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>AWM Cinema · Movie nights, made memorable</title>
    <link rel="stylesheet" href="assets/style.css?v=awm-1" />
  </head>
  <body data-mode="customer" class="signed-out">
    <header>
      <a class="logo" href="#" aria-label="Home"
        ><span>AWM</span><small>CINEMA</small></a
      >
      <nav>
        <button class="nav active" data-view="movies">Now showing</button
        ><button class="nav" data-view="bookings">My tickets</button
        ><button class="nav manager hidden" data-view="operations">
          Hall activity</button
        ><button class="nav manager hidden" data-view="manage">
          Screenings & halls</button
        ><button class="nav manager hidden" data-view="seatManagement">
          Seat studio</button
        ><button class="nav admin-only hidden" data-view="users">Users</button>
      </nav>
      <div class="account">
        <select id="brandScope" aria-label="Cinema brand">
          <option>AWM</option></select
        ><span id="userLabel"></span
        ><button id="loginBtn" class="outline">Sign in</button
        ><button id="logoutBtn" class="outline hidden">Sign out</button>
      </div>
    </header>
    <main>
      <!-- SHARED ENTRY: THREE LOGIN SECTIONS + PUBLIC MOVIE PREVIEW -->
      <section id="welcomeView" class="view welcome-layout">
        <div class="welcome-showcase">
          <div class="eyebrow">AWM CINEMA · CLASSICS ON THE BIG SCREEN</div>
          <h1>Some stories deserve<br /><em>the big screen.</em></h1>
          <p class="welcome-copy">
            Rediscover unforgettable films. Preview the line-up, then sign in to
            choose your seats and make it a movie night.
          </p>
          <div id="moviePreview" class="preview-grid"></div>
          <button id="browseMovies" class="outline">
            Explore all screenings ↗
          </button>
          <p class="muted small">
            Demo screenings of classic films · no real payment
          </p>
        </div>
        <aside class="welcome-signin">
          <div class="eyebrow">YOUR NEXT EXPERIENCE STARTS HERE</div>
          <h2>Welcome to AWM.</h2>
          <p class="muted">
            Sign in to book, or enter your assigned team workspace.
          </p>
          <div class="role-tabs">
            <button class="role-choice active" data-role="customer">
              Customer<br /><small>Afif</small></button
            ><button class="role-choice" data-role="admin">
              Admin<br /><small>Aiman</small></button
            ><button class="role-choice" data-role="organiser">
              Staff<br /><small>Mat Fairuz</small>
            </button>
          </div>
          <form id="welcomeForm">
            <label
              >Email<input
                name="email"
                type="email"
                autocomplete="username"
                placeholder="you@example.com"
                required /></label
            ><label
              >Password<input
                name="password"
                type="password"
                autocomplete="current-password"
                required
                maxlength="72"
            /></label>
            <p id="welcomeError" class="error" role="alert"></p>
            <button class="gold full" type="submit">Sign in →</button>
          </form>
          <button id="welcomeRegister" class="text-btn">
            New customer? Create an account
          </button>
          <div id="welcomeRoleHelp" class="login-note">
            Customer · Find a film, choose seats and manage your tickets.
          </div>
        </aside>
      </section>
      <!-- MAT FAIRUZ: TICKET DESK + ONGOING HALL OVERVIEW -->
      <section id="operationsView" class="view hidden">
        <div class="section-heading">
          <div>
            <div class="eyebrow">AWM STAFF OPERATIONS</div>
            <h1 class="page-title">Every hall, in view.</h1>
            <p class="muted">
              Live screening activity, seat readiness and ticket counts.
            </p>
          </div>
          <button id="refreshOperations" class="outline">
            Refresh activity
          </button>
        </div>
        <div id="operationStats" class="stat-grid"></div>
        <div id="hallActivity" class="hall-grid"></div>
      </section>

      <!-- WAN AFIFUDDIN: CUSTOMER SEAT SELECTION -->
      <section id="seatsView" class="view hidden">
        <button id="seatBack" class="text-btn">← Back to screenings</button>
        <div class="section-heading">
          <div>
            <div class="eyebrow">STEP 02 · MAKE IT YOUR SEAT</div>
            <h1 class="page-title">Choose your seats</h1>
            <p id="seatSubtitle" class="muted"></p>
          </div>
          <button id="seatRefresh" class="outline">Refresh availability</button>
        </div>
        <div class="seat-layout">
          <div class="seat-hall">
            <div class="screen-bar">SCREEN</div>
            <div id="customerSeats" class="seat-grid"></div>
            <div class="seat-legend">
              <span>○ Available</span
              ><span class="selected-color">● Selected</span
              ><span>▣ Booked</span><span>× Unavailable</span>
            </div>
          </div>
          <aside class="seat-summary">
            <div class="eyebrow">YOUR MOVIE NIGHT</div>
            <h2 id="seatMovie"></h2>
            <p id="selectedLabels" class="muted">Choose 1–10 seats.</p>
            <div class="total">
              <span>Total</span><strong id="seatPrice">RM 0.00</strong>
            </div>
            <p id="seatError" class="error" role="alert"></p>
            <button id="confirmSeats" class="gold full" disabled>
              Confirm seats & booking
            </button>
            <p class="small muted">
              Seats are reserved only when your booking is confirmed. No payment
              is collected.
            </p>
          </aside>
        </div>
      </section>
      <!-- MAT FAIRUZ: SEAT MANAGEMENT -->
      <section id="seatManagementView" class="view hidden">
        <div class="section-heading">
          <div>
            <div class="eyebrow">MAT FAIRUZ · STAFF WORKSPACE</div>
            <h1 class="page-title">Seat studio</h1>
            <p class="muted">
              Keep every hall ready. Select a hall and click a seat to block or
              reopen it.
            </p>
          </div>
          <button id="seatManagerRefresh" class="outline">Refresh</button>
        </div>
        <div class="filters">
          <select id="seatVenue" aria-label="Choose cinema hall"></select
          ><button id="generateSeats" class="gold">
            Generate default layout
          </button>
        </div>
        <div class="seat-hall">
          <div class="screen-bar">SCREEN</div>
          <div id="organiserSeats" class="seat-grid"></div>
        </div>
        <p class="small muted">
          Blocked seats cannot be booked. Seats reserved for ongoing/future
          screenings cannot be blocked. Layouts use ten seats per row and the
          hall's existing capacity.
        </p>
      </section>
      <!-- AIMAN HAKIM: ADMIN USER MANAGEMENT -->
      <section id="usersView" class="view hidden">
        <div class="section-heading">
          <div>
            <div class="eyebrow">AIMAN'S ADMIN WORKSPACE</div>
            <h1 class="page-title">People & permissions</h1>
            <p class="muted">
              Manage customer, staff and organiser accounts across cinema
              brands.
            </p>
          </div>
          <button id="addUser" class="gold">+ Add user</button>
        </div>
        <div id="userTable" class="table-wrap"></div>
      </section>

      <!-- WAN AFIFUDDIN: CUSTOMER MOVIE BROWSING -->
      <section id="moviesView" class="view">
        <div class="hero">
          <div class="hero-copy">
            <div class="eyebrow">THE BIG SCREEN IS CALLING</div>
            <h1>A movie night<br /><em>worth remembering.</em></h1>
            <p>
              Your next escape starts here. Discover a screening, choose your
              tickets and make it a movie night.
            </p>
            <a href="#nowshowing" class="gold button"
              >Explore screenings <span>↗</span></a
            >
            <div class="hero-meta">
              <span>AWM Cinema</span><span>Book in moments</span
              ><span>Digital tickets</span>
            </div>
          </div>
          <div class="hero-art" aria-hidden="true">
            <div class="orbit"></div>
            <div class="art-sun"></div>
            <div class="art-mountain"></div>
            <div class="art-label">
              THE NEXT<br />ADVENTURE<span>STARTS ON THE BIG SCREEN</span>
            </div>
            <div class="art-ticket">ADMIT ONE <b>✦</b> YOUR NEXT ESCAPE</div>
          </div>
        </div>
        <section class="listing" id="nowshowing">
          <div class="section-heading">
            <div>
              <div class="eyebrow">FIND YOUR NEXT FAVOURITE</div>
              <h2>Now showing</h2>
            </div>
            <span id="movieCount" class="muted"></span>
          </div>
          <div class="filters">
            <input
              id="search"
              type="search"
              placeholder="Search a movie…"
              aria-label="Search movie"
            /><select id="sort" aria-label="Sort screenings">
              <option value="starts_at">Next screening</option>
              <option value="price_cents">Lowest ticket price</option>
              <option value="id">Recently added</option></select
            ><button id="refresh" class="outline">Refresh</button>
          </div>
          <div id="movies" class="movie-grid"></div>
          <div id="moviePaging" class="paging"></div>
        </section>
      </section>
      <section id="bookingsView" class="view hidden">
        <div class="section-heading">
          <div>
            <div class="eyebrow">YOUR NEXT NIGHT OUT</div>
            <h1 class="page-title">My tickets</h1>
          </div>
          <button id="reloadBookings" class="outline">Refresh</button>
        </div>
        <p class="muted">
          Your AWM bookings, confirmations and cancellations in one place.
        </p>
        <div id="bookingList" class="ticket-list"></div>
        <div id="bookingPaging" class="paging"></div>
      </section>
      <section id="manageView" class="view hidden">
        <div class="section-heading">
          <div>
            <div class="eyebrow">CINEMA OPERATIONS WORKSPACE</div>
            <h1 class="page-title">Manage cinema</h1>
          </div>
          <div>
            <button id="newVenue" class="outline">+ Add hall</button>
            <button id="newEvent" class="gold">+ Add screening</button>
          </div>
        </div>
        <div class="tabs">
          <button id="showEvents" class="outline">Screenings</button
          ><button id="showVenues" class="outline">Cinema halls</button>
        </div>
        <div id="management" class="table-wrap"></div>
      </section>
    </main>
    <footer>
      <span class="logo footer-logo">AWM <small>CINEMA</small></span>
      <p>
        Aiman · Admin / Wan Afif · Customer / Mat Fairuz · Staff<br />Fictional
        screenings. No payments or connection to the actual AWM Cinema.
      </p>
      <span>SWC3633 / SWC4443</span>
    </footer>
    <div id="toast" role="status" class="toast hidden"></div>
    <dialog id="authDialog">
      <button class="close" data-close="authDialog" aria-label="Close">
        ×
      </button>
      <div class="eyebrow">WELCOME TO MOVIE NIGHT</div>
      <h2 id="authTitle">Sign in</h2>
      <form id="authForm">
        <label id="nameField" class="hidden"
          >Name<input name="name" maxlength="200" /></label
        ><label
          >Email<input
            name="email"
            type="email"
            autocomplete="username"
            required /></label
        ><label
          >Password<input
            name="password"
            type="password"
            autocomplete="current-password"
            minlength="12"
            maxlength="72"
            required
        /></label>
        <p id="authError" class="error" role="alert"></p>
        <button class="gold full" type="submit">Sign in</button>
      </form>
      <button id="switchAuth" class="text-btn">
        New here? Create an account
      </button>
      <p class="muted small">
        Use a customer account for ticket bookings. Organiser accounts open the
        cinema dashboard.
      </p>
    </dialog>
    <dialog id="editDialog">
      <button class="close" data-close="editDialog" aria-label="Close">
        ×
      </button>
      <div class="eyebrow">ORGANISER TOOLS</div>
      <h2 id="editTitle"></h2>
      <form id="editForm">
        <div id="editFields"></div>
        <p id="editError" class="error" role="alert"></p>
        <button class="gold full" type="submit">Save changes</button>
      </form>
    </dialog>
    <dialog id="qrDialog">
      <button class="close" data-close="qrDialog" aria-label="Close">×</button>
      <div class="eyebrow">YOUR DIGITAL TICKET</div>
      <h2>Movie night, confirmed.</h2>
      <div id="qrContent"></div>
      <p class="muted small">
        Demo booking reference. Not valid at actual cinemas.
      </p>
    </dialog>
    <dialog id="userDialog">
      <button class="close" data-close="userDialog" aria-label="Close">
        ×
      </button>
      <div class="eyebrow">ADMINISTRATOR TOOLS</div>
      <h2 id="userDialogTitle">Add user</h2>
      <form id="userForm">
        <label>Name<input name="name" required maxlength="200" /></label
        ><label
          >Email<input
            name="email"
            type="email"
            required
            maxlength="254" /></label
        ><label
          >Role<select name="role">
            <option value="customer">Customer</option>
            <option value="organiser">Organiser</option>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select></label
        ><label id="userBrandLabel"
          >Brand<select name="brand">
            <option>AWM</option>
          </select></label
        ><label
          >Password<input
            name="password"
            type="password"
            minlength="12"
            maxlength="72"
            autocomplete="new-password"
          /><small class="muted"
            >For edits, leave empty to keep the current password.</small
          ></label
        >
        <p id="userError" class="error" role="alert"></p>
        <button class="gold full" type="submit">Save user</button>
      </form>
    </dialog>
    <script src="assets/catalog.js?v=awm-1"></script>
    <script src="assets/common.js?v=awm-1"></script>
    <script src="assets/workspace.js?v=awm-1"></script>
    <script src="assets/customer-wan-afif.js?v=awm-1"></script>
    <script src="assets/seats-mat.js?v=awm-1"></script>
    <script src="assets/admin-aiman.js?v=awm-1"></script>
    <script src="assets/app.js?v=awm-1"></script>
  </body>
</html>
