"use client";

import { SYMFONY_URL } from "@/lib/config";
import { useAuth } from "@/lib/use-auth";

export default function NavBar() {
  const { isLoggedIn } = useAuth();

  return (
    <nav className="flex items-center gap-4 px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">
      <a
        href="/"
        className="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
      >
        Home
      </a>
      {isLoggedIn === false && (
        <a
          href="/login"
          className="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          Login
        </a>
      )}
      <a
        href="/profile"
        className="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
      >
        Profile
      </a>
      <a
        href="/protected"
        className="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
      >
        Protected
      </a>
      <div className="ml-auto">
        {isLoggedIn === true && (
          <a
            href={`${SYMFONY_URL}/logout`}
            className="text-sm font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
          >
            Logout
          </a>
        )}
      </div>
    </nav>
  );
}
