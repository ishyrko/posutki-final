import { Fragment } from "react";
import Link from "next/link";
import { buildDistrictCatalogUrlFromAddress } from "@/features/catalog/slugs";
import { formatCityDistrictLabel, type Address } from "../types";

interface PropertyAddressProps {
  address: Address;
  /** Ссылка на каталог района — только для квартир. */
  propertyType?: string;
  className?: string;
  linkClassName?: string;
}

export function PropertyAddress({
  address,
  propertyType,
  className,
  linkClassName = "text-primary hover:underline",
}: PropertyAddressProps) {
  const parts: React.ReactNode[] = [];

  if (address.streetName) parts.push(address.streetName);
  if (address.building && address.block) {
    parts.push(`${address.building}, корп. ${address.block}`);
  } else if (address.building) {
    parts.push(address.building);
  } else if (address.block) {
    parts.push(`корп. ${address.block}`);
  }

  const districtUrl =
    propertyType === "apartment"
      ? buildDistrictCatalogUrlFromAddress(
          address.regionName,
          address.citySlug,
          address.cityDistrictSlug,
        )
      : undefined;

  if (address.cityDistrictName) {
    const label = formatCityDistrictLabel(address.cityDistrictName);
    parts.push(
      districtUrl ? (
        <Link key="district" href={districtUrl} className={linkClassName}>
          {label}
        </Link>
      ) : (
        label
      ),
    );
  }

  if (address.cityName) parts.push(address.cityName);

  return (
    <span className={className}>
      {parts.map((part, index) => (
        <Fragment key={index}>
          {index > 0 && ", "}
          {part}
        </Fragment>
      ))}
    </span>
  );
}
