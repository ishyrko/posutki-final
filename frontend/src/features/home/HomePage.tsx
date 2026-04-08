"use client";

import HeroSection from "@/components/HeroSection";
import FeaturedProperties from "@/components/FeaturedProperties";
import CitySection from "@/components/CitySection";
import FeaturesSection from "@/components/FeaturesSection";
import type { PropertyListResponse } from "@/features/properties/types";

interface HomePageProps {
    regionSlug?: string;
    featuredInitial?: PropertyListResponse;
}

export default function HomePage({ regionSlug, featuredInitial }: HomePageProps) {
    return (
        <div className="min-h-screen">
            <main>
                <HeroSection regionSlug={regionSlug} />
                <CitySection />
                <FeaturedProperties regionSlug={regionSlug} featuredInitial={featuredInitial} />
                <FeaturesSection />
            </main>
        </div>
    );
}
