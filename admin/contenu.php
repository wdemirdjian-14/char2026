<?php
declare(strict_types=1);
$page = 'contenu'; $titre = 'Textes du site';
require __DIR__ . '/entete.php';
require_once __DIR__ . '/../inc/contenu.php';

$message = ''; $classe = '';

/* =========================================================
   Fusion des champs envoyés dans la structure existante.
   Le type d'origine est préservé (entier, booléen, texte),
   et le HTML est nettoyé de tout ce qui peut exécuter du code.
   ========================================================= */
function fusionner($origine, $envoye)
{
    if (is_array($origine) && !empty($origine) && array_is_list($origine)) {

        // Liste d'objets (candidats, priorités, valeurs…) : on fusionne
        // index par index et on ne supprime JAMAIS d'entrée. Un envoi
        // incomplet ne peut donc pas amputer la liste.
        if (is_array($origine[0] ?? null)) {
            $sortie = $origine;
            foreach ((array)$envoye as $i => $v) {
                if (!is_numeric($i)) continue;
                $i = (int)$i;
                if (isset($origine[$i])) $sortie[$i] = fusionner($origine[$i], $v);
            }
            return $sortie;
        }

        // Liste de textes : le formulaire permet d'ajouter et de retirer des
        // lignes, on la reconstruit donc — mais seulement s'il l'a bien
        // rendue (marqueur __rendu). Sans marqueur, on garde l'original.
        if (!is_array($envoye) || empty($envoye['__rendu'])) return $origine;
        $sortie = [];
        foreach ($envoye as $i => $v) {
            if (!is_numeric($i)) continue;
            $val = nettoyer_html((string)$v);
            if (trim($val) === '') continue;      // ligne vidée = supprimée
            $sortie[] = $val;
        }
        return $sortie;
    }
    if (is_array($origine)) {
        $sortie = $origine;
        foreach ($origine as $k => $v) {
            if (is_array($envoye) && array_key_exists($k, $envoye)) {
                $sortie[$k] = fusionner($v, $envoye[$k]);
            }
        }
        return $sortie;
    }
    if (is_bool($origine))  return (bool)$envoye;
    if (is_int($origine))   return (int)$envoye;
    if (is_float($origine)) return (float)$envoye;
    return nettoyer_html((string)$envoye);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verifier()) {
        $message = "Jeton de sécurité invalide, rien n'a été enregistré."; $classe = 'err';
    } else {
        $avant = contenu();
        // sauvegarde horodatée avant écriture
        @copy(CONTENU_FICHIER, DATA_DIR . '/contenu-' . date('Ymd-His') . '.json');
        $apres = fusionner($avant, $_POST['champs'] ?? []);
        if (enregistrer_contenu($apres)) {
            $message = "Textes enregistrés. Les modifications sont en ligne immédiatement."; $classe = 'ok';
            header('Location: contenu.php?ok=1'); exit;
        }
        $message = "Échec de l'enregistrement : vérifiez les droits d'écriture sur contenu.json."; $classe = 'err';
    }
}
if (isset($_GET['ok'])) { $message = "Textes enregistrés. Les modifications sont en ligne immédiatement."; $classe = 'ok'; }

$data = contenu();

