# AutoParts Direct SA — Chat-Commerce MVP

> **Working project name:** AutoParts Direct SA  
> **Product:** A chat-first online store for new and second-hand automotive exterior parts in South Africa.  
> **Launch approach:** Sweat-capital first. Start with a small, manually verified catalogue and automate only after real orders prove what is needed.

---

## 1. The idea

Build an online store where customers can find and order car parts by answering simple questions in a chat-style interface.

Instead of forcing customers to understand complicated menus, filters, and automotive terminology, the site guides them:

1. “What vehicle do you have?”
2. “Which model and year?”
3. “Which part do you need?”
4. “Is it the left or right side?”
5. “Would you like to see available options?”

The customer sees matching products as simple cards with photographs, prices, condition, defects, and delivery information. They can ask questions or continue to checkout.

**The chat is the shopping interface, not a promise that AI can identify every part perfectly.** For the first release, use guided questions and a manually maintained catalogue. Add AI assistance later, with human confirmation for uncertain fitment.

## 2. The value proposition

**Find the right car part without driving from shop to shop.** Tell us what vehicle you have and what part you need. View real product photos, see the condition and price clearly, and arrange delivery anywhere we can service.

The business aims to offer:
- Easier part discovery for people who find traditional online stores confusing.
- Clear photographs and honest condition descriptions.
- Vehicle and part compatibility information.
- A single place to browse products from participating suppliers.
- Online ordering and delivery options.
- Simple support when a customer is unsure.

## 3. Who the site serves

Initial customer groups:
- Private vehicle owners replacing damaged parts.
- Panel beaters and independent repair shops.
- Mechanics sourcing replacement components.
- Used-car dealers and automotive traders.

Start with a narrow catalogue: **headlights and taillights**, initially for a small set of popular vehicle models. Expand into mirrors, bumpers, doors, fenders, and other exterior parts only when supplier availability and fulfilment are reliable.

## 4. MVP principles

Keep the first version deliberately simple.

- Mobile-first: most visitors are likely to arrive from TikTok or a phone.
- Chat-first: one clear question at a time.
- Large buttons and plain language.
- Minimal typing; offer selectable answers wherever possible.
- Show prices in South African rand (ZAR).
- Show actual product photographs, not generic images presented as the exact item.
- Clearly label used, refurbished, aftermarket, and new parts.
- Disclose every known defect.
- Never claim a part fits a vehicle unless compatibility has been checked.
- Keep a human support route available.
- Do not build a complex multi-vendor seller portal before the first orders.

## 5. Customer journey

### Step 1 — Landing page

Headline: **“Find the car part you need.”**

Supporting text: “Answer a few quick questions and see available parts.”

Primary button: **Find a part**

Secondary links:
- Browse parts
- How it works
- Delivery and returns
- Contact us

### Step 2 — Guided chat

Ask one question per screen/message.

Suggested conversation:

**Store:** Hi! Let's find the right part. What vehicle do you have?

Buttons:
- Volkswagen
- Toyota
- Ford
- Hyundai
- Other

Then ask:
- Model (e.g. Polo Vivo, Golf, Corolla)
- Year
- Part category (headlight, taillight, mirror, bumper, other)
- Side (left, right, both, not sure), when relevant
- Optional: registration/VIN or part number, if the customer is comfortable sharing it

Provide **“I’m not sure”** and **“Talk to a person”** options.

Do not ask for unnecessary personal information at this stage.

### Step 3 — Matching products

Display product cards containing:
- Main product photograph.
- Product title.
- Vehicle compatibility (including any limitations).
- Left/right side, where relevant.
- Condition grade.
- Known defects.
- Price.
- Stock confirmation status and timestamp.
- Delivery estimate only when it has been confirmed for the destination and service.
- **View details** and **Ask about this part** actions.

If there is no confirmed match, do not invent one. Offer to send the request to support for a manual check.

### Step 4 — Product details

Each product page/card detail should include:
- Multiple real photos where possible.
- Supplier's internal stock reference.
- OEM/part number if known.
- Vehicle make, model, year range and variant compatibility.
- Condition and a plain-language description.
- Every known crack, broken mounting tab, scratch, missing connector, moisture issue, or other defect.
- Whether bulbs, ballasts, brackets, or other components are included.
- Return/warranty terms applicable to that item.
- Price and delivery information.

Use a clear notice: **“Please confirm compatibility before ordering if you are unsure.”**

