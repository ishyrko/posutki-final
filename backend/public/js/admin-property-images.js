(function () {
    function escapeSelector(value) {
        if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
            return CSS.escape(value);
        }

        return value.replace(/["\\]/g, '\\$&');
    }

    function findImagesInputs() {
        return Array.from(document.querySelectorAll('input[name^="Property[images]"]'));
    }

    function updateImageUrl(oldUrl, newUrl, thumbnailUrl) {
        findImagesInputs().forEach((input) => {
            if (input.value === oldUrl) {
                input.value = newUrl;
            }
        });

        document
            .querySelectorAll('[data-image-url="' + escapeSelector(oldUrl) + '"]')
            .forEach((tile) => {
                tile.dataset.imageUrl = newUrl;

                const img = tile.querySelector('img');
                if (img) {
                    img.src = thumbnailUrl || newUrl;
                }

                const button = tile.querySelector('[data-rotate-url]');
                if (button) {
                    button.dataset.rotateUrl = newUrl;
                }
            });
    }

    function bindPreview(root) {
        const propertyId = root.dataset.propertyId;
        const csrfToken = root.dataset.csrfToken;

        if (!propertyId || !csrfToken) {
            return;
        }

        root.querySelectorAll('[data-rotate-url]').forEach((button) => {
            if (button.dataset.bound === '1') {
                return;
            }

            button.dataset.bound = '1';
            button.addEventListener('click', async () => {
                const url = button.dataset.rotateUrl;
                if (!url || button.disabled) {
                    return;
                }

                button.disabled = true;

                try {
                    const response = await fetch('/admin/properties/' + propertyId + '/images/rotate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ url: url }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const message = payload.error || 'Не удалось повернуть фото';
                        throw new Error(message);
                    }

                    updateImageUrl(url, payload.url, payload.thumbnailUrl);
                } catch (error) {
                    const message = error instanceof Error ? error.message : 'Не удалось повернуть фото';
                    window.alert(message);
                } finally {
                    button.disabled = false;
                }
            });
        });
    }

    function init() {
        document.querySelectorAll('[data-property-images-preview]').forEach(bindPreview);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
