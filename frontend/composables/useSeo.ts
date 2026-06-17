interface SeoInput {
  title?: string;
  description?: string;
  path?: string;
  image?: string;
  type?: string;
  noindex?: boolean;
}

interface JsonLdEntity {
  [key: string]: unknown;
}

const DEFAULT_DESCRIPTION =
  'Stel je dagelijkse vraag en krijg meteen antwoord van meerdere AI’s. Vergelijk de adviezen en stem op het beste antwoord.';

export function useSiteIdentity() {
  const config = useRuntimeConfig();
  const siteName = String(config.public.siteName || 'AI Weet Raad');
  const siteUrl = String(config.public.siteUrl || 'https://aiweetraad.nl').replace(/\/+$/, '');

  function absoluteUrl(path = '/'): string {
    if (/^https?:\/\//i.test(path)) return path;
    return `${siteUrl}${path.startsWith('/') ? path : `/${path}`}`;
  }

  return {
    siteName,
    siteUrl,
    logoUrl: absoluteUrl('/images/aiweetraadlogo.png'),
    absoluteUrl,
  };
}

export function textExcerpt(value?: string | null, maxLength = 155): string {
  const text = String(value || '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  if (!text) return DEFAULT_DESCRIPTION;
  if (text.length <= maxLength) return text;

  return `${text.slice(0, maxLength - 1).trim()}…`;
}

export function usePageSeo(input: SeoInput | (() => SeoInput)) {
  const route = useRoute();
  const { siteName, absoluteUrl } = useSiteIdentity();

  useHead(() => {
    const seo = typeof input === 'function' ? input() : input;
    const description = textExcerpt(seo.description);
    const canonical = absoluteUrl(seo.path || route.path || '/');
    const image = absoluteUrl(seo.image || '/images/aiweetraadlogo.png');
    const title = seo.title || siteName;

    return {
      title,
      link: [{ rel: 'canonical', href: canonical }],
      meta: [
        { name: 'description', content: description },
        { name: 'robots', content: seo.noindex ? 'noindex,nofollow' : 'index,follow' },
        { property: 'og:site_name', content: siteName },
        { property: 'og:type', content: seo.type || 'website' },
        { property: 'og:title', content: title },
        { property: 'og:description', content: description },
        { property: 'og:url', content: canonical },
        { property: 'og:image', content: image },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: title },
        { name: 'twitter:description', content: description },
        { name: 'twitter:image', content: image },
      ],
    };
  });
}

export function useJsonLd(key: string, input: JsonLdEntity | JsonLdEntity[] | null | (() => JsonLdEntity | JsonLdEntity[] | null)) {
  useHead(() => {
    const value = typeof input === 'function' ? input() : input;

    if (!value) {
      return { script: [] };
    }

    return {
      script: [
        {
          key,
          type: 'application/ld+json',
          innerHTML: JSON.stringify(value).replace(/</g, '\\u003c'),
        },
      ],
    };
  });
}
