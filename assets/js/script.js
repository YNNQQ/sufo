// Load color schemes in editor
(function () {
    // Bail if we're not in the block editor, or already initialized
    if (
        typeof wp === 'undefined' ||
        !wp.blocks ||
        !wp.blockEditor ||
        !wp.data ||
        window.__sufoEditorInit
    ) {
        return;
    }
    window.__sufoEditorInit = true;

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

// Add "is-visible" class to section-container when in view
document.addEventListener('DOMContentLoaded', () => {
    const containers = document.querySelectorAll('.site-main .section-container');

    if (!containers.length) return;

    const observer = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
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

// Header — on-{scheme} class matching whichever section sits behind it
document.addEventListener('DOMContentLoaded', () => {
    const siteHeader = document.getElementById('site-header');
    if (!siteHeader) return;

    const sections = Array.from(document.querySelectorAll('.site-main .section'));
    if (!sections.length) return;

    const THEME_AHEAD = -60;

    // no scheme-* class -> falls back to the page's default white background
    function schemeOf(section) {
        const container = section.querySelector(':scope > .section-container');
        const match = container && container.className.match(/\bscheme-([\w-]+)\b/);
        return match ? match[1] : 'white';
    }

    let currentClass = '';

    function updateHeaderTheme() {
        const hBottom = siteHeader.getBoundingClientRect().bottom;
        let scheme = 'white';
        for (const section of sections) {
            const { top, bottom } = section.getBoundingClientRect();
            if (top < hBottom + THEME_AHEAD && bottom > 0) {
                scheme = schemeOf(section);
            }
        }

        const nextClass = 'on-' + scheme;
        if (nextClass === currentClass) return;

        if (currentClass) siteHeader.classList.remove(currentClass);
        siteHeader.classList.add(nextClass);
        currentClass = nextClass;
    }

    let ticking = false;
    window.addEventListener('scroll', () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => { updateHeaderTheme(); ticking = false; });
    }, { passive: true });

    updateHeaderTheme();
});

// section--gallery scroll-strip helpers
(function () {
    // wait for images so widths/orientation measure correctly
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

    function classifyOrientation(images) {
        images.forEach(function (img) {
            var landscape = img.naturalWidth > img.naturalHeight;
            img.classList.add(landscape ? 'is-landscape' : 'is-portrait');
        });
    }

    // section--gallery: scroll-driven horizontal strip.
    var LOOPS = 0.4; // lower = slower drift
    var REST_VISIBLE_FRACTION = 0.5; // portion of the first slide's width kept visible

    function buildGalleryScroll(section, gallery) {
        if (gallery.classList.contains('gallery-scroll')) return null;

        var originals = Array.prototype.slice.call(gallery.children).filter(function (el) {
            return el.classList.contains('wp-block-image');
        });
        if (!originals.length) return null;

        gallery.dataset.slideCount = originals.length;

        var strip = document.createElement('div');
        strip.className = 'gallery-strip';

        // drop is-cropped — WP forces width/height/cover via that class
        gallery.classList.remove('is-cropped');
        gallery.classList.add('gallery-scroll');
        gallery.setAttribute('aria-hidden', 'true');
        gallery.appendChild(strip);

        return {
            section: section,
            gallery: gallery,
            strip: strip,
            originals: originals,
            startOffset: 0,
            cycleWidth: 0,
            distance: 0,
            baseProgress: 0
        };
    }

    function layoutGalleryScroll(instance) {
        var strip = instance.strip;
        var originals = instance.originals;

        var gap = parseFloat(getComputedStyle(instance.gallery).gap) || 0;
        var cycleWidth = 0;
        var firstWidth = 0;

        originals.forEach(function (slide, i) {
            var width = slide.getBoundingClientRect().width;
            if (i === 0) firstWidth = width;
            cycleWidth += width + gap;
        });

        if (!cycleWidth) return;

        while (strip.firstChild) strip.removeChild(strip.firstChild);

        var viewportWidth = window.innerWidth;
        var totalCycles = Math.ceil(viewportWidth / cycleWidth) + 2;

        var frag = document.createDocumentFragment();

        originals.forEach(function (slide) { frag.appendChild(slide); });

        for (var i = 1; i < totalCycles; i++) {
            originals.forEach(function (slide) {
                var clone = slide.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.classList.add('gallery-strip-clone');
                frag.appendChild(clone);
            });
        }

        strip.appendChild(frag);

        instance.startOffset = -(firstWidth * (1 - REST_VISIBLE_FRACTION));
        instance.cycleWidth = cycleWidth;
        instance.distance = LOOPS * cycleWidth;
        instance.baseProgress = progressAtScrollTop(instance.section);
    }

    function progressFor(section) {
        var rect = section.getBoundingClientRect();
        var viewportHeight = window.innerHeight;
        var total = viewportHeight + rect.height;
        if (total <= 0) return 0;
        return Math.min(1, Math.max(0, (viewportHeight - rect.top) / total));
    }

    function progressAtScrollTop(section) {
        var rect = section.getBoundingClientRect();
        var absoluteTop = rect.top + window.scrollY;
        var viewportHeight = window.innerHeight;
        var total = viewportHeight + rect.height;
        if (total <= 0) return 0;
        return Math.min(1, Math.max(0, (viewportHeight - absoluteTop) / total));
    }

    function paintGalleryScroll(instance) {
        var progress = progressFor(instance.section);
        var relativeProgress = Math.max(0, progress - instance.baseProgress);
        var travelled = relativeProgress * instance.distance;
        var wrapped = instance.cycleWidth ? travelled % instance.cycleWidth : 0;
        var x = instance.startOffset - wrapped;
        instance.strip.style.transform = 'translate3d(' + x + 'px, 0, 0)';
    }

    function initGalleryScroll() {
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduceMotion) return; // sensible static fallback: leave the plain flex gallery as-is, no scroll binding

        var instances = [];
        var listenersBound = false;

        function bindListeners() {
            if (listenersBound) return;
            listenersBound = true;

            var scrollTicking = false;
            window.addEventListener('scroll', function () {
                if (scrollTicking) return;
                scrollTicking = true;
                requestAnimationFrame(function () {
                    instances.forEach(paintGalleryScroll);
                    scrollTicking = false;
                });
            }, { passive: true });

            var resizeTicking = false;
            window.addEventListener('resize', function () {
                if (resizeTicking) return;
                resizeTicking = true;
                requestAnimationFrame(function () {
                    instances.forEach(function (instance) {
                        layoutGalleryScroll(instance);
                        paintGalleryScroll(instance);
                    });
                    resizeTicking = false;
                });
            });
        }

        document.querySelectorAll('.section--gallery .wp-block-gallery').forEach(function (gallery) {
            var section = gallery.closest('.section--gallery');
            if (!section) return;

            var images = Array.prototype.slice.call(gallery.querySelectorAll('img'));
            whenImagesReady(images, function () {
                classifyOrientation(images);

                var instance = buildGalleryScroll(section, gallery);
                if (!instance) return;

                layoutGalleryScroll(instance);
                paintGalleryScroll(instance);
                instances.push(instance);
                bindListeners();
            });
        });
    }

    function init() {
        initGalleryScroll();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// .color-picker click handler
(function () {
    if (window.__sufoColorPickerInit) return;
    window.__sufoColorPickerInit = true;

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.color-picker');
        if (!button) return;

        var section = button.closest('.section--material');
        if (!section) return;

        var targetImg = section.querySelector('[data-role="color-image"]');
        if (!targetImg) return;

        var figure = targetImg.closest('figure') || targetImg.parentElement;

        var src = button.getAttribute('data-image');
        var srcset = button.getAttribute('data-srcset');
        var alt = button.getAttribute('data-alt');

        // crossfade color images
        var previousOverlay = figure.querySelector('.color-image-overlay');
        if (previousOverlay) {
            clearTimeout(previousOverlay._swapTimeout);
            previousOverlay.remove();
        }

        var overlay = targetImg.cloneNode();
        overlay.classList.add('color-image-overlay');
        overlay.removeAttribute('data-role');
        if (src) overlay.src = src;
        if (srcset) {
            overlay.srcset = srcset;
        } else {
            overlay.removeAttribute('srcset');
        }
        if (alt !== null) overlay.alt = alt;

        figure.appendChild(overlay);
        
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                overlay.classList.add('is-visible');
            });
        });

        overlay._swapTimeout = setTimeout(function () {
            if (src) targetImg.src = src;

            if (srcset) {
                targetImg.srcset = srcset;
            } else {
                targetImg.removeAttribute('srcset');
            }

            if (alt !== null) targetImg.alt = alt;
            overlay.remove();
        }, 200); // matches --animation-fast

        section.querySelectorAll('.color-picker').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn === button ? 'true' : 'false');
        });
    });

    // preload color images
    document.querySelectorAll('.color-picker[data-image]').forEach(function (button) {
        var url = button.getAttribute('data-image');
        if (!url) return;
        var preload = new Image();
        preload.src = url;
    });
})();

