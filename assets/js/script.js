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

        var activeItem = null;

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
            if (item === activeItem) return;

            var isFirst = !activeItem;
            activeItem = item;
            place(item, !isFirst);
        });

        // hide when leaving the whole menu
        menu.addEventListener('mouseleave', function () {
            activeItem = null;
            highlight.classList.remove('is-visible');
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
