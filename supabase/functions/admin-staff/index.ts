// Supabase Edge Function: admin-staff
// Lets an authenticated ADMIN create / delete staff accounts and reset
// passwords. Uses the service-role key (never exposed to the browser).
//
// Deploy:  supabase functions deploy admin-staff
// Secrets are provided automatically (SUPABASE_URL, SUPABASE_ANON_KEY,
// SUPABASE_SERVICE_ROLE_KEY) when running on Supabase.
//
// Request body: { action: "create" | "delete" | "reset_password", ... }

import { createClient } from "https://esm.sh/@supabase/supabase-js@2";

const SUPABASE_URL = Deno.env.get("SUPABASE_URL")!;
const ANON_KEY = Deno.env.get("SUPABASE_ANON_KEY")!;
const SERVICE_KEY = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!;

const cors = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
};

function json(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...cors, "Content-Type": "application/json" },
  });
}

Deno.serve(async (req) => {
  if (req.method === "OPTIONS") return new Response("ok", { headers: cors });
  if (req.method !== "POST") return json({ error: "Method not allowed" }, 405);

  const authHeader = req.headers.get("Authorization") ?? "";
  const token = authHeader.replace("Bearer ", "");
  if (!token) return json({ error: "Missing auth token" }, 401);

  const admin = createClient(SUPABASE_URL, SERVICE_KEY);

  // Verify the caller and that they are an admin.
  const { data: userData, error: userErr } = await admin.auth.getUser(token);
  if (userErr || !userData.user) return json({ error: "Invalid session" }, 401);

  const { data: profile } = await admin
    .from("staff_profiles")
    .select("role")
    .eq("id", userData.user.id)
    .single();

  if (!profile || profile.role !== "admin") {
    return json({ error: "Admins only" }, 403);
  }

  let body: Record<string, unknown>;
  try {
    body = await req.json();
  } catch {
    return json({ error: "Invalid JSON" }, 400);
  }

  const action = body.action;

  try {
    if (action === "create") {
      const { email, password, name, username, contact, role } = body as Record<string, string>;
      if (!email || !password) return json({ error: "Email and password required" }, 400);
      const { data, error } = await admin.auth.admin.createUser({
        email,
        password,
        email_confirm: true,
        user_metadata: { name, username, contact, role: role === "admin" ? "admin" : "staff" },
      });
      if (error) return json({ error: error.message }, 400);
      return json({ ok: true, id: data.user?.id });
    }

    if (action === "delete") {
      const { id } = body as Record<string, string>;
      if (!id) return json({ error: "Staff id required" }, 400);
      if (id === userData.user.id) return json({ error: "Cannot delete your own account" }, 400);
      const { error } = await admin.auth.admin.deleteUser(id);
      if (error) return json({ error: error.message }, 400);
      return json({ ok: true });
    }

    if (action === "reset_password") {
      const { id, password } = body as Record<string, string>;
      if (!id || !password) return json({ error: "Staff id and password required" }, 400);
      const { error } = await admin.auth.admin.updateUserById(id, { password });
      if (error) return json({ error: error.message }, 400);
      return json({ ok: true });
    }

    return json({ error: "Unknown action" }, 400);
  } catch (e) {
    return json({ error: String(e) }, 500);
  }
});
