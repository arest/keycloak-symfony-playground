"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { SYMFONY_URL } from "@/lib/config";

interface UserInfo {
  username: string;
  email: string;
  roles: string[];
  [key: string]: unknown;
}

export default function ProfilePage() {
  const router = useRouter();
  const [user, setUser] = useState<UserInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetch(`${SYMFONY_URL}/api/me`, { credentials: "include" })
      .then(async (res) => {
        if (!res.ok) {
          if (res.status === 401 || res.status === 302) {
            router.replace("/login");
            return;
          }
          throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        const data = await res.json();
        setUser(data);
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
        <p className="text-zinc-500">Loading profile...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-1 flex-col items-center justify-center gap-4 px-4">
        <h1 className="text-2xl font-semibold text-red-500">Error</h1>
        <p className="text-zinc-600 dark:text-zinc-400">{error}</p>
        <a
          href="/login"
          className="inline-flex h-10 items-center gap-2 rounded-md bg-zinc-900 px-6 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
        >
          Go to Login
        </a>
      </div>
    );
  }

  if (!user) {
    return (
      <div className="flex flex-1 items-center justify-center">
        <p className="text-zinc-500">No user data available.</p>
      </div>
    );
  }

  return (
    <div className="flex flex-1 flex-col items-center justify-center gap-6 px-4">
      <h1 className="text-2xl font-semibold">Profile</h1>
      <div className="w-full max-w-sm rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
        <dl className="space-y-4">
          <div>
            <dt className="text-sm font-medium text-zinc-500">Username</dt>
            <dd className="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
              {user.username}
            </dd>
          </div>
          <div>
            <dt className="text-sm font-medium text-zinc-500">Email</dt>
            <dd className="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
              {user.email}
            </dd>
          </div>
          <div>
            <dt className="text-sm font-medium text-zinc-500">Roles</dt>
            <dd className="mt-1 flex flex-wrap gap-2">
              {user.roles.map((role) => (
                <span
                  key={role}
                  className="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
                >
                  {role}
                </span>
              ))}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  );
}
