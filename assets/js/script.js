// Load color schemes in editor
(function () {
    // Bail if we're not in the block editor, or already initialized
    if (
        typeof wp === 'undefined' ||
        !wp.blocks ||
        !wp.blockEditor ||
        !wp.data ||
        window.__stirEditorInit
    ) {
        return;
    }
    window.__stirEditorInit = true;

    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl } = wp.components;
    const { createElement: el, Fragment } = wp.element;

    const SCHEMES = window.SCHEMES?.schemes || [];

    /* 1. Add scheme attribute to core/column */
    addFilter(
        'blocks.registerBlockType',
        'theme/column-scheme-attribute',
        function (settings, name) {
            if (name !== 'core/column' && name !== 'core/columns') return settings;

            settings.attributes = Object.assign({}, settings.attributes, {
                scheme: {
                    type: 'string',
                    default: '',
                },
            });

            return settings;
        }
    );

    /* 2. Inspector dropdown (only if Column is inside Columns) */
    addFilter(
        'editor.BlockEdit',
        'theme/column-scheme-control',
        createHigherOrderComponent(function (BlockEdit) {
            return function (props) {
                if (
                    props.name !== 'core/column' &&
                    props.name !== 'core/columns'
                ) {
                    return el(BlockEdit, props);
                }

                const { attributes, setAttributes, clientId } = props;

                if (props.name === 'core/column') {
                    const parents = wp.data
                        .select('core/block-editor')
                        .getBlockParents(clientId);

                    const isInColumns = parents.some(function (id) {
                        const block = wp.data
                            .select('core/block-editor')
                            .getBlock(id);
                        return block && block.name === 'core/columns';
                    });

                    if (!isInColumns) {
                        return el(BlockEdit, props);
                    }
                }

                return el(
                    Fragment,
                    {},
                    el(BlockEdit, props),
                    el(
                        InspectorControls,
                        {},
                        el(
                            PanelBody,
                            { title: 'Color scheme', initialOpen: true },
                            el(SelectControl, {
                                label: 'Scheme',
                                value: attributes.scheme,
                                options: SCHEMES,
                                onChange: function (value) {
                                    setAttributes({ scheme: value });
                                },
                            }),
                            attributes.scheme &&
                            el(
                                'div',
                                {
                                    className: 'scheme-preview ' + attributes.scheme,
                                },
                                el('span', null, 'Aa'),
                                el('span')
                            )
                        )
                    )
                );
            };
        })
    );

    /* 3. Persist scheme as class in saved markup */
    addFilter(
        'blocks.getSaveContent.extraProps',
        'theme/column-scheme-class',
        function (extraProps, blockType, attributes) {
            if (
                blockType.name !== 'core/column' &&
                blockType.name !== 'core/columns'
            ) {
                return extraProps;
            }

            if (attributes.scheme) {
                extraProps.className = [
                    extraProps.className,
                    attributes.scheme,
                ]
                    .filter(Boolean)
                    .join(' ');
            }

            return extraProps;
        }
    );
})();

// Add "visible" class to section-container when in view
document.addEventListener('DOMContentLoaded', () => {
    const containers = document.querySelectorAll('.site-main .section-container');

    if (!containers.length) return;

    const observer = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    obs.unobserve(entry.target); // add once, never remove
                }
            });
        },
        {
            threshold: 0.08,
        }
    );

    containers.forEach(el => observer.observe(el));
});

