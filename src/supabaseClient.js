import { createClient } from '@supabase/supabase-js'

const url = import.meta.env.VITE_SUPABASE_URL
const anonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

// Flag for the UI: when false, Supabase isn't configured for this deployment.
export const supabaseConfigured = Boolean(url && anonKey)

if (!supabaseConfigured) {
  console.error(
    'Missing Supabase env vars. Set VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY ' +
    '(in .env locally, or in Cloudflare Pages → Settings → Environment variables) and redeploy.'
  )
}

// Use harmless placeholders if unset so the app still renders (instead of a
// white screen from createClient throwing). Calls will simply fail until the
// real values are provided.
export const supabase = createClient(
  url || 'https://placeholder.supabase.co',
  anonKey || 'placeholder-anon-key'
)
