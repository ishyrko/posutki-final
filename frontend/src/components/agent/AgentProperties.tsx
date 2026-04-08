import { useState } from "react";
import { Search, Plus, Eye, MessageSquare, Edit, MoreVertical, Star, Archive, Trash2, Copy, Share2, Rocket, MapPin, LayoutGrid, LayoutList } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import Link from "next/link";
import { parseBynPrice, formatBynWithUsd } from "@/lib/currency";

const statusConfig: Record<string, { label: string; color: string }> = {
  active: { label: "Активен", color: "bg-green-500" },
  moderation: { label: "На модерации", color: "bg-yellow-500" },
  draft: { label: "Черновик", color: "bg-blue-500" },
  reserved: { label: "Забронирован", color: "bg-orange-500" },
  sold: { label: "Продан", color: "bg-red-500" },
  archived: { label: "Архив", color: "bg-gray-500" },
};

const properties = [
  { id: 1, image: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400", title: "2-комн. квартира, 54 м²", address: "ул. Притыцкого, 34, Минск", price: "150 000 BYN", views: 847, inquiries: 12, status: "active", vip: true, date: "15.01.2026", type: "apartment", area: "Фрунзенский" },
  { id: 2, image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=400", title: "3-комн. квартира, 85 м²", address: "пр. Независимости, 89, Минск", price: "210 000 BYN", views: 623, inquiries: 8, status: "active", vip: false, date: "20.01.2026", type: "apartment", area: "Центральный" },
  { id: 3, image: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=400", title: "1-комн. квартира, 38 м²", address: "ул. Ленина, 12, Минск", price: "85 000 BYN", views: 512, inquiries: 15, status: "active", vip: false, date: "25.01.2026", type: "apartment", area: "Ленинский" },
  { id: 4, image: "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=400", title: "Дом, 220 м²", address: "д. Боровляны, Минский р-н", price: "320 000 BYN", views: 445, inquiries: 6, status: "reserved", vip: false, date: "01.02.2026", type: "house", area: "Минский р-н" },
  { id: 5, image: "https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=400", title: "Офис, 120 м²", address: "ул. Немига, 5, Минск", price: "280 000 BYN", views: 398, inquiries: 4, status: "moderation", vip: false, date: "10.02.2026", type: "office", area: "Центральный" },
  { id: 6, image: "https://images.unsplash.com/photo-1600573472592-401b489a3cdc?w=400", title: "Студия, 28 м²", address: "ул. Каменногорская, 45, Минск", price: "62 000 BYN", views: 234, inquiries: 3, status: "draft", vip: false, date: "12.02.2026", type: "apartment", area: "Фрунзенский" },
  { id: 7, image: "https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=400", title: "4-комн. квартира, 120 м²", address: "ул. Голубева, 3, Минск", price: "350 000 BYN", views: 0, inquiries: 0, status: "sold", vip: false, date: "05.12.2025", type: "apartment", area: "Первомайский" },
];

const AgentProperties = () => {
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [typeFilter, setTypeFilter] = useState("all");
  const [viewMode, setViewMode] = useState<"grid" | "list">("list");
  const [openMenu, setOpenMenu] = useState<number | null>(null);

  const filtered = properties.filter((p) => {
    if (search && !p.title.toLowerCase().includes(search.toLowerCase()) && !p.address.toLowerCase().includes(search.toLowerCase())) return false;
    if (statusFilter !== "all" && p.status !== statusFilter) return false;
    if (typeFilter !== "all" && p.type !== typeFilter) return false;
    return true;
  });

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-display font-bold text-foreground">Мои объекты</h1>
          <p className="text-sm text-muted-foreground mt-1">{filtered.length} из {properties.length} объектов</p>
        </div>
        <Button asChild className="bg-gradient-primary text-primary-foreground border-0">
          <Link href="/razmestit/"><Plus className="w-4 h-4 mr-2" />Добавить объект</Link>
        </Button>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-3 mb-5">
        <div className="relative flex-1 min-w-[200px] max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <Input placeholder="Поиск по объектам..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9 h-10" />
        </div>
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-[160px] h-10"><SelectValue placeholder="Статус" /></SelectTrigger>
          <SelectContent className="bg-card border-border z-50">
            <SelectItem value="all">Все статусы</SelectItem>
            {Object.entries(statusConfig).map(([k, v]) => <SelectItem key={k} value={k}>{v.label}</SelectItem>)}
          </SelectContent>
        </Select>
        <Select value={typeFilter} onValueChange={setTypeFilter}>
          <SelectTrigger className="w-[160px] h-10"><SelectValue placeholder="Тип" /></SelectTrigger>
          <SelectContent className="bg-card border-border z-50">
            <SelectItem value="all">Все типы</SelectItem>
            <SelectItem value="apartment">Квартира</SelectItem>
            <SelectItem value="house">Дом</SelectItem>
            <SelectItem value="office">Офис</SelectItem>
          </SelectContent>
        </Select>
        <div className="ml-auto flex rounded-lg border border-border overflow-hidden">
          <button onClick={() => setViewMode("list")} className={`p-2 ${viewMode === "list" ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground"}`}><LayoutList className="w-4 h-4" /></button>
          <button onClick={() => setViewMode("grid")} className={`p-2 ${viewMode === "grid" ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground"}`}><LayoutGrid className="w-4 h-4" /></button>
        </div>
      </div>

      {/* Properties */}
      <div className={viewMode === "grid" ? "grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4" : "space-y-3"}>
        {filtered.map((prop) => {
          const st = statusConfig[prop.status];
          return viewMode === "list" ? (
            <div key={prop.id} className="flex flex-col sm:flex-row bg-card rounded-xl border border-border overflow-hidden shadow-card">
              <div className="relative sm:w-48 h-36 sm:h-auto flex-shrink-0">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={prop.image} alt={prop.title} className="w-full h-full object-cover" />
                {prop.vip && <span className="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-yellow-500 text-white text-[10px] font-bold">⭐ VIP</span>}
              </div>
              <div className="flex-1 p-4">
                <div className="flex items-start justify-between gap-2 mb-1">
                  <div>
                    <div className="flex items-center gap-2 mb-1">
                      <span className={`w-2 h-2 rounded-full ${st.color}`} />
                      <span className="text-xs text-muted-foreground">{st.label}</span>
                    </div>
                    <h3 className="font-semibold text-foreground">{prop.title}</h3>
                  </div>
                  <div className="text-right whitespace-nowrap">
                    <span className="text-lg font-bold text-foreground">{prop.price}</span>
                    <p className="text-xs text-muted-foreground">{formatBynWithUsd(parseBynPrice(prop.price)).usd}</p>
                  </div>
                </div>
                <p className="text-sm text-muted-foreground flex items-center gap-1 mb-2">
                  <MapPin className="w-3.5 h-3.5" />{prop.address}
                </p>
                <div className="flex items-center gap-4 text-xs text-muted-foreground mb-3">
                  <span className="flex items-center gap-1"><Eye className="w-3.5 h-3.5" />{prop.views}</span>
                  <span className="flex items-center gap-1"><MessageSquare className="w-3.5 h-3.5" />{prop.inquiries}</span>
                  <span>Опубликовано: {prop.date}</span>
                </div>
                <div className="flex items-center gap-2 pt-3 border-t border-border">
                  <Button variant="ghost" size="sm" asChild><Link href={`/edit/${prop.id}/`}><Edit className="w-3.5 h-3.5 mr-1" />Редактировать</Link></Button>
                  <Button variant="ghost" size="sm"><Eye className="w-3.5 h-3.5 mr-1" />Статистика</Button>
                  <div className="relative ml-auto">
                    <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => setOpenMenu(openMenu === prop.id ? null : prop.id)}>
                      <MoreVertical className="w-4 h-4" />
                    </Button>
                    {openMenu === prop.id && (
                      <div className="absolute right-0 top-full mt-1 w-48 bg-card rounded-lg border border-border shadow-card-hover z-50 py-1">
                        <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted"><Rocket className="w-3.5 h-3.5" />Поднять в топ</button>
                        <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted"><Star className="w-3.5 h-3.5" />Сделать VIP</button>
                        <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted"><Share2 className="w-3.5 h-3.5" />Поделиться</button>
                        <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted"><Copy className="w-3.5 h-3.5" />Дублировать</button>
                        <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted"><Archive className="w-3.5 h-3.5" />В архив</button>
                        <div className="border-t border-border my-1" />
                        <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-destructive hover:bg-muted"><Trash2 className="w-3.5 h-3.5" />Удалить</button>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <div key={prop.id} className="bg-card rounded-xl border border-border overflow-hidden shadow-card group">
              <div className="relative aspect-[4/3] overflow-hidden">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={prop.image} alt={prop.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                <div className="absolute top-2 left-2 flex items-center gap-1.5 px-2 py-1 rounded-full bg-card/90 backdrop-blur-sm">
                  <span className={`w-2 h-2 rounded-full ${st.color}`} /><span className="text-xs font-medium text-foreground">{st.label}</span>
                </div>
                {prop.vip && <span className="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-yellow-500 text-white text-[10px] font-bold">⭐ VIP</span>}
              </div>
              <div className="p-4">
                <h3 className="font-semibold text-foreground mb-0.5">{prop.price}</h3>
                <p className="text-xs text-muted-foreground mb-1">{formatBynWithUsd(parseBynPrice(prop.price)).usd}</p>
                <p className="text-sm text-foreground/80 mb-1">{prop.title}</p>
                <p className="text-xs text-muted-foreground flex items-center gap-1 mb-3"><MapPin className="w-3 h-3" />{prop.address}</p>
                <div className="flex items-center gap-3 text-xs text-muted-foreground pt-3 border-t border-border">
                  <span className="flex items-center gap-1"><Eye className="w-3 h-3" />{prop.views}</span>
                  <span className="flex items-center gap-1"><MessageSquare className="w-3 h-3" />{prop.inquiries}</span>
                  <Button variant="ghost" size="sm" className="ml-auto h-7 text-xs" asChild><Link href={`/edit/${prop.id}/`}><Edit className="w-3 h-3 mr-1" />Ред.</Link></Button>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default AgentProperties;
