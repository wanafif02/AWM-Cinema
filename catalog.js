"use strict";

// SHARED MOVIE PREVIEW — fixed local poster paths, not user-supplied URLs.
const MOVIE_CATALOG = {
  "Avengers: Endgame": {
    path: "assets/posters/avengers-endgame.jpg",
    year: 2019,
  },
  Avatar: {
    path: "assets/posters/avatar.jpg",
    year: 2009,
  },
  Interstellar: {
    path: "assets/posters/interstellar.jpg",
    year: 2014,
  },
  Inception: {
    path: "assets/posters/inception.jpg",
    year: 2010,
  },
  Titanic: {
    path: "assets/posters/titanic.jpg",
    year: 1997,
  },
  "Top Gun: Maverick": {
    path: "assets/posters/top-gun-maverick.jpg",
    year: 2022,
  },
};

function posterMarkup(title) {
  const movie = MOVIE_CATALOG[title];
  return movie
    ? `<img class="movie-poster" src="${movie.path}" alt="${title} theatrical poster" loading="lazy">`
    : "";
}

function renderMoviePreview() {
  document.getElementById("moviePreview").innerHTML = Object.entries(
    MOVIE_CATALOG,
  )
    .map(
      ([title, movie]) => `
        <article class="preview-card"><img src="${movie.path}" alt="${title} poster" loading="lazy"><div><h3>${title}</h3><span>${movie.year} · Classic film</span></div></article>
    `,
    )
    .join("");
}
