import { Property } from '../types';
import { MyPropertyCard } from './MyPropertyCard';
import { Button } from '@/components/ui/button';
import Link from 'next/link';
import { Plus } from 'lucide-react';

interface MyPropertyListProps {
    properties: Property[];
    isLoading?: boolean;
}

export function MyPropertyList({ properties, isLoading }: MyPropertyListProps) {
    if (isLoading) {
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {[...Array(3)].map((_, i) => (
                    <div key={i} className="h-[300px] w-full bg-muted animate-pulse rounded-lg" />
                ))}
            </div>
        );
    }

    if (properties.length === 0) {
        return (
            <div className="text-center py-12 border-2 border-dashed rounded-lg">
                <h3 className="text-lg font-semibold mb-2">No properties found</h3>
                <p className="text-muted-foreground mb-6">You haven&apos;t posted any properties yet.</p>
                <Button asChild>
                    <Link href="/razmestit/">
                        <Plus className="h-4 w-4 mr-2" />
                        Create New Property
                    </Link>
                </Button>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {properties.map((property) => (
                <MyPropertyCard key={property.id} property={property} />
            ))}
        </div>
    );
}
