# Motor Parts Market

A chat-first online shop for vehicle lights (headlights and taillights). Customers type what they need — "Hilux 2018 tail light, driver side" — and see real photos and videos of what is in stock.

**Stack:** PHP 8.1+ · MySQL · plain JavaScript (no build step) · Groq AI (optional) · built with SWIFTDNA.
**Live:** https://motorpartsmarket.co.za · **Repo:** https://github.com/bothamaria169-code/motorpartsmarket

Full switch-on steps (database, WhatsApp, Groq, Google): **[docs/SETUP-GUIDE.md](docs/SETUP-GUIDE.md)**

## Area → Where

| Area | File |
|---|---|
| Chat home page (vehicle list, all products, chat) | `index.php` |
| Product page (photos, video, enquiry form) | `part.php` |
| Privacy / Terms | `privacy.php`, `terms.php` |
| Chat brain (reads vehicle, year, light, side) | `includes/chat.php` |
| Product search and vehicle list | `includes/catalog.php` |
| Groq AI | `includes/ai.php` |
| Chat + enquiry endpoints | `ajax/chat.php`, `ajax/enquiry.php` |
| Admin | `admin/` (Dashboard, Products, Enquiries, Chats, Settings, Audit log) |
| Staff sign-in | `login.php` |
| WhatsApp bot | `webhook.php` |
| Daily clean-up job | `cron.php` |
| Design (glass look, light + dark) | `assets/css/style.css`, `assets/css/admin.css` |
| Front-end behaviour | `assets/js/app.js` |
| Tables + first 12 products | `database/migrate.php`, `database/seed-products.json` |
| Product photos and videos | `assets/media/` (first stock), `uploads/products/` (added in Admin) |
| Passwords and keys | `config/secrets.php` (never in GitHub) |

## Run it locally (5 min)

1. Open **XAMPP Control Panel** → **Start** Apache and MySQL.
2. Open http://localhost/motorpartsmarket/ — the database `motorpartsmarket` and the 12 products are created on the first load.
3. Sign in at http://localhost/motorpartsmarket/login.php with `admin@swiftfusion.co.za` and the `admin_seed_password` in `config/secrets.php`.

**Test:** type `Kia Rio 2014 headlight` in the chat. One Kia Rio card should appear.

## Deploy (GitHub + cPanel)

Same method as BidCo Auctions and EasyAuctions: push to GitHub, then press Deploy in cPanel. The site copies into `public_html`.

**First time — on your computer** (in `C:\xampp\htdocs\motorpartsmarket`):

```bash
git init -b main
```
```bash
git remote add origin https://github.com/bothamaria169-code/motorpartsmarket.git
```
```bash
git add -A
```
```bash
git commit -m "Motor Parts Market: chat shop"
```
```bash
git push -u origin main
```

> The repository is about 60 MB because it carries the product photos and videos. The first push takes a few minutes.

**Then in cPanel** — see [docs/SETUP-GUIDE.md](docs/SETUP-GUIDE.md) sections 1–3 for the database, `secrets.php` and the Git screen.

**Every change after that:** commit → `git push` → cPanel → **Git™ Version Control** → **Manage** → **Pull or Deploy** → **Update from Remote** → **Deploy HEAD Commit**.

## After deploying — switch things on

1. 🟩 **ADMIN** → **Products**: add a price and condition to each part (they show "Ask for price" until you do).
2. 🟩 **ADMIN** → **Settings**: WhatsApp number, enquiry email.
3. 🟦 **ACCOUNT** Groq key → 🟩 **Settings** → **Test AI**.
4. Google Search Console + Analytics, WhatsApp bot — all in the setup guide.

## Troubleshooting

| You see | Do this |
|---|---|
| "We'll be right back" | Read the grey "For the site owner" line. It names the fix (missing `secrets.php`, wrong password, user not added to database). |
| "Setup could not finish" | The database is not empty. Use a **new** database — the old site's tables have the same names. |
| "needs PHP 8.1 or newer" | cPanel → **Select PHP Version** (or **MultiPHP Manager**) → **8.2**. |
| Old site still shows | You pressed Update but not **Deploy HEAD Commit**. Or clear the browser cache. |
| Locked out of admin | Add `'admin_reset_password' => 'NewPassword123'` to `config/secrets.php`, load any page once, sign in. |
| Photo upload fails | Videos must be MP4 under 40 MB. Check **Select PHP Version → Options → upload_max_filesize**. |
| No WhatsApp buttons | Add the number in **Admin → Settings**. |

## Not built yet

- Online card payment (PayFast / Yoco). Orders are enquiries: you confirm fitment, price and delivery with the customer, then take payment.
- Automatic Facebook / Instagram posting and courier quotes.
- A cover picture for videos uploaded in Admin (the first 12 products have them).
