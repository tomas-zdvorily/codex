"""Command line interface for the Czech weather forecast application."""

from __future__ import annotations

import argparse
import textwrap

from . import api


WEATHER_EMOJIS = {
    0: "☀️ Jasno",
    1: "🌤️ Převážně jasno",
    2: "⛅ Polojasno",
    3: "☁️ Zataženo",
    45: "🌫️ Mlha",
    48: "🌫️ Mrznoucí mlha",
    51: "🌦️ Mrholení",
    61: "🌧️ Déšť",
    71: "🌨️ Sníh",
    80: "🌧️ Přeháňky",
    95: "⛈️ Bouřky",
}


def parse_args(argv: list[str] | None = None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Stylová předpověď počasí pro Českou republiku",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    parser.add_argument(
        "city",
        nargs="?",
        default="Praha",
        help="Město, pro které chcete zobrazit předpověď",
    )
    parser.add_argument(
        "--language",
        "-l",
        default="cs",
        help="Jazyk výsledků",
    )
    parser.add_argument(
        "--hours",
        type=int,
        default=12,
        help="Počet hodin pro detailní hodinovou předpověď",
    )
    return parser.parse_args(argv)


def _headline(text: str) -> str:
    border = "═" * (len(text) + 2)
    return f"╔{border}╗\n║ {text} ║\n╚{border}╝"


def _format_current(current: api.CurrentWeather) -> str:
    label = WEATHER_EMOJIS.get(current.weather_code, "ℹ️ Počasí")
    lines = [
        _headline("Aktuální stav"),
        f"{label}",
        f"Teplota: {current.temperature_c:.1f} °C",
        f"Vítr: {current.wind_speed_kmh:.1f} km/h",
        f"Aktualizováno: {current.time.strftime('%d.%m.%Y %H:%M')}",
    ]
    return "\n".join(lines)


def _format_daily(daily: list[api.DailyForecast]) -> str:
    lines = [_headline("Denní přehled")]
    for day in daily[:5]:
        date = day.date.strftime("%A %d.%m").capitalize()
        lines.append(
            f"• {date}: {day.temp_min_c:.0f}–{day.temp_max_c:.0f} °C, pravděpodobnost srážek {day.precipitation_probability:.0f}%"
        )
    return "\n".join(lines)


def _format_hourly(hourly: list[api.HourlyForecast], hours: int) -> str:
    lines = [_headline("Hodinová předpověď"), "Čas  | Teplota | Srážky"]
    lines.append("""-----┼---------┼--------""")
    for entry in hourly[:hours]:
        lines.append(
            f"{entry.time.strftime('%H:%M')} | {entry.temperature_c:>6.1f}°C | {entry.precipitation_probability:>3.0f}%"
        )
    return "\n".join(lines)


def run(argv: list[str] | None = None) -> int:
    args = parse_args(argv)
    try:
        location = api.geocode_city(args.city, language=args.language)
        current, daily, hourly = api.fetch_forecast(location)
    except api.WeatherAPIError as exc:
        print(f"❌ {exc}")
        return 1

    print(_headline(f"{location.name} — stylová meteo"))
    print()
    print(_format_current(current))
    print()
    print(_format_daily(daily))
    print()
    print(_format_hourly(hourly, args.hours))
    print()
    print(textwrap.fill(
        "Zdroj dat: Open-Meteo.com (zdarma a bez registrace)",
        width=80,
        initial_indent="ℹ️ ",
        subsequent_indent="    ",
    ))
    return 0


if __name__ == "__main__":
    raise SystemExit(run())
