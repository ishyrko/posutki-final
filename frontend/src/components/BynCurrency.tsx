import { cn } from "@/lib/utils";
import type { Currency } from "@/features/properties/types";

/** Belarusian ruble sign via nbrb font ligature: the string "BYN" becomes the official symbol. */
export function BynCurrencyMark({
  className,
  /** Use in currency Select so scale matches $ / € (default is sized for price lines). */
  variant = "price",
}: {
  className?: string;
  variant?: "price" | "select";
}) {
  return (
    <span
      className={cn("nbrb-icon", variant === "select" && "nbrb-icon--select", className)}
      aria-hidden
    >
      BYN
    </span>
  );
}

/** Formatted amount in BYN with the graphic symbol (not the ISO code). */
export function PriceInByn({ amount, className }: { amount: number; className?: string }) {
  const num = amount.toLocaleString("ru-BY");
  return (
    <span className={cn("font-body", className)}>
      {num}{" "}
      <BynCurrencyMark />
      <span className="sr-only"> BYN</span>
    </span>
  );
}

/** Renders a price in the given currency with the appropriate symbol. */
export function PriceDisplay({
  amount,
  currency,
  className,
}: {
  amount: number;
  currency: Currency;
  className?: string;
}) {
  const num = amount.toLocaleString("ru-BY");
  if (currency === "USD") {
    return (
      <span className={cn("font-body", className)}>
        {num} $
      </span>
    );
  }
  if (currency === "RUB") {
    return (
      <span className={cn("font-body", className)}>
        {num} ₽
      </span>
    );
  }
  return <PriceInByn amount={amount} className={className} />;
}
