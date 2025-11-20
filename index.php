<?php
$cities = [
    'praha' => ['label' => 'Praha', 'latitude' => 50.0755, 'longitude' => 14.4378],
    'brno' => ['label' => 'Brno', 'latitude' => 49.1951, 'longitude' => 16.6068],
    'ostrava' => ['label' => 'Ostrava', 'latitude' => 49.8209, 'longitude' => 18.2625],
    'plzen' => ['label' => 'Plzeň', 'latitude' => 49.7384, 'longitude' => 13.3736],
    'liberec' => ['label' => 'Liberec', 'latitude' => 50.7671, 'longitude' => 15.0562],
];

$languageOptions = [
    'cs' => 'Čeština',
    'en' => 'Angličtina',
];

$selectedCityKey = strtolower($_GET['city'] ?? 'praha');
$selectedCity = $cities[$selectedCityKey] ?? reset($cities);
$selectedLanguage = strtolower($_GET['lang'] ?? 'cs');
if (!array_key_exists($selectedLanguage, $languageOptions)) {
    $selectedLanguage = 'cs';
}

$forecast = null;
$errorMessage = null;

try {
    $forecast = fetchForecast($selectedCity['latitude'], $selectedCity['longitude'], $selectedLanguage);
} catch (RuntimeException $exception) {
    $errorMessage = $exception->getMessage();
}

function fetchForecast(float $latitude, float $longitude, string $language): array
{
    $query = http_build_query([
        'latitude' => $latitude,
        'longitude' => $longitude,
        'hourly' => 'temperature_2m,relativehumidity_2m,precipitation_probability',
        'daily' => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_sum',
        'current_weather' => true,
        'timezone' => 'Europe/Prague',
        'forecast_days' => 3,
        'language' => $language,
    ]);

    $url = 'https://api.open-meteo.com/v1/forecast?' . $query;

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FAILONERROR => true,
        CURLOPT_USERAGENT => 'Czech Weather Demo/1.0',
    ]);

    $response = curl_exec($curl);
    if ($response === false) {
        $message = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException('Nepodařilo se získat data z Open-Meteo: ' . ($message ?: 'neznámá chyba'));
    }

    $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($statusCode >= 400) {
        throw new RuntimeException('Open-Meteo vrátilo chybu HTTP ' . $statusCode . '.');
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Neplatná odpověď z Open-Meteo.');
    }

    return $data;
}

function formatHour(string $isoTime): string
{
    $timestamp = strtotime($isoTime);
    return $timestamp ? date('H:i', $timestamp) : $isoTime;
}

function formatDate(string $isoDate): string
{
    $timestamp = strtotime($isoDate);
    setlocale(LC_TIME, 'cs_CZ.UTF-8', 'cs_CZ', 'cs', 'Czech');
    if ($timestamp) {
        $formatted = strftime('%A %e. %B %Y', $timestamp);
        if ($formatted !== false && trim($formatted) !== '') {
            return mb_convert_case($formatted, MB_CASE_TITLE, 'UTF-8');
        }
        return date('l j. F Y', $timestamp);
    }
    return $isoDate;
}

function weatherIcon(int $code): string
{
    $icons = [
        0 => '☀️',
        1 => '🌤️',
        2 => '⛅',
        3 => '☁️',
        45 => '🌫️',
        48 => '🌫️',
        51 => '🌦️',
        53 => '🌦️',
        55 => '🌦️',
        56 => '🌧️',
        57 => '🌧️',
        61 => '🌧️',
        63 => '🌧️',
        65 => '🌧️',
        66 => '🌧️',
        67 => '🌧️',
        71 => '🌨️',
        73 => '🌨️',
        75 => '🌨️',
        77 => '🌨️',
        80 => '🌧️',
        81 => '🌧️',
        82 => '🌧️',
        85 => '❄️',
        86 => '❄️',
        95 => '⛈️',
        96 => '⛈️',
        99 => '⛈️',
    ];
    return $icons[$code] ?? '🌡️';
}

