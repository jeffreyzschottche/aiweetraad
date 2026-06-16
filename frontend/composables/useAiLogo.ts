import type { AiModel } from '~/types/content';

interface AiLogoMeta {
  src: string;
  needsWhiteBg: boolean;
}

const AI_LOGOS: Record<string, AiLogoMeta> = {
  claude: { src: '/images/claudelogo.png', needsWhiteBg: true },
  openai: { src: '/images/chatgpt.png', needsWhiteBg: true },
  chatgpt: { src: '/images/chatgpt.png', needsWhiteBg: true },
  gpt: { src: '/images/chatgpt.png', needsWhiteBg: true },
  gemini: { src: '/images/geminilogo.png', needsWhiteBg: true },
  grok: { src: '/images/groklogo.png', needsWhiteBg: true },
  deepseek: { src: '/images/deepseek.png', needsWhiteBg: true },
};

export function useAiLogo() {
  function aiLogoMetaFor(model?: AiModel | null): AiLogoMeta | null {
    if (!model) return null;

    const slug = model.slug?.toLowerCase();
    const logo = AI_LOGOS[slug];

    return logo || (model.logo_url ? { src: model.logo_url, needsWhiteBg: false } : null);
  }

  function aiLogoFor(model?: AiModel | null): string | null {
    return aiLogoMetaFor(model)?.src ?? null;
  }

  return { aiLogoFor, aiLogoMetaFor };
}
