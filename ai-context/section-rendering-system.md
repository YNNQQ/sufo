# Section Rendering System

`render_sections()` converts top-level Gutenberg blocks carrying a `section--<name>` class into the theme's predictable wrapper:

```html
<section class="section section--gallery">
  <div class="section-container scheme-dark-grey">
    <!-- rendered block -->
  </div>
</section>
```

## Pipeline

For every top-level parsed block it:

1. Skips genuinely empty blocks.
2. Detects section behavior from the authored `section--*` class.
3. Removes `section`, `section--*`, and section-level `scheme-*` classes from the inner block before rendering.
4. Builds the synthetic Section/Section Container wrapper and applies schemes from either the authored class list or the custom Gutenberg `scheme` attribute.
5. Runs section-specific processors:
   - `sufo_inject_color_pickers()` fills exact empty-column placeholders in `section--material` from `sufo_colors` and marks the image as `data-role="color-image"`.
   - `sufo_inject_faq_schema()` builds FAQPage JSON-LD before icons are added.
   - `sufo_inject_faq_icons()` appends the inline plus icon to summaries.
6. Drops a section whose generated container is still empty.
7. Renders non-section blocks normally.

## Authored section names

`section--hero`, `section--gallery`, `section--values`, `section--50`, `section--material`, `section--blocks`, `section--personalise`, `section--editorial`, `section--faq`, `section--contact`, and `section--card` have dedicated CSS.

The PHP pipeline recognizes any `section--*` name, so a new purely visual section usually needs only authored markup plus CSS. Add a PHP post-processor only when server data must alter the rendered block.

## Important coupling

The color injection currently matches WordPress-generated HTML strings and exact empty-column markup. Changes to the authored Material block structure must be tested against `sufo_inject_color_pickers()`.