function weatherDescription(int $code, string $language): string
{
    $descriptions = [
        'cs' => [
            0 => 'Jasno',
            1 => 'Převážně jasno',
            2 => 'Částečně oblačno',
            3 => 'Zataženo',
            45 => 'Mlha',
            48 => 'Námrazová mlha',
            51 => 'Slabé mrholení',
            53 => 'Mírné mrholení',
            55 => 'Silné mrholení',
            56 => 'Slabé mrznoucí mrholení',
            57 => 'Silné mrznoucí mrholení',
            61 => 'Slabý déšť',
            63 => 'Mírný déšť',
            65 => 'Silný déšť',
            66 => 'Slabý mrznoucí déšť',
            67 => 'Silný mrznoucí déšť',
            71 => 'Slabé sněžení',
            73 => 'Mírné sněžení',
            75 => 'Silné sněžení',
            77 => 'Sněhové krupky',
            80 => 'Slabé přeháňky',
            81 => 'Mírné přeháňky',
            82 => 'Silné přeháňky',
            85 => 'Slabé sněhové přeháňky',
            86 => 'Silné sněhové přeháňky',
            95 => 'Bouřky',
            96 => 'Bouřky se slabým krupobitím',
            99 => 'Bouřky se silným krupobitím',
        ],
        'en' => [
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45 => 'Fog',
            48 => 'Depositing rime fog',
            51 => 'Light drizzle',
            53 => 'Moderate drizzle',
            55 => 'Dense drizzle',
            56 => 'Light freezing drizzle',
            57 => 'Dense freezing drizzle',
            61 => 'Slight rain',
            63 => 'Moderate rain',
            65 => 'Heavy rain',
            66 => 'Light freezing rain',
            67 => 'Heavy freezing rain',
            71 => 'Slight snowfall',
            73 => 'Moderate snowfall',
            75 => 'Heavy snowfall',
            77 => 'Snow grains',
            80 => 'Slight showers',
            81 => 'Moderate showers',
            82 => 'Violent showers',
            85 => 'Slight snow showers',
            86 => 'Heavy snow showers',
            95 => 'Thunderstorm',
            96 => 'Thunderstorm with slight hail',
            99 => 'Thunderstorm with heavy hail',
        ],
    ];

    $language = array_key_exists($language, $descriptions) ? $language : 'cs';
    return $descriptions[$language][$code] ?? ($language === 'cs' ? 'Neznámé počasí' : 'Unknown weather');
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Počasí v České republice</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" rel="stylesheet">
</head>
<body>
    <main class="page">
        <header class="hero">
            <h1>☁️ Česká předpověď počasí</h1>
            <p>Aktuální informace z Open-Meteo pro vybraná města. Zvolte si oblíbenou destinaci a jazyk rozhraní.</p>
        </header>
        <section class="controls">
            <form method="get" class="controls__form">
                <label>
                    <span>Město</span>
                    <select name="city">
                        <?php foreach ($cities as $key => $city): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === $selectedCityKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Jazyk</span>
                    <select name="lang">
                        <?php foreach ($languageOptions as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === $selectedLanguage ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Zobrazit předpověď</button>
            </form>
        </section>

        <?php if ($errorMessage): ?>
            <section class="error">
                <h2>😞 Omlouváme se</h2>
                <p><?= htmlspecialchars($errorMessage) ?></p>
                <p>Zkuste to prosím později nebo zkontrolujte své internetové připojení.</p>
            </section>
        <?php elseif ($forecast): ?>
            <?php $current = $forecast['current_weather'] ?? null; ?>
            <section class="current">
                <h2>Aktuálně v <?= htmlspecialchars($selectedCity['label']) ?></h2>
                <?php if ($current): ?>
                    <div class="current__card">
                        <div class="current__temp">
                            <?php $currentCode = (int) ($current['weathercode'] ?? 0); ?>
                            <span class="current__icon"><?= weatherIcon($currentCode) ?></span>
                            <span class="current__value"><?= round($current['temperature'] ?? 0, 1) ?>°C</span>
                        </div>
                        <p class="current__description"><?= htmlspecialchars(weatherDescription($currentCode, $selectedLanguage)) ?></p>
                        <dl>
                            <div>
                                <dt>Vítr</dt>
                                <dd><?= round($current['windspeed'] ?? 0, 1) ?> km/h</dd>
                            </div>
                            <div>
                                <dt>Směr větru</dt>
                                <dd><?= round($current['winddirection'] ?? 0) ?>°</dd>
                            </div>
                            <div>
                                <dt>Poslední aktualizace</dt>
                                <dd><?= htmlspecialchars($current['time'] ?? '') ?></dd>
                            </div>
                        </dl>
                    </div>
                <?php else: ?>
                    <p>Aktuální data nejsou k dispozici.</p>
                <?php endif; ?>
            </section>

            <?php
                $hourly = $forecast['hourly'] ?? [];
                $hourlyTimes = $hourly['time'] ?? [];
                $hourlyTemps = $hourly['temperature_2m'] ?? [];
                $hourlyHumidity = $hourly['relativehumidity_2m'] ?? [];
                $hourlyPrecipProb = $hourly['precipitation_probability'] ?? [];
                $hourlyItems = [];
                $limit = 12;
                for ($i = 0; $i < min($limit, count($hourlyTimes)); $i++) {
                    $hourlyItems[] = [
                        'time' => $hourlyTimes[$i],
                        'temperature' => $hourlyTemps[$i] ?? null,
                        'humidity' => $hourlyHumidity[$i] ?? null,
                        'precip' => $hourlyPrecipProb[$i] ?? null,
                    ];
                }
            ?>
            <section class="hourly">
                <h2>Výhled na dalších 12 hodin</h2>
                <?php if ($hourlyItems): ?>
                    <div class="hourly__grid">
                        <?php foreach ($hourlyItems as $item): ?>
                            <article>
                                <header><?= htmlspecialchars(formatHour($item['time'])) ?></header>
                                <p class="temp"><?= round($item['temperature'] ?? 0, 1) ?>°C</p>
                                <p>Vlhkost: <?= round($item['humidity'] ?? 0) ?>%</p>
                                <p>Pravděpodobnost srážek: <?= round($item['precip'] ?? 0) ?>%</p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Hodinová data nejsou k dispozici.</p>
                <?php endif; ?>
            </section>

            <?php
                $daily = $forecast['daily'] ?? [];
                $dailyTimes = $daily['time'] ?? [];
                $dailyMax = $daily['temperature_2m_max'] ?? [];
                $dailyMin = $daily['temperature_2m_min'] ?? [];
                $dailyPrecip = $daily['precipitation_sum'] ?? [];
                $dailyCodes = $daily['weathercode'] ?? [];
            ?>
            <section class="daily">
                <h2>Další dny</h2>
                <?php if ($dailyTimes): ?>
                    <div class="daily__cards">
                        <?php for ($i = 0; $i < count($dailyTimes); $i++): ?>
                            <article>
                                <h3><?= htmlspecialchars(formatDate($dailyTimes[$i])) ?></h3>
                                <?php $dayCode = (int) ($dailyCodes[$i] ?? 0); ?>
                                <p class="daily__icon"><?= weatherIcon($dayCode) ?></p>
                                <p class="daily__temps">
                                    <span class="max"><?= round($dailyMax[$i] ?? 0, 1) ?>°C</span>
                                    <span class="min"><?= round($dailyMin[$i] ?? 0, 1) ?>°C</span>
                                </p>
                                <p class="daily__description"><?= htmlspecialchars(weatherDescription($dayCode, $selectedLanguage)) ?></p>
                                <p>Srážky: <?= round($dailyPrecip[$i] ?? 0, 1) ?> mm</p>
                            </article>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <p>Denní data nejsou k dispozici.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <footer class="footer">
            <p>Data poskytuje <a href="https://open-meteo.com/" target="_blank" rel="noopener">Open-Meteo</a>. Projekt vytvořen pro demonstraci v PHP.</p>
        </footer>
    </main>
</body>
</html>
