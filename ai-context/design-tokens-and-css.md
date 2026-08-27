# Design Tokens and CSS

`style.css` contains theme metadata, typography/spacing/effect tokens, reset/base rules, layout, navigation, buttons, components, section styles, and responsive overrides. `assets/css/colors.css` contains color primitives, scheme mappings, and scheme-aware button tokens.

## Component vocabulary

- `.island`: glass-like floating surface used for navigation, popovers, notices, and the object bar.
- `.island__highlight`: animated navigation-only selection pill created by the `.nav-highlight` JavaScript.
- `.card` with `.card--padded`: reusable card surface and same-element padding modifier.
- `.button`, `.button--icon`, `.btn--highlight`: button base, icon direction modifier, and emphasized action treatment.
- `.menu`, `.menu--popover`, `.menu__toggle`, `.menu__panel`, `.menu__flyout`: generalized open/close component controlled through `[data-menu]`.
- `.bottom-nav__menu`: navigation-specific popover inside the bottom navigation.
- `.choice-list`: generalized choice container with `__option`, `__label`, `__price`, and `__swatch` elements.
- `.object-bar__customise-group`: product-domain wrapper combining a group label with a reusable choice list.
- `.is-visible`: shared visibility state for reveals, panels, overlays, backdrops, and notices.

## Schemes

Scheme classes assign semantic variables such as `--scheme-bg`, `--scheme-text`, and `--scheme-accent`. Components should consume semantic scheme/button variables rather than adding scheme-specific variants.

## Responsive structure

- At 1200px, the primary navigation becomes a horizontally scrollable strip with edge fades and the Material choices reflow.
- At 781px, root typography/padding tokens change, the primary header navigation is hidden, the bottom navigation becomes a flex container, `.bottom-nav__menu` appears, and section-specific mobile layouts apply.
- At 380px, the first secondary-navigation item is hidden.

When changing a component, search both its desktop definition and responsive overrides. State names and behavior hooks must also be kept synchronized with `assets/js/script.js`.