### Step 5 — Checkout

Collect only what is needed:
- Customer name.
- Mobile number.
- Email (optional if the payment/fulfilment process allows).
- Delivery address or supported collection/locker option.
- Order summary.
- Product price.
- Delivery charge.
- Total payable.
- Acceptance of the terms and relevant returns policy.

Do not show a delivery price as final until the courier quotation is available. If a quote cannot be obtained automatically, mark it as **“Delivery quote to be confirmed before payment”** rather than guessing.

### Step 6 — Payment and order confirmation

Use a South African payment provider after merchant onboarding and testing. The payment provider must confirm successful payment server-side; never trust only a browser success message.

After verified payment:
1. Create the order.
2. Reserve or re-confirm the product with the supplier.
3. Send the supplier the order details.
4. Arrange collection/drop-off with the courier.
5. Send the customer an order confirmation and updates.

**Important:** Agree with each supplier in writing when they are paid. Some may require payment before dispatch; others may agree to settlement after delivery. Do not promise a supplier payment schedule until that supplier has accepted it. Keep enough cash flow to meet the agreed terms and handle refunds or failed deliveries.

### Step 7 — Delivery and completion

Show order states:
- Awaiting payment
- Payment confirmed
- Stock reconfirmation
- Supplier preparing order
- Ready for courier
- Collected by courier
- In transit
- Delivered
- Completed
- Cancelled / refund in progress

Send tracking information when available. Confirm delivery before marking the order complete.

---

## 6. Admin dashboard

The owner needs one simple private dashboard. The MVP dashboard should support:

### Products
- Add/edit/archive a product.
- Upload photographs.
- Set price and supplier cost.
- Set condition and disclose defects.
- Enter compatibility and part number.
- Mark available, reserved, sold, or unavailable.
- Record last stock confirmation time.

### Suppliers
- Supplier name and contact details.
- Collection address and operating hours.
- Products supplied.
- Agreed cost and payment terms.
- Dispatch expectations.
- Reliability notes and order history.

### Orders
- View customer and order details.
- Confirm stock with supplier.
- Record supplier confirmation.
- Record supplier payment status.
- Record courier and tracking details.
- Update order status.
- Record cancellations, returns, refunds, and reasons.

### Basic reporting
- Orders per day/week/month.
- Sales value.
- Product gross margin.
- Orders by product category and vehicle.
- Supplier cancellation rate.
- Delivery delays and returns.

Keep the dashboard functional, not fancy.

## 7. Supplier operating model

The business starts as a centrally managed store. Customers deal with the store; suppliers do not need to manage customer chats.

Start with one to three reliable suppliers and a small verified catalogue.

Before listing a supplier's products:
1. Obtain permission to use their photographs and product information.
2. Agree on the selling price, supplier price, and your margin.
3. Agree how long stock can be considered available before reconfirmation.
4. Agree who checks the part and packs it.
5. Agree collection arrangements and dispatch deadlines.
6. Agree what happens if the item is sold elsewhere, damaged, incorrect, or returned.
7. Agree when the supplier gets paid.
8. Keep a written record of the agreement.

**Stock accuracy rule:** Second-hand stock can sell quickly. Reconfirm availability before accepting or finalising an order. For products not synchronised with supplier stock, show “Availability must be confirmed” until checked.

A simple supplier scorecard can track:
- Stock accuracy.
- Dispatch speed.
- Correct item supplied.
- Accuracy of defect descriptions.
- Cancellation rate.
- Return rate.

Do not publish private supplier contact details unless they have agreed.

## 8. Revenue model

Start with one clear method:

**Retail markup:** negotiate a supplier price and list the item at a higher customer price. The difference is the gross product margin.

Alternative:
- **Commission:** supplier receives an agreed amount and the store retains an agreed fee.

Do not count delivery money as profit if it is passed to the courier. Track payment fees, refunds, returns, packaging, and any other transaction costs separately.

Example only:

| Item | Amount |
|---|---:|
| Customer product price | R1,200 |
| Supplier cost | R900 |
| Gross product margin | R300 |
| Payment fee and other costs | Deduct actual amounts |
| Net contribution | Calculate per order |

Do not assume every product can support the same margin. Compare competitor prices and negotiate case by case.

## 9. Delivery

Potential delivery options to investigate:
- The Courier Guy.
- Bob Go and its supported courier services/collection options.
- Other couriers that can safely handle the product dimensions and destination.

