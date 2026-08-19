"use client";

import type { ReactNode } from "react";
import HeroSection from "@/components/HeroSection";
import FeaturedProperties from "@/components/FeaturedProperties";
import CitySection from "@/components/CitySection";
import RegionHouseSection from "@/components/RegionHouseSection";
import ArticlesSection from "@/components/ArticlesSection";
import type { Article } from "@/features/articles/types";
import type { ApartmentCatalogCity } from "@/features/home/apartment-catalog-cities";
import type { PropertyListResponse } from "@/features/properties/types";

interface HomePageProps {
    featuredInitial?: PropertyListResponse;
    articles?: Article[];
    apartmentCatalogCities?: ApartmentCatalogCity[];
    cityApartmentCounts?: Record<string, number>;
    regionHouseCounts?: Record<string, number>;
    features?: ReactNode;
}

export default function HomePage({
    featuredInitial,
    articles,
    apartmentCatalogCities,
    cityApartmentCounts,
    regionHouseCounts,
    features,
}: HomePageProps) {
    return (
        <div className="min-h-screen">
            <main>
                <HeroSection />
                <CitySection
                    apartmentCatalogCities={apartmentCatalogCities}
                    apartmentCountsBySlug={cityApartmentCounts}
                />
                <FeaturedProperties featuredInitial={featuredInitial} />
                <RegionHouseSection houseCountsBySlug={regionHouseCounts} />
                {articles && articles.length > 0 ? <ArticlesSection articles={articles} /> : null}
                {features}
            </main>
        </div>
    );
}
