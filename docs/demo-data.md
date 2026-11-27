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

Sign in with the **email or the phone number**. Phone numbers can be typed the local way (`0803 000 1234`) or with `+234`. Each person lands in the area their role allows.

| Name | Email | Phone | Role | Lands on |
|---|---|---|---|---|
| Adunni Bakare | adunni@adunni.test | 0902 000 0774 | Owner | Admin |
| Ngozi Okafor | ngozi@adunni.test | 0811 000 3336 | Supervisor (also braids and wigs) | Admin |
| Kemi Adeyemi | kemi@adunni.test | 0806 000 1917 | Front desk / Cashier | Front desk |
| Emeka Nwosu | emeka@adunni.test | 0708 000 1060 | Front desk / Cashier | Front desk |
| Chiamaka Eze | chiamaka@adunni.test | 0807 000 5779 | Employee: braider, wigs | Staff |
| Funmilayo Ogunleye | funmilayo@adunni.test | 0814 000 6133 | Employee: braider, hair care | Staff |
| Blessing Udoh | blessing@adunni.test | 0708 000 5564 | Employee: braider | Staff |
| Hauwa Abdullahi | hauwa@adunni.test | 0817 000 0998 | Employee: braider, hair care | Staff |
| Zainab Musa | zainab@adunni.test | 0809 000 0923 | Employee: nail technician | Staff |
| Efe Omoregie | efe@adunni.test | 0908 000 4249 | Employee: nail technician (pedicures) | Staff |
| Tolani Johnson | tolani@adunni.test | 0807 000 4176 | Employee: lashes and makeup | Staff |
| Precious Okon | precious@adunni.test | 0808 000 8751 | Employee: apprentice | Staff |

The phone numbers are the same on every machine. The two cashiers have no employee profile, because they do not do salon work; everyone else can be credited for services.

### Things to try in Phase 1

1. Sign in as **Chiamaka** and open `/admin/settings/`: you are told you have no access, and the attempt is recorded.
2. Sign in as **Adunni**, open **Staff**, add a cashier with only a phone number, then sign in as them: they must choose a new password first.
3. As Adunni, open **Settings > Tax**, switch VAT on at 7.5%, and see the change in the history. Switch it off again the same way.
4. Type a wrong password for **Kemi** five times: sign-in is locked for 15 minutes on that device.
5. Leave **Kemi** signed in and idle for 30 minutes: the next click asks her to sign in again. Employees stay signed in for 7 days.

### Things to try in Phase 2

1. Sign in as **Adunni** and open **Catalogue**. Services are grouped by category; Knotless braids shows "3 variants from ₦30,000".
2. Open **Knotless braids**, change the Medium price, save, and see the change in the history at the bottom. Change the credit split to 70% and read the worked example for 1, 2 and 3 staff.
3. Add a new service with variants, then add the variants and their prices.
4. Sign in as **Kemi**, open **Customers**, and add a customer using the phone number of someone already in the list (any way of writing it). You are warned and shown the existing customer; **Add anyway** saves.
5. Search customers by name, by a full number, or by its last 4 digits.
6. Sign in as **Adunni**, open **Customers**, open anyone, and use **Remove personal data** (type REMOVE). The name becomes "Removed customer". Kemi and Ngozi do not see this button, and the API refuses them.
7. Sign in as **Chiamaka**: the Catalogue and Customers pages are refused, and the services list the API gives her has no prices.

### Things to try in Phase 3

Use a laptop for the desk and phones (or narrow browser windows, one per person) for staff. Each person signs in on their own device.

1. Sign in as **Kemi** (desk). The **Visits board** shows this morning's visits: Funke's knotless braids done by three braiders, a pedicure still waiting for Zainab, a frontal install with an extra wash waiting for approval, lashes that Precious disputed, and a walk-in for cornrows.
2. Open **New visit**: pick a customer or type a guest name, add "Knotless braids, Medium" and three packs of X-pression hair, assign Chiamaka, and open it. The reference looks like V-261127-006.
3. On a phone, sign in as **Chiamaka**. The new visit is at the top, marked "For you". Open it, tap the service, tap **I did this**. That is three taps.
4. On another phone, sign in as **Funmilayo**, open the same visit and tap **I did this**: she is told it is already recorded and offered **Join**. After she joins, the credit shows Chiamaka 60%, Funmilayo 40% (the service's split).
5. As **Hauwa**, open Tope's visit and use **Ask for an extra service**. Sign in as **Ngozi** (supervisor) and open **Approvals**: approve or reject it. Hauwa sees the decision under the bell.
6. As **Ngozi**, open Ladi's lashes visit from the board: Precious disputed it. Dismiss the dispute, or **Correct** it to Tolani alone. The original stays visible as "Replaced".
7. As **Kemi**, try to remove a service that has been recorded: there is no button, and the API refuses her. Ngozi can remove it with a reason, which cancels the record too.
8. As **Chiamaka**, open **My work** to see what she is credited on and her share. No prices anywhere on the staff screens.

The script for a live rehearsal with real staff is in [staff-walkthrough.md](staff-walkthrough.md).

## What is in it, phase by phase

| Phase | Adds |
|---|---|
| P0 | Staff login accounts. The full price list and calculation scenarios (`app:demo:calc`). |
| P1 | Roles for every account, phone sign-in, the salon's profile and default settings, tax switched off (turn on VAT 7.5% to try it) |
| P2 | The price list in the database (5 service and 2 product categories, variants, products, credit splits) and 150 customers. A few share a phone number, as family members booked on one phone do, and some have no phone |
| P3 (now) | Today's visits in every state: waiting, done by one, done by three, disputed, and an extra service waiting for approval. Added once per day, so running `app:demo` again tomorrow adds a new morning |
| P4 to P5 | About 90 days of visits: Saturday rushes, shared braids, extra services, part payments, cash, POS and transfers |
| P6 | Cashier closings, including a cash shortage, plus unverified and rejected transfers |
| P7 | A month of staff performance and bonus calculations |

## The price list

Prices are plausible Lagos mid-range prices for late 2026. Edit them in **Admin > Catalogue**. Running `app:demo` again puts the demo prices back; `--fresh` resets everything.

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
