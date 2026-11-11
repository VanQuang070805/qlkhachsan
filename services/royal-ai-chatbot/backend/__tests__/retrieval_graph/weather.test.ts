import { HumanMessage } from '@langchain/core/messages';
import { fetchWeather, isWeatherQuestion, weatherLocation } from '../../src/retrieval_graph/weather.js';

describe('weather grounding', () => {
  it('understands NYC and follow-up questions without using the hotel knowledge base', () => {
    expect(isWeatherQuestion('Thời tiết NYC hôm nay thế nào?')).toBe(true);
    expect(isWeatherQuestion('NYC ở đâu?')).toBe(false);
    expect(isWeatherQuestion('Tôi buồn vì NYC nhắn tin')).toBe(false);
    expect(weatherLocation('Thời tiết NYC hôm nay thế nào?')).toBe('New York, US');
    expect(weatherLocation('Còn thời tiết ở đó?', [new HumanMessage('NYC ở đâu?')])).toBe('New York, US');
    expect(weatherLocation('Thời tiết Hà Nội?')).toBe('Hanoi, Vietnam');
    expect(weatherLocation('Thời tiết Tokyo?')).toBe('Tokyo');
  });

  it('fetches a named location and measured forecast from fixed endpoints', async () => {
    const fetchMock = jest.spyOn(globalThis, 'fetch')
      .mockResolvedValueOnce({ ok: true, json: async () => ({ results: [{ name: 'New York', country: 'United States', latitude: 40.7, longitude: -74, timezone: 'America/New_York' }] }) } as Response)
      .mockResolvedValueOnce({ ok: true, json: async () => ({ timezone: 'America/New_York', current: { time: '2026-11-04T08:00', temperature_2m: 14, apparent_temperature: 12, relative_humidity_2m: 70, precipitation: 0, weather_code: 3 }, daily: { temperature_2m_max: [17], temperature_2m_min: [8], precipitation_probability_max: [20] } }) } as Response);
    try {
      const weather = await fetchWeather('New York, US');
      expect(weather?.temperatureC).toBe(14);
      expect(weather?.location).toBe('New York');
      expect(fetchMock).toHaveBeenCalledTimes(2);
      expect(String(fetchMock.mock.calls[0][0])).toContain('geocoding-api.open-meteo.com');
      expect(String(fetchMock.mock.calls[1][0])).toContain('api.open-meteo.com');
    } finally {
      fetchMock.mockRestore();
    }
  });
});
