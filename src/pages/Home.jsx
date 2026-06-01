import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { supabase } from '../supabaseClient'
import { SITE, peso, categoryEmoji } from '../lib/config'
import { productImageUrl } from '../lib/images'

export default function Home() {
  const [products, setProducts] = useState([])

  useEffect(() => {
    supabase
      .from('products')
      .select('*, categories(name)')
      .eq('is_available', true)
      .order('id', { ascending: true })
      .limit(8)
      .then(({ data }) => setProducts(data ?? []))
  }, [])

  return (
    <>
      {/* HERO */}
      <section className="hero">
        <div className="hero-bg" />
        <div className="hero-lines" />
        <div className="container hero-content">
          <div className="hero-eyebrow">
            <span className="hero-eyebrow-line" />
            Premium Footwear · Loon, Bohol
          </div>
          <h1 className="hero-title">
            Every Step<br />
            <em>Tells a Story.</em>
          </h1>
          <p className="hero-subtitle">
            Discover handpicked shoes crafted for comfort, built for style.
            From the streets of Bohol to wherever life takes you.
          </p>
          <div className="hero-info-strip">
            <div className="hero-info-item"><span className="icon">📍</span><span>{SITE.location}</span></div>
            <div className="hero-info-item"><span className="icon">📞</span><span>{SITE.contact}</span></div>
            <div className="hero-info-item"><span className="icon">🕐</span><span>{SITE.hours}</span></div>
          </div>
        </div>
      </section>

      {/* PROMOTIONS */}
      <section className="section" style={{ background: 'var(--off-black)' }}>
        <div className="container">
          <span className="section-tag">Exclusive Offers</span>
          <h2 className="section-title">Why Shop With Us</h2>
          <p className="section-subtitle mb-4">Perks that make every purchase sweeter.</p>
          <div className="promos-grid">
            <div className="promo-card">
              <span className="promo-icon">📶</span>
              <div className="promo-title">Free WiFi In-Store</div>
              <p className="promo-desc">Stay connected while you browse our collection. Comfortable seating and fast internet — shop at your pace.</p>
            </div>
            <div className="promo-card">
              <span className="promo-icon">🏷️</span>
              <div className="promo-title">10% Off Orders Over ₱500</div>
              <p className="promo-desc">Mix and match your favourites. Any order totalling ₱500 and above automatically gets a 10% discount at checkout.</p>
            </div>
            <div className="promo-card">
              <span className="promo-icon">🚚</span>
              <div className="promo-title">Free Delivery Over ₱300</div>
              <p className="promo-desc">Orders above ₱300 qualify for free delivery to nearby areas in Loon, Bohol. Get your shoes right at your door.</p>
            </div>
          </div>
        </div>
      </section>

      {/* FEATURED */}
      <section className="section">
        <div className="container">
          <span className="section-tag">Featured</span>
          <h2 className="section-title">Best Sellers</h2>
          <p className="section-subtitle mb-4">Our top picks — loved by our customers.</p>
          <div className="products-grid">
            {products.map((p) => {
              const img = productImageUrl(p.image)
              return (
                <div className="product-card" key={p.id}>
                  <div className="product-img-wrap">
                    {img
                      ? <img src={img} alt={p.name} />
                      : <div className="product-placeholder">{categoryEmoji(p.category_id)}</div>}
                    <span className="product-badge">{p.categories?.name}</span>
                  </div>
                  <div className="product-info">
                    <div className="product-name">{p.name}</div>
                    <p className="product-desc">{p.description}</p>
                    <div className="product-footer">
                      <div className="product-price">{peso(p.price)}</div>
                      <Link to="/order" className="product-add-btn">Order →</Link>
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
          <div className="text-center mt-4">
            <Link to="/menu" className="btn btn-outline">View Full Collection →</Link>
          </div>
        </div>
      </section>

      {/* ABOUT */}
      <section className="section-sm" style={{ background: 'var(--off-black)', borderTop: '1px solid var(--border)' }}>
        <div className="container">
          <div className="flex-between flex-wrap gap-2">
            <div>
              <span className="section-tag">About Jade Sole</span>
              <h2 style={{ fontSize: '1.6rem' }}>Quality You Can Feel With Every Step</h2>
              <p className="text-dim mt-2" style={{ maxWidth: 520 }}>
                Based in the heart of Loon, Bohol, Jade Sole brings premium footwear to your doorstep.
                We curate shoes that blend comfort, durability, and undeniable style — for every occasion.
              </p>
            </div>
            <Link to="/order" className="btn btn-gold" style={{ flexShrink: 0 }}>Order Now →</Link>
          </div>
        </div>
      </section>
    </>
  )
}
