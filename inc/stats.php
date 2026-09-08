<?php
/* =========================================================
   CHAR 2026 — statistiques d'audience
   Agrégats journaliers, sans cookie ni adresse IP conservée.
   ========================================================= */
declare(strict_types=1);

function stats_fichier(string $jour): string
{
    return DATA_DIR . '/stats-' . $jour . '.json';
}

/** Empreinte anonyme du visiteur, renouvelée chaque jour (non réversible). */
function stats_empreinte_visiteur(): string
{
    $sel = function_exists('secret') ? secret('sel_stats', 'sel-par-defaut-char2026') : 'sel-par-defaut-char2026';
    $brut = ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . gmdate('Y-m-d') . '|' . $sel;
    return substr(hash('sha256', $brut), 0, 16);
}

function stats_squelette(string $jour): array
{
    return [
        'date' => $jour, 'vues' => 0, 'soutiens' => 0,
        'visiteurs' => [], 'clics' => [], 'appareils' => [],
        'referents' => [], 'profondeur' => ['25' => 0, '50' => 0, '75' => 0, '100' => 0],
    ];
}

/**
 * Incrémente les compteurs du jour. Lecture-modification-écriture
 * sous verrou exclusif : plusieurs visiteurs simultanés ne peuvent
 * pas s'écraser mutuellement.
 */
function stats_enregistrer(array $ev): bool
{
    $jour = gmdate('Y-m-d');
    $f = stats_fichier($jour);
    $fh = @fopen($f, 'c+');
    if (!$fh) return false;
    if (!flock($fh, LOCK_EX)) { fclose($fh); return false; }

    $brut = stream_get_contents($fh);
    $d = $brut ? json_decode($brut, true) : null;
    if (!is_array($d)) $d = stats_squelette($jour);
    $d += stats_squelette($jour);

    $type = (string)($ev['t'] ?? '');
    $emp  = stats_empreinte_visiteur();

    if ($type === 'vue') {
        $d['vues']++;
        if (!in_array($emp, $d['visiteurs'], true)) $d['visiteurs'][] = $emp;

        $app = (string)($ev['d'] ?? 'inconnu');
        if (!in_array($app, ['mobile', 'tablette', 'bureau'], true)) $app = 'inconnu';
        $d['appareils'][$app] = ($d['appareils'][$app] ?? 0) + 1;

        $ref = (string)($ev['r'] ?? '');
        $ref = $ref === '' ? 'direct' : (parse_url($ref, PHP_URL_HOST) ?: 'direct');
        $ref = mb_substr(preg_replace('/[^a-z0-9.\-]/i', '', $ref) ?? 'direct', 0, 60);
        $d['referents'][$ref] = ($d['referents'][$ref] ?? 0) + 1;

    } elseif ($type === 'clic') {
        $id = mb_substr(preg_replace('/[^a-z0-9\-_]/i', '', (string)($ev['id'] ?? '')) ?? '', 0, 40);
        if ($id !== '') $d['clics'][$id] = ($d['clics'][$id] ?? 0) + 1;

    } elseif ($type === 'profondeur') {
        $s = (string)(int)($ev['s'] ?? 0);
        if (isset($d['profondeur'][$s])) $d['profondeur'][$s]++;

    } elseif ($type === 'soutien') {
        $d['soutiens']++;
    }

    // borne de sécurité : la liste des empreintes ne grossit pas sans fin
    if (count($d['visiteurs']) > 20000) $d['visiteurs'] = array_slice($d['visiteurs'], -20000);

    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($d, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}

/** Agrégat sur les N derniers jours, du plus ancien au plus récent. */
function stats_lire(int $jours = 30): array
{
    $series = [];
    $total = ['vues' => 0, 'visiteurs' => 0, 'soutiens' => 0];
    $clics = $appareils = $referents = [];
    $profondeur = ['25' => 0, '50' => 0, '75' => 0, '100' => 0];

    for ($i = $jours - 1; $i >= 0; $i--) {
        $jour = gmdate('Y-m-d', strtotime("-$i day"));
        $f = stats_fichier($jour);
        $d = is_readable($f) ? json_decode((string)file_get_contents($f), true) : null;
        if (!is_array($d)) $d = stats_squelette($jour);

        $nbVisiteurs = count($d['visiteurs'] ?? []);
        $series[] = [
            'date' => $jour,
            'vues' => (int)($d['vues'] ?? 0),
            'visiteurs' => $nbVisiteurs,
            'soutiens' => (int)($d['soutiens'] ?? 0),
        ];
        $total['vues'] += (int)($d['vues'] ?? 0);
        $total['visiteurs'] += $nbVisiteurs;
        $total['soutiens'] += (int)($d['soutiens'] ?? 0);

        foreach (($d['clics'] ?? []) as $k => $v)     $clics[$k] = ($clics[$k] ?? 0) + (int)$v;
        foreach (($d['appareils'] ?? []) as $k => $v) $appareils[$k] = ($appareils[$k] ?? 0) + (int)$v;
        foreach (($d['referents'] ?? []) as $k => $v) $referents[$k] = ($referents[$k] ?? 0) + (int)$v;
        foreach (($d['profondeur'] ?? []) as $k => $v) $profondeur[$k] = ($profondeur[$k] ?? 0) + (int)$v;
    }

    arsort($clics); arsort($referents); arsort($appareils);
    return compact('series', 'total', 'clics', 'appareils', 'referents', 'profondeur');
}
