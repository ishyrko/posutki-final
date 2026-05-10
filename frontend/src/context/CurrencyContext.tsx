"use client";

import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import type { Currency } from "@/features/properties/types";

const STORAGE_KEY = "preferred-currency";
const DEFAULT_CURRENCY: Currency = "BYN";

interface CurrencyContextValue {
  selectedCurrency: Currency;
  setSelectedCurrency: (currency: Currency) => void;
}

const CurrencyContext = createContext<CurrencyContextValue>({
  selectedCurrency: DEFAULT_CURRENCY,
  setSelectedCurrency: () => {},
});

export function CurrencyProvider({ children }: { children: ReactNode }) {
  const [selectedCurrency, setSelectedCurrencyState] = useState<Currency>(DEFAULT_CURRENCY);

  useEffect(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === "BYN" || stored === "USD" || stored === "RUB") {
      setSelectedCurrencyState(stored);
    }
  }, []);

  const setSelectedCurrency = (currency: Currency) => {
    setSelectedCurrencyState(currency);
    localStorage.setItem(STORAGE_KEY, currency);
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
