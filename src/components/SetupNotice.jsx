// Shown when the app is deployed without Supabase env vars baked in.
// Turns a blank/white screen into an actionable message.
export default function SetupNotice() {
  return (
    <div className="flex-center" style={{ minHeight: '100vh', padding: '2rem' }}>
      <div className="data-card" style={{ maxWidth: 560, padding: '2rem' }}>
        <h2 style={{ marginBottom: '0.75rem' }}>⚙️ Setup needed</h2>
        <p className="text-dim" style={{ marginBottom: '1rem' }}>
          This site can’t reach its database because the Supabase keys weren’t
          included when it was built.
        </p>
        <p className="text-dim" style={{ marginBottom: '1rem' }}>
          In <strong>Cloudflare Pages → Settings → Environment variables</strong>,
          add both of these to <strong>Production</strong>, then trigger a new
          deployment (Vite bakes them in at build time):
        </p>
        <pre style={{
          background: 'var(--dark)', border: '1px solid var(--border)',
          borderRadius: 8, padding: '0.9rem', fontSize: '0.8rem', overflowX: 'auto',
        }}>
{`VITE_SUPABASE_URL=https://YOUR-REF.supabase.co
VITE_SUPABASE_ANON_KEY=your-anon-public-key`}
        </pre>
      </div>
    </div>
  )
}
