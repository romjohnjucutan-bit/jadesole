import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { supabase } from '../supabaseClient'
import { peso, categoryEmoji } from '../lib/config'
import { productImageUrl } from '../lib/images'
import { useCart } from '../context/CartContext'
import SizeModal from '../components/SizeModal'

export default function Menu() {
  const [products, setProducts] = useState([])
  const [categories, setCategories] = useState([])
  const [activeCat, setActiveCat] = useState('all')
  const [sizeFor, setSizeFor] = useState(null)
  const { addItem } = useCart()

  useEffect(() => {
    supabase.from('categories').select('*').order('id')
      .then(({ data }) => setCategories(data ?? []))
    supabase.from('products').select('*, categories(name)')
      .order('category_id').order('name')
      .then(({ data }) => setProducts(data ?? []))
  }, [])

  const visible = useMemo(
    () => products.filter((p) => activeCat === 'all' || String(p.category_id) === String(activeCat)),
    [products, activeCat]
  )

  function handleAdd(product, size) {
    addItem(product, size)
    setSizeFor(null)
  }

  return (
    <section className="section">
      <div className="container">
        <div className="flex-between mb-4 flex-wrap gap-2">
          <div>
            <span className="section-tag">Our Shoes</span>
            <h1 className="section-title">Full Collection</h1>
          </div>
          <Link to="/order" className="btn btn-gold">Order Online →</Link>
        </div>

        <div className="products-filter">
          <button className={`filter-btn${activeCat === 'all' ? ' active' : ''}`} onClick={() => setActiveCat('all')}>All</button>
          {categories.map((c) => (
            <button key={c.id} className={`filter-btn${String(activeCat) === String(c.id) ? ' active' : ''}`} onClick={() => setActiveCat(c.id)}>
              {c.name}
            </button>
          ))}
        </div>

        <div className="products-grid">
          {visible.map((p) => {
            const available = p.is_available && p.stock > 0
            const img = productImageUrl(p.image)
            return (
              <div className="product-card" key={p.id}>
                <div className="product-img-wrap">
                  {img
                    ? <img src={img} alt={p.name} />
                    : <div className="product-placeholder">{categoryEmoji(p.category_id)}</div>}
                  {available
                    ? <span className="product-badge">In Stock</span>
                    : <span className="product-badge unavailable">Unavailable</span>}
                </div>
                <div className="product-info">
                  <div className="product-category">{p.categories?.name}</div>
                  <div className="product-name">{p.name}</div>
                  <p className="product-desc">{p.description}</p>
                  <div className="product-footer">
                    <div className="product-price">{peso(p.price)}</div>
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4 }}>
                      <span style={{ fontSize: '0.7rem', color: 'var(--text-dim)' }}>Stock: {p.stock}</span>
                      {available
                        ? <button type="button" className="product-add-btn" onClick={() => setSizeFor(p)}>Add to Order</button>
                        : <button className="product-add-btn" disabled>Unavailable</button>}
                    </div>
                  </div>
                </div>
              </div>
            )
          })}
        </div>
      </div>

      {sizeFor && (
        <SizeModal
          product={sizeFor}
          onClose={() => setSizeFor(null)}
          onConfirm={handleAdd}
        />
      )}
    </section>
  )
}
