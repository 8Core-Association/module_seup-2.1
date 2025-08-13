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
 *	\file       seup/novi_predmet.php
 *	\ingroup    seup
 *	\brief      Creation page for new predmet
 */


// Učitaj Dolibarr okruženje
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
  $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Pokušaj učitati main.inc.php iz korijenskog direktorija weba, koji je određen na temelju vrijednosti SCRIPT_FILENAME.
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

// Pokretanje buffera - potrebno za flush emitiranih podataka (fokusiranje na json format)
ob_start();

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php'; // ECM klasa - za baratanje dokumentima


// Lokalne klase
require_once __DIR__ . '/../class/predmet_helper.class.php';
require_once __DIR__ . '/../class/request_handler.class.php';

// Postavljanje debug logova
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Učitaj datoteke prijevoda potrebne za stranicu
$langs->loadLangs(array("seup@seup"));

$action = GETPOST('action', 'aZ09');

$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Sigurnosna provjera – zaštita ako je korisnik eksterni
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
  $action = '';
  $socid = $user->socid;
}


// definiranje direktorija za privremene datoteke
define('TEMP_DIR_RELATIVE', '/temp/'); // Relative to DOL_DATA_ROOT
define('TEMP_DIR_FULL', DOL_DATA_ROOT . TEMP_DIR_RELATIVE);
define('TEMP_DIR_WEB', DOL_URL_ROOT . '/documents' . TEMP_DIR_RELATIVE);

