"use client";

import HeroSection from "@/components/HeroSection";
import FeaturedProperties from "@/components/FeaturedProperties";
import CategoriesSection from "@/components/CategoriesSection";
import ArticlesSection from "@/components/ArticlesSection";
import type { Article } from "@/features/articles/types";
import type { PropertyListResponse } from "@/features/properties/types";

interface HomePageProps {
    regionSlug?: string;
    articles?: Article[];
    featuredInitial?: PropertyListResponse;
}

export default function HomePage({ regionSlug, articles, featuredInitial }: HomePageProps) {
    return (
        <div className="min-h-screen">
            <main>
                <HeroSection regionSlug={regionSlug} />
                <FeaturedProperties regionSlug={regionSlug} featuredInitial={featuredInitial} />
                <CategoriesSection />
                <ArticlesSection articles={articles} />
            </main>
        </div>
    );
}
