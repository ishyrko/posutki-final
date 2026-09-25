"use client";

import { createContext, useContext, useSyncExternalStore, type ReactNode } from "react";
import type { Currency } from "@/features/properties/types";

const STORAGE_KEY = "preferred-currency";
const CURRENCY_CHANGE_EVENT = "preferred-currency-change";
const DEFAULT_CURRENCY: Currency = "BYN";

function readStoredCurrency(): Currency {
  if (typeof window === "undefined") {
    return DEFAULT_CURRENCY;
  }
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored === "BYN" || stored === "USD" || stored === "RUB") {
    return stored;
  }
  return DEFAULT_CURRENCY;
}

function subscribeCurrency(onStoreChange: () => void): () => void {
  const onStorage = (event: StorageEvent) => {
    if (event.key === STORAGE_KEY || event.key === null) {
      onStoreChange();
    }
  };
  window.addEventListener("storage", onStorage);
  window.addEventListener(CURRENCY_CHANGE_EVENT, onStoreChange);
  return () => {
    window.removeEventListener("storage", onStorage);
    window.removeEventListener(CURRENCY_CHANGE_EVENT, onStoreChange);
  };
}

interface CurrencyContextValue {
  selectedCurrency: Currency;
  setSelectedCurrency: (currency: Currency) => void;
}

const CurrencyContext = createContext<CurrencyContextValue>({
  selectedCurrency: DEFAULT_CURRENCY,
  setSelectedCurrency: () => {},
});

export function CurrencyProvider({ children }: { children: ReactNode }) {
  const selectedCurrency = useSyncExternalStore(
    subscribeCurrency,
    readStoredCurrency,
    () => DEFAULT_CURRENCY,
  );

  const setSelectedCurrency = (currency: Currency) => {
    localStorage.setItem(STORAGE_KEY, currency);
    window.dispatchEvent(new Event(CURRENCY_CHANGE_EVENT));
  };

  return (
    <CurrencyContext.Provider value={{ selectedCurrency, setSelectedCurrency }}>
      {children}
    </CurrencyContext.Provider>
  );
}

export function useCurrency(): CurrencyContextValue {
  return useContext(CurrencyContext);
}