Before integrating an API:
1. Confirm the provider supports your business account and parcel type.
2. Check collection, tracking, claims, and insurance terms.
3. Confirm pricing for the actual parcel dimensions and weight.
4. Test the full flow with a real low-value shipment.
5. Confirm whether lockers accept the size and shape of the product.

Headlights may be easier to ship than bumpers or doors, but packaging is critical. Use protective packaging and photograph the item before dispatch. Do not promise a fixed two-to-four-day delivery window unless the courier confirms that service for the route.

For large body panels, obtain a separate bulky-item delivery quote. Do not assume standard locker delivery will work.

## 10. Suggested technology

Choose familiar, low-cost tools and keep the application maintainable.

- **Frontend:** Next.js with TypeScript.
- **Styling:** Tailwind CSS.
- **Database and authentication:** Supabase (PostgreSQL and Auth).
- **Product images:** Supabase Storage or another compatible object store.
- **Hosting:** Vercel or another platform that supports the chosen Next.js deployment.
- **Payments:** A South African gateway such as PayFast or Yoco, subject to account approval and current fees.
- **Courier:** Start with manual booking; add The Courier Guy or Bob Go integration after the order process is proven.
- **Email/SMS:** Start with email or manual WhatsApp updates; automate later if volume justifies it.

Do not build your own payment-card storage. Use the payment provider's hosted or tokenised checkout.

### Suggested project structure

```text
autoparts-direct/
├── app/
│   ├── page.tsx
│   ├── chat/
│   │   └── page.tsx
│   ├── products/
│   │   └── [slug]/
│   │       └── page.tsx
│   ├── checkout/
│   │   └── page.tsx
│   ├── order/
│   │   └── [reference]/
│   │       └── page.tsx
│   ├── admin/
│   │   ├── page.tsx
│   │   ├── products/
│   │   ├── suppliers/
│   │   └── orders/
│   └── api/
│       ├── checkout/
│       ├── payment-webhook/
│       └── courier/
├── components/
│   ├── chat/
│   ├── products/
│   ├── checkout/
│   └── ui/
├── lib/
│   ├── database/
│   ├── payments/
│   ├── shipping/
│   └── validation/
├── types/
├── public/
└── README.md
```

This is a suggested structure, not a requirement to create every folder on day one.

## 11. Initial database model

Create these tables in PostgreSQL:

### `products`
- `id`
- `title`
- `slug`
- `category`
- `make`
- `model`
- `year_from`
- `year_to`
- `variant_notes`
- `side`
- `part_number`
- `condition`
- `defects`
- `description`
- `price`
- `supplier_cost`
- `supplier_id`
- `stock_status`
- `last_stock_confirmed_at`
- `created_at`
- `updated_at`

### `product_images`
- `id`
- `product_id`
- `image_url`
- `sort_order`
- `is_primary`

### `suppliers`
- `id`
- `business_name`
- `contact_name`
- `phone`
- `email`
- `collection_address`
- `payment_terms`
- `dispatch_terms`
- `active`
- `internal_notes`

### `orders`
- `id`
- `order_reference`
- `customer_name`
- `customer_phone`
- `customer_email`
- `delivery_address`
- `delivery_method`
- `product_total`
- `delivery_total`
- `grand_total`
- `payment_status`
- `order_status`
- `tracking_reference`
- `created_at`

### `order_items`
- `id`
- `order_id`
- `product_id`
- `supplier_id`
- `product_title_snapshot`
- `unit_price`
- `supplier_cost_snapshot`
- `quantity`

### `supplier_payments`
- `id`
- `order_id`
- `supplier_id`
- `amount_due`
- `payment_status`
- `paid_at`
- `payment_reference`

### `chat_sessions`
- `id`
- `session_token`
- `vehicle_make`
- `vehicle_model`
- `vehicle_year`
- `requested_category`
- `requested_side`
- `created_at`

Store order item snapshots so a later product price or title change does not rewrite historical orders.

Protect customer data. Restrict admin access, validate all inputs, and never expose supplier costs or private customer details to the public.

## 12. Chat logic: start guided, add AI later

The first chat does not need an LLM.

Use a simple state machine:
1. Ask make.
2. Ask model.
3. Ask year.
4. Ask category.
5. Ask side if applicable.
6. Search the catalogue using those answers.
7. Show matching products.
8. If no reliable match exists, collect a contact request for manual assistance.

