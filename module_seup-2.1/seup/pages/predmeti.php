<?php

/**
 * Plaćena licenca
 * (c) 2025 Tomislav Galić <tomislav@8core.hr>
 * Suradnik: Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zabranjeno ga je
 * distribuirati ili mijenjati bez izričitog dopuštenia autora.
 */
/**
 *	\file       seup/predmeti.php
 *	\ingroup    seup
 *	\brief      List of open cases
 */

// Učitaj Dolibarr okruženje
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

// Lokalne klase
require_once __DIR__ . '/../class/predmet_helper.class.php';

// Učitaj datoteke prijevoda
$langs->loadLangs(array("seup@seup"));

$action = GETPOST('action', 'aZ09');
$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Sigurnosna provjera
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'ID_predmeta';
$sortOrder = GETPOST('order', 'aZ09') ?: 'ASC';

// Validate sort fields
$allowedSortFields = ['ID_predmeta', 'klasa_br', 'naziv_predmeta', 'name_ustanova', 'ime_prezime', 'tstamp_created'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'ID_predmeta';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Use helper to build ORDER BY
require_once __DIR__ . '/../class/predmet_helper.class.php';
$orderByClause = Predmet_helper::buildOrderByKlasa($sortField, $sortOrder);

// Fetch all open cases with proper sorting
$sql = "SELECT 
            p.ID_predmeta,
            p.klasa_br,
            p.sadrzaj,
            p.dosje_broj,
            p.godina,
            p.predmet_rbr,
            p.naziv_predmeta,
            DATE_FORMAT(p.tstamp_created, '%d/%m/%Y') as datum_otvaranja,
            u.name_ustanova,
            k.ime_prezime,
            ko.opis_klasifikacijske_oznake
        FROM " . MAIN_DB_PREFIX . "a_predmet p
        LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
        LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON p.ID_interna_oznaka_korisnika = k.ID
        LEFT JOIN " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ko ON p.ID_klasifikacijske_oznake = ko.ID_klasifikacijske_oznake
        {$orderByClause}";

$resql = $db->query($sql);
$predmeti = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $predmeti[] = $obj;
    }
}

// Generate HTML table
$predmetTableHTML = '<div style="overflow-x: auto;">';
$predmetTableHTML .= '<table class="seup-table">';
$predmetTableHTML .= '<thead>';
$predmetTableHTML .= '<tr>';

