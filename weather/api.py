"""Utilities for fetching weather data from free public APIs."""

from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from typing import Dict, Iterable, List, Tuple
import json
import urllib.error
import urllib.parse
import urllib.request


@dataclass
class Location:
    """Simple container describing a resolved location."""

    name: str
    latitude: float
    longitude: float


@dataclass
class CurrentWeather:
    """Current weather snapshot returned by the API."""

    time: datetime
    temperature_c: float
    wind_speed_kmh: float
    weather_code: int


@dataclass
class DailyForecast:
    """Summary forecast for a single day."""

    date: datetime
    temp_max_c: float
    temp_min_c: float
    precipitation_probability: float


@dataclass
class HourlyForecast:
    """Hourly forecast entry."""

    time: datetime
    temperature_c: float
    precipitation_probability: float


class WeatherAPIError(RuntimeError):
    """Raised when the remote API responds with an error."""


GEOCODING_ENDPOINT = "https://geocoding-api.open-meteo.com/v1/search"
FORECAST_ENDPOINT = "https://api.open-meteo.com/v1/forecast"


def _get_json(url: str) -> Dict:
    try:
        with urllib.request.urlopen(url) as response:  # type: ignore[arg-type]
            if response.status != 200:
                raise WeatherAPIError(f"API request failed with status {response.status}")
            return json.loads(response.read().decode("utf-8"))
    except urllib.error.URLError as exc:  # pragma: no cover - defensive
        raise WeatherAPIError("Nepodařilo se spojit se serverem Open-Meteo.") from exc


def geocode_city(city: str, country_code: str = "CZ", language: str = "cs") -> Location:
    """Resolve a city name to precise coordinates within the Czech Republic."""

    query = urllib.parse.urlencode(
        {
            "name": city,
            "count": 1,
            "language": language,
            "format": "json",
            "country_code": country_code,
        }
    )
    payload = _get_json(f"{GEOCODING_ENDPOINT}?{query}")
    results = payload.get("results") or []
    if not results:
        raise WeatherAPIError(f"Město '{city}' se nepodařilo najít.")
    record = results[0]
    return Location(
        name=record.get("name", city),
        latitude=float(record["latitude"]),
        longitude=float(record["longitude"]),
    )


def fetch_forecast(location: Location) -> Tuple[CurrentWeather, List[DailyForecast], List[HourlyForecast]]:
    """Fetch current, daily and hourly forecast for the given location."""

    query = urllib.parse.urlencode(
        {
            "latitude": location.latitude,
            "longitude": location.longitude,
            "current_weather": "true",
            "hourly": "temperature_2m,precipitation_probability",
            "daily": "temperature_2m_max,temperature_2m_min,precipitation_probability_max",
            "timezone": "auto",
        }
    )
    payload = _get_json(f"{FORECAST_ENDPOINT}?{query}")

    current_raw = payload.get("current_weather")
    if not current_raw:
        raise WeatherAPIError("Aktuální data nejsou k dispozici.")
    current = CurrentWeather(
        time=_parse_dt(current_raw["time"]),
        temperature_c=float(current_raw["temperature"]),
        wind_speed_kmh=float(current_raw["windspeed"]),
        weather_code=int(current_raw["weathercode"]),
    )

    daily = _build_daily(payload.get("daily", {}))
    hourly = _build_hourly(payload.get("hourly", {}), hours=12)
    return current, daily, hourly


def _parse_dt(value: str) -> datetime:
    return datetime.fromisoformat(value)


def _build_daily(raw: Dict[str, Iterable]) -> List[DailyForecast]:
    dates = [_parse_dt(date) for date in raw.get("time", [])]
    max_temps = _ensure_length(raw.get("temperature_2m_max", []), len(dates))
    min_temps = _ensure_length(raw.get("temperature_2m_min", []), len(dates))
    precipitation = _ensure_length(raw.get("precipitation_probability_max", []), len(dates))

    forecasts = []
    for idx, date in enumerate(dates):
        forecasts.append(
            DailyForecast(
                date=date,
                temp_max_c=float(max_temps[idx]),
                temp_min_c=float(min_temps[idx]),
                precipitation_probability=float(precipitation[idx] or 0),
            )
        )
    return forecasts


def _build_hourly(raw: Dict[str, Iterable], hours: int) -> List[HourlyForecast]:
    times = [_parse_dt(ts) for ts in raw.get("time", [])][:hours]
    temps = _ensure_length(raw.get("temperature_2m", []), len(times))
    precipitation = _ensure_length(raw.get("precipitation_probability", []), len(times))

    forecasts = []
    for idx, time in enumerate(times):
        forecasts.append(
            HourlyForecast(
                time=time,
                temperature_c=float(temps[idx]),
                precipitation_probability=float(precipitation[idx] or 0),
            )
        )
    return forecasts


def _ensure_length(values: Iterable, length: int) -> List:
    items = list(values)
    if len(items) < length:
        items.extend([0.0] * (length - len(items)))
    return items
