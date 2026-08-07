import Link from "next/link";
import { Fragment } from "react";

import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import type { Crumb } from "@/lib/breadcrumbs";
import { cn } from "@/lib/utils";

type PageBreadcrumbsProps = {
  items: Crumb[];
  className?: string;
  /** На узких экранах скрыть последний пункт (для карточки объявления — заголовок уже в H1). */
  hideCurrentOnMobile?: boolean;
};

function BreadcrumbTrail({ items }: { items: Crumb[] }) {
  return (
    <>
      {items.map((item, index) => {
        const isLast = index === items.length - 1;
        const isFirst = index === 0;

        return (
          <Fragment key={`${item.label}-${index}`}>
            {index > 0 ? <BreadcrumbSeparator className="shrink-0" /> : null}
            <BreadcrumbItem
              className={cn(
                "shrink-0",
                isFirst && "max-w-none",
                !isFirst && !isLast && "max-w-[42vw] sm:max-w-none",
                isLast && "min-w-0 max-w-[52vw] sm:max-w-[28rem]",
              )}
            >
              {item.href ? (
                <BreadcrumbLink asChild className="block truncate whitespace-nowrap">
                  <Link href={item.href} className="block truncate whitespace-nowrap">
                    {item.label}
                  </Link>
                </BreadcrumbLink>
              ) : (
                <BreadcrumbPage className="block truncate whitespace-nowrap">
                  {item.label}
                </BreadcrumbPage>
              )}
            </BreadcrumbItem>
          </Fragment>
        );
      })}
    </>
  );
}

export function PageBreadcrumbs({
  items,
  className,
  hideCurrentOnMobile = false,
}: PageBreadcrumbsProps) {
  if (items.length < 2) {
    return null;
  }

  const mobileItems = hideCurrentOnMobile ? items.slice(0, -1) : items;

  return (
    <Breadcrumb className={cn("mb-3 min-w-0 sm:mb-4", className)}>
      {hideCurrentOnMobile && mobileItems.length >= 2 ? (
        <BreadcrumbList className="max-w-full flex-nowrap gap-1 overflow-x-auto overscroll-x-contain [-ms-overflow-style:none] [scrollbar-width:none] sm:hidden [&::-webkit-scrollbar]:hidden">
          <BreadcrumbTrail items={mobileItems} />
        </BreadcrumbList>
      ) : null}
      <BreadcrumbList
        className={cn(
          "max-w-full flex-nowrap gap-1 overflow-x-auto overscroll-x-contain [-ms-overflow-style:none] [scrollbar-width:none] sm:gap-2.5 [&::-webkit-scrollbar]:hidden",
          hideCurrentOnMobile && mobileItems.length >= 2 && "hidden sm:flex",
        )}
      >
        <BreadcrumbTrail items={items} />
      </BreadcrumbList>
    </Breadcrumb>
  );
}
