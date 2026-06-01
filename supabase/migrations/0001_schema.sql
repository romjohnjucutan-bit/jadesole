-- =============================================
-- Jade Sole — Postgres schema (Supabase)
-- Ported from database/jade_sole.sql (MySQL)
-- =============================================

-- ---------- Enums ----------
do $$ begin
  create type delivery_option as enum ('delivery', 'pickup');
exception when duplicate_object then null; end $$;

do $$ begin
  create type payment_method as enum ('cod', 'cash');
exception when duplicate_object then null; end $$;

do $$ begin
  create type order_status as enum (
    'Received', 'Preparing', 'Ready for Pickup',
    'Out for Delivery', 'Completed', 'Cancelled'
  );
exception when duplicate_object then null; end $$;

do $$ begin
  create type staff_role as enum ('admin', 'staff');
exception when duplicate_object then null; end $$;

-- ---------- Tables ----------
create table if not exists categories (
  id          bigint generated always as identity primary key,
  name        text not null,
  description text,
  created_at  timestamptz not null default now()
);

create table if not exists products (
  id            bigint generated always as identity primary key,
  name          text not null,
  description   text,
  price         numeric(10,2) not null,
  category_id   bigint references categories(id) on delete set null,
  image         text not null default '',
  stock         integer not null default 10,
  is_available  boolean not null default true,
  created_at    timestamptz not null default now()
);

-- Staff profiles are linked 1:1 to Supabase Auth users.
create table if not exists staff_profiles (
  id          uuid primary key references auth.users(id) on delete cascade,
  name        text not null,
  contact     text,
  email       text,
  username    text unique,
  role        staff_role not null default 'staff',
  created_at  timestamptz not null default now()
);

create table if not exists orders (
  id              bigint generated always as identity primary key,
  order_id        text unique not null,
  customer_name   text not null,
  contact_number  text not null,
  delivery_option delivery_option not null default 'pickup',
  address         text,
  payment_method  payment_method not null default 'cash',
  total_amount    numeric(10,2) not null,
  discount        numeric(10,2) not null default 0,
  status          order_status not null default 'Received',
  notes           text,
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now()
);

create table if not exists order_items (
  id           bigint generated always as identity primary key,
  order_id     text not null references orders(order_id) on delete cascade,
  product_id   bigint,
  product_name text not null,
  price        numeric(10,2) not null,
  quantity     integer not null,
  subtotal     numeric(10,2) not null
);

create index if not exists idx_products_category on products(category_id);
create index if not exists idx_orders_status on orders(status);
create index if not exists idx_order_items_order on order_items(order_id);

-- keep orders.updated_at fresh
create or replace function set_updated_at() returns trigger
language plpgsql as $$
begin
  new.updated_at = now();
  return new;
end $$;

drop trigger if exists trg_orders_updated_at on orders;
create trigger trg_orders_updated_at
  before update on orders
  for each row execute function set_updated_at();

-- =============================================
-- Seed data (matches the original MySQL seed)
-- =============================================
insert into categories (name, description) values
  ('Running Shoes', 'High-performance shoes built for speed and endurance'),
  ('Casual Sneakers', 'Everyday comfort meets effortless style'),
  ('Formal Shoes', 'Sophisticated footwear for every occasion'),
  ('Sandals & Slippers', 'Breezy, relaxed footwear for leisure'),
  ('Boots', 'Durable and stylish boots for any terrain')
on conflict do nothing;

insert into products (name, description, price, category_id, stock) values
  ('SpeedFlex Pro', 'Lightweight mesh upper with responsive cushioning for max performance', 2999.00, 1, 10),
  ('TrailBlazer X', 'Rugged outsole with enhanced grip for off-road running', 3299.00, 1, 10),
  ('AirStride Elite', 'Carbon fiber plate for explosive energy return', 4500.00, 1, 10),
  ('PaceRunner Lite', 'Ultra-light design for competitive runners', 2499.00, 1, 10),
  ('UrbanEdge Classic', 'Minimalist leather upper with vulcanized sole', 1799.00, 2, 10),
  ('SoftStep Canvas', 'Breathable canvas with memory foam insole', 1299.00, 2, 10),
  ('NeoWave Street', 'Bold chunky sole with retro colorway', 2199.00, 2, 10),
  ('CloudComfort Knit', 'Sock-fit knit upper with plush cushioning', 1999.00, 2, 10),
  ('Oxford Premier', 'Full-grain leather oxford with Goodyear welt construction', 3999.00, 3, 10),
  ('Derby Luxe', 'Smooth calfskin derby with leather sole', 3499.00, 3, 10),
  ('Loafer Signature', 'Penny loafer in suede with golden bit detail', 2999.00, 3, 10),
  ('Drift Slide', 'EVA foam slide with anatomical footbed', 699.00, 4, 10),
  ('Bali Strap', 'Woven leather strap sandal for beach or brunch', 1199.00, 4, 10),
  ('Terra Sport', 'Sport sandal with adjustable straps and traction sole', 999.00, 4, 10),
  ('Highland Chukka', 'Desert boot in suede with crepe sole', 2799.00, 5, 10),
  ('Storm Rider', 'Waterproof combat boot with lug sole', 3599.00, 5, 10),
  ('Chelsea Noir', 'Sleek Chelsea boot in full-grain leather', 3199.00, 5, 10)
on conflict do nothing;
