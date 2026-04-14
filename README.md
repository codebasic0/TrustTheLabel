# Trust The Label

Consumer-focused site to expose product ingredients, highlight risks, and suggest safer alternatives.

Status: Prototype — static demo with category browsing, product dataset, and a camera barcode scanner.

Quick start

1. Serve the folder locally (Python):

```powershell
cd "C:\FILES {not related to windows}\TrustTheLabel-main"
py -3 -m http.server 8000
# Open http://localhost:8000/Home.html
```

What’s included

- `Home.html` — landing page with browse categories, facts ticker, and mood slider.
- `products.js` — central product dataset (name, brand, harmfulSubstances, saferAlternatives, barcode, image path).
- `category.html` + `category.js` — category listing with risk badges and search.
- `scan.html` + `scan.js` — camera barcode scanner using ZXing and instant product lookup.
- `images/README.md` — expected product image filenames to place in `images/`.

Next steps

- Add product images to `images/` using the names in `images/README.md`.
- Import a CSV to bulk-map UPCs and product metadata.
- Add user uploads, reviews, and persistent backend (server + DB) for production.

License: See `LICENSE`.