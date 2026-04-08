import { useState } from "react";
import { ChevronLeft, ChevronRight, Plus, Phone, Clock, MapPin, User } from "lucide-react";
import { Button } from "@/components/ui/button";

const daysOfWeek = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"];

const showings: Record<string, { time: string; endTime: string; address: string; client: string; phone: string }[]> = {
  "2026-02-14": [
    { time: "09:00", endTime: "10:00", address: "ул. Притыцкого, 34, кв. 12", client: "Александр Иванов", phone: "+375 29 123-45-67" },
    { time: "14:00", endTime: "15:00", address: "ул. Ленина, 12, кв. 78", client: "Дмитрий Сидоров", phone: "+375 29 555-44-33" },
  ],
  "2026-02-15": [
    { time: "09:00", endTime: "10:00", address: "ул. Притыцкого, 34, кв. 12", client: "Александр Иванов", phone: "+375 29 123-45-67" },
    { time: "11:00", endTime: "12:00", address: "пр. Независимости, 89, кв. 45", client: "Мария Петрова", phone: "+375 29 987-65-43" },
    { time: "14:00", endTime: "15:00", address: "ул. Ленина, 12, кв. 78", client: "Дмитрий Сидоров", phone: "+375 29 555-44-33" },
  ],
  "2026-02-17": [
    { time: "10:00", endTime: "11:00", address: "д. Боровляны, ул. Центральная 5", client: "Елена Козлова", phone: "+375 33 222-11-00" },
  ],
  "2026-02-18": [
    { time: "11:00", endTime: "12:00", address: "ул. Немига, 5, оф. 301", client: "ООО «ПримаСтрой»", phone: "+375 17 222-33-44" },
    { time: "16:00", endTime: "17:00", address: "ул. Каменногорская, 45", client: "Андрей Новиков", phone: "+375 29 111-22-33" },
  ],
  "2026-02-21": [
    { time: "10:00", endTime: "11:30", address: "ул. Голубева, 3, кв. 89", client: "Ирина Волкова", phone: "+375 44 333-22-11" },
    { time: "15:00", endTime: "16:00", address: "ул. Притыцкого, 34, кв. 12", client: "Сергей Кузнецов", phone: "+375 29 444-55-66" },
  ],
};

function getDaysInMonth(year: number, month: number) {
  return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfWeek(year: number, month: number) {
  const d = new Date(year, month, 1).getDay();
  return d === 0 ? 6 : d - 1; // Mon=0
}

const monthNames = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];

const AgentCalendar = () => {
  const [year, setYear] = useState(2026);
  const [month, setMonth] = useState(1); // Feb
  const today = new Date(2026, 1, 14);
  const [selectedDate, setSelectedDate] = useState<string>("2026-02-15");

  const daysInMonth = getDaysInMonth(year, month);
  const firstDay = getFirstDayOfWeek(year, month);
  const days: (number | null)[] = [...Array(firstDay).fill(null), ...Array.from({ length: daysInMonth }, (_, i) => i + 1)];

  const dateKey = (day: number) => `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
  const selectedShowings = showings[selectedDate] ?? [];

  const prevMonth = () => { if (month === 0) { setMonth(11); setYear(year - 1); } else setMonth(month - 1); };
  const nextMonth = () => { if (month === 11) { setMonth(0); setYear(year + 1); } else setMonth(month + 1); };

  const selectedDateObj = new Date(selectedDate);
  const selectedLabel = `${daysOfWeek[selectedDateObj.getDay() === 0 ? 6 : selectedDateObj.getDay() - 1]}, ${selectedDateObj.getDate()} ${monthNames[selectedDateObj.getMonth()].toLowerCase()}`;

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-display font-bold text-foreground">Календарь показов</h1>
        <Button className="bg-gradient-primary text-primary-foreground border-0"><Plus className="w-4 h-4 mr-2" />Добавить показ</Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
        {/* Calendar */}
        <div className="lg:col-span-3 bg-card rounded-xl border border-border p-5 shadow-card">
          <div className="flex items-center justify-between mb-4">
            <button onClick={prevMonth} className="p-1.5 rounded-lg hover:bg-muted"><ChevronLeft className="w-4 h-4" /></button>
            <h3 className="font-semibold text-foreground">{monthNames[month]} {year}</h3>
            <button onClick={nextMonth} className="p-1.5 rounded-lg hover:bg-muted"><ChevronRight className="w-4 h-4" /></button>
          </div>

          <div className="grid grid-cols-7 gap-1">
            {daysOfWeek.map((d) => (
              <div key={d} className="text-center text-xs text-muted-foreground py-2 font-medium">{d}</div>
            ))}
            {days.map((day, i) => {
              if (!day) return <div key={`e${i}`} />;
              const dk = dateKey(day);
              const hasShowings = showings[dk];
              const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
              const isSelected = dk === selectedDate;
              return (
                <button
                  key={i}
                  onClick={() => setSelectedDate(dk)}
                  className={`relative aspect-square flex flex-col items-center justify-center rounded-lg text-sm transition-colors ${
                    isSelected ? "bg-primary text-primary-foreground" :
                    isToday ? "bg-primary/10 text-primary font-bold" :
                    "hover:bg-muted text-foreground"
                  }`}
                >
                  {day}
                  {hasShowings && (
                    <div className="flex gap-0.5 mt-0.5">
                      {hasShowings.slice(0, 3).map((_, j) => (
                        <span key={j} className={`w-1 h-1 rounded-full ${isSelected ? "bg-primary-foreground" : "bg-primary"}`} />
                      ))}
                    </div>
                  )}
                </button>
              );
            })}
          </div>
        </div>

        {/* Day detail */}
        <div className="lg:col-span-2 bg-card rounded-xl border border-border p-5 shadow-card">
          <h3 className="font-semibold text-foreground capitalize mb-4">{selectedLabel}</h3>

          {selectedShowings.length > 0 ? (
            <div className="space-y-3">
              {selectedShowings.map((s, i) => (
                <div key={i} className="p-3 rounded-lg border border-border hover:border-primary/30 transition-colors">
                  <div className="flex items-center gap-2 mb-2">
                    <Clock className="w-3.5 h-3.5 text-primary" />
                    <span className="text-sm font-medium text-foreground">{s.time} — {s.endTime}</span>
                  </div>
                  <p className="text-sm text-muted-foreground flex items-center gap-1.5 mb-1">
                    <MapPin className="w-3.5 h-3.5 flex-shrink-0" />{s.address}
                  </p>
                  <p className="text-sm text-muted-foreground flex items-center gap-1.5 mb-3">
                    <User className="w-3.5 h-3.5 flex-shrink-0" />{s.client}
                  </p>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm" className="h-7 text-xs"><Phone className="w-3 h-3 mr-1" />Позвонить</Button>
                    <Button variant="ghost" size="sm" className="h-7 text-xs">Отменить</Button>
                    <Button variant="ghost" size="sm" className="h-7 text-xs">Перенести</Button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-10 text-muted-foreground text-sm">
              <p className="mb-3">Нет показов на этот день</p>
              <Button variant="outline" size="sm"><Plus className="w-3.5 h-3.5 mr-1" />Добавить показ</Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AgentCalendar;
