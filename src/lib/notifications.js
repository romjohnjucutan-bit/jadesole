import { supabase } from '../supabaseClient'
import { peso } from './config'

// Mirrors legacy includes/notifications.php:
//  - new orders (status 'Received') for all staff
//  - low stock (< 10, available) for admins only
export async function getNotifications(isAdmin) {
  const notifications = []

  const { data: orders } = await supabase
    .from('orders')
    .select('order_id, customer_name, total_amount, created_at')
    .eq('status', 'Received')
    .order('created_at', { ascending: false })
    .limit(10)

  for (const o of orders ?? []) {
    notifications.push({
      type: 'order',
      icon: '📦',
      title: 'New Order: ' + o.order_id,
      message: `${o.customer_name} — ${peso(o.total_amount)}`,
      customer: o.customer_name,
      amount: o.total_amount,
      orderId: o.order_id,
      time: o.created_at,
      link: '/admin/orders',
    })
  }

  if (isAdmin) {
    const { data: low } = await supabase
      .from('products')
      .select('id, name, stock')
      .lt('stock', 10)
      .eq('is_available', true)
      .order('stock', { ascending: true })
      .limit(10)

    for (const p of low ?? []) {
      notifications.push({
        type: 'stock',
        icon: '⚠️',
        title: 'Low Stock: ' + p.name,
        message: `Only ${p.stock} unit${p.stock === 1 ? '' : 's'} remaining`,
        productName: p.name,
        time: null,
        link: '/admin/products',
      })
    }
  }

  return { items: notifications, count: notifications.length }
}