// section--gallery swiper
(function () {
    function toSwiper(gallery) {
        if (gallery.classList.contains('swiper')) return null;

        var slides = Array.prototype.slice.call(gallery.children);
        if (!slides.length) return null;

        var wrapper = document.createElement('div');
        wrapper.className = 'swiper-wrapper';

        gallery.dataset.slideCount = slides.length;

        slides.concat(slides.map(function (slide) {
            var clone = slide.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            return clone;
        })).forEach(function (slide) {
            slide.classList.add('swiper-slide');
            wrapper.appendChild(slide);
        });

        // drop is-cropped — WP forces width/height/cover via that class
        gallery.classList.remove('is-cropped');
        gallery.classList.add('swiper');
        gallery.setAttribute('aria-hidden', 'true');
        gallery.appendChild(wrapper);

        return wrapper;
    }

    // wait for images so Swiper measures real widths
    function whenImagesReady(images, callback) {
        var remaining = images.length;

        if (!remaining) {
            callback();
            return;
        }

        images.forEach(function (img) {
            if (img.complete) {
                if (--remaining === 0) callback();
                return;
            }

            function onSettle() {
                img.removeEventListener('load', onSettle);
                img.removeEventListener('error', onSettle);
                if (--remaining === 0) callback();
            }

            img.addEventListener('load', onSettle);
            img.addEventListener('error', onSettle);
        });
    }

    function createSwiper(gallery) {
        var gap = parseFloat(getComputedStyle(gallery).gap) || 0;

        return new Swiper(gallery, {
            loop: true,
            slidesPerView: 'auto',
            spaceBetween: gap,
            speed: 4000,
            autoplay: {
                delay: 0,
                disableOnInteraction: false,
                pauseOnMouseEnter: false,
            },
            allowTouchMove: false,
            simulateTouch: false,
            grabCursor: false,
            keyboard: { enabled: false },
            navigation: false,
            pagination: false,
        });
    }

    function classifyOrientation(images) {
        images.forEach(function (img) {
            var landscape = img.naturalWidth > img.naturalHeight;
            img.classList.add(landscape ? 'is-landscape' : 'is-portrait');
        });
    }

    function initGallerySwipers() {
        if (typeof Swiper === 'undefined') return;

        document.querySelectorAll('.section--gallery .wp-block-gallery').forEach(function (gallery) {
            var wrapper = toSwiper(gallery);
            if (!wrapper) return;

            var images = Array.prototype.slice.call(wrapper.querySelectorAll('img'));
            whenImagesReady(images, function () {
                classifyOrientation(images);
                createSwiper(gallery);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGallerySwipers);
    } else {
        initGallerySwipers();
    }
})();

// .material-picker click handler
(function () {
    if (window.__sufoMaterialPickerInit) return;
    window.__sufoMaterialPickerInit = true;

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.material-picker');
        if (!button) return;

        var section = button.closest('.section--material');
        if (!section) return;

        var targetImg = section.querySelector('[data-role="material-image"]');
        if (!targetImg) return;

        var src = button.getAttribute('data-image');
        var srcset = button.getAttribute('data-srcset');

        if (src) targetImg.src = src;

        if (srcset) {
            targetImg.srcset = srcset;
        } else {
            targetImg.removeAttribute('srcset');
        }

        var alt = button.getAttribute('data-alt');
        if (alt !== null) targetImg.alt = alt;

        section.querySelectorAll('.material-picker').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn === button ? 'true' : 'false');
        });
    });

    // preload material images
    document.querySelectorAll('.material-picker[data-image]').forEach(function (button) {
        var url = button.getAttribute('data-image');
        if (!url) return;
        var preload = new Image();
        preload.src = url;
    });
})();

