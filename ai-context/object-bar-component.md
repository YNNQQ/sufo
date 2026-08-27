# Object Bar and Choice Lists

The sticky bottom bar is rendered by `template-parts/object-bar.php`. It combines product configuration, checkout, language navigation, and the small-screen navigation popover.

## Server-rendered choices

`sufo_render_customise_group()` renders each configured field from `sufo_object_fields()` as:

```html
<div class="object-bar__customise-group" data-field-key="colors">
  <span class="object-bar__customise-label">Color</span>
  <div class="choice-list">
    <button class="choice-list__option button" data-index="0" data-price="0" aria-pressed="true">
      <span class="choice-list__swatch icon"></span>
      <span class="choice-list__label">Black</span>
    </button>
    <span class="choice-list__price"></span>
  </div>
</div>
```

`choice-list` is independent of the object bar so the same choice UI can be reused elsewhere. Its elements are `choice-list__option`, `choice-list__label`, `choice-list__price`, and `choice-list__swatch`.

The first row in every configured option array is the default. `data-index` is mirrored into hidden checkout fields; `data-price` is only for the live preview. Checkout always resolves indices against server-side post meta and recalculates the price.

## Shared menu behavior

Both popovers use the generalized `data-menu` controller in `assets/js/script.js`:

- `data-menu="customise"` contains all choice groups.
- `data-menu="navigation"` is the small-screen navigation popover.
- `.menu--popover` opts a menu into the shared backdrop.
- `.bottom-nav__menu` is the navigation-specific component name; it is not part of the page header.
- Only one `[data-menu]` can be open at a time. Open state is represented by `data-open`, panel visibility by `.is-visible`, and `aria-expanded` is synchronized on the toggle.

The menu panel stays in its rendered location; there is no portal or panel-relocation system.

## Live price and conditional sides

`updatePrice()` starts from `data-base-price`, adds the active choice from each group, updates `[data-price-value]`, and mirrors each active index into the checkout form. A finish with `data-hide-sides` hides the Sides group and resets it to index `0`. The server repeats this rule in `sufo_resolve_selection()`.

See [checkout-and-orders.md](checkout-and-orders.md) for the authoritative pricing and payment flow.
