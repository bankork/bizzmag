# Demo data: Adunni Hair & Beauty Studio

A fictional Lagos salon you can load on a development or staging copy to try every phase yourself. It is never loaded in production; the command refuses.

## Load it

```bash
cd backend
php artisan app:demo            # add or refresh demo data
php artisan app:demo --fresh    # WIPE the database first, then load (asks to confirm)
php artisan app:demo:calc       # print worked invoices and staff credit, no database needed
```

## Sign in

**Password for every account:** `Adunni@2026`

Sign-in is switched on in Phase 1, and roles are applied then.

| Name | Email | Role |
|---|---|---|
| Adunni Bakare | adunni@adunni.test | Owner |
| Ngozi Okafor | ngozi@adunni.test | Supervisor (also braids and wigs) |
| Kemi Adeyemi | kemi@adunni.test | Front desk / cashier |
| Emeka Nwosu | emeka@adunni.test | Front desk / cashier |
| Chiamaka Eze | chiamaka@adunni.test | Braider, wigs |
| Funmilayo Ogunleye | funmilayo@adunni.test | Braider, hair care |
| Blessing Udoh | blessing@adunni.test | Braider |
| Hauwa Abdullahi | hauwa@adunni.test | Braider, hair care |
| Zainab Musa | zainab@adunni.test | Nail technician |
| Efe Omoregie | efe@adunni.test | Nail technician (pedicures) |
| Tolani Johnson | tolani@adunni.test | Lashes and makeup |
| Precious Okon | precious@adunni.test | Apprentice |

## What is in it, phase by phase

| Phase | Adds |
|---|---|
| P0 (now) | Staff login accounts. The full price list and calculation scenarios (`app:demo:calc`). |
| P1 | Roles, permissions and business settings (tax off; a VAT 7.5% variant to try) |
| P2 | Catalogue in the database (6 categories, variants, products) and about 150 customers |
| P3 to P5 | About 90 days of visits: Saturday rushes, shared braids, extra services, part payments, cash, POS and transfers |
| P6 | Cashier closings, including a cash shortage, plus unverified and rejected transfers |
| P7 | A month of staff performance and bonus calculations |

## The price list

Prices are plausible Lagos mid-range prices for late 2026. Edit them in the catalogue screens from Phase 2.

| Category | Examples |
|---|---|
| Braids | Knotless (S/M/L) ₦55,000 / ₦40,000 / ₦30,000; Box braids from ₦25,000; Boho knotless ₦65,000; Ghana weaving; Cornrows |
| Wigs and installs | Frontal install ₦25,000; Closure install ₦15,000; Revamp; Customisation; Sew-in |
| Hair care | Silk press ₦18,000; Relaxer retouch; Treatment; Twist-out; Loc retwist |
| Nails | Manicure ₦7,000; Pedicure ₦9,000; Gel; Acrylic (short or long); Refill; Nail art |
| Lashes and makeup | Classic and volume lashes; Soft glam ₦25,000; Bridal ₦120,000; Gele tying ₦8,000 |
| Products | X-pression hair (pack) ₦3,500; Pre-stretched hair; Edge control; Leave-in; Lace glue; Lash glue |

The full list is in `backend/database/seeders/Demo/Data/catalogue.php`.

## Everything is fictional

- Names, the salon and its address details are invented.
- Emails use the reserved `.test` domain, so no message can ever be delivered.
- Phone numbers use real Nigerian network prefixes and a fixed `000` block, for example `0803 000 4821`. Nigeria has no reserved fictional range, so a demo number could in principle belong to someone. **Never use demo data to send real SMS or WhatsApp messages.**
- The bank account on demo receipts is `0000000000` at "Demo Bank".
- Transfer and POS references only copy the *style* of real alerts.
