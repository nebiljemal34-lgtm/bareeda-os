# Bareeda Injera — QR Code & Landing Page Deployment

## What You Have

1. **`bareeda-qr-business-card.png`** — 800x800px QR code for your business card
2. **`bareeda-qr-business-card.svg`** — Vector version (infinite scaling, best for print)
3. **`links/index.html`** — The landing page the QR code points to

## QR Code Details

- **Encoded URL:** `https://www.bareeda-injera.com/links`
- **Error Correction:** Level H (30% damage tolerance)
- **Color:** Dark navy (#1a1a2e) on white
- **Recommended print size:** Minimum 2cm x 2cm on business card

---

## DEPLOYMENT: Get the Landing Page Live (Tonight)

### Option A: cPanel File Manager (Easiest)

1. Log in to your cPanel at `www.bareeda-injera.com/cpanel` (or your hosting provider's URL)
2. Open **File Manager**
3. Navigate to `public_html/`
4. Click **+ Folder** → create a folder named `links`
5. Open the `links` folder
6. Click **Upload** → select `links/index.html`
7. Done! Test by visiting: `https://www.bareeda-injera.com/links`

### Option B: FTP

1. Connect via FTP (FileZilla or similar)
2. Navigate to `/public_html/`
3. Create folder `links/`
4. Upload `index.html` into it
5. Test: `https://www.bareeda-injera.com/links`

---

## IMPORTANT: Before Printing Business Cards

1. **Upload the landing page FIRST** (follow steps above)
2. **Test the QR code** — scan it with your phone, verify it opens the page
3. **Only then** send the QR code to the printer

If the page isn't live when cards are printed, the QR code will show an error until you upload it.

---

## Future: When the Full Website Launches

When the new e-commerce site is ready, the `/links` page will be replaced with the full contact/social media page as part of the new site. The QR code URL stays the same — no need to reprint cards.

---

## Files Location

```
/home/bareeda/Documents/Qoder/2026-11-07/29c73df2/
├── bareeda-qr-business-card.png   ← Send this to printer
├── bareeda-qr-business-card.svg   ← Vector version (better for print)
├── links/
│   └── index.html                  ← Upload this to cPanel
└── DEPLOY-INSTRUCTIONS.md          ← This file
```

---

*Created: 2026-11-07*
*QR Code URL: https://www.bareeda-injera.com/links*
