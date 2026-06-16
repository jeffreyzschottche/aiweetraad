export interface Category {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  icon: string | null;
  color: string;
  sort_order: number;
  questions_count?: number;
}

export interface AiModel {
  id: number;
  name: string;
  slug: string;
  tagline: string | null;
  accent_color: string;
  logo_url?: string | null;
  enabled: boolean;
  sort_order: number;
  // leaderboard extras
  score?: number;
  answer_count?: number;
  total_upvotes?: number;
  total_downvotes?: number;
}

export interface Answer {
  id: number;
  question_id: number;
  ai_model_id: number | null;
  is_ai: boolean;
  body: string;
  status?: 'completed' | 'fallback' | 'failed';
  upvotes: number;
  downvotes: number;
  score: number;
  my_vote?: number;
  ai_model?: AiModel | null;
}

export interface Question {
  id: number;
  category_id: number | null;
  title: string;
  slug: string;
  body: string | null;
  status: string;
  views: number;
  answers_count?: number;
  created_at: string;
  category?: Category | null;
  answers?: Answer[];
}

export interface Page {
  id: number;
  slug: string;
  title: string;
  meta_description: string | null;
  body: string;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}
