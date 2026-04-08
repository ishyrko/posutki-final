import type { Metadata } from "next";
import MortgageCalculatorPage from "@/features/mortgage/components/MortgageCalculatorPage";

export const metadata: Metadata = {
  title: "Ипотечный калькулятор — RNB.by",
  description:
    "Рассчитайте ежемесячный платеж, сумму переплаты и общую стоимость ипотечного кредита.",
};

export default function MortgageCalculatorRoute() {
  return <MortgageCalculatorPage />;
}