// .island hover highlight
(function () {
    function createHighlight() {
        var el = document.createElement('span');
        el.className = 'island__highlight';
        el.setAttribute('aria-hidden', 'true');
        return el;
    }

    function initIsland(island) {
        if (island.dataset.navHighlightInit) return;
        island.dataset.navHighlightInit = 'true';

        var menu = island.querySelector('ul');
        if (!menu) return;

        var highlight = createHighlight();
        menu.insertBefore(highlight, menu.firstChild);

        var activeItem = null; // whichever item the highlight currently sits on
        var sectionItem = null; // the item matching the in-view section, if any
        var isHovering = false;

        // measure once, then batch the writes
        function place(item, animate) {
            var itemRect = item.getBoundingClientRect();
            var menuRect = menu.getBoundingClientRect();
            var radius = getComputedStyle(item).borderRadius;

            var x = itemRect.left - menuRect.left;
            var y = itemRect.top - menuRect.top;

            if (!animate) {
                // skip the position/size transition on first show
                highlight.style.transition = 'opacity var(--animation-fast)';
            }

            highlight.style.width = itemRect.width + 'px';
            highlight.style.height = itemRect.height + 'px';
            highlight.style.borderRadius = radius;
            highlight.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
            highlight.classList.add('is-visible');

            if (!animate) {
                void highlight.offsetWidth;
                highlight.style.transition = '';
            }
        }

        function syncActive() {
            if (activeItem) place(activeItem, true);
        }

        // delegated per island menu
        menu.addEventListener('mouseover', function (event) {
            var item = event.target.closest('li');
            if (!item || !menu.contains(item)) return;
            isHovering = true;
            if (item === activeItem) return;

            var isFirst = !activeItem;
            activeItem = item;
            place(item, !isFirst);
        });

        // on leaving the menu, fall back to the active section's item
        // instead of hiding, so scroll position keeps something highlighted
        menu.addEventListener('mouseleave', function () {
            isHovering = false;
            if (sectionItem) {
                activeItem = sectionItem;
                place(sectionItem, true);
            } else {
                activeItem = null;
                highlight.classList.remove('is-visible');
            }
        });

        // resync on size change
        if ('ResizeObserver' in window) {
            var resizeObserver = new ResizeObserver(syncActive);
            resizeObserver.observe(menu);
            menu.querySelectorAll('li').forEach(function (li) {
                resizeObserver.observe(li);
            });
        }

        // resync after fonts load
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(syncActive);
        }

        // scroll-spy: track which linked section is in view and keep its
        // item highlighted at rest (hover still previews other items)
        var sectionLinks = Array.prototype.slice.call(menu.querySelectorAll('a[href^="#section--"]'));
        var sectionMap = {};
        sectionLinks.forEach(function (a) {
            var id = a.getAttribute('href').slice(1);
            var section = document.getElementById(id);
            if (section) sectionMap[id] = a.closest('li');
        });

        var sectionIds = Object.keys(sectionMap);
        if (sectionIds.length && 'IntersectionObserver' in window) {
            var sectionObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    sectionItem = sectionMap[entry.target.id];
                    if (isHovering || sectionItem === activeItem) return;

                    var isFirst = !activeItem;
                    activeItem = sectionItem;
                    place(sectionItem, !isFirst);
                });
            }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });

            sectionIds.forEach(function (id) {
                sectionObserver.observe(document.getElementById(id));
            });
        }
    }

    function init() {
        document.querySelectorAll('.island').forEach(initIsland);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// sufo_object repeater fields (Tags / Materials / Finishes)
(function () {
    var rowCounters = new WeakMap();

    document.addEventListener('click', function (event) {
        var addBtn = event.target.closest('.sufo-add-row');
        if (addBtn) {
            var repeater = document.querySelector('.sufo-repeater[data-repeater="' + addBtn.dataset.target + '"]');
            var template = repeater.querySelector('.sufo-repeater-template');
            var clone = template.content.firstElementChild.cloneNode(true);

            // give this row's fields a shared index so PHP groups them together
            var n = (rowCounters.get(repeater) || 0) + 1;
            rowCounters.set(repeater, n);
            clone.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace('__index__', 'new-' + n);
            });

            repeater.insertBefore(clone, template);
            return;
        }

        var removeBtn = event.target.closest('.sufo-remove-row');
        if (removeBtn) {
            removeBtn.closest('.sufo-repeater-row').remove();
            return;
        }

        var selectBtn = event.target.closest('.sufo-select-image');
        if (selectBtn) {
            if (typeof wp === 'undefined' || !wp.media) return;

            var col = selectBtn.closest('.sufo-image-col');
            var frame = wp.media({ title: 'Select image', multiple: false });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                var preview = col.querySelector('.sufo-image-preview');
                col.querySelector('input[type="hidden"]').value = attachment.id;
                preview.src = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                preview.style.display = 'block';
            });

            frame.open();
            return;
        }

        var removeImgBtn = event.target.closest('.sufo-remove-image');
        if (removeImgBtn) {
            var imgCol = removeImgBtn.closest('.sufo-image-col');
            var imgColPreview = imgCol.querySelector('.sufo-image-preview');
            imgCol.querySelector('input[type="hidden"]').value = '';
            imgColPreview.removeAttribute('src');
            imgColPreview.style.display = 'none';
        }
    });
})();

// section--faq details: click anywhere on the block to open
(function () {
    document.addEventListener('click', function (event) {
        var details = event.target.closest('.section--faq details');
        if (!details) return;
        if (window.getSelection().toString()) return; // don't fight text selection

        event.preventDefault(); // stop the native instant toggle on summary clicks
        details.open = !details.open;
    });
})();

