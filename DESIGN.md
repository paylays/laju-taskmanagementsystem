# Lajur design

A signed-in task board for a small product team: flat, square-edged, thin type, calm.

## Direction

| Field | Direction |
|---|---|
| Identity | A work tool, not a marketing page. |
| Typography | No large type. Thin weights. |
| Shape | No rounded corners on cards, buttons, inputs, or similar elements (radius 0). |
| Palette | Simple colors. |
| Gradients | None. |
| Theme | Light only: an office tool used in daylight. |
| Motion | None beyond hover and focus color changes. |

## Decisions and reasons

| Decision | Reason |
|---|---|
| IBM Plex Sans, weight 300 for text, 400 for controls | Has a true Light cut drawn for screens, so "thin" stays legible at 14px; bundled locally, no font CDN. |
| Type scale 13 / 14 / 16 / 18px, nothing larger | Owner asked for no large type; 18px is the page title ceiling. |
| `border-radius: 0` on everything | Owner direction. |
| Palette "Marka Jalan" | Taken from road markings, the source of the name Lajur: asphalt ink, concrete lanes, marking yellow. Chosen from four options (Hutan, Nila, Marka Jalan, Grafit). |
| Asphalt ink `#26282A` on white, muted `#5E6266` | Thin strokes lose apparent contrast, so body text sits near black (14.8:1); muted text is still 6.2:1. |
| Concrete lane ground `#EEEFEF` | Separates the three status lanes from the white top bar without borders or shadows. Muted text on it is 5.3:1. |
| One accent: marking yellow `#F5B800` | Fills the one primary action per screen ("New task", "Save changes", "Sign in"), the selected lane tab's marking, and the live marker. Yellow on white is only 1.8:1, so it is always a filled surface with ink text (8.3:1) and an ink edge; the focus ring and upload progress use ink instead. |
| Yellow header band | The dashboard top bar is filled with marking yellow and closed by an ink line. Muted grey is only 3.4:1 on yellow, so every header text is ink (8.3:1); the live marker turns ink there so it stays visible. |
| Toasts as simple alerts | White box with an ink border, a square marker, and the kind written out (Success, Info, Error) so the state never depends on color. Bottom right on desktop, full width on phones. Success and Info close after 4 s; Error stays until closed. While the task panel (a modal dialog) is open, toasts render inside it: beside the panel on wide screens, at the panel's bottom edge on narrow ones, where scrolling to the end always uncovers the last button. |
| Yellow tint `#FFF3CC` | Background of selected rows and of the drop zone while a file is dragged over it. Ink, muted, and red text all stay above 5.5:1 on it. |
| Red `#B42318` for high priority and errors only | A functional status color, always paired with a text label, never color alone. |
| Control borders `#878B8F` | 3.4:1 against white, meets the 3:1 non-text bar. Never used for text. |
| No shadows | Flat by direction. The task panel is the only overlay and is separated by a 1px ink border instead. |
| Identity motif: yellow dashed lane dividers | "Lajur" means lane; the board's columns are divided like road lane markings. The divider is decorative (each lane has its own heading), so its 1.6:1 against the concrete ground is accepted. |
| Square status markers | The live-connection marker and upload progress are square, matching the zero-radius rule. |
| Motion: none beyond hover/focus color changes | Keeps the tool calm. Upload progress moves because it reports real progress. |
| Mobile: one lane at a time with lane tabs | Three lanes do not fit a phone; tabs keep each lane full width. |
