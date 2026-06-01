import { Link } from 'react-router-dom'
import { SITE } from '../lib/config'

export default function Footer() {
  return (
    <footer>
      <div className="container">
        <div className="footer-grid">
          <div className="footer-brand">
            <h3>Jade <span style={{ color: 'var(--gold)' }}>Sole</span></h3>
            <p>Premium footwear for every step of the way. Based in the heart of Loon, Bohol.</p>
            <p className="mt-2" style={{ color: 'var(--coral)', fontSize: '0.85rem' }}>📞 {SITE.contact}</p>
          </div>
          <div className="footer-col">
            <h4>Quick Links</h4>
            <ul className="footer-links">
              <li><Link to="/">Home</Link></li>
              <li><Link to="/menu">Collection</Link></li>
              <li><Link to="/order">Order Online</Link></li>
              <li><Link to="/track">Track Order</Link></li>
            </ul>
          </div>
          <div className="footer-col">
            <h4>Info</h4>
            <ul className="footer-links">
              <li><span>{SITE.location}</span></li>
              <li><span>{SITE.hours}</span></li>
              <li><Link to="/login">Staff Portal</Link></li>
            </ul>
          </div>
        </div>
        <div className="footer-bottom">
          <span>© {new Date().getFullYear()} Jade Sole. All rights reserved.</span>
          <span>Made with ❤️ in Bohol</span>
        </div>
      </div>
    </footer>
  )
}
