# Stylová česká předpověď počasí (HTML + PHP)

Jednoduchá webová stránka v PHP, která zobrazuje aktuální počasí a krátkodobou předpověď pro vybraná města v České republice. Data získává z bezplatného rozhraní [Open-Meteo](https://open-meteo.com/) a výsledky prezentuje v moderním, responzivním designu.

## Funkce
- Aktuální stav včetně teploty, rychlosti a směru větru.
- Hodinová předpověď na dalších 12 hodin.
- Denní výhled na tři dny dopředu.
- Výběr z několika českých měst a možnost přepnutí jazyka rozhraní mezi češtinou a angličtinou.
- Přehledné, „cool“ rozhraní s vlastní grafikou.

## Požadavky
- PHP 8.1+
- Připojení k internetu (pro stažení dat z Open-Meteo)

## Lokální spuštění
1. Naklonujte repozitář a přejděte do jeho složky.
2. Spusťte vestavěný PHP server:
   ```bash
   php -S localhost:8000
   ```
3. Otevřete prohlížeč na adrese [http://localhost:8000](http://localhost:8000) a vyberte požadované město.

Pokud se nepodaří stáhnout data (např. kvůli omezenému přístupu k internetu v prostředí), aplikace na stránce zobrazí přehlednou chybovou zprávu.

## Struktura projektu
```
index.php           # Hlavní stránka s logikou volání API a vykreslením HTML
assets/styles.css   # Stylování rozhraní ve stylu moderní „glassmorphism“ karty
```

## Přizpůsobení
- V souboru `index.php` můžete rozšířit seznam měst (pole `$cities`).
- Pro změnu vzhledu upravte `assets/styles.css`.

## Licence dat
Data poskytuje Open-Meteo zdarma pro nekomerční použití. Dbejte na jejich [podmínky používání](https://open-meteo.com/en/features).
