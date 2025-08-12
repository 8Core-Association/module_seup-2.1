<?php
/**
 * Plaćena licenca
 * (c) 2025 Tomislav Galić <tomislav@8core.hr>
 * Suradnik: Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zabranjeno ga je
 * distribuirati ili mijenjati bez izričitog dopuštenja autora.
 */
/**
 *	\file       seup2/seup2index.php
 *	\ingroup    seup2
 *	\brief      Home page of seup2 top menu
 */


// Učitaj Dolibarr okruženje
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Pokušaj učitati main.inc.php iz korijenskog direktorija weba, koji je određen na temelju vrijednosti SCRIPT_FILENAME.
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Pokušaj učitati main.inc.php koristeći relativnu putanju

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Učitaj datoteke prijevoda potrebne za stranicu
$langs->loadLangs(array("seup2@seup2"));

$action = GETPOST('action', 'aZ09');

$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Sigurnosna provjera – zaštita ako je korisnik eksterni
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}


require_once __DIR__ . '/class/predmet_helper.class.php';

// Provjeri da li postoje potrebne tablice u bazi - ako ne postoje, kreiraj ih
Predmet_helper::createSeupDatabaseTables($db);


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", "", '', '', 0, 0, '', '', '', 'mod-seup2 page-index');

// Modern SEUP Styles
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Hero Section
print '<div class="seup-hero">';
print '<div class="seup-container">';
print '<div class="seup-hero-content">';
print '<h1 class="seup-hero-title">Elektronski sustav uredskog poslovanja</h1>';
print '<p class="seup-hero-subtitle">Moderan i efikasan sustav za upravljanje dokumentima i predmetima</p>';
print '<div class="seup-hero-actions">';
print '<a href="pages/novi_predmet.php" class="seup-hero-btn seup-interactive">';
print '<i class="fas fa-plus"></i> Novi Predmet';
print '</a>';
print '<a href="pages/predmeti.php" class="seup-hero-btn seup-interactive">';
print '<i class="fas fa-folder-open"></i> Predmeti';
print '</a>';
print '<a href="pages/plan_klasifikacijskih_oznaka.php" class="seup-hero-btn seup-interactive">';
print '<i class="fas fa-list"></i> Klasifikacije';
print '</a>';
print '<a href="pages/postavke.php" class="seup-hero-btn seup-interactive">';
print '<i class="fas fa-cog"></i> Postavke';
print '</a>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Main Content Area
print '<div class="seup-container" style="margin-top: var(--seup-space-12);">';
print '<div class="seup-grid seup-grid-3">';

// Quick Stats Cards
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-primary-600);">';
print '<i class="fas fa-folder-open"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">Aktivni Predmeti</h3>';
print '<p class="seup-text-small">Trenutno otvoreni predmeti</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-accent);">';
print '<i class="fas fa-file-alt"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">Dokumenti</h3>';
print '<p class="seup-text-small">Ukupno dokumenata u sustavu</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-success);">';
print '<i class="fas fa-chart-line"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">Produktivnost</h3>';
print '<p class="seup-text-small">Mjesečni pregled aktivnosti</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End grid

// Recent Activity Section
print '<div class="seup-card" style="margin-top: var(--seup-space-8);">';
print '<div class="seup-card-header">';
print '<h2 class="seup-heading-3" style="margin: 0;">Nedavne Aktivnosti</h2>';
print '</div>';
print '<div class="seup-card-body">';
print '<div class="seup-empty-state">';
print '<div class="seup-empty-state-icon">';
print '<i class="fas fa-clock"></i>';
print '</div>';
print '<h3 class="seup-empty-state-title">Nema nedavnih aktivnosti</h3>';
print '<p class="seup-empty-state-description">Aktivnosti će se prikazati kada počnete koristiti sustav</p>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';

// End of page
llxFooter();
$db->close();
