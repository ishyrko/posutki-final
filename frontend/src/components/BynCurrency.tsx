import { cn } from "@/lib/utils";

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