// .section--material column scroller: mouse drag-to-scroll
(function () {
    var mq = window.matchMedia('(max-width: 786px)');

    document.querySelectorAll('.section--material .wp-block-columns .wp-block-columns').forEach(function (scroller) {
        var isDown = false;
        var didDrag = false;
        var startX = 0;
        var startScrollLeft = 0;

        scroller.addEventListener('mousedown', function (event) {
            if (!mq.matches) return;
            isDown = true;
            didDrag = false;
            startX = event.pageX;
            startScrollLeft = scroller.scrollLeft;
            scroller.classList.add('is-dragging');
        }, { passive: true });

        window.addEventListener('mousemove', function (event) {
            if (!isDown) return;
            var delta = event.pageX - startX;
            if (Math.abs(delta) > 3) didDrag = true;
            scroller.scrollLeft = startScrollLeft - delta;
        }, { passive: true });

        window.addEventListener('mouseup', function () {
            if (!isDown) return;
            isDown = false;
            scroller.classList.remove('is-dragging');
        }, { passive: true });

        // capture phase, so a real drag swallows the click before it can reach the picker button underneath
        scroller.addEventListener('click', function (event) {
            if (didDrag) {
                event.stopPropagation();
                didDrag = false;
            }
        }, true);
    });
})();

// nav highlight (opt-in via .nav-highlight)
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

        var menu = island.querySelector(':scope > ul');
        if (!menu) return;

        var highlight = createHighlight();
        menu.insertBefore(highlight, menu.firstChild);

        var activeItem = null; // whichever item the highlight currently sits on
        var sectionItem = null; // the item matching the in-view section, if any
        var isHovering = false;

        // below 1200px the menu becomes a horizontally-scrollable strip
        function updateEdgeFades(targetScroll) {
            var maxScroll = menu.scrollWidth - menu.clientWidth;
            var pos = typeof targetScroll === 'number' ? targetScroll : menu.scrollLeft;
            island.classList.toggle('can-scroll-left', pos > 1);
            island.classList.toggle('can-scroll-right', pos < maxScroll - 1);
        }

        menu.addEventListener('scroll', function () {
            updateEdgeFades();
        });
        updateEdgeFades();

        function logicalX(itemRect, menuRect) {
            return itemRect.left - menuRect.left + menu.scrollLeft;
        }

        // move/size the highlight onto item
        function positionHighlight(item, animate) {
            var itemRect = item.getBoundingClientRect();
            var menuRect = menu.getBoundingClientRect();
            var radius = getComputedStyle(item).borderRadius;

            var x = logicalX(itemRect, menuRect);
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

        // scroll so item is centred in the visible window
        function place(item, animate) {
            var itemRect = item.getBoundingClientRect();
            var menuRect = menu.getBoundingClientRect();
            var radius = getComputedStyle(item).borderRadius;

            var x = logicalX(itemRect, menuRect);
            var y = itemRect.top - menuRect.top;

            var maxScroll = menu.scrollWidth - menu.clientWidth;
            if (maxScroll > 0) {
                var itemCenter = x + itemRect.width / 2;
                var targetScroll = Math.max(0, Math.min(itemCenter - menu.clientWidth / 2, maxScroll));
                menu.scrollTo({ left: targetScroll, behavior: animate ? 'smooth' : 'auto' });
                updateEdgeFades(targetScroll);
            }

            if (!animate) {
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
            if (activeItem) {
                place(activeItem, false);
                return;
            }

            var maxScroll = menu.scrollWidth - menu.clientWidth;
            if (menu.scrollLeft > maxScroll) {
                menu.scrollTo({ left: Math.max(0, maxScroll), behavior: 'auto' });
            }
            updateEdgeFades();
        }

        var leaveTimeout = null;

        function onPreviewStart(event) {
            var item = event.target.closest('li');
            if (!item || !menu.contains(item)) return;
            isHovering = true;
            clearTimeout(leaveTimeout);
            if (item === activeItem) return;

            var isFirst = !activeItem;
            activeItem = item;
            positionHighlight(item, !isFirst);
        }
        menu.addEventListener('mouseover', onPreviewStart);
        menu.addEventListener('focusin', onPreviewStart);

        // delay before falling back to the active section's item
        function onPreviewEnd() {
            isHovering = false;
            clearTimeout(leaveTimeout);
            leaveTimeout = setTimeout(function () {
                if (sectionItem) {
                    activeItem = sectionItem;
                    place(sectionItem, true);
                } else {
                    activeItem = null;
                    highlight.classList.remove('is-visible');
                }
            }, 300);
        }
        menu.addEventListener('mouseleave', onPreviewEnd);
        menu.addEventListener('focusout', onPreviewEnd);

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

        var currentItem = menu.querySelector('li.is-current');
        if (currentItem) {
            sectionItem = currentItem;
            activeItem = currentItem;
            place(currentItem, false);
        }

        // scroll-spy: track which linked section is in view and keep its
        // item highlighted at rest (hover still previews other items).
        var sectionLinks = Array.prototype.slice.call(menu.querySelectorAll('a[href^="#"]'));
        var sectionMap = new Map();
        sectionLinks.forEach(function (a) {
            var slug = a.getAttribute('href').slice(1);
            if (!slug) return;
            var section = document.querySelector('.section--' + slug);
            if (section) sectionMap.set(section, a.closest('li'));
        });

        if (sectionMap.size && 'IntersectionObserver' in window) {
            var sectionObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var newSectionItem = sectionMap.get(entry.target);
                    if (newSectionItem === sectionItem) return;

                    if (sectionItem) sectionItem.classList.remove('is-current');
                    sectionItem = newSectionItem;
                    sectionItem.classList.add('is-current');

                    if (isHovering || sectionItem === activeItem) return;

                    var isFirst = !activeItem;
                    activeItem = sectionItem;
                    place(sectionItem, !isFirst);
                });
            }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });

            sectionMap.forEach(function (item, section) {
                sectionObserver.observe(section);
            });
        }
    }

    function init() {
        document.querySelectorAll('.nav-highlight').forEach(initIsland);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// sufo_object repeater fields (Tags / Colors / Finishes)
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
        if (event.target.closest('a, button, input, select, textarea, label')) return;

        // Stop the native instant toggle only when the click came from summary;
        // clicks elsewhere in the card are custom toggles with no default action.
        if (event.target.closest('summary')) event.preventDefault();
        details.open = !details.open;
    });
})();

