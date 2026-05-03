import { useState } from "react";
import { Camera, Star, Phone, Mail, Briefcase, Globe, Plus, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";

const reviews = [
  { name: "Александр И.", rating: 5, text: "Отличный специалист! Помог быстро продать квартиру по хорошей цене. Рекомендую!", date: "15.01.2026" },
  { name: "Мария П.", rating: 5, text: "Очень профессиональный подход. Внимательно выслушал все пожелания и подобрал идеальный вариант.", date: "28.12.2025" },
  { name: "Дмитрий С.", rating: 4, text: "Хороший агент, но показ немного задержался. В целом доволен результатом.", date: "10.12.2025" },
];

const AgentProfile = () => {
  const [tags, setTags] = useState(["Квартиры", "Центр города", "Посуточно"]);
  const [newTag, setNewTag] = useState("");

  const addTag = () => {
    if (newTag.trim() && tags.length < 5) {
      setTags([...tags, newTag.trim()]);
      setNewTag("");
    }
  };

  return (
    <div className="max-w-3xl">
      <h1 className="text-2xl font-display font-bold text-foreground mb-6">Профиль агента</h1>

      {/* Public preview */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
        <div className="flex items-start gap-4 mb-4">
          <div className="relative">
            <div className="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center text-primary text-2xl font-bold">
              ИП
            </div>
            <button className="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-primary text-primary-foreground flex items-center justify-center shadow-primary">
              <Camera className="w-3.5 h-3.5" />
            </button>
          </div>
          <div className="flex-1">
            <h3 className="font-semibold text-foreground text-lg">Иван Петров</h3>
            <div className="flex items-center gap-1 text-yellow-500 mb-1">
              {Array.from({ length: 5 }).map((_, i) => <Star key={i} className={`w-3.5 h-3.5 ${i < 5 ? "fill-current" : ""}`} />)}
              <span className="text-sm text-foreground ml-1 font-medium">4.8</span>
              <span className="text-xs text-muted-foreground ml-1">(24 отзыва)</span>
            </div>
            <p className="text-xs text-green-600 font-medium">✓ Проверенный агент</p>
            <div className="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
              <span>📊 На сайте с 2023</span>
              <span>🏢 24 объекта</span>
              <span>✅ 156 сделок</span>
            </div>
          </div>
        </div>
        <div className="flex flex-wrap gap-1.5 mb-3">
          {tags.map((tag) => (
            <span key={tag} className="px-2.5 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium">{tag}</span>
          ))}
        </div>
      </div>

      {/* Edit form */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
        <h3 className="font-semibold text-foreground mb-4">Личная информация</h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
          <div>
            <label className="text-sm text-muted-foreground mb-1.5 block">Имя</label>
            <Input defaultValue="Иван" />
          </div>
          <div>
            <label className="text-sm text-muted-foreground mb-1.5 block">Фамилия</label>
            <Input defaultValue="Петров" />
          </div>
          <div>
            <label className="text-sm text-muted-foreground mb-1.5 block flex items-center gap-1.5"><Phone className="w-3.5 h-3.5" />Телефон</label>
            <Input defaultValue="+375 29 123-45-67" />
          </div>
          <div>
            <label className="text-sm text-muted-foreground mb-1.5 block flex items-center gap-1.5"><Mail className="w-3.5 h-3.5" />Email</label>
            <Input defaultValue="ivan.petrov@realty.by" />
          </div>
        </div>

        <h4 className="font-medium text-foreground mb-3 flex items-center gap-1.5"><Briefcase className="w-4 h-4" />Агентство</h4>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
          <div>
            <label className="text-sm text-muted-foreground mb-1.5 block">Название</label>
            <Input defaultValue="Премиум Недвижимость" />
          </div>
          <div>
            <label className="text-sm text-muted-foreground mb-1.5 block">Должность</label>
            <Input defaultValue="Старший риэлтор" />
          </div>
          <div className="sm:col-span-2">
            <label className="text-sm text-muted-foreground mb-1.5 block flex items-center gap-1.5"><Globe className="w-3.5 h-3.5" />Сайт</label>
            <Input defaultValue="https://premium-realty.by" />
          </div>
        </div>

        <div className="mb-4">
          <label className="text-sm text-muted-foreground mb-1.5 block">О себе</label>
          <Textarea defaultValue="Профессиональный риэлтор с опытом работы более 5 лет. Специализируюсь на продаже квартир в центре Минска. Помогу подобрать идеальный вариант!" rows={3} />
        </div>

        <div className="mb-4">
          <label className="text-sm text-muted-foreground mb-1.5 block">Специализация (до 5 тегов)</label>
          <div className="flex flex-wrap gap-1.5 mb-2">
            {tags.map((tag) => (
              <span key={tag} className="flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium">
                {tag}
                <button onClick={() => setTags(tags.filter((t) => t !== tag))} className="hover:text-destructive"><X className="w-3 h-3" /></button>
              </span>
            ))}
          </div>
          {tags.length < 5 && (
            <div className="flex gap-2">
              <Input placeholder="Новый тег..." value={newTag} onChange={(e) => setNewTag(e.target.value)} className="max-w-xs" onKeyDown={(e) => e.key === "Enter" && addTag()} />
              <Button variant="outline" size="sm" onClick={addTag}><Plus className="w-3.5 h-3.5" /></Button>
            </div>
          )}
        </div>

        <h4 className="font-medium text-foreground mb-3">Социальные сети</h4>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
          <Input placeholder="Например: @username" defaultValue="@ivan_petrov" />
          <Input placeholder="Например: +375 29 123-45-67" defaultValue="+375 29 123-45-67" />
          <Input placeholder="Например: @account" />
          <Input placeholder="Например: ссылка на страницу" />
        </div>

        <h4 className="font-medium text-foreground mb-3">Приватность</h4>
        <div className="space-y-3 mb-4">
          {[
            { label: "Показывать телефон в профиле", defaultOn: true },
            { label: "Показывать email в профиле", defaultOn: true },
            { label: "Разрешить отзывы", defaultOn: true },
            { label: "Показывать количество сделок", defaultOn: false },
          ].map((item) => (
            <div key={item.label} className="flex items-center justify-between">
              <span className="text-sm text-foreground">{item.label}</span>
              <Switch defaultChecked={item.defaultOn} />
            </div>
          ))}
        </div>

        <div className="flex justify-end">
          <Button className="bg-gradient-primary text-primary-foreground border-0">Сохранить изменения</Button>
        </div>
      </div>

      {/* Reviews */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card">
        <h3 className="font-semibold text-foreground mb-4">Отзывы ({reviews.length})</h3>
        <div className="space-y-4">
          {reviews.map((r, i) => (
            <div key={i} className="p-4 rounded-lg border border-border">
              <div className="flex items-center gap-2 mb-2">
                <div className="flex text-yellow-500">
                  {Array.from({ length: 5 }).map((_, j) => <Star key={j} className={`w-3 h-3 ${j < r.rating ? "fill-current" : ""}`} />)}
                </div>
                <span className="text-sm font-medium text-foreground">{r.name}</span>
                <span className="text-xs text-muted-foreground ml-auto">{r.date}</span>
              </div>
              <p className="text-sm text-foreground/80">{r.text}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default AgentProfile;
