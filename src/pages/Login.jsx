import { useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function Login() {
  const { signIn, loading, isLoggedIn, isStaff } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(false)

  async function submit(e) {
    e.preventDefault()
    setError(null)
    setBusy(true)
    const { error } = await signIn(email.trim(), password)
    setBusy(false)
    if (error) {
      setError('Incorrect email or password.')
      return
    }
    navigate('/admin')
  }

  if (loading) {
    return (
      <div className="flex-center" style={{ minHeight: '60vh', color: 'var(--text-dim)' }}>
        Loading…
      </div>
    )
  }

  if (isLoggedIn && isStaff) {
    return <Navigate to="/admin" replace />
  }

  return (
    <div className="login-page">
      <div className="login-bg">
        <div className="login-bg-tag">
          <span className="login-bg-tag-line" />
          Staff &amp; Admin Portal
        </div>
        <div className="login-bg-brand">Step Into<br />Your Shift.</div>
        <p className="login-bg-sub">
          Manage orders, products, and your team — all from one place. Welcome to the Jade Sole dashboard.
        </p>
      </div>

      <div className="login-card">
        <div className="login-logo">
          <div className="login-logo-icon">👟</div>
          <h2>Welcome back</h2>
          <p>Sign in to your staff account</p>
        </div>

        {error && <div className="alert alert-error" style={{ width: '100%', maxWidth: 380 }}>{error}</div>}

        <form onSubmit={submit} style={{ width: '100%', maxWidth: 380 }}>
          <div className="form-group">
            <label className="form-label">Email</label>
            <input type="email" className="form-control" placeholder="e.g. admin@jadesole.com"
                   value={email} onChange={(e) => setEmail(e.target.value)} required autoFocus />
          </div>
          <div className="form-group">
            <label className="form-label">Password</label>
            <input type="password" className="form-control" placeholder="Enter your password"
                   value={password} onChange={(e) => setPassword(e.target.value)} required />
          </div>
          <button type="submit" className="btn btn-gold w-full" style={{ justifyContent: 'center', marginTop: '0.8rem' }} disabled={busy}>
            {busy ? 'Signing in…' : 'Sign In →'}
          </button>
        </form>

        <div className="mt-3" style={{ width: '100%', maxWidth: 380 }}>
          <Link to="/" style={{ fontSize: '0.82rem', color: 'var(--text-dim)' }}>← Back to Store</Link>
        </div>
      </div>
    </div>
  )
}