$titresBlocs = [
  'meta' => 'Référencement (titre et description Google)',
  'site' => 'Adresse officielle du site',
  'marque' => 'Nom du club',
  'hero' => "Écran d'accueil",
  'compteur' => "Tableau d'affichage des soutiens",
  'bandeau_defilant' => 'Bandeau jaune défilant',
  'engagement' => 'Section « Notre engagement »',
  'valeurs' => 'Section « Valeurs C·H·A·R »',
  'projet' => 'Section « 10 priorités »',
  'equipe' => 'Section « L\'équipe »',
  'soutien' => 'Section « Formulaire de soutien »',
  'liste' => 'Section « Notre liste » (les 13 candidats)',
  'finale' => 'Bandeau jaune de fin',
  'pied' => 'Pied de page',
];
$libelles = [
  'titre' => 'Titre', 'description' => 'Description',
  'domaine' => 'Adresse encodée dans le QR code', 'sigle' => 'Sigle', 'nom' => 'Nom complet',
  'bandeau_court' => 'Badge (version mobile)', 'bandeau_long' => 'Badge (version ordinateur)',
  'bandeau_date' => 'Date affichée dans le badge',
  'titre_ligne1' => 'Titre — ligne 1', 'titre_ligne2' => 'Titre — ligne 2 (en jaune)',
  'titre_ligne3' => 'Titre — ligne 3 (en jaune)', 'chapo' => 'Phrase d\'accroche',
  'cta_principal' => 'Bouton principal', 'cta_secondaire' => 'Bouton secondaire',
  'objectif' => 'Objectif interne (pilote la jauge, non affiché)',
  'afficher_objectif' => 'Afficher l\'objectif sous le compteur',
  'ligne_basse' => 'Ligne sous le compteur', 'libelle' => 'Étiquette', 'periode' => 'Mention à droite',
  'direct' => 'Voyant « en direct »', 'surtitre' => 'Surtitre', 'texte' => 'Texte',
  'citation' => 'Citation', 'citation_source' => 'Source de la citation',
  'ambition_titre' => 'Encart ambition — titre', 'ambition_texte' => 'Encart ambition — texte',
  'items' => 'Les quatre valeurs', 'lettre' => 'Lettre', 'priorites' => 'Les priorités',
  'points' => 'Puces', 'piliers' => 'Les quatre piliers', 'bouton' => 'Bouton',
  'consentement' => 'Case de consentement', 'affichage_public' => 'Case d\'affichage public',
  'rgpd' => 'Mention RGPD', 'profils' => 'Choix du menu « Vous êtes »',
  'membres' => 'Les candidats', 'nom' => 'Nom affiché sous la photo',
  'photo' => 'Chemin de la photo', 'roles' => 'Fonctions (en filigrane sur la photo)',
  'phrase' => 'Le mot du candidat (apparaît sur la photo au défilement ou au clic)',
  'base' => 'Bloc de gauche', 'email' => 'Adresse e-mail de contact',
  'programme_url' => 'Lien du programme', 'programme_libelle' => 'Libellé du lien',
  'liste' => 'Bloc « Liste »', 'mentions' => 'Mentions légales',
];
function lib(string $k, array $libelles): string
{
    return $libelles[$k] ?? ucfirst(str_replace('_', ' ', $k));
}
function hh(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/** Rendu récursif des champs. */
function champs($valeur, string $nom, string $cle, array $libelles, int $niveau = 0): void
{
    if (is_bool($valeur)) {
        echo '<label class="champ"><span>' . hh(lib($cle, $libelles)) . '</span>';
        echo '<input type="hidden" name="' . hh($nom) . '" value="0">';
        echo '<input type="checkbox" name="' . hh($nom) . '" value="1" style="width:auto;min-height:auto"' . ($valeur ? ' checked' : '') . '>';
        echo '</label>';
        return;
    }
    if (is_int($valeur) || is_float($valeur)) {
        echo '<label class="champ"><span>' . hh(lib($cle, $libelles)) . '</span>';
        echo '<input type="number" name="' . hh($nom) . '" value="' . hh((string)$valeur) . '"></label>';
        return;
    }
    if (is_string($valeur)) {
        $long = mb_strlen($valeur) > 90;
        echo '<label class="champ"><span>' . hh(lib($cle, $libelles)) . '</span>';
        if ($long) {
            echo '<textarea name="' . hh($nom) . '" rows="' . (mb_strlen($valeur) > 260 ? 5 : 3) . '">' . hh($valeur) . '</textarea>';
        } else {
            echo '<input type="text" name="' . hh($nom) . '" value="' . hh($valeur) . '">';
        }
        echo '</label>';
        return;
    }
    if (is_array($valeur)) {
        $liste = array_is_list($valeur);
        echo '<div class="sous-bloc"><h4>' . hh(lib($cle, $libelles)) . '</h4>';
        if ($liste) {
            if (!is_array($valeur[0] ?? null)) {
                echo '<input type="hidden" name="' . hh($nom . '[__rendu]') . '" value="1">';
            }
            echo '<div class="liste-repetable">';
            foreach ($valeur as $i => $v) {
                if (is_array($v)) {
                    echo '<div class="sous-bloc" style="border-color:var(--jaune-3)"><h4>' . ($i + 1) . '</h4>';
                    foreach ($v as $k2 => $v2) champs($v2, $nom . '[' . $i . '][' . $k2 . ']', (string)$k2, $libelles, $niveau + 1);
                    echo '</div>';
                } else {
                    echo '<div class="ligne-repetable" style="display:flex;gap:8px;margin-bottom:7px">';
                    echo '<input type="text" name="' . hh($nom . '[' . $i . ']') . '" value="' . hh((string)$v) . '">';
                    echo '<button type="button" class="btn btn--danger" onclick="this.parentNode.remove()" title="Supprimer">✕</button>';
                    echo '</div>';
                }
            }
            echo '</div>';
            if (!$valeur || !is_array($valeur[0] ?? null)) {
                echo '<button type="button" class="btn btn--fin" style="min-height:38px;padding:8px 14px;font-size:13px" '
                   . 'onclick="ajouterLigne(this,\'' . hh($nom) . '\')">+ Ajouter</button>';
            }
        } else {
            foreach ($valeur as $k2 => $v2) champs($v2, $nom . '[' . $k2 . ']', (string)$k2, $libelles, $niveau + 1);
        }
        echo '</div>';
    }
}
?>
<h1 class="titre">Textes du site</h1>
<p class="sous">Chaque modification est publiée immédiatement. Une sauvegarde est conservée avant chaque enregistrement.</p>

<?php if ($message !== ''): ?><div class="msg msg--<?= $classe ?>"><?= hh($message) ?></div><?php endif; ?>
<div class="msg msg--info">
  Mise en forme autorisée dans les textes : <code>&lt;b&gt;gras&lt;/b&gt;</code>,
  <code>&lt;em&gt;jaune&lt;/em&gt;</code>, <code>&lt;br&gt;</code> pour un retour à la ligne.
  Tout le reste est retiré automatiquement.
</div>

<form method="post">
  <?= csrf_champ() ?>
  <?php foreach ($data as $cle => $valeur): ?>
    <details class="bloc"<?= in_array($cle, ['hero', 'compteur'], true) ? ' open' : '' ?>>
      <summary><?= hh($titresBlocs[$cle] ?? ucfirst($cle)) ?></summary>
      <div class="bloc__corps">
        <?php
        if (is_array($valeur) && !array_is_list($valeur)) {
            foreach ($valeur as $k => $v) champs($v, "champs[$cle][$k]", (string)$k, $libelles);
        } else {
            champs($valeur, "champs[$cle]", (string)$cle, $libelles);
        }
        ?>
      </div>
    </details>
  <?php endforeach; ?>

  <div class="enregistrer">
    <button class="btn btn--plein" type="submit">Enregistrer les textes</button>
  </div>
</form>

<script>
function ajouterLigne(bouton, nom){
  var liste = bouton.previousElementSibling;
  var n = liste.querySelectorAll('.ligne-repetable').length;
  var d = document.createElement('div');
  d.className = 'ligne-repetable';
  d.style.cssText = 'display:flex;gap:8px;margin-bottom:7px';
  var i = document.createElement('input');
  i.type = 'text'; i.name = nom + '[' + (n + 1000) + ']';
  var b = document.createElement('button');
  b.type = 'button'; b.className = 'btn btn--danger'; b.textContent = '✕';
  b.onclick = function(){ d.remove(); };
  d.appendChild(i); d.appendChild(b); liste.appendChild(d); i.focus();
}
</script>
<?php require __DIR__ . '/pied.php'; ?>
