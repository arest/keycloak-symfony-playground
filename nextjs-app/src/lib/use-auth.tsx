"use client";

import {
  createContext,
  useContext,
  useEffect,
  useRef,
  useState,
  type ReactNode,
} from "react";
import { SYMFONY_URL } from "@/lib/config";

interface AuthState {
  isLoggedIn: boolean | null;
  isLoading: boolean;
}

const AuthContext = createContext<AuthState>({
  isLoggedIn: null,
  isLoading: true,
});

let fetchInitiated = false;

export function AuthProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<AuthState>({
    isLoggedIn: null,
    isLoading: true,
  });
  const fetchRef = useRef(false);

  useEffect(() => {
    if (fetchRef.current || fetchInitiated) return;
    fetchRef.current = true;
    fetchInitiated = true;

    fetch(`${SYMFONY_URL}/api/me`, { credentials: "include" })
      .then((res) => {
        setState({ isLoggedIn: res.ok, isLoading: false });
      })
      .catch(() => {
        setState({ isLoggedIn: false, isLoading: false });
      });
  }, []);

  return (
    <AuthContext.Provider value={state}>{children}</AuthContext.Provider>
  );
}

export function useAuth(): AuthState {
  return useContext(AuthContext);
}
