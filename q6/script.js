"use strict";

const container = document.querySelector("#paragraphs");
const addButton = document.querySelector("#add-paragraphs");

// Capture only the three originals, before adding separators or copies.
const originals = [...container.querySelectorAll(":scope > p")].map(
  (paragraph) => paragraph.cloneNode(true),
);

let imagesHidden = false;

const toggleButton = document.createElement("button");
toggleButton.type = "button";
toggleButton.textContent = "Hide image paragraphs";
toggleButton.setAttribute("aria-controls", "paragraphs");
toggleButton.setAttribute("aria-pressed", "false");
addButton.after(toggleButton);

function updateParagraphs() {
  container.querySelectorAll("hr").forEach((hr) => hr.remove());

  const paragraphs = [...container.querySelectorAll(":scope > p")];

  paragraphs.forEach((paragraph, index) => {
    paragraph.hidden = imagesHidden && paragraph.querySelector("img") !== null;

    // Count every paragraph, including hidden ones.
    // No separator after the final paragraph.
    if ((index + 1) % 2 === 0 && index < paragraphs.length - 1) {
      paragraph.after(document.createElement("hr"));
    }
  });
}

addButton.addEventListener("click", () => {
  const copies = originals.map((paragraph) => paragraph.cloneNode(true));

  // Fisher–Yates shuffle.
  for (let i = copies.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [copies[i], copies[j]] = [copies[j], copies[i]];
  }

  container.append(...copies);
  updateParagraphs();
});

toggleButton.addEventListener("click", () => {
  imagesHidden = !imagesHidden;

  toggleButton.textContent = imagesHidden
    ? "Show image paragraphs"
    : "Hide image paragraphs";

  toggleButton.setAttribute("aria-pressed", String(imagesHidden));
  updateParagraphs();
});

updateParagraphs();
