"use client";

import HeroSection from "@/components/HeroSection";
import FeaturedProperties from "@/components/FeaturedProperties";
import CitySection from "@/components/CitySection";
import ArticlesSection from "@/components/ArticlesSection";
import FeaturesSection from "@/components/FeaturesSection";
import type { Article } from "@/features/articles/types";
import type { PropertyListResponse } from "@/features/properties/types";

interface HomePageProps {
    featuredInitial?: PropertyListResponse;
    articles?: Article[];
}

export default function HomePage({ featuredInitial, articles }: HomePageProps) {
    return (
        <div className="min-h-screen">
            <main>
                <HeroSection />
                <CitySection />
                <FeaturedProperties featuredInitial={featuredInitial} />
                {articles && articles.length > 0 ? <ArticlesSection articles={articles} /> : null}
                <FeaturesSection />
            </main>
        </div>
    );
}
