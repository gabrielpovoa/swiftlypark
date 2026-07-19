(function () {
    'use strict';

    const FALLBACK_FAVICON = '/images/favicons-swiftlypark.png';
    const CANVAS_SIZE = 128;
    const LOGO_PADDING = 18;
    const CACHE_PREFIX = 'swiftlypark.company-favicon.v1:';
    let refreshSequence = 0;

    function faviconLinks() {
        const selectors = [
            'link[rel="icon"]',
            'link[rel="shortcut icon"]',
            'link[rel="apple-touch-icon"]',
        ];
        const links = selectors.flatMap((selector) => Array.from(document.querySelectorAll(selector)));

        if (links.length > 0) {
            return [...new Set(links)];
        }

        const link = document.createElement('link');
        link.rel = 'icon';
        link.type = 'image/png';
        document.head.appendChild(link);

        return [link];
    }

    function applyFavicon(url) {
        faviconLinks().forEach((link) => {
            link.href = url;
        });
    }

    function normalizeLogoUrl(logoPath) {
        const path = String(logoPath || '').trim();
        if (!path) {
            return null;
        }

        const url = path.startsWith('/uploads/')
            ? path
            : `/uploads/${path.replace(/^\/+/, '')}`;
        const resolved = new URL(url, window.location.origin);

        return resolved.origin === window.location.origin ? resolved.href : null;
    }

    function roundedSquare(context) {
        const inset = 4;
        const size = CANVAS_SIZE - (inset * 2);
        const radius = 28;

        context.beginPath();
        context.roundRect(inset, inset, size, size, radius);
        context.fillStyle = '#11151e';
        context.fill();
    }

    function loadImage(url) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.decoding = 'async';
            image.onload = () => resolve(image);
            image.onerror = () => reject(new Error('Não foi possível carregar a logo da empresa.'));
            image.src = url;
        });
    }

    function drawContainedImage(context, image) {
        const available = CANVAS_SIZE - (LOGO_PADDING * 2);
        const scale = Math.min(available / image.naturalWidth, available / image.naturalHeight);
        const width = Math.max(1, image.naturalWidth * scale);
        const height = Math.max(1, image.naturalHeight * scale);
        const x = (CANVAS_SIZE - width) / 2;
        const y = (CANVAS_SIZE - height) / 2;

        context.drawImage(image, x, y, width, height);
    }

    async function createCompanyFavicon(logoPath) {
        const logoUrl = normalizeLogoUrl(logoPath);
        if (!logoUrl) {
            return FALLBACK_FAVICON;
        }

        const cacheKey = `${CACHE_PREFIX}${logoUrl}`;
        try {
            const cached = window.sessionStorage.getItem(cacheKey);
            if (cached) {
                return cached;
            }
        } catch (_) {
            // O favicon continua funcionando quando o storage está indisponível.
        }

        const image = await loadImage(logoUrl);
        const canvas = document.createElement('canvas');
        canvas.width = CANVAS_SIZE;
        canvas.height = CANVAS_SIZE;
        const context = canvas.getContext('2d');
        if (!context) {
            return FALLBACK_FAVICON;
        }

        context.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
        roundedSquare(context);
        drawContainedImage(context, image);

        const favicon = canvas.toDataURL('image/png');
        try {
            window.sessionStorage.setItem(cacheKey, favicon);
        } catch (_) {
            // Cache é apenas uma otimização.
        }

        return favicon;
    }

    function currentCompanyLogo(context) {
        const companyId = Number.parseInt(String(context.currentCompanyId || ''), 10);
        if (!Number.isInteger(companyId) || companyId < 1) {
            return null;
        }

        if (Number.parseInt(String(context.currentCompany?.id || ''), 10) === companyId) {
            return context.currentCompany.logo_path || null;
        }

        const company = Array.isArray(context.tenants)
            ? context.tenants.find((tenant) => Number.parseInt(String(tenant.id), 10) === companyId)
            : null;

        return company?.logo_path || null;
    }

    async function refreshCompanyFavicon(logoPath) {
        const refreshId = ++refreshSequence;
        const logoUrl = normalizeLogoUrl(logoPath);

        if (!logoUrl) {
            applyFavicon(FALLBACK_FAVICON);
            return;
        }

        // Mantém a identidade da empresa visível enquanto o canvas prepara a
        // versão quadrada. Isso também evita o retorno visual ao fallback.
        applyFavicon(logoUrl);

        try {
            const favicon = await createCompanyFavicon(logoUrl);
            if (refreshId === refreshSequence) {
                applyFavicon(favicon);
            }
        } catch (_) {
            if (refreshId === refreshSequence) {
                applyFavicon(FALLBACK_FAVICON);
            }
        }
    }

    function initialize() {
        const context = window.SwiftlyParkTenant || {};
        refreshCompanyFavicon(currentCompanyLogo(context));
    }

    window.CompanyFavicon = Object.freeze({
        create: createCompanyFavicon,
        refresh: refreshCompanyFavicon,
        fallback: FALLBACK_FAVICON,
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
