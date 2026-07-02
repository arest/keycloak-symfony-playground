"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { SYMFONY_URL } from "@/lib/config";

export default function Home() {
  const router = useRouter();
  const [checking, setChecking] = useState(true);

  useEffect(() => {
    fetch(`${SYMFONY_URL}/api/me`, { credentials: "include" })
      .then((res) => {
        if (res.ok) {
          router.replace("/profile");
        } else {
          setChecking(false);
        }
      })
      .catch(() => {
        setChecking(false);
      });
  }, [router]);

  if (checking) {
    return (
      <div className="flex flex-1 items-center justify-center">
        <p className="text-zinc-500">Checking authentication...</p>
      </div>
    );
  }

  return (
    <div className="flex flex-1 flex-col items-center justify-center gap-6 px-4">
      <h1 className="text-2xl font-semibold">Keycloak SSO SPA Demo</h1>
      <p className="max-w-md text-center text-zinc-600 dark:text-zinc-400">
        This is a demo SPA that uses Symfony as a Backend-for-Frontend (BFF) with
        Keycloak SSO. Login to view your profile and access protected resources.
      </p>
      <a
        href={`${SYMFONY_URL}/login`}
        className="inline-flex h-10 items-center gap-2 rounded-md bg-zinc-900 px-6 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
      >
        Login with Keycloak
      </a>
    </div>
  );
}
