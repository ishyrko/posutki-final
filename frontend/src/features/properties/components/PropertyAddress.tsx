import { Fragment } from "react";
import Link from "next/link";
import {
  buildDistrictCatalogUrlFromAddress,
  buildMicrodistrictCatalogUrlFromAddress,
  buildResidentialComplexCatalogUrlFromAddress,
} from "@/features/catalog/slugs";
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

  const isApartment = propertyType === "apartment";

  const microdistrictUrl = isApartment
    ? buildMicrodistrictCatalogUrlFromAddress(
        address.regionName,
        address.citySlug,
        address.cityMicrodistrictSlug,
      )
    : undefined;

  if (address.cityMicrodistrictName) {
    parts.push(
      microdistrictUrl ? (
        <Link key="microdistrict" href={microdistrictUrl} className={linkClassName}>
          {address.cityMicrodistrictName}
        </Link>
      ) : (
        address.cityMicrodistrictName
      ),
    );
  }

  const residentialComplexUrl = isApartment
    ? buildResidentialComplexCatalogUrlFromAddress(
        address.regionName,
        address.citySlug,
        address.residentialComplexSlug,
      )
    : undefined;

  if (address.residentialComplexName) {
    parts.push(
      residentialComplexUrl ? (
        <Link key="residential-complex" href={residentialComplexUrl} className={linkClassName}>
          {address.residentialComplexName}
        </Link>
      ) : (
        address.residentialComplexName
      ),
    );
  }

  const districtUrl = isApartment
    ? buildDistrictCatalogUrlFromAddress(
        address.regionName,
        address.citySlug,
        address.cityDistrictSlug,
      )
    : undefined;

  if (address.cityDistrictName) {
    const label = formatCityDistrictLabel(address.cityDistrictName);
    parts.push(
      <span key="district" className="whitespace-nowrap">
        {districtUrl ? (
          <Link href={districtUrl} className={linkClassName}>
            {label}
          </Link>
        ) : (
          label
        )}
      </span>,
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
