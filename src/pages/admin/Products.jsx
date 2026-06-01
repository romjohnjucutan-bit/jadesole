import { useEffect, useState, useCallback } from 'react'
import { supabase } from '../../supabaseClient'
import { peso, categoryEmoji, PRODUCT_BUCKET } from '../../lib/config'
import { productImageUrl } from '../../lib/images'

const EMPTY = { name: '', description: '', price: '', category_id: '', stock: 10, is_available: true, image: '' }

export default function AdminProducts() {
  const [products, setProducts] = useState([])
  const [categories, setCategories] = useState([])
  const [msg, setMsg] = useState(null)
  const [modal, setModal] = useState(null) // null | 'add' | product object (edit)
  const [form, setForm] = useState(EMPTY)
  const [file, setFile] = useState(null)
  const [busy, setBusy] = useState(false)

  const load = useCallback(async () => {
    const { data: prods } = await supabase.from('products').select('*, categories(name)').order('category_id').order('name')
    setProducts(prods ?? [])
    const { data: cats } = await supabase.from('categories').select('*').order('name')
    setCategories(cats ?? [])
  }, [])

  useEffect(() => { load() }, [load])

  function openAdd() {
    setForm({ ...EMPTY, category_id: categories[0]?.id ?? '' })
    setFile(null)
    setModal('add')
  }
  function openEdit(p) {
    setForm({ name: p.name, description: p.description || '', price: p.price, category_id: p.category_id, stock: p.stock, is_available: p.is_available, image: p.image })
    setFile(null)
    setModal(p)
  }

  async function uploadImage() {
    if (!file) return form.image
    const ext = file.name.split('.').pop()
    const path = `prod_${Date.now()}.${ext}`
    const { error } = await supabase.storage.from(PRODUCT_BUCKET).upload(path, file, { upsert: true })
    if (error) throw error
    return path
  }

  async function save(e) {
    e.preventDefault()
    setBusy(true); setMsg(null)
    try {
      const image = await uploadImage()
      const payload = {
        name: form.name,
        description: form.description,
        price: parseFloat(form.price),
        category_id: form.category_id ? Number(form.category_id) : null,
        stock: parseInt(form.stock, 10),
        is_available: !!form.is_available,
        image,
      }
      if (modal === 'add') {
        const { error } = await supabase.from('products').insert(payload)
        if (error) throw error
        setMsg({ type: 'success', text: 'Product added successfully!' })
      } else {
        const { error } = await supabase.from('products').update(payload).eq('id', modal.id)
        if (error) throw error
        setMsg({ type: 'success', text: 'Product updated!' })
      }
      setModal(null)
      load()
    } catch (err) {
      setMsg({ type: 'error', text: err.message })
    } finally {
      setBusy(false)
    }
  }

  async function toggle(p) {
    await supabase.from('products').update({ is_available: !p.is_available }).eq('id', p.id)
    load()
  }

  async function remove(p) {
    if (!confirm('Delete this product?')) return
    await supabase.from('products').delete().eq('id', p.id)
    setMsg({ type: 'success', text: 'Product deleted.' })
    load()
  }

  return (
    <>
      <div className="dash-header">
        <h1 className="dash-title">Products</h1>
        <button className="btn btn-gold" onClick={openAdd}>+ Add Product</button>
      </div>

      {msg && <div className={`alert alert-${msg.type === 'success' ? 'success' : 'error'}`}>{msg.text}</div>}

      <div className="data-card">
        <div className="table-wrap">
          <table>
            <thead>
              <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              {products.map((p) => {
                const img = productImageUrl(p.image)
                return (
                  <tr key={p.id}>
                    <td>
                      <div style={{ width: 44, height: 44, background: 'var(--dark)', borderRadius: 4, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '1.4rem' }}>
                        {img ? <img src={img} style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: 4 }} /> : categoryEmoji(p.category_id)}
                      </div>
                    </td>
                    <td><strong>{p.name}</strong></td>
                    <td>{p.categories?.name}</td>
                    <td className="text-gold">{peso(p.price)}</td>
                    <td>{p.stock}</td>
                    <td>
                      <button onClick={() => toggle(p)} style={{ background: 'none', border: 'none', cursor: 'pointer' }}>
                        <span className={`badge ${p.is_available ? 'badge-green' : 'badge-red'}`}>{p.is_available ? 'Available' : 'Unavailable'}</span>
                      </button>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '0.4rem' }}>
                        <button className="btn btn-ghost btn-sm" onClick={() => openEdit(p)}>Edit</button>
                        <button className="btn btn-danger btn-sm" onClick={() => remove(p)}>Delete</button>
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </div>

      {modal && (
        <div className="modal-overlay active" onClick={(e) => { if (e.target === e.currentTarget) setModal(null) }}>
          <div className="modal">
            <div className="modal-header">
              <h3 className="modal-title">{modal === 'add' ? 'Add Product' : 'Edit Product'}</h3>
              <button className="modal-close" onClick={() => setModal(null)}>✕</button>
            </div>
            <form onSubmit={save}>
              <div className="modal-body">
                <div className="form-row">
                  <div className="form-group">
                    <label className="form-label">Product Name</label>
                    <input className="form-control" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Category</label>
                    <select className="form-control" value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value })} required>
                      {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                  </div>
                </div>
                <div className="form-group">
                  <label className="form-label">Description</label>
                  <input className="form-control" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
                </div>
                <div className="form-row">
                  <div className="form-group">
                    <label className="form-label">Price (₱)</label>
                    <input type="number" step="0.01" className="form-control" value={form.price} onChange={(e) => setForm({ ...form, price: e.target.value })} required />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Stock</label>
                    <input type="number" className="form-control" value={form.stock} onChange={(e) => setForm({ ...form, stock: e.target.value })} required />
                  </div>
                </div>
                <div className="form-group">
                  <label className="form-label">Product Image {modal !== 'add' && '(leave blank to keep current)'}</label>
                  <input type="file" accept="image/*" className="form-control" onChange={(e) => setFile(e.target.files[0])} />
                </div>
                {modal !== 'add' && (
                  <div className="form-group" style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <input type="checkbox" id="avail" checked={form.is_available} onChange={(e) => setForm({ ...form, is_available: e.target.checked })} style={{ width: 'auto' }} />
                    <label htmlFor="avail" className="form-label" style={{ margin: 0 }}>Available for sale</label>
                  </div>
                )}
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-ghost" onClick={() => setModal(null)}>Cancel</button>
                <button type="submit" className="btn btn-gold" disabled={busy}>{busy ? 'Saving…' : (modal === 'add' ? 'Add Product' : 'Save Changes')}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  )
}