// Popover menus sharing one open/close mechanism: the Customise choices and navigation menu.
(function () {
    function menuToggle(menu) {
        return menu.querySelector('.menu__toggle');
    }

    function menuPanel(menu) {
        return menu.querySelector('.menu__panel');
    }

    function activeOption(panel) {
        return panel.querySelector('.choice-list__option[aria-pressed="true"]');
    }

    // fade the panel in/out; hiding is deferred until the fade-out finishes
    function showPanel(panel) {
        clearTimeout(panel._hideTimeout);
        panel.hidden = false;

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

    function setBackdropVisible(visible) {
        var backdrop = document.querySelector('[data-menu-backdrop]');
        if (backdrop) backdrop.classList.toggle('is-visible', visible);
    }

    function closeMenu(menu) {
        var toggle = menuToggle(menu);
        var panel = menu._panel;

        menu.removeAttribute('data-open');
        toggle.setAttribute('aria-expanded', 'false');
        hidePanel(panel);

        if (menu.classList.contains('menu--popover')) setBackdropVisible(false);
    }

    function openMenu(menu) {
        document.querySelectorAll('[data-menu][data-open]').forEach(function (other) {
            if (other !== menu) closeMenu(other);
        });

        var toggle = menuToggle(menu);
        var panel = menu._panel;

        menu.setAttribute('data-open', 'true');
        toggle.setAttribute('aria-expanded', 'true');

        if (menu.classList.contains('menu--popover')) setBackdropVisible(true);

        showPanel(panel);
    }

    // group is a .object-bar__customise-group; there is no per-group toggle label to sync
    function selectOption(group, option) {
        group.querySelectorAll('.choice-list__option').forEach(function (o) {
            o.setAttribute('aria-pressed', o === option ? 'true' : 'false');
        });

        updatePrice(group.closest('.object-bar'));
    }

    function syncSidesLock(bar) {
        var finishGroup = bar.querySelector('.object-bar__customise-group[data-field-key="finishes"]');
        var sidesGroup = bar.querySelector('.object-bar__customise-group[data-field-key="sides"]');
        if (!finishGroup || !sidesGroup) return;

        var activeFinish = activeOption(finishGroup);
        var hide = !!(activeFinish && activeFinish.hasAttribute('data-hide-sides'));
        sidesGroup.style.display = hide ? 'none' : '';

        if (hide) {
            var active = activeOption(sidesGroup);
            if (active && active.dataset.index !== '0') {
                sidesGroup.querySelectorAll('.choice-list__option').forEach(function (o) {
                    o.setAttribute('aria-pressed', o.dataset.index === '0' ? 'true' : 'false');
                });
            }
        }
    }

    function formatPrice(amount) {
        return amount % 1 === 0 ? String(amount) : amount.toFixed(2);
    }

    function updatePrice(bar) {
        if (!bar) return;

        syncSidesLock(bar);

        var form = bar.querySelector('[data-checkout-form]');
        var total = parseFloat(bar.dataset.basePrice) || 0;

        bar.querySelectorAll('.object-bar__customise-group').forEach(function (group) {
            var active = activeOption(group);
            if (!active) return;

            total += parseFloat(active.dataset.price) || 0;

            // mirror the selection into the checkout form; the server prices it by index
            var field = form && form.querySelector('[data-checkout-option="' + group.dataset.fieldKey + '"]');
            if (field) field.value = active.dataset.index || '0';
        });

        var priceValue = bar.querySelector('[data-price-value]');
        if (priceValue) priceValue.textContent = '€' + formatPrice(total);
    }

    function initMenu(menu) {
        if (menu.dataset.menuInit) return;

        var panel = menuPanel(menu);
        if (!panel || !menuToggle(menu)) return;

        menu.dataset.menuInit = 'true';
        menu._panel = panel;
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('.menu__toggle');
        if (toggle) {
            var menu = toggle.closest('[data-menu]');
            if (!menu) return;
            menu.hasAttribute('data-open') ? closeMenu(menu) : openMenu(menu);
            return;
        }

        var option = event.target.closest('.choice-list__option');
        if (option) {
            // every option lives inside a Customise group; picking one never closes the
            // panel, so the user can keep adjusting the other groups
            var group = option.closest('.object-bar__customise-group');
            if (group) selectOption(group, option);
            return;
        }

        document.querySelectorAll('[data-menu][data-open]').forEach(function (openMenuEl) {
            var panel = openMenuEl._panel;
            var clickedInside = openMenuEl.contains(event.target) || (panel && panel.contains(event.target));
            if (!clickedInside) closeMenu(openMenuEl);
        });
    });

    // close any open panel on scroll
    window.addEventListener('scroll', function () {
        document.querySelectorAll('[data-menu][data-open]').forEach(closeMenu);
    }, { passive: true });

    // crossing back above a menu's breakpoint would otherwise strand it open with its toggle hidden
    function closeIfStranded(mq, dataValue) {
        mq.addEventListener('change', function (event) {
            if (event.matches) return;
            var menu = document.querySelector('[data-menu="' + dataValue + '"]');
            if (menu && menu.hasAttribute('data-open')) closeMenu(menu);
        });
    }

    closeIfStranded(window.matchMedia('(max-width: 781px)'), 'navigation');

    function init() {
        document.querySelectorAll('[data-menu]').forEach(initMenu);
        document.querySelectorAll('.object-bar').forEach(updatePrice);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// footer newsletter: subscribe stays disabled until the email field is filled and the privacy checkbox is checked
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


// ============================================================
// CHECKOUT NOTICE
// ============================================================
(function () {
    function init() {
        var notice = document.querySelector('.checkout-notice');
        if (!notice) return;

        var backdrop = document.querySelector('[data-menu-backdrop]');
        if (backdrop) backdrop.classList.add('is-visible');

        function dismiss() {
            notice.hidden = true;
            if (backdrop) backdrop.classList.remove('is-visible');
            document.removeEventListener('click', dismiss);
            // drop ?checkout= so a refresh doesn't resurrect the notice
            if (window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.delete('checkout');
                url.searchParams.delete('session_id');
                window.history.replaceState({}, '', url);
            }
        }

        // deferred so the click that loaded the page can't immediately dismiss it
        setTimeout(function () {
            document.addEventListener('click', dismiss);
        }, 0);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

document.addEventListener('click', function (event) {
    var trigger = event.target.closest('.js-open-customise');
    if (!trigger) return;
    event.preventDefault();

    var menu = document.querySelector('[data-menu="customise"]');
    var toggle = menu && menu.querySelector('.menu__toggle');
    if (toggle && !menu.hasAttribute('data-open')) toggle.click();
});
