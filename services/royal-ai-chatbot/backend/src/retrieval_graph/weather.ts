import { BaseMessage, HumanMessage } from '@langchain/core/messages';

const WEATHER_INTENT = /weather|forecast|temperature|\brain\b|thời tiết|thoi tiet|nhiệt độ|nhiet do|mưa|\bmua\b/i;
const NYC = /\b(nyc|new york city|new york)\b/i;
const HANOI = /hà nội|ha noi|\bhanoi\b|\brosaliza\b/i;
const SAIGON = /hồ chí minh|ho chi minh|sài gòn|sai gon|\b(saigon|hcmc)\b/i;

export function isWeatherQuestion(query: string): boolean {
  return WEATHER_INTENT.test(query);
}

export function weatherLocation(query: string, history: BaseMessage[] = []): string {
  const recentPlace = [...history].reverse()
    .filter((message) => message instanceof HumanMessage)
    .map((message) => String(message.content || ''))
    .find((message) => NYC.test(message) || HANOI.test(message) || SAIGON.test(message)) || '';
  const context = NYC.test(query) || HANOI.test(query) || SAIGON.test(query) ? query : recentPlace;
  if (NYC.test(context)) return 'New York, US';
  if (SAIGON.test(context)) return 'Ho Chi Minh City, Vietnam';
  if (HANOI.test(context)) return 'Hanoi, Vietnam';

  const place = query
    .replace(/thời tiết|thoi tiet|dự báo|du bao|nhiệt độ|nhiet do|weather|forecast|temperature|hôm nay|hom nay|ngày mai|ngay mai|bây giờ|bay gio|hiện tại|hien tai|bao nhiêu|bao nhieu|như thế nào|nhu the nao|\b(có|không|khong|tại|tai|cho|thì|thi|thế|the|nào|nao|mua|sao|vậy|vay)\b|ở|mưa/gi, ' ')
    .replace(/[?!.,]/g, ' ').replace(/\s+/g, ' ').trim();
  return place.length >= 2 && place.length <= 80 ? place : 'Hanoi, Vietnam';
}

type WeatherSnapshot = {
  location: string;
  country: string;
  timezone: string;
  observedAt: string;
  temperatureC: number;
  apparentTemperatureC: number;
  humidityPercent: number;
  precipitationMm: number;
  weatherCode: number;
  todayHighC: number;
  todayLowC: number;
  todayPrecipitationProbabilityPercent: number;
};

const weatherCache = new Map<string, { expires: number; value: WeatherSnapshot }>();

export async function fetchWeather(place: string): Promise<WeatherSnapshot | null> {
  const key = place.toLowerCase();
  const cached = weatherCache.get(key);
  if (cached && cached.expires > Date.now()) return cached.value;

  const geocodeUrl = new URL('https://geocoding-api.open-meteo.com/v1/search');
  geocodeUrl.searchParams.set('name', place);
  geocodeUrl.searchParams.set('count', '1');
  geocodeUrl.searchParams.set('language', 'en');
  const geoResponse = await fetch(geocodeUrl, { signal: AbortSignal.timeout(6000) });
  if (!geoResponse.ok) throw new Error('Weather geocoding unavailable');
  const geo = await geoResponse.json() as { results?: Array<{ name: string; country: string; latitude: number; longitude: number; timezone: string }> };
  const location = geo.results?.[0];
  if (!location || !Number.isFinite(location.latitude) || !Number.isFinite(location.longitude)) return null;

  const forecastUrl = new URL('https://api.open-meteo.com/v1/forecast');
  forecastUrl.searchParams.set('latitude', String(location.latitude));
  forecastUrl.searchParams.set('longitude', String(location.longitude));
  forecastUrl.searchParams.set('current', 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code');
  forecastUrl.searchParams.set('daily', 'temperature_2m_max,temperature_2m_min,precipitation_probability_max');
  forecastUrl.searchParams.set('forecast_days', '1');
  forecastUrl.searchParams.set('timezone', 'auto');
  const forecastResponse = await fetch(forecastUrl, { signal: AbortSignal.timeout(6000) });
  if (!forecastResponse.ok) throw new Error('Weather forecast unavailable');
  const forecast = await forecastResponse.json() as {
    timezone?: string;
    current?: { time?: string; temperature_2m?: number; apparent_temperature?: number; relative_humidity_2m?: number; precipitation?: number; weather_code?: number };
    daily?: { temperature_2m_max?: number[]; temperature_2m_min?: number[]; precipitation_probability_max?: number[] };
  };
  const current = forecast.current;
  if (!current || !Number.isFinite(current.temperature_2m)) throw new Error('Weather data incomplete');
  const value: WeatherSnapshot = {
    location: location.name,
    country: location.country,
    timezone: forecast.timezone || location.timezone,
    observedAt: current.time || '',
    temperatureC: current.temperature_2m as number,
    apparentTemperatureC: current.apparent_temperature ?? current.temperature_2m as number,
    humidityPercent: current.relative_humidity_2m ?? 0,
    precipitationMm: current.precipitation ?? 0,
    weatherCode: current.weather_code ?? -1,
    todayHighC: forecast.daily?.temperature_2m_max?.[0] ?? 0,
    todayLowC: forecast.daily?.temperature_2m_min?.[0] ?? 0,
    todayPrecipitationProbabilityPercent: forecast.daily?.precipitation_probability_max?.[0] ?? 0,
  };
  for (const [storedKey, entry] of weatherCache) {
    if (entry.expires <= Date.now()) weatherCache.delete(storedKey);
  }
  if (weatherCache.size >= 200) {
    const oldest = weatherCache.keys().next().value;
    if (oldest) weatherCache.delete(oldest);
  }
  weatherCache.set(key, { expires: Date.now() + 10 * 60_000, value });
  return value;
}
