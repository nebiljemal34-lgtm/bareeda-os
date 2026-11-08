# Bareeda Injera — Website Project

**Domain:** www.bareeda-injera.com
**Brand Color:** #c6b79b (Gold/Beige)
**Location:** Jerba Aliyi, Dire Dawa, Ethiopia
**Contact:** +251 940 434 748 | info@bareeda-injera.com

---

## Folder Structure

```
bareeda-website/
├── assets/
│   └── images/
│       ├── bareeda-qr-business-card.png   ← QR code for business card (800px)
│       ├── bareeda-qr-business-card.svg   ← Vector QR (best for print)
│       ├── business-card/                  ← Business card design reference
│       └── product-label.png               ← Product label design reference
├── links/
│   └── index.html                          ← Landing page (QR code target)
├── docs/
│   ├── bareeda-website-scope.md            ← Full project scope (50 questions)
│   └── DEPLOY-INSTRUCTIONS.md              ← How to deploy to cPanel
├── bareeda-links.html                      ← Earlier landing page draft
├── bareeda-social-qr.html                  ← Multi-QR page draft
└── qr-generator.html                       ← QR code generator tool
```

---

## Business Card QR Code

- **File:** `assets/images/bareeda-qr-business-card.png`
- **URL:** `https://www.bareeda-injera.com/links`
- **Error Correction:** Level H (30% damage tolerance)
- **Print Size:** Minimum 2cm x 2cm

### To Deploy the Landing Page

1. Log in to cPanel → File Manager → `public_html/`
2. Create folder `links/`
3. Upload `links/index.html` into it
4. Test: visit `https://www.bareeda-injera.com/links`

---

## Design System (from Business Card)

| Element | Value |
|---|---|
| Primary Background | Deep Navy `#0b1a2b` |
| Brand Accent | Gold/Beige `#c6b79b` |
| Accent Light | `#d4c9b3` |
| Accent Dark | `#a89878` |
| Text Primary | `#f0ece4` |
| Text Muted | `#7a7568` |
| Heading Font | Inter (900 weight, uppercase, letter-spacing) |
| Body Font | Inter (400-600) |
| Accent Font | Playfair Display (italic, for taglines) |
| Pattern | Concentric circles (injera bubble texture) |
| Logo | Circular "B" with injera texture + wheat stalks |
| Amharic | በሬዳ እንጀራ |

---

## Project Status

- [x] Business card QR code generated
- [x] Landing page designed (matches business card style)
- [x] Project scope documented (32/50 questions answered)
- [ ] Landing page deployed to cPanel
- [ ] Full e-commerce website build (Phase 1-3)

---

*Created: 2026-11-07*
*Last Updated: 2026-11-07*
