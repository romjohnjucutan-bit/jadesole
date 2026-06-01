import { createContext, useContext, useEffect, useState } from 'react'
import { supabase } from '../supabaseClient'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [session, setSession] = useState(null)
  const [profile, setProfile] = useState(null)
  const [loading, setLoading] = useState(true)

  async function loadProfile(userId) {
    if (!userId) { setProfile(null); return }
    try {
      const { data } = await supabase
        .from('staff_profiles')
        .select('*')
        .eq('id', userId)
        .maybeSingle()
      setProfile(data ?? null)
    } catch {
      setProfile(null)
    }
  }

  useEffect(() => {
    let active = true

    // Safety net: never let the app hang on a blocking "Loading…" screen.
    const safety = setTimeout(() => { if (active) setLoading(false) }, 5000)

    // Initial session check.
    supabase.auth.getSession()
      .then(async ({ data }) => {
        if (!active) return
        setSession(data.session)
        await loadProfile(data.session?.user?.id)
      })
      .catch(() => { if (active) setSession(null) })
      .finally(() => { if (active) { clearTimeout(safety); setLoading(false) } })

    // IMPORTANT: this callback must stay synchronous. Awaiting another Supabase
    // call inside it deadlocks supabase-js (it holds an auth lock), which is what
    // caused the stuck "Loading…". Defer the profile fetch outside the lock.
    const { data: sub } = supabase.auth.onAuthStateChange((_event, sess) => {
      if (!active) return
      setSession(sess)
      setTimeout(() => {
        if (!active) return
        if (sess?.user) loadProfile(sess.user.id)
        else setProfile(null)
      }, 0)
    })

    return () => { active = false; clearTimeout(safety); sub.subscription.unsubscribe() }
  }, [])

  const value = {
    session,
    user: session?.user ?? null,
    profile,
    loading,
    isLoggedIn: !!session,
    isAdmin: profile?.role === 'admin',
    isStaff: !!profile,
    async signIn(email, password) {
      return supabase.auth.signInWithPassword({ email, password })
    },
    async signOut() {
      await supabase.auth.signOut()
    },
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