// Ensure temp directory exists
if (!file_exists(TEMP_DIR_FULL)) {
  dol_mkdir(TEMP_DIR_FULL);
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", "", '', '', 0, 0, '', '', '', 'mod-seup page-index');



/************************************
 ******** POST REQUESTOVI ************
 *************************************
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  dol_syslog('POST request', LOG_INFO);

  // OTVORI PREDMET
  if (isset($_POST['action']) && $_POST['action'] === 'otvori_predmet') {
    Request_Handler::handleOtvoriPredmet($db);
    exit;
  }
}

// Registriranje requestova za autocomplete i dinamicko popunjavanje vrijednosti Sadrzaja
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
  Request_Handler::handleCheckPredmetExists($db);
  exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 'autocomplete_stranka') {
  Request_Handler::handleStrankaAutocomplete($db);
  exit;
}


// Dohvat tagova iz baze 
$tags = array();
$sql = "SELECT rowid, tag FROM " . MAIN_DB_PREFIX . "a_tagovi WHERE entity = " . $conf->entity . " ORDER BY tag ASC";
$resql = $db->query($sql);
if ($resql) {
  while ($obj = $db->fetch_object($resql)) {
    $tags[] = $obj;
    dol_syslog("Tag: " . $obj->tag, LOG_DEBUG);
  }
}

$availableTagsHTML = '';
foreach ($tags as $tag) {
  $availableTagsHTML .= '<button type="button" class="btn btn-sm btn-outline-primary tag-option" 
                          data-tag-id="' . $tag->rowid . '">';
  $availableTagsHTML .= htmlspecialchars($tag->tag);
  $availableTagsHTML .= '</button>';
}

// Potrebno za kreiranje klase predmeta
// Inicijalno punjenje podataka za potrebe klase
$klasaOptions = '';
$zaposlenikOptions = '';
$code_ustanova = '';

$klasa_text = 'KLASA: OZN-SAD/GOD-DOS/RBR';
$klasaMapJson = '';

Predmet_helper::fetchDropdownData($db, $langs, $klasaOptions, $klasaMapJson, $zaposlenikOptions);


// Modern SEUP Styles
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Create modern date inputs with popup date picker
$strankaDateHTML = '<div class="seup-date-input-wrapper">
    <input type="text" class="seup-input seup-date-input" name="strankaDatumOtvaranja" placeholder="dd.mm.yyyy" readonly>
    <button type="button" class="seup-date-trigger" data-target="strankaDatumOtvaranja">
        <i class="fas fa-calendar-alt"></i>
    </button>
</div>';

$datumOtvaranjaHTML = '<div class="seup-date-input-wrapper">
    <input type="text" class="seup-input seup-date-input" name="datumOtvaranja" placeholder="dd.mm.yyyy" readonly>
    <button type="button" class="seup-date-trigger" data-target="datumOtvaranja">
        <i class="fas fa-calendar-alt"></i>
    </button>
</div>';

// Page Header
print '<div class="seup-page-header">';
print '<div class="seup-container">';
print '<h1 class="seup-page-title">Novi Predmet</h1>';
print '<div class="seup-breadcrumb">';
print '<a href="../seupindex.php">SEUP</a>';
print '<i class="fas fa-chevron-right"></i>';
print '<span>Novi Predmet</span>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-container">';

$htmlContent = <<<HTML
<div class="seup-card seup-slide-up">
    <div class="seup-card-header">
        <h2 class="seup-heading-3" style="margin: 0;">Kreiranje Novog Predmeta</h2>
        <p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Unesite podatke za novi predmet u sustav</p>
    </div>
    <div class="seup-card-body">
        <div class="seup-form-group">
            <label class="seup-label">Klasa Predmeta</label>
            <div class="seup-badge seup-badge-primary" id="klasa-value" style="font-family: var(--seup-font-mono); font-size: 1rem; padding: var(--seup-space-3) var(--seup-space-4);">$klasa_text</div>
        </div>
    
        <div class="seup-grid seup-grid-2">
            <div class="seup-card" style="border: 1px solid var(--seup-gray-200);">
                <div class="seup-card-header">
                    <h3 class="seup-heading-4" style="margin: 0;">Parametri Klase</h3>
                </div>
                <div class="seup-card-body">
          
                    <div class="seup-form-group">
                        <label for="klasa_br" class="seup-label">{$langs->trans("Klasa broj")}</label>
                        <select name="klasa_br" id="klasa_br" class="seup-select">
                            $klasaOptions
                        </select>
                    </div>

                    <div class="seup-form-group">
                        <label for="sadrzaj" class="seup-label">{$langs->trans("Sadrzaj")}</label>
                        <select name="sadrzaj" id="sadrzaj" class="seup-select" data-placeholder="{$langs->trans("Odaberi Sadrzaj")}">
                            <option value="">{$langs->trans("Odaberi Sadrzaj")}</option>
                        </select>
                    </div>

                    <div class="seup-form-group">
                        <label for="dosjeBroj" class="seup-label">{$langs->trans("Dosje Broj")}</label>
                        <select name="dosjeBroj" id="dosjeBroj" class="seup-select" data-placeholder="{$langs->trans("Odaberi Dosje Broj")}">
                            <option value="">{$langs->trans("Odaberi Dosje Broj")}</option>
                        </select>
                    </div>
          
                    <div class="seup-form-group">
                        <label for="zaposlenik" class="seup-label">{$langs->trans("Zaposlenik")}</label>
                        <select class="seup-select" id="zaposlenik" name="zaposlenik" required>
                            $zaposlenikOptions
                        </select>
                    </div>

                    <div class="seup-form-group">
                        <label for="stranka" class="seup-label">{$langs->trans("Stranka")}</label>
                        <div class="seup-flex seup-gap-2">
                            <select class="seup-select" id="stranka" name="stranka" disabled style="flex: 1;"></select>
                            <div class="seup-flex seup-items-center">
                                <input type="checkbox" id="strankaCheck" autocomplete="off" style="display: none;">
                                <label class="seup-btn seup-btn-secondary" for="strankaCheck" id="strankaCheckLabel" style="white-space: nowrap;">
                        Otvorila stranka?
                                </label>
                            </div>
                        </div>
                        <div id="strankaDatumContainer" class="seup-form-group" style="display:none; margin-top: var(--seup-space-4);">
                            <label for="strankaDatumOtvaranja" class="seup-label">Datum otvaranja predmeta od strane stranke</label>
                            $strankaDateHTML
                            <div id="strankaDateError" class="seup-field-error" style="display: none;">
                                Odaberite datum otvaranja predmeta!
                            </div>
                        </div>
                        <div id="strankaError" class="seup-field-error" style="display: none;">
                            Odaberite stranku!
                        </div>
                    </div>
                </div>
            </div>
      
            <div class="seup-card" style="border: 1px solid var(--seup-gray-200);">
                <div class="seup-card-header">
                    <h3 class="seup-heading-4" style="margin: 0;">Detalji Predmeta</h3>
                </div>
                <div class="seup-card-body">
                    <div class="seup-form-group">
                        <label for="naziv" class="seup-label">Naziv Predmeta</label>
                        <textarea class="seup-textarea" id="naziv" name="naziv" rows="6" maxlength="500" placeholder="Unesite naziv predmeta (maksimalno 500 znakova)" style="resize: vertical;"></textarea>
                    </div>
                    
                    <div class="seup-form-group">
                        <label for="datumOtvaranja" class="seup-label">Datum Otvaranja Predmeta</label>
                        $datumOtvaranjaHTML
                        <small class="seup-text-small" style="margin-top: var(--seup-space-1); display: block;">Ostavite prazno za današnji datum</small>
                    </div>
                    
                    <div class="seup-form-group">
                        <label class="seup-label">{$langs->trans('Oznake')}</label>
                        <div class="seup-flex seup-gap-2" style="margin-bottom: var(--seup-space-3);">
                            <div class="seup-dropdown" style="flex: 1;">
                                <button class="seup-btn seup-btn-secondary" type="button" id="tagsDropdown" style="width: 100%; justify-content: space-between;">
                                    <span>Odaberi oznake</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="seup-dropdown-menu" id="tags-dropdown-menu" style="display: none;">
                                    <div class="available-tags-container" id="available-tags">
                                        {$availableTagsHTML}
                                    </div>
                                </div>
                            </div>
                            <button class="seup-btn seup-btn-primary" type="button" id="add-tag-btn">
                                <i class="fas fa-plus"></i> Dodaj
                            </button>
                        </div>
                        <div class="selected-tags-container" id="selected-tags">
                            <span class="seup-text-small" style="color: var(--seup-gray-500); align-self: center;" id="tags-placeholder">Odabrane oznake će se prikazati ovdje</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="seup-card-footer">
            <div class="seup-flex seup-justify-between seup-items-center">
                <div class="seup-text-small" style="color: var(--seup-gray-500);">
                    <i class="fas fa-info-circle"></i> Sva polja označena * su obavezna
                </div>
                <button type="button" class="seup-btn seup-btn-primary seup-btn-lg seup-interactive" id="otvoriPredmetBtn">
                    <i class="fas fa-plus"></i> Otvori Predmet
                </button>
            </div>
        </div>
    </div>
HTML;

// Print the HTML content
print $htmlContent;


// Ne diraj dalje ispod ništa ne mjenjaj dole je samo bootstrap cdn java scripta i dolibarr footer postavke kao što vidiš//

// Date Picker Modal
print '<div id="datePickerModal" class="seup-modal" style="display: none;">';
print '<div class="seup-modal-overlay"></div>';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h3 class="seup-modal-title">Odaberite datum</h3>';
print '<button type="button" class="seup-modal-close" id="closeDatePicker">';
print '<i class="fas fa-times"></i>';
print '</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div id="calendarContainer"></div>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="todayBtn">Danas</button>';
print '<button type="button" class="seup-btn seup-btn-secondary" id="clearDateBtn">Očisti</button>';
print '<button type="button" class="seup-btn seup-btn-primary" id="confirmDateBtn">Potvrdi</button>';
print '</div>';
print '</div>';
print '</div>';

// Load required JavaScript libraries
print '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';

// End of page
llxFooter();
$db->close();
// TODO add Tagovi polje nakon implementacije
?>

<script type="text/javascript">
  // override da se u dropdownu prikazuje hrvatski jezik za placeholder text
  jQuery.fn.select2.defaults.set('language', {
    inputTooShort: function(args) {
      return "Unesite barem 2 znaka za pretraživanje";
    }
  });

  // Global variable for current date
  const now = new Date();

  // Popup Date Picker Implementation
  class SEUPPopupDatePicker {
    constructor() {
      this.modal = document.getElementById('datePickerModal');
      this.calendarContainer = document.getElementById('calendarContainer');
      this.currentInput = null;
      this.selectedDate = null;
      this.currentMonth = new Date().getMonth();
      this.currentYear = new Date().getFullYear();
      this.today