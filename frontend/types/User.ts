export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  avatar_url?: string | null;
  google_id?: string | null;
  premium?: boolean;
  created_at: string;
  updated_at: string;
}
