import { useEffect, useState, useCallback } from 'react'
import { supabase } from '../../supabaseClient'
import { useAuth } from '../../context/AuthContext'

const EMPTY = { name: '', role: 'staff', contact: '', email: '', username: '', password: '' }

export default function AdminStaff() {
  const { user } = useAuth()
  const [staff, setStaff] = useState([])
  const [msg, setMsg] = useState(null)
  const [modal, setModal] = useState(null) // null | 'add' | profile (edit)
  const [form, setForm] = useState(EMPTY)
  const [busy, setBusy] = useState(false)

  const load = useCallback(async () => {
    const { data } = await supabase.from('staff_profiles').select('*').order('role', { ascending: false }).order('name')
    setStaff(data ?? [])
  }, [])

  useEffect(() => { load() }, [load])

  function openAdd() { setForm(EMPTY); setModal('add') }
  function openEdit(s) {
    setForm({ name: s.name, role: s.role, contact: s.contact || '', email: s.email || '', username: s.username || '', password: '' })
    setModal(s)
  }

  async function callFn(body) {
    const { data, error } = await supabase.functions.invoke('admin-staff', { body })
    if (error) {
      // Try to surface the function's JSON error message
      let detail = error.message
      try { detail = (await error.context?.json())?.error || detail } catch { /* ignore */ }
      throw new Error(detail)
    }
    if (data?.error) throw new Error(data.error)
    return data
  }

  async function save(e) {
    e.preventDefault()
    setBusy(true); setMsg(null)
    try {
      if (modal === 'add') {
        await callFn({
          action: 'create',
          email: form.email, password: form.password,
          name: form.name, username: form.username,
          contact: form.contact, role: form.role,
        })
        setMsg({ type: 'success', text: 'Staff member added!' })
      } else {
        const { error } = await supabase.from('staff_profiles')
          .update({ name: form.name, contact: form.contact, role: form.role })
          .eq('id', modal.id)
        if (error) throw error
        if (form.password) {
          await callFn({ action: 'reset_password', id: modal.id, password: form.password })
        }
        setMsg({ type: 'success', text: 'Staff updated!' })
      }
      setModal(null)
      load()
    } catch (err) {
      setMsg({ type: 'error', text: err.message })
    } finally {
      setBusy(false)
    }
  }

  async function remove(s) {
    if (s.id === user.id) { setMsg({ type: 'error', text: 'Cannot delete your own account.' }); return }
    if (!confirm('Remove this staff member?')) return
    setMsg(null)
    try {
      await callFn({ action: 'delete', id: s.id })
      setMsg({ type: 'success', text: 'Staff removed.' })
      load()
    } catch (err) {
      setMsg({ type: 'error', text: err.message })
    }
  }

  return (
    <>
      <div className="dash-header">
        <h1 className="dash-title">Staff Management</h1>
        <button className="btn btn-gold" onClick={openAdd}>+ Add Staff</button>
      </div>

      {msg && <div className={`alert alert-${msg.type === 'success' ? 'success' : 'error'}`}>{msg.text}</div>}

      <div className="data-card">
        <div className="table-wrap">
          <table>
            <thead>
              <tr><th>Name</th><th>Username</th><th>Email</th><th>Contact</th><th>Role</th><th>Actions</th></tr>
            </thead>
            <tbody>
              {staff.map((s) => (
                <tr key={s.id}>
                  <td><strong>{s.name}</strong></td>
                  <td style={{ fontFamily: 'monospace', color: 'var(--gold)' }}>{s.username}</td>
                  <td style={{ fontSize: '0.82rem' }}>{s.email}</td>
                  <td style={{ fontSize: '0.82rem' }}>{s.contact}</td>
                  <td><span className={`badge ${s.role === 'admin' ? 'badge-gold' : 'badge-blue'}`}>{s.role}</span></td>
                  <td>
                    <div style={{ display: 'flex', gap: '0.4rem' }}>
                      <button className="btn btn-ghost btn-sm" onClick={() => openEdit(s)}>Edit</button>
                      {s.id !== user.id && <button className="btn btn-danger btn-sm" onClick={() => remove(s)}>Delete</button>}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {modal && (
        <div className="modal-overlay active" onClick={(e) => { if (e.target === e.currentTarget) setModal(null) }}>
          <div className="modal">
            <div className="modal-header">
              <h3 className="modal-title">{modal === 'add' ? 'Add Staff Member' : 'Edit Staff'}</h3>
              <button className="modal-close" onClick={() => setModal(null)}>✕</button>
            </div>
            <form onSubmit={save}>
              <div className="modal-body">
                <div className="form-row">
                  <div className="form-group">
                    <label className="form-label">Full Name</label>
                    <input className="form-control" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Role</label>
                    <select className="form-control" value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })}>
                      <option value="staff">Staff</option>
                      <option value="admin">Admin</option>
                    </select>
                  </div>
                </div>
                <div className="form-row">
                  <div className="form-group">
                    <label className="form-label">Contact</label>
                    <input className="form-control" value={form.contact} onChange={(e) => setForm({ ...form, contact: e.target.value })} />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Email {modal === 'add' && '(login)'}</label>
                    <input type="email" className="form-control" value={form.email}
                           onChange={(e) => setForm({ ...form, email: e.target.value })}
                           required={modal === 'add'} disabled={modal !== 'add'} />
                  </div>
                </div>
                {modal === 'add' ? (
                  <div className="form-row">
                    <div className="form-group">
                      <label className="form-label">Username</label>
                      <input className="form-control" value={form.username} onChange={(e) => setForm({ ...form, username: e.target.value })} required />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Password</label>
                      <input type="password" className="form-control" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required minLength={6} />
                    </div>
                  </div>
                ) : (
                  <div className="form-group">
                    <label className="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" className="form-control" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} placeholder="Enter new password" minLength={6} />
                  </div>
                )}
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-ghost" onClick={() => setModal(null)}>Cancel</button>
                <button type="submit" className="btn btn-gold" disabled={busy}>{busy ? 'Saving…' : (modal === 'add' ? 'Add Staff' : 'Save Changes')}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  )
}
