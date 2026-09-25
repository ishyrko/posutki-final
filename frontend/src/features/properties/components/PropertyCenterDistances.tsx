import { Navigation } from "lucide-react";
import {
  formatLocationDistancesLabel,
  type LocationDistances,
} from "@/features/properties/center-distance";

type PropertyCenterDistancesProps = {
  distances?: LocationDistances | null;
  className?: string;
};

export function PropertyCenterDistances({
  distances,
  className,
}: PropertyCenterDistancesProps) {
  const label = formatLocationDistancesLabel(distances);
  if (!label) {
    return null;
  }

  return (
    <p className={`flex items-start gap-1.5 text-sm text-muted-foreground md:items-center ${className ?? ""}`}>
      <Navigation className="mt-0.5 h-4 w-4 shrink-0 md:mt-0" />
      <span>{label}</span>
    </p>
  );
}
