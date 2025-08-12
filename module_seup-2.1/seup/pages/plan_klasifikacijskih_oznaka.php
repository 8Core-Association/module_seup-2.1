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
 *	\file       seup/klasifikacijske_oznake.php
 *	\ingroup    seup
 *	\brief      List of classification marks
 */

// Load Dolibarr environment
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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Local classes
require_once __DIR__ . '/../class/predmet_helper.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Security check
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'ID_klasifikacijske_oznake';
$sortOrder = GETPOST('order', 'aZ09') ?: 'ASC';

// Validate sort fields
$allowedSortFields = [
    'ID_klasifikacijske_oznake',
    'klasa_broj',
    'sadrzaj',
    'dosje_broj',
    'vrijeme_cuvanja',
    'opis_klasifikacijske_oznake'
];

if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'ID_klasifikacijske_oznake';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Use specialized helper for classification marks
$orderByClause = Predmet_helper::buildKlasifikacijaOrderBy($sortField, $sortOrder, 'ko');

// Fetch all classification marks // TODO definiraj kriterij selecta (user ustanova.....)
$sql = "SELECT 
            ko.ID_klasifikacijske_oznake,
            ko.klasa_broj,
            ko.sadrzaj,
            ko.dosje_broj,
            ko.vrijeme_cuvanja,
            ko.opis_klasifikacijske_oznake
        FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ko
        {$orderByClause}";

$resql = $db->query($sql);
$oznake = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $oznake[] = $obj;
    }
}

// Generate HTML table
$tableHTML = '<div style="overflow-x: auto;">';
$tableHTML .= '<table class="seup-table">';
$tableHTML .= '<thead>';
$tableHTML .= '<tr>';

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
$tableHTML .= sortableHeader('ID_klasifikacijske_oznake', $langs->trans('ID'), $sortField, $sortOrder);
$tableHTML .= sortableHeader('klasa_broj', $langs->trans('klasaBr'), $sortField, $sortOrder);
$tableHTML .= sortableHeader('sadrzaj', $langs->trans('Sadrzaj'), $sortField, $sortOrder);
$tableHTML .= sortableHeader('dosje_broj', $langs->trans('dosjeBroj'), $sortField, $sortOrder);
$tableHTML .= sortableHeader('vrijeme_cuvanja', $langs->trans('vrijemeCuvanja'), $sortField, $sortOrder);
$tableHTML .= sortableHeader('opis_klasifikacijske_oznake', $langs->trans('Opis'), $sortField, $sortOrder);
$tableHTML .= '<th>' . $langs->trans('Actions') . '</th>';
$tableHTML .= '</tr>';
$tableHTML .= '</thead>';
$tableHTML .= '<tbody>';

if (count($oznake)) {
    foreach ($oznake as $oznaka) {
        $tableHTML .= '<tr>';
        $tableHTML .= '<td>' . $oznaka->ID_klasifikacijske_oznake . '</td>';
        $tableHTML .= '<td>' . $oznaka->klasa_broj . '</td>';
        $tableHTML .= '<td>' . $oznaka->sadrzaj . '</td>';
        $tableHTML .= '<td>' . $oznaka->dosje_broj . '</td>';

        // Handle retention period display
        $retentionDisplay = '';
        if ($oznaka->vrijeme_cuvanja == 0) {
            $retentionDisplay = 'Trajno';
        } else {
            $yearsText = ($oznaka->vrijeme_cuvanja == 1) ?
                $langs->trans('Year') :
                $langs->trans('Years');
            $retentionDisplay = $oznaka->vrijeme_cuvanja . ' ' . $yearsText;
        }
        $tableHTML .= '<td>' . $retentionDisplay . '</td>';

        $tableHTML .= '<td>' . dol_trunc($oznaka->opis_klasifikacijske_oznake, 40) . '</td>';

        // Action buttons
        $tableHTML .= '<td>';
        $tableHTML .= '<div class="seup-flex seup-gap-2">';
        $tableHTML .= '<a href="edit_oznaka.php?id=' . $oznaka->ID_klasifikacijske_oznake . '" class="seup-btn seup-btn-sm seup-btn-ghost seup-tooltip" data-tooltip="' . $langs->trans('Edit') . '">';
        $tableHTML .= '<i class="fas fa-edit"></i>';
        $tableHTML .= '</a>';
        $tableHTML .= '<a href="#" class="seup-btn seup-btn-sm seup-btn-danger seup-tooltip" data-tooltip="' . $langs->trans('Delete') . '">';
        $tableHTML .= '<i class="fas fa-trash"></i>';
        $tableHTML .= '</a>';
        $tableHTML .= '</div>';
        $tableHTML .= '</td>';

        $tableHTML .= '</tr>';
    }
} else {
    $tableHTML .= '<tr><td colspan="7">';
    $tableHTML .= '<div class="seup-empty-state">';
    $tableHTML .= '<div class="seup-empty-state-icon">';
    $tableHTML .= '<i class="fas fa-tags"></i>';
    $tableHTML .= '</div>';
    $tableHTML .= '<h3 class="seup-empty-state-title">' . $langs->trans('NoClassificationMarks') . '</h3>';
    $tableHTML .= '<p class="seup-empty-state-description">Dodajte novu klasifikacijsku oznaku za početak</p>';
    $tableHTML .= '</div>';
    $tableHTML .= '</td></tr>';
}

$tableHTML .= '</tbody>';
$tableHTML .= '</table>';
$tableHTML .= '</div>';

$form = new Form($db);
llxHeader("", $langs->trans("ClassificationMarks"), '', '', 0, 0, '', '', '', 'mod-seup page-oznake');

// Modern SEUP Styles
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Page Header
print '<div class="seup-page-header">';
print '<div class="seup-container">';
print '<h1 class="seup-page-title">Plan Klasifikacijskih Oznaka</h1>';
print '<div class="seup-breadcrumb">';
print '<a href="../seupindex.php">SEUP</a>';
print '<i class="fas fa-chevron-right"></i>';
print '<span>Klasifikacijske Oznake</span>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-container">';
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<h2 class="seup-heading-3" style="margin: 0;">' . $langs->trans('ClassificationMarks') . '</h2>';
print '<a href="postavke.php" class="seup-btn seup-btn-primary seup-interactive">';
print '<i class="fas fa-plus"></i> ' . $langs->trans('NewClassificationMark');
print '</a>';
print '</div>';
print '</div>';
print '<div class="seup-card-body">';
print $tableHTML;
print '</div>';
print '<div class="seup-card-footer">';
print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<div class="seup-text-small" style="color: var(--seup-gray-500);">';
print '<i class="fas fa-info-circle"></i> Prikazano ' . count($oznake) . ' klasifikacijskih oznaka';
print '</div>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';
llxFooter();
$db->close();