The chat should:
- Allow users to go back and change an answer.
- Offer “Not sure” and “Talk to a person”.
- Preserve answers while the customer browses.
- Avoid long paragraphs.
- Use images/icons alongside clear labels where helpful.
- Work well on a small Android phone and on slow connections.

### AI phase (later)

AI may help interpret messages such as:
- “My Polo's left light is broken.”
- “I need a right headlight for a 2015 Vivo.”
- “Do you have a Golf 7 rear light?”

AI should translate the message into structured search fields, then search the real product database. It must not invent products, prices, stock, compatibility, or delivery estimates. If uncertain, ask a follow-up question or hand over to a person.

Photo-based part identification can be explored later, but a photo alone should not be treated as proof of fitment.

## 13. SEO and TikTok content

Each real product should have a shareable page with a descriptive title, for example:

`Used VW Polo Vivo right headlight — condition and price`

Create useful category and vehicle pages only when there are genuine products and useful information behind them. Avoid creating hundreds of empty or near-duplicate SEO pages.

TikTok content ideas:
- Show the actual part from several angles.
- Explain the defects honestly.
- Show how to identify left versus right.
- Explain what information is needed to find the correct part.
- Demonstrate the chat shopping process.
- Answer common questions about used headlights.

A call to action should be simple: **“Need this part? Visit the website and answer a few quick questions.”**

Track:
- Video views.
- Profile visits.
- Website visits.
- Chat starts.
- Product enquiries.
- Checkout starts.
- Paid orders.

Views are useful, but paid orders and fulfilled orders prove the business.

## 14. Legal and customer-trust checklist

Before taking orders, prepare:
- Terms and conditions.
- Privacy policy.
- Delivery policy.
- Returns, refunds, and cancellation policy.
- Clear second-hand product condition and defect disclosures.
- Supplier permission to use product photos and descriptions.
- Business contact and complaints process.

Review applicable South African consumer and e-commerce requirements, including the Consumer Protection Act and Electronic Communications and Transactions Act. Do not assume that describing an item as second-hand removes all consumer rights. Get appropriate legal advice for the final policies.

## 15. Build plan

### Phase 1 — Validate before coding
- Speak to a few suppliers.
- Obtain permission to list products.
- Agree on supplier pricing and payment terms.
- Collect 20–30 real products with photos and defect details.
- Confirm how collection and delivery will work.

### Phase 2 — Build the MVP
- Landing page.
- Guided chat.
- Product results and details.
- Manual stock confirmation.
- Simple checkout/payment integration.
- Order confirmation.
- Private admin dashboard.

### Phase 3 — First real orders
- Test with friends or a small group, then real customers.
- Process each order manually behind the scenes.
- Record every delay, cancellation, wrong-part issue, and customer question.
- Improve the flow based on what actually happens.

### Phase 4 — Automate proven tasks
Only after the manual process works:
- Courier quotes and labels.
- Tracking updates.
- Supplier stock imports.
- Supplier performance reports.
- AI-assisted chat.
- Supplier self-service portal.

## 16. First launch target

A useful first validation target:

- 1–3 dependable suppliers.
- 20–30 accurate product listings.
- One focused category: headlights and taillights.
- A working mobile chat flow.
- A tested payment process.
- A tested delivery process.
- 10 successfully fulfilled paid orders.

This is a learning target, not a forecast or guarantee.

## 17. Definition of done for version 1

Version 1 is ready when a customer can:

- Open the site on a phone.
- Answer simple questions about their vehicle and required part.
- See genuine matching products.
- Read the price, condition, defects, and compatibility notes.
- Ask for human help if unsure.
- See the delivery charge or be told clearly that a quote is pending.
- Pay securely.
- Receive an order reference and updates.

And the owner can:

- Add and update products.
- Confirm stock with suppliers.
- See paid orders.
- Contact suppliers.
- Record supplier payments.
- Arrange delivery and add tracking.
- Resolve cancellations and returns.

**Do not add features just because large marketplaces have them. Build only what helps a customer find the correct part and helps you fulfil the order reliably.**

---

## Working business rule

**Never take a customer's money for a part you cannot identify and fulfil with reasonable confidence.** If stock, compatibility, delivery cost, or condition is uncertain, confirm it first and communicate clearly.

The first objective is not to build the biggest automotive marketplace. It is to create a simple, trusted buying experience and fulfil the first ten orders successfully.
