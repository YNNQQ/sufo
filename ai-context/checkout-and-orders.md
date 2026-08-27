# Checkout and Orders

Checkout is implemented in `functions.php` and submitted through WordPress's `admin-post.php` endpoint. Prices are always reconstructed from server-side object metadata; browser-supplied prices are ignored.

## Configuration

Production requires these constants:

```php
define('STRIPE_RESTRICTED_KEY', 'rk_...');
define('STRIPE_WEBHOOK_SECRET', 'whsec_...');
```

`STRIPE_TAX_RATE_ID` is optional. When present, Stripe receives the net amount plus the configured tax rate. Without it, the theme sends the VAT-inclusive amount directly.

Configure the Stripe webhook destination as:

```text
https://<site>/wp-json/sufo/v1/stripe-webhook
```

Subscribe it to:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `checkout.session.expired`

## Checkout flow

1. `sufo_start_checkout()` verifies the nonce, published object, availability, and Stripe configuration.
2. `sufo_resolve_selection()` resolves submitted option indices against saved Color, Finish, Sides, and Delivery rows. Unknown indices fall back to the first row. A finish marked `hide_sides` forces Sides to index `0`.
3. VAT and line-item data are calculated server-side and a Checkout Session is created through `sufo_stripe_request()`.
4. A local `sufo_order` record stores the session ID, selected choices, totals, and an initial `pending_payment` status.
5. Stripe redirects to the hosted checkout URL.

## Authoritative payment synchronization

The REST webhook verifies Stripe's `Stripe-Signature` header with HMAC-SHA256 and `STRIPE_WEBHOOK_SECRET`. Its timestamp tolerance defaults to 300 seconds and is filterable through `sufo_stripe_webhook_tolerance`.

Successful session events apply customer details, address, amount, and payment state to the order. Failed or expired sessions move an order from `pending_payment` to `cancelled`. Statuses changed manually after the pending state are never overwritten.

Processed Stripe event IDs are stored as repeated `_sufo_order_stripe_event` metadata, making webhook retries idempotent. If an event arrives before the local order is saved, the endpoint returns HTTP 409 so Stripe retries it.

The success-page sync remains as a fast fallback, and the admin order screen also provides a manual “Sync from Stripe” action. Neither is required for normal payment-state updates once the webhook is configured.

## Product schema price-range follow-up

The current Product JSON-LD range is simpler than checkout pricing: it treats the base as the minimum and adds only each group's maximum delta. A future `sufo_get_legal_price_range()` helper should enumerate valid option-index combinations through the same Finish/Sides rule as `sufo_resolve_selection()`, then return the true minimum, maximum, and legal configuration count for `AggregateOffer`. This correctly handles negative deltas and finishes that force Sides to index `0` without maintaining a second pricing formula.

## Order statuses

`pending_payment`, `paid`, `in_production`, `completed`, and `cancelled` are stored in `_sufo_order_status`. The status column is display-only; it is not registered as sortable.
