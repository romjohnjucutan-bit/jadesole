import { supabase } from '../supabaseClient'
import { PRODUCT_BUCKET } from './config'

// products.image stores the object path inside the product-images bucket
// (or '' / 'default.jpg' for "no image"). Returns a public URL or null.
export function productImageUrl(image) {
  if (!image || image === 'default.jpg') return null
  const { data } = supabase.storage.from(PRODUCT_BUCKET).getPublicUrl(image)
  return data?.publicUrl || null
}
