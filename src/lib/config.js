// Site-wide constants (ported from the legacy config.php) and small helpers.

export const SITE = {
  name: 'Jade Sole',
  tagline: 'Step Into Style',
  location: 'Moto Norte, Loon, Bohol',
  contact: '09701933534',
  hours: 'Mon–Sun, 9AM – 9PM',
}

export const DISCOUNT_THRESHOLD = 500
export const DISCOUNT_PERCENT = 10
export const FREE_DELIVERY_THRESHOLD = 300

export const ORDER_STATUSES = [
  'Received',
  'Preparing',
  'Ready for Pickup',
  'Out for Delivery',
  'Completed',
  'Cancelled',
]

// Bucket where product images are stored in Supabase Storage.
export const PRODUCT_BUCKET = 'product-images'

export function peso(amount) {
  return '₱' + Number(amount || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

export function categoryEmoji(categoryId) {
  switch (Number(categoryId)) {
    case 1: return '👟'
    case 2: return '👟'
    case 3: return '👞'
    case 4: return '🩴'
    case 5: return '🥾'
    default: return '👟'
  }
}

export function statusBadgeClass(status) {
  switch (status) {
    case 'Received': return 'badge-blue'
    case 'Preparing': return 'badge-yellow'
    case 'Ready for Pickup':
    case 'Out for Delivery': return 'badge-gold'
    case 'Completed': return 'badge-green'
    case 'Cancelled': return 'badge-red'
    default: return 'badge-gray'
  }
}

export function timeAgo(datetime) {
  if (!datetime) return ''
  const diff = Math.floor((Date.now() - new Date(datetime).getTime()) / 1000)
  if (diff < 60) return 'just now'
  if (diff < 3600) return Math.floor(diff / 60) + 'm ago'
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago'
  return Math.floor(diff / 86400) + 'd ago'
}

export function formatDate(datetime) {
  if (!datetime) return ''
  return new Date(datetime).toLocaleString('en-PH', {
    month: 'short', day: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
