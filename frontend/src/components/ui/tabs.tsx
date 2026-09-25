"use client";

import * as React from "react";
import * as TabsPrimitive from "@radix-ui/react-tabs";

import { useIsHydrated } from "@/hooks/useIsHydrated";
import { cn } from "@/lib/utils";

type TabsSSRContextValue = {
  activeValue: string;
};

const TabsHydrationContext = React.createContext(true);
const TabsSSRContext = React.createContext<TabsSSRContextValue>({ activeValue: "" });

const triggerClassName =
  "inline-flex cursor-pointer items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50";

const Tabs = React.forwardRef<
  React.ElementRef<typeof TabsPrimitive.Root>,
  React.ComponentPropsWithoutRef<typeof TabsPrimitive.Root>
>(({ defaultValue, value, className, children, ...props }, ref) => {
  const hydrated = useIsHydrated();
  const activeValue = value ?? defaultValue ?? "";

  if (!hydrated) {
    return (
      <TabsHydrationContext.Provider value={false}>
        <TabsSSRContext.Provider value={{ activeValue }}>
          <div className={className} data-tabs-pre-hydration>
            {children}
          </div>
        </TabsSSRContext.Provider>
      </TabsHydrationContext.Provider>
    );
  }

  return (
    <TabsHydrationContext.Provider value={true}>
      <TabsPrimitive.Root
        ref={ref}
        defaultValue={defaultValue}
        value={value}
        className={className}
        {...props}
      >
        {children}
      </TabsPrimitive.Root>
    </TabsHydrationContext.Provider>
  );
});
Tabs.displayName = TabsPrimitive.Root.displayName;

const TabsList = React.forwardRef<
  React.ElementRef<typeof TabsPrimitive.List>,
  React.ComponentPropsWithoutRef<typeof TabsPrimitive.List>
>(({ className, children, ...props }, ref) => {
  const hydrated = React.useContext(TabsHydrationContext);

  if (!hydrated) {
    return (
      <div
        className={cn(
          "inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground",
          className,
        )}
      >
        {children}
      </div>
    );
  }

  return (
    <TabsPrimitive.List
      ref={ref}
      className={cn(
        "inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground",
        className,
      )}
      {...props}
    >
      {children}
    </TabsPrimitive.List>
  );
});
TabsList.displayName = TabsPrimitive.List.displayName;

const TabsTrigger = React.forwardRef<
  React.ElementRef<typeof TabsPrimitive.Trigger>,
  React.ComponentPropsWithoutRef<typeof TabsPrimitive.Trigger>
>(({ className, value, children, ...props }, ref) => {
  const hydrated = React.useContext(TabsHydrationContext);
  const { activeValue } = React.useContext(TabsSSRContext);
  const isActive = value === activeValue;

  if (!hydrated) {
    return (
      <div
        className={cn(
          triggerClassName,
          isActive && "bg-background text-foreground shadow-sm",
          className,
        )}
      >
        {children}
      </div>
    );
  }

  return (
    <TabsPrimitive.Trigger
      ref={ref}
      value={value}
      className={cn(
        triggerClassName,
        "data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm",
        className,
      )}
      {...props}
    >
      {children}
    </TabsPrimitive.Trigger>
  );
});
TabsTrigger.displayName = TabsPrimitive.Trigger.displayName;

const TabsContent = React.forwardRef<
  React.ElementRef<typeof TabsPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof TabsPrimitive.Content>
>(({ className, value, children, ...props }, ref) => {
  const hydrated = React.useContext(TabsHydrationContext);
  const { activeValue } = React.useContext(TabsSSRContext);

  if (!hydrated) {
    if (value !== activeValue) {
      return null;
    }

    return (
      <div
        className={cn(
          "mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2",
          className,
        )}
      >
        {children}
      </div>
    );
  }

  return (
    <TabsPrimitive.Content
      ref={ref}
      value={value}
      className={cn(
        "mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2",
        className,
      )}
      {...props}
    >
      {children}
    </TabsPrimitive.Content>
  );
});
TabsContent.displayName = TabsPrimitive.Content.displayName;

export { Tabs, TabsList, TabsTrigger, TabsContent };
