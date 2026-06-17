interface SitemapQuestion {
  slug: string;
  updated_at?: string;
  created_at?: string;
}

interface SitemapCategory {
  slug: string;
}

interface SitemapPaginatedQuestions {
  data: SitemapQuestion[];
  current_page?: number;
  last_page?: number;
}

const STATIC_PATHS = [
  '/',
  '/categorieen',
  '/vraag-stellen',
  '/zoeken',
  '/over-ons',
  '/adverteren',
  '/contact',
  '/privacy',
  '/voorwaarden',
];

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig();
  const siteUrl = String(config.public.siteUrl || 'https://aiweetraad.nl').replace(/\/+$/, '');
  const apiBaseUrl = String(config.public.apiBaseUrl || '').replace(/\/+$/, '');
  const urls = new Map<string, { lastmod?: string }>();

  for (const path of STATIC_PATHS) {
    urls.set(path, {});
  }

  if (apiBaseUrl) {
    try {
      const categories = await $fetch<{ data: SitemapCategory[] }>(`${apiBaseUrl}/categories`);
      for (const category of categories.data || []) {
        urls.set(`/categorie/${category.slug}`, {});
      }
    } catch {
      /* Keep the static sitemap available if the API is temporarily unreachable. */
    }

    try {
      let page = 1;
      let lastPage = 1;

      do {
        const questions = await $fetch<SitemapPaginatedQuestions>(`${apiBaseUrl}/questions?sort=recent&page=${page}`);

        for (const question of questions.data || []) {
          urls.set(`/vraag/${question.slug}`, {
            lastmod: question.updated_at || question.created_at,
          });
        }

        lastPage = questions.last_page || page;
        page += 1;
      } while (page <= lastPage);
    } catch {
      /* Keep the static sitemap available if the API is temporarily unreachable. */
    }
  }

  const body = [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ...Array.from(urls.entries()).map(([path, meta]) => {
      const loc = xmlEscape(`${siteUrl}${path}`);
      const lastmod = meta.lastmod ? `<lastmod>${xmlEscape(meta.lastmod.slice(0, 10))}</lastmod>` : '';

      return `<url><loc>${loc}</loc>${lastmod}</url>`;
    }),
    '</urlset>',
  ].join('');

  setHeader(event, 'content-type', 'application/xml; charset=utf-8');

  return body;
});

function xmlEscape(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}