// .object-picker dropdowns in the fixed bottom object-bar (Material / Finish)
(function () {
    // swap the label text with a small upward slide + fade instead of an instant swap
    function setLabel(label, text) {
        if (!label || label.textContent === text) return;

        label.classList.add('is-fading');
        setTimeout(function () {
            label.textContent = text;
            label.classList.remove('is-fading');
        }, 200); // matches --animation-fast
    }

    // fade the panel in/out; hiding is deferred until the fade-out finishes
    function showPanel(panel) {
        clearTimeout(panel._hideTimeout);
        panel.hidden = false;
        // double rAF: let display:none->flex actually paint before fading in,
        // or the transition has no "before" state to play from
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                panel.classList.add('is-visible');
            });
        });
    }

    function hidePanel(panel) {
        clearTimeout(panel._hideTimeout);
        panel.classList.remove('is-visible');
        panel._hideTimeout = setTimeout(function () {
            panel.hidden = true;
        }, 200); // matches --animation-fast
    }

    function closePicker(picker) {
        var toggle = picker.querySelector('.object-picker__toggle');
        var panel = picker.querySelector('.object-picker__panel');
        var active = picker.querySelector('.object-picker__option[aria-pressed="true"]');

        picker.removeAttribute('data-open');
        toggle.setAttribute('aria-expanded', 'false');
        hidePanel(panel);

        var label = toggle.querySelector('.object-picker__label');
        var activeLabel = active && active.querySelector('.object-picker__option-label');
        if (activeLabel) setLabel(label, activeLabel.textContent);
    }

    function openPicker(picker) {
        document.querySelectorAll('.object-picker[data-open]').forEach(function (other) {
            if (other !== picker) closePicker(other);
        });

        var toggle = picker.querySelector('.object-picker__toggle');
        var panel = picker.querySelector('.object-picker__panel');
        var label = toggle.querySelector('.object-picker__label');

        picker.setAttribute('data-open', 'true');
        toggle.setAttribute('aria-expanded', 'true');
        showPanel(panel);
        setLabel(label, picker.dataset.genericLabel || '');
    }

    function selectOption(picker, option) {
        picker.querySelectorAll('.object-picker__option').forEach(function (o) {
            o.setAttribute('aria-pressed', o === option ? 'true' : 'false');
        });

        updatePrice(picker.closest('.object-bar'));
    }

    function updatePrice(bar) {
        if (!bar) return;

        var total = parseFloat(bar.dataset.basePrice) || 0;
        bar.querySelectorAll('.object-picker').forEach(function (picker) {
            var active = picker.querySelector('.object-picker__option[aria-pressed="true"]');
            if (active) total += parseFloat(active.dataset.price) || 0;
        });

        var priceValue = bar.querySelector('[data-price-value]');
        if (priceValue) priceValue.textContent = '€' + (total % 1 === 0 ? total : total.toFixed(2));
    }

    // lock the label to the widest option's width so the toggle button
    // doesn't resize as the user switches between options
    function matchLabelWidth(picker) {
        var panel = picker.querySelector('.object-picker__panel');
        var wasHidden = panel.hidden;
        if (wasHidden) panel.hidden = false;

        var max = 0;
        picker.querySelectorAll('.object-picker__option-label').forEach(function (label) {
            max = Math.max(max, label.getBoundingClientRect().width);
        });
        picker.style.setProperty('--picker-label-width', max + 'px');

        if (wasHidden) panel.hidden = true;
    }

    function initPicker(picker) {
        if (picker.dataset.pickerInit) return;
        picker.dataset.pickerInit = 'true';

        matchLabelWidth(picker);

        var active = picker.querySelector('.object-picker__option[aria-pressed="true"]');
        var label = picker.querySelector('.object-picker__toggle .dropdown__label');
        var activeLabel = active && active.querySelector('.object-picker__option-label');
        if (label && activeLabel) label.textContent = activeLabel.textContent;
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('.object-picker__toggle');
        if (toggle) {
            var picker = toggle.closest('.object-picker');
            if (picker.hasAttribute('data-open')) {
                closePicker(picker);
            } else {
                openPicker(picker);
            }
            return;
        }

        var option = event.target.closest('.object-picker__option');
        if (option) {
            var optionPicker = option.closest('.object-picker');
            selectOption(optionPicker, option);
            closePicker(optionPicker);
            return;
        }

        document.querySelectorAll('.object-picker[data-open]').forEach(function (openPickerEl) {
            if (!openPickerEl.contains(event.target)) closePicker(openPickerEl);
        });
    });

    function init() {
        document.querySelectorAll('.object-picker').forEach(initPicker);
        document.querySelectorAll('.object-bar').forEach(updatePrice);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// footer newsletter: subscribe stays disabled until the email field is
// filled and the privacy checkbox is checked
(function () {
    function init() {
        var wrap = document.querySelector('.footer__col--newsletter');
        if (!wrap) return;

        var email = wrap.querySelector('.footer__newsletter input[type="email"]');
        var consent = wrap.querySelector('.footer__consent input[type="checkbox"]');
        var button = wrap.querySelector('.mailerlite-subscribe-submit');
        if (!email || !consent || !button) return;

        function sync() {
            var shouldDisable = !(email.value.trim() !== '' && consent.checked);
            
            if (button.disabled !== shouldDisable) {
                button.disabled = shouldDisable;
            }
        }

        email.addEventListener('input', sync);
        consent.addEventListener('change', sync);

        // MailerLite's own script re-enables the button asynchronously
        // (after an nonce fetch) — reassert our condition whenever it does
        new MutationObserver(sync).observe(button, { attributes: true, attributeFilter: ['disabled'] });

        sync();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
