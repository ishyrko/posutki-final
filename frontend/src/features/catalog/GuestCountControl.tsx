"use client";

import { Minus, Plus, Users } from "lucide-react";
import { Button } from "@/components/ui/button";
import { MAX_DAILY_GUESTS } from "@/features/create-listing/validation";
import { clampGuests, MIN_GUESTS } from "@/features/catalog/guests-filter";
import { cn } from "@/lib/utils";

interface GuestCountControlProps {
  id: string;
  value: number;
  onChange: (value: number) => void;
  label?: string;
  hideLabel?: boolean;
  showIcon?: boolean;
  className?: string;
  inputClassName?: string;
}

export function GuestCountControl({
  id,
  value,
  onChange,
  label = "Гости",
  hideLabel = false,
  showIcon = true,
  className,
  inputClassName,
}: GuestCountControlProps) {
  return (
    <div className={cn("flex items-center gap-3", className)}>
      {showIcon && <Users className="h-5 w-5 text-primary shrink-0" />}
      <div className="flex-1 min-w-0">
        {!hideLabel && (
          <label htmlFor={id} className="text-xs font-medium text-muted-foreground block mb-1">
            {label}
          </label>
        )}
        <div className={cn("flex items-center gap-0.5", hideLabel && "justify-center")}>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="h-8 w-8 shrink-0 text-foreground hover:text-foreground"
            aria-label="Меньше гостей"
            disabled={value <= MIN_GUESTS}
            onClick={() => onChange(clampGuests(value - 1))}
          >
            <Minus className="h-4 w-4" />
          </Button>
          <input
            id={id}
            name="guests"
            type="number"
            inputMode="numeric"
            min={MIN_GUESTS}
            max={MAX_DAILY_GUESTS}
            step={1}
            value={value}
            onChange={(e) => {
              const v = e.target.valueAsNumber;
              if (Number.isNaN(v)) return;
              onChange(clampGuests(v));
            }}
            onBlur={() => onChange(clampGuests(value))}
            className={cn(
              "min-w-0 w-10 flex-1 max-w-[3.25rem] bg-transparent text-center text-sm font-medium text-foreground tabular-nums outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none",
              inputClassName,
            )}
          />
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="h-8 w-8 shrink-0 text-foreground hover:text-foreground"
            aria-label="Больше гостей"
            disabled={value >= MAX_DAILY_GUESTS}
            onClick={() => onChange(clampGuests(value + 1))}
          >
            <Plus className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  );
}
