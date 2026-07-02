"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { SYMFONY_URL } from "@/lib/config";

interface ProtectedResponse {
  message: string;
  user?: string;
  roles?: string[];
  [key: string]: unknown;
}

export default function ProtectedPage() {
  const router = useRouter();
  const [data, setData] = useState<ProtectedResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetch(`${SYMFONY_URL}/api/protected`, { credentials: "include" })
      .then(async (res) => {
        if (res.status === 401 || res.status === 302) {
          router.replace("/login");
          return;
        }
        if (!res.ok) {
          const body = await res.text();
          throw new Error(`HTTP ${res.status}: ${body || res.statusText}`);
        }
        const json = await res.json();
        setData(json);
        setLoading(false);
      })
      .catch((err) => {
        setError(err.message);
        setLoading(false);
      });
  }, [router]);

  if (loading) {
    return (
      <div className="flex flex-1 items-center justify-center">
        <p className="text-zinc-500">Loading protected resource...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-1 flex-col items-center justify-center gap-4 px-4">
        <h1 className="text-2xl font-semibold text-red-500">Access Denied</h1>
        <p className="max-w-md text-center text-zinc-600 dark:text-zinc-400">
          {error}
        </p>
        <p className="text-sm text-zinc-500 dark:text-zinc-500">
          This page requires the <strong>ADMIN</strong> role. Try logging in as{" "}
          <code className="rounded bg-zinc-100 px-1 dark:bg-zinc-800">admin1</code>.
        </p>
        <a
          href="/login"
          className="inline-flex h-10 items-center gap-2 rounded-md bg-zinc-900 px-6 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
        >
          Go to Login
        </a>
      </div>
    );
  }

  return (
    <div className="flex flex-1 flex-col items-center justify-center gap-6 px-4">
      <h1 className="text-2xl font-semibold text-green-600">Protected Resource</h1>
      <div className="w-full max-w-sm rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
        <p className="text-sm text-zinc-600 dark:text-zinc-400">
          {data?.message || "Access granted"}
        </p>
        {data?.user && (
          <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Authenticated as: <strong>{data.user}</strong>
          </p>
        )}
        {data?.roles && data.roles.length > 0 && (
          <div className="mt-3">
            <p className="text-sm font-medium text-zinc-500">Roles</p>
            <div className="mt-1 flex flex-wrap gap-2">
              {data.roles.map((role) => (
                <span
                  key={role}
                  className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-100"
                >
                  {role}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
