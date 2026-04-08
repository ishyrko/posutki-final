const BYN_TO_USD_RATE = 0.307; // 1 BYN ≈ 0.307 USD

export function formatBynWithUsd(priceByn: number): { byn: string; usd: string } {
  const byn = priceByn.toLocaleString("ru-BY") + " BYN";
  const usdValue = Math.round(priceByn * BYN_TO_USD_RATE);
  const usd = "≈ " + usdValue.toLocaleString("en-US") + " $";
  return { byn, usd };
}

export function parseBynPrice(priceStr: string): number {
  return parseInt(priceStr.replace(/[^\d]/g, ""), 10) || 0;
}
