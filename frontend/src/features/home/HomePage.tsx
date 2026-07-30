"use client";

import type { ReactNode } from "react";
import HeroSection from "@/components/HeroSection";
import FeaturedProperties from "@/components/FeaturedProperties";
import CitySection from "@/components/CitySection";
import RegionHouseSection from "@/components/RegionHouseSection";
import ArticlesSection from "@/components/ArticlesSection";
import type { Article } from "@/features/articles/types";
import type { PropertyListResponse } from "@/features/properties/types";

interface HomePageProps {
    featuredInitial?: PropertyListResponse;
    articles?: Article[];
    cityApartmentCounts?: Record<string, number>;
    regionHouseCounts?: Record<string, number>;
    features?: ReactNode;
}

export default function HomePage({
    featuredInitial,
    articles,
    cityApartmentCounts,
    regionHouseCounts,
    features,
}: HomePageProps) {
    return (
        <div className="min-h-screen">
            <main>
                <HeroSection />
                <CitySection apartmentCountsBySlug={cityApartmentCounts} />
                <FeaturedProperties featuredInitial={featuredInitial} />
                <RegionHouseSection houseCountsBySlug={regionHouseCounts} />
                {articles && articles.length > 0 ? <ArticlesSection articles={articles} /> : null}
                {features}
            </main>
        </div>
    );
}
