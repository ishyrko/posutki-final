'use client';

import { useUser } from '@/features/auth/hooks';
import { Button } from '@/components/ui/button';
import { Search, Bell } from 'lucide-react';
import { UserAvatar } from '@/components/UserAvatar';

export function Header() {
    const { data: user } = useUser();

    return (
        <header className="h-20 border-b border-border/50 bg-card/50 backdrop-blur-xl px-8 flex items-center justify-between sticky top-0 z-30">
            <div className="flex-1 max-w-xl">
                <div className="relative group">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                    <input 
                        type="text" 
                        placeholder="Поиск по кабинету..."
                        className="w-full bg-muted/50 border-none rounded-xl h-11 pl-10 pr-4 text-sm focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                    />
                </div>
            </div>

            <div className="flex items-center gap-4">
                <Button variant="ghost" size="icon" className="rounded-xl relative text-muted-foreground hover:text-foreground">
                    <Bell className="h-5 w-5" />
                    <span className="absolute top-2.5 right-2.5 h-2 w-2 bg-primary rounded-full border-2 border-card" />
                </Button>

                <div className="h-8 w-[1px] bg-border/50 mx-2" />

                <div className="flex items-center gap-3 pl-2">
                    <div className="text-right hidden sm:block">
                        <div className="text-sm font-bold text-foreground leading-none mb-1">
                            {user?.firstName} {user?.lastName}
                        </div>
                        <div className="text-[10px] font-medium text-muted-foreground uppercase tracking-widest">
                            {user?.email}
                        </div>
                    </div>
                    <UserAvatar
                        user={user}
                        className="h-10 w-10 text-sm border-border/50"
                        fallbackClassName="bg-muted text-muted-foreground"
                    />
                </div>
            </div>
        </header>
    );
}
