import api from "@/lib/api";

export async function fetchHomeCityApartmentCounts(): Promise<Record<string, number>> {
  try {
    const response = await api.get<{ data: Record<string, number> }>(
      "/properties/home-city-apartment-counts",
    );
    return response.data.data ?? {};
  } catch {
    return {};
  }
}

export function formatApartmentCount(count: number): string {
  const mod10 = count % 10;
  const mod100 = count % 100;
  if (mod10 === 1 && mod100 !== 11) {
    return `${count} квартира`;
  }
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) {
    return `${count} квартиры`;
  }
  return `${count} квартир`;
}
