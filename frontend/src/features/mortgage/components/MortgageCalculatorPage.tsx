"use client";

import { useMemo, useState } from "react";
import {
  Banknote,
  CalendarDays,
  Info,
  Percent,
  TrendingDown,
} from "lucide-react";
import { motion } from "framer-motion";
import { Slider } from "@/components/ui/slider";

type MortgageResult = {
  monthly: number;
  total: number;
  overpayment: number;
  principal: number;
};

function formatByn(amount: number): string {
  return amount.toLocaleString("ru-BY", { maximumFractionDigits: 0 });
}

function getYearsLabel(years: number): string {
  if (years === 1) return "год";
  if (years >= 2 && years <= 4) return "года";
  return "лет";
}

export default function MortgageCalculatorPage() {
  const [price, setPrice] = useState(150_000);
  const [downPayment, setDownPayment] = useState(30_000);
  const [rate, setRate] = useState(12);
  const [years, setYears] = useState(20);

  const result = useMemo<MortgageResult>(() => {
    const principal = price - downPayment;
    if (principal <= 0) {
      return { monthly: 0, total: 0, overpayment: 0, principal: 0 };
    }

    const monthlyRate = rate / 100 / 12;
    const months = years * 12;

    const monthly =
      monthlyRate > 0
        ? (principal * monthlyRate * Math.pow(1 + monthlyRate, months)) /
          (Math.pow(1 + monthlyRate, months) - 1)
        : principal / months;

    const total = monthly * months;

    return {
      monthly: Math.round(monthly),
      total: Math.round(total),
      overpayment: Math.round(total - principal),
      principal,
    };
  }, [price, downPayment, rate, years]);

  const downPaymentPercent =
    price > 0 ? Math.round((downPayment / price) * 100) : 0;

  const principalRatio =
    result.total > 0 ? (result.principal / result.total) * 100 : 50;
  const overpaymentRatio =
    result.total > 0 ? (result.overpayment / result.total) * 100 : 50;

  return (
    <div className="min-h-screen bg-dark-bg">
      <section className="relative overflow-hidden pb-16 pt-28">
        <div className="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent" />
        <div className="container relative mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="mb-12 text-center"
        >
          <h1 className="mb-4 text-4xl font-bold text-dark-fg md:text-5xl">
            Ипотечный <span className="text-primary">калькулятор</span>
          </h1>
          <p className="mx-auto max-w-lg text-dark-fg/60">
            Рассчитайте ежемесячный платеж, переплату и общую стоимость
            ипотечного кредита.
          </p>
        </motion.div>

          <div className="mx-auto grid max-w-5xl gap-8 lg:grid-cols-5">
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            className="space-y-8 rounded-2xl border border-dark-card bg-dark-card p-6 md:p-8 lg:col-span-3"
          >
            <div>
              <div className="mb-3 flex items-baseline justify-between">
                <label className="text-sm font-medium text-dark-fg">
                  Стоимость недвижимости
                </label>
                <span className="text-lg font-bold text-primary">
                  {formatByn(price)} BYN
                </span>
              </div>
              <Slider
                value={[price]}
                onValueChange={([value]) => {
                  setPrice(value);
                  if (downPayment > value) setDownPayment(value);
                }}
                min={20_000}
                max={1_000_000}
                step={5_000}
                className="w-full"
              />
              <div className="mt-1 flex justify-between text-xs text-dark-fg/40">
                <span>20 000</span>
                <span>1 000 000</span>
              </div>
            </div>

            <div>
              <div className="mb-3 flex items-baseline justify-between">
                <label className="text-sm font-medium text-dark-fg">
                  Первоначальный взнос
                </label>
                <span className="text-lg font-bold text-primary">
                  {formatByn(downPayment)} BYN{" "}
                  <span className="text-sm font-normal text-dark-fg/50">
                    ({downPaymentPercent}%)
                  </span>
                </span>
              </div>
              <Slider
                value={[downPayment]}
                onValueChange={([value]) => setDownPayment(value)}
                min={0}
                max={price}
                step={5_000}
                className="w-full"
              />
              <div className="mt-1 flex justify-between text-xs text-dark-fg/40">
                <span>0</span>
                <span>{formatByn(price)}</span>
              </div>
            </div>

            <div>
              <div className="mb-3 flex items-baseline justify-between">
                <label className="text-sm font-medium text-dark-fg">
                  Процентная ставка
                </label>
                <span className="text-lg font-bold text-primary">{rate}%</span>
              </div>
              <Slider
                value={[rate]}
                onValueChange={([value]) => setRate(value)}
                min={1}
                max={30}
                step={0.5}
                className="w-full"
              />
              <div className="mt-1 flex justify-between text-xs text-dark-fg/40">
                <span>1%</span>
                <span>30%</span>
              </div>
            </div>

            <div>
              <div className="mb-3 flex items-baseline justify-between">
                <label className="text-sm font-medium text-dark-fg">
                  Срок кредита
                </label>
                <span className="text-lg font-bold text-primary">
                  {years} {getYearsLabel(years)}
                </span>
              </div>
              <Slider
                value={[years]}
                onValueChange={([value]) => setYears(value)}
                min={1}
                max={30}
                step={1}
                className="w-full"
              />
              <div className="mt-1 flex justify-between text-xs text-dark-fg/40">
                <span>1 год</span>
                <span>30 лет</span>
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            className="space-y-5 lg:col-span-2"
          >
            <div className="rounded-2xl border border-primary/20 bg-primary/5 p-6 text-center">
              <Banknote className="mx-auto mb-2 h-8 w-8 text-primary" />
              <div className="mb-1 text-sm text-dark-fg/60">
                Ежемесячный платеж
              </div>
              <div className="text-3xl font-bold text-primary md:text-4xl">
                {formatByn(result.monthly)} <span className="text-lg">BYN</span>
              </div>
            </div>

            <div className="space-y-4 rounded-2xl border border-dark-card bg-dark-card p-6">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-dark-fg/60">
                  <CalendarDays className="h-4 w-4" />
                  Сумма кредита
                </div>
                <span className="text-sm font-semibold text-dark-fg">
                  {formatByn(result.principal)} BYN
                </span>
              </div>
              <div className="h-px bg-dark-bg" />
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-dark-fg/60">
                  <Percent className="h-4 w-4" />
                  Переплата по процентам
                </div>
                <span className="text-sm font-semibold text-dark-fg">
                  {formatByn(result.overpayment)} BYN
                </span>
              </div>
              <div className="h-px bg-dark-bg" />
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-dark-fg/60">
                  <TrendingDown className="h-4 w-4" />
                  Общая выплата
                </div>
                <span className="text-sm font-semibold text-dark-fg">
                  {formatByn(result.total)} BYN
                </span>
              </div>
            </div>

            <div className="rounded-2xl border border-dark-card bg-dark-card p-6">
              <div className="mb-3 text-sm text-dark-fg/60">Структура выплат</div>
              <div className="flex h-4 overflow-hidden rounded-full bg-dark-bg">
                <div
                  className="rounded-l-full bg-primary transition-all duration-300"
                  style={{ width: `${principalRatio}%` }}
                />
                <div
                  className="rounded-r-full bg-primary/30 transition-all duration-300"
                  style={{ width: `${overpaymentRatio}%` }}
                />
              </div>
              <div className="mt-2 flex justify-between text-xs text-dark-fg/50">
                <span className="flex items-center gap-1.5">
                  <span className="h-2.5 w-2.5 rounded-full bg-primary" /> Тело
                  кредита
                </span>
                <span className="flex items-center gap-1.5">
                  <span className="h-2.5 w-2.5 rounded-full bg-primary/30" />{" "}
                  Проценты
                </span>
              </div>
            </div>

            <div className="flex items-start gap-2 px-1 text-xs text-dark-fg/40">
              <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
              <span>
                Расчет является приблизительным. Точные условия уточняйте в
                банке.
              </span>
            </div>
          </motion.div>
          </div>
        </div>
      </section>
    </div>
  );
}
