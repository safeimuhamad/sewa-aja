const SewaAjaSeo = (() => {
    function upsertMeta(selector, attributes) {
        let element = document.head.querySelector(selector);

        if (!element) {
            element = document.createElement('meta');
            document.head.appendChild(element);
        }

        Object.entries(attributes).forEach(([key, value]) => {
            element.setAttribute(key, value);
        });
    }

    function canonical(url = window.location.href) {
        let element = document.head.querySelector('link[rel="canonical"]');

        if (!element) {
            element = document.createElement('link');
            element.rel = 'canonical';
            document.head.appendChild(element);
        }

        element.href = url.split('#')[0];
    }

    function product(product) {
        const title = `${product.name} - SewaAja`;
        const description = (product.description || `Sewa ${product.name} di ${product.city || 'SewaAja'} dengan booking mudah.`).slice(0, 155);
        const image = product.primary_image || product.images?.[0]?.image_url || 'assets/img/sewaaja-logo-wordmark.webp';

        document.title = title;
        upsertMeta('meta[name="description"]', { name: 'description', content: description });
        upsertMeta('meta[property="og:title"]', { property: 'og:title', content: title });
        upsertMeta('meta[property="og:description"]', { property: 'og:description', content: description });
        upsertMeta('meta[property="og:image"]', { property: 'og:image', content: new URL(image, window.location.href).href });
        upsertMeta('meta[property="og:type"]', { property: 'og:type', content: 'product' });
        upsertMeta('meta[name="twitter:card"]', { name: 'twitter:card', content: 'summary_large_image' });
        canonical();
        schema({
            '@context': 'https://schema.org',
            '@type': 'Product',
            name: product.name,
            description,
            image: new URL(image, window.location.href).href,
            brand: { '@type': 'Brand', name: 'SewaAja' },
            offers: {
                '@type': 'Offer',
                priceCurrency: 'IDR',
                price: Number(product.price_per_day || 0),
                availability: Number(product.stock_quantity || 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                url: window.location.href,
            },
        });
    }

    function schema(data) {
        let script = document.getElementById('structuredData');

        if (!script) {
            script = document.createElement('script');
            script.id = 'structuredData';
            script.type = 'application/ld+json';
            document.head.appendChild(script);
        }

        script.textContent = JSON.stringify(data);
    }

    return { canonical, product, schema };
})();

window.SewaAjaSeo = SewaAjaSeo;
