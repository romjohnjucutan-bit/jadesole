-- =============================================
-- Jade Sole — RLS, role helpers, RPCs, storage
-- =============================================

-- ---------- Role helpers (SECURITY DEFINER avoids RLS recursion) ----------
create or replace function public.is_staff()
returns boolean
language sql stable security definer set search_path = public as $$
  select exists (select 1 from staff_profiles where id = auth.uid());
$$;

create or replace function public.is_admin()
returns boolean
language sql stable security definer set search_path = public as $$
  select exists (select 1 from staff_profiles where id = auth.uid() and role = 'admin');
$$;

-- ---------- Enable RLS ----------
alter table categories     enable row level security;
alter table products       enable row level security;
alter table staff_profiles enable row level security;
alter table orders         enable row level security;
alter table order_items    enable row level security;

-- ---------- Categories: public read, admin write ----------
drop policy if exists categories_read on categories;
create policy categories_read on categories
  for select using (true);

drop policy if exists categories_admin on categories;
create policy categories_admin on categories
  for all using (public.is_admin()) with check (public.is_admin());

-- ---------- Products: public read, admin write ----------
drop policy if exists products_read on products;
create policy products_read on products
  for select using (true);

drop policy if exists products_admin on products;
create policy products_admin on products
  for all using (public.is_admin()) with check (public.is_admin());

-- ---------- Staff profiles ----------
-- A logged-in staff member can read their own row; admins can read all.
drop policy if exists staff_select on staff_profiles;
create policy staff_select on staff_profiles
  for select using (id = auth.uid() or public.is_admin());

-- Admins can update profile fields (name/contact/role). Account creation and
-- deletion go through the admin-staff Edge Function (service role).
drop policy if exists staff_admin_update on staff_profiles;
create policy staff_admin_update on staff_profiles
  for update using (public.is_admin()) with check (public.is_admin());

-- ---------- Orders / order_items: staff read, staff update status ----------
-- Customers never touch these tables directly; they use the RPCs below.
drop policy if exists orders_staff_read on orders;
create policy orders_staff_read on orders
  for select using (public.is_staff());

drop policy if exists orders_staff_update on orders;
create policy orders_staff_update on orders
  for update using (public.is_staff()) with check (public.is_staff());

drop policy if exists order_items_staff_read on order_items;
create policy order_items_staff_read on order_items
  for select using (public.is_staff());

-- =============================================
-- Auto-create a staff profile when an auth user is created.
-- The admin-staff Edge Function passes name/username/contact/role in metadata.
-- (There is no public signup in this app, so every auth user is staff.)
-- =============================================
create or replace function public.handle_new_user()
returns trigger
language plpgsql security definer set search_path = public as $$
begin
  insert into public.staff_profiles (id, name, email, username, contact, role)
  values (
    new.id,
    coalesce(new.raw_user_meta_data->>'name', new.email),
    new.email,
    coalesce(new.raw_user_meta_data->>'username', split_part(new.email, '@', 1)),
    new.raw_user_meta_data->>'contact',
    coalesce((new.raw_user_meta_data->>'role')::staff_role, 'staff')
  )
  on conflict (id) do nothing;
  return new;
end $$;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
  after insert on auth.users
  for each row execute function public.handle_new_user();

-- =============================================
-- RPC: place_order  (called by anonymous customers)
-- items = jsonb array of { "product_id": int, "quantity": int, "size": text }
-- Prices are looked up server-side; stock is decremented atomically.
-- Returns the generated order_id.
-- =============================================
create or replace function public.place_order(
  p_customer_name   text,
  p_contact_number  text,
  p_delivery_option delivery_option,
  p_address         text,
  p_payment_method  payment_method,
  p_items           jsonb
)
returns text
language plpgsql security definer set search_path = public as $$
declare
  discount_threshold constant numeric := 500;
  discount_percent   constant numeric := 10;
  v_order_id text;
  v_subtotal numeric := 0;
  v_discount numeric := 0;
  v_total    numeric := 0;
  v_item     jsonb;
  v_product  products%rowtype;
  v_qty      integer;
  v_size     text;
  v_name     text;
