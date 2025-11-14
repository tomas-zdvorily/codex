# Stylová česká předpověď počasí

Konzolová aplikace, která zobrazuje aktuální počasí a krátkodobou předpověď pro libovolné město v České republice. Data pocházejí z [Open-Meteo](https://open-meteo.com/), což je zcela zdarma a nevyžaduje registraci.

## Jak to funguje
- Zadané město se nejprve převede na přesné souřadnice pomocí Open-Meteo Geocoding API.
- Následně se stáhne aktuální stav, pět dní dopředu a detailní hodinová předpověď.
- Výstup je hezky formátovaný pomocí rámečků, emotikon a zvýraznění, aby byl přehledný a "cool".

## Požadavky
- Python 3.11+
- Připojení k internetu (pro stažení dat z API)

## Instalace a spuštění
```
python -m weather.cli Praha
```
Nebo například:
```
python -m weather.cli Brno --hours 6
```
Výchozí jazyk je čeština, ale můžete použít i například `--language en`.

## Ukázka výstupu
```
╔════════════════════════╗
║ Praha — stylová meteo ║
╚════════════════════════╝

╔═══════════════╗
║ Aktuální stav ║
╚═══════════════╝
☀️ Jasno
Teplota: 24.1 °C
Vítr: 11.0 km/h
Aktualizováno: 14.07.2024 15:00

╔═══════════════╗
║ Denní přehled ║
╚═══════════════╝
• Sobota 14.07: 16–27 °C, pravděpodobnost srážek 15%
...
```

## Poznámka k API
Open-Meteo omezuje pouze rychlost dotazů. Proto je aplikace vhodná pro osobní použití a nenáročné skripty.