// Function to generate sortable header
function sortableHeader($field, $label, $currentSort, $currentOrder)
{
    $newOrder = ($currentSort === $field && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    $icon = '';

    if ($currentSort === $field) {
        $icon = ($currentOrder === 'ASC')
            ? ' <i class="fas fa-arrow-up"></i>'
            : ' <i class="fas fa-arrow-down"></i>';
    }

    return '<th class="seup-sortable-header">' .
        '<a href="?sort=' . $field . '&order=' . $newOrder . '">' .
        $label . $icon .
        '</a></th>';
}

// Generate sortable headers
$predmetTableHTML .= sortableHeader('ID_predmeta', $langs->trans('ID'), $sortField, $sortOrder);
$predmetTableHTML .= sortableHeader('klasa_br', $langs->trans('Klasa'), $sortField, $sortOrder);
$predmetTableHTML .= sortableHeader('naziv_predmeta', $langs->trans('NazivPredmeta'), $sortField, $sortOrder);
$predmetTableHTML .= sortableHeader('name_ustanova', $langs->trans('Ustanova'), $sortField, $sortOrder);
$predmetTableHTML .= sortableHeader('ime_prezime', $langs->trans('Zaposlenik'), $sortField, $sortOrder);
$predmetTableHTML .= sortableHeader('tstamp_created', $langs->trans('DatumOtvaranja'), $sortField, $sortOrder);
$predmetTableHTML .= '<th>' . $langs->trans('Actions') . '</th>';
$predmetTableHTML .= '</tr>';
$predmetTableHTML .= '</thead>';
$predmetTableHTML .= '<tbody>';

if (count($predmeti)) {
    foreach ($predmeti as $predmet) {
        $klasa = $predmet->klasa_br . '-' . $predmet->sadrzaj . '/' .
            $predmet->godina . '-' . $predmet->dosje_broj . '/' .
            $predmet->predmet_rbr;
        dol_syslog("Predmet: " . $klasa, LOG_DEBUG);
        $predmetTableHTML .= '<tr>';
        $predmetTableHTML .= '<td>' . $predmet->ID_predmeta . '</td>';
        // Make Klasa badge clickable
        $url = dol_buildpath('/custom/seup/pages/predmet.php', 1) . '?id=' . $predmet->ID_predmeta;
        $predmetTableHTML .= '<td><a href="' . $url . '" class="seup-badge seup-badge-primary seup-interactive" style="text-decoration: none;">' . $klasa . '</a></td>';
        $predmetTableHTML .= '<td>' . dol_trunc($predmet->naziv_predmeta, 40) . '</td>';
        $predmetTableHTML .= '<td>' . $predmet->name_ustanova . '</td>';
        $predmetTableHTML .= '<td>' . $predmet->ime_prezime . '</td>';
        $predmetTableHTML .= '<td>' . $predmet->datum_otvaranja . '</td>';
        $predmetTableHTML .= '<td>';
        $predmetTableHTML .= '<div class="seup-flex seup-gap-2">';
        $predmetTableHTML .= '<a href="' . $url . '" class="seup-btn seup-btn-sm seup-btn-ghost seup-tooltip" data-tooltip="' . $langs->trans('ViewDetails') . '">';
        $predmetTableHTML .= '<i class="fas fa-eye"></i>';
        $predmetTableHTML .= '</a>';
        $predmetTableHTML .= '<a href="#" class="seup-btn seup-btn-sm seup-btn-secondary seup-tooltip" data-tooltip="' . $langs->trans('Edit') . '">';
        $predmetTableHTML .= '<i class="fas fa-edit"></i>';
        $predmetTableHTML .= '</a>';
        $predmetTableHTML .= '<a href="#" class="seup-btn seup-btn-sm seup-btn-danger seup-tooltip" data-tooltip="' . $langs->trans('CloseCase') . '">';
        $predmetTableHTML .= '<i class="fas fa-lock"></i>';
        $predmetTableHTML .= '</a>';
        $predmetTableHTML .= '</div>';
        $predmetTableHTML .= '</td>';
        $predmetTableHTML .= '</tr>';
    }
} else {
    $predmetTableHTML .= '<tr><td colspan="7">';
    $predmetTableHTML .= '<div class="seup-empty-state">';
    $predmetTableHTML .= '<div class="seup-empty-state-icon">';
    $predmetTableHTML .= '<i class="fas fa-inbox"></i>';
    $predmetTableHTML .= '</div>';
    $predmetTableHTML .= '<h3 class="seup-empty-state-title">' . $langs->trans('NoOpenCases') . '</h3>';
    $predmetTableHTML .= '<p class="seup-empty-state-description">Kreirajte novi predmet za početak rada</p>';
    $predmetTableHTML .= '</div>';
    $predmetTableHTML .= '</td></tr>';
}

$predmetTableHTML .= '</tbody>';
$predmetTableHTML .= '</table>';
$predmetTableHTML .= '</div>'; // table-responsive

$form = new Form($db);
llxHeader("", $langs->trans("OpenCases"), '', '', 0, 0, '', '', '', 'mod-seup page-predmeti');

// === BOOTSTRAP CDN ===
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/style.css" rel="stylesheet">';

print '<div class="container mt-5 shadow-sm p-3 mb-5 bg-body rounded">';
print '<div class="p-3 border rounded">';
print '<div class="d-flex justify-content-between align-items-center mb-4">';
print '<h4 class="mb-0">' . $langs->trans('OpenCases') . '</h4>';
print '<button type="button" class="btn btn-primary btn-sm" id="noviPredmetBtn">';
print '<i class="fas fa-plus me-1"></i> ' . $langs->trans('NewCase');
print '</button>';
print '</div>';
print $predmetTableHTML;
print '</div>';
print '<div class="seup-card-footer">';
print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<div class="seup-text-small" style="color: var(--seup-gray-500);">';
print '<i class="fas fa-info-circle"></i> ' . $langs->trans('ShowingCases', count($predmeti));
print '</div>';
print '</div>';
print '</div>';
print '</div>'; // End card
print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("noviPredmetBtn").addEventListener("click", function() {
            window.location.href = "novi_predmet.php";
        });
    });
</script>


<?php
llxFooter();
$db->close();