begin
  if p_items is null or jsonb_array_length(p_items) = 0 then
    raise exception 'Cart is empty';
  end if;

  -- generate a unique order id like JS-XXXXXXXX
  v_order_id := 'JS-' || upper(substr(md5(gen_random_uuid()::text), 1, 8));

  -- first pass: validate + compute subtotal
  for v_item in select * from jsonb_array_elements(p_items) loop
    v_qty := coalesce((v_item->>'quantity')::int, 0);
    select * into v_product from products
      where id = (v_item->>'product_id')::bigint;
    if not found or not v_product.is_available then
      raise exception 'Product unavailable';
    end if;
    if v_qty <= 0 or v_qty > v_product.stock then
      raise exception 'Not enough stock for %', v_product.name;
    end if;
    v_subtotal := v_subtotal + (v_product.price * v_qty);
  end loop;

  if v_subtotal >= discount_threshold then
    v_discount := round(v_subtotal * discount_percent / 100, 2);
  end if;
  v_total := v_subtotal - v_discount;

  insert into orders (order_id, customer_name, contact_number, delivery_option,
                      address, payment_method, total_amount, discount, status)
  values (v_order_id, p_customer_name, p_contact_number, p_delivery_option,
          nullif(p_address, ''), p_payment_method, v_total, v_discount, 'Received');

  -- second pass: insert items + decrement stock
  for v_item in select * from jsonb_array_elements(p_items) loop
    v_qty  := (v_item->>'quantity')::int;
    v_size := nullif(v_item->>'size', '');
    select * into v_product from products
      where id = (v_item->>'product_id')::bigint;

    v_name := v_product.name;
    if v_size is not null then
      v_name := v_name || ' (Size ' || v_size || ')';
    end if;

    insert into order_items (order_id, product_id, product_name, price, quantity, subtotal)
    values (v_order_id, v_product.id, v_name, v_product.price, v_qty, v_product.price * v_qty);

    update products set stock = stock - v_qty
      where id = v_product.id and stock >= v_qty;
  end loop;

  return v_order_id;
end $$;

-- =============================================
-- RPC: track_order  (anonymous, by order id)
-- =============================================
create or replace function public.track_order(p_order_id text)
returns jsonb
language plpgsql stable security definer set search_path = public as $$
declare
  v_order orders%rowtype;
  v_items jsonb;
begin
  select * into v_order from orders where order_id = p_order_id;
  if not found then
    return null;
  end if;

  select coalesce(jsonb_agg(to_jsonb(oi) order by oi.id), '[]'::jsonb)
    into v_items
    from order_items oi where oi.order_id = p_order_id;

  return to_jsonb(v_order) || jsonb_build_object('items', v_items);
end $$;

-- =============================================
-- RPC: cancel_order  (anonymous, only while 'Received')
-- =============================================
create or replace function public.cancel_order(p_order_id text)
returns jsonb
language plpgsql security definer set search_path = public as $$
declare
  v_status order_status;
begin
  select status into v_status from orders where order_id = p_order_id;
  if not found then
    raise exception 'Order not found';
  end if;
  if v_status <> 'Received' then
    raise exception 'Order cannot be cancelled at this stage';
  end if;
  update orders set status = 'Cancelled' where order_id = p_order_id;
  return public.track_order(p_order_id);
end $$;

-- Allow anonymous + authenticated callers to run the customer RPCs.
grant execute on function public.place_order(text, text, delivery_option, text, payment_method, jsonb) to anon, authenticated;
grant execute on function public.track_order(text) to anon, authenticated;
grant execute on function public.cancel_order(text) to anon, authenticated;

-- =============================================
-- Storage: public bucket for product images
-- =============================================
insert into storage.buckets (id, name, public)
values ('product-images', 'product-images', true)
on conflict (id) do nothing;

drop policy if exists product_images_public_read on storage.objects;
create policy product_images_public_read on storage.objects
  for select using (bucket_id = 'product-images');

drop policy if exists product_images_admin_write on storage.objects;
create policy product_images_admin_write on storage.objects
  for insert with check (bucket_id = 'product-images' and public.is_admin());

drop policy if exists product_images_admin_update on storage.objects;
create policy product_images_admin_update on storage.objects
  for update using (bucket_id = 'product-images' and public.is_admin());

drop policy if exists product_images_admin_delete on storage.objects;
create policy product_images_admin_delete on storage.objects
  for delete using (bucket_id = 'product-images' and public.is_admin());
