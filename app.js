"use strict";

// AWM SHARED STARTUP — login sections alongside public movie preview.
chooseWelcomeRole("customer");
updateAccount();
view("welcome");
renderMoviePreview();
loadMovies();
