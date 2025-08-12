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

// Create modern date inputs with custom date picker
$strankaDateHTML = '<div class="seup-date-picker">
    <input type="text" class="seup-input seup-date-input" name="strankaDatumOtvaranja" placeholder="dd.mm.yyyy" readonly>
    <i class="fas fa-calendar-alt seup-date-icon"></i>
    <div class="seup-calendar" style="display: none;"></div>
</div>';

$datumOtvaranjaHTML = '<div class="seup-date-picker">
    <input type="text" class="seup-input seup-date-input" name="datumOtvaranja" placeholder="dd.mm.yyyy" readonly>
    <i class="fas fa-calendar-alt seup-date-icon"></i>
    <div class="seup-calendar" style="display: none;"></div>
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

  // Enhanced Date Picker Implementation
  class SEUPDatePicker {
    constructor(container) {
      this.container = container;
      this.input = container.querySelector('.seup-date-input');
      this.calendar = container.querySelector('.seup-calendar');
      this.icon = container.querySelector('.seup-date-icon');
      this.selectedDate = null;
      this.currentMonth = new Date().getMonth();
      this.currentYear = new Date().getFullYear();
      
      this.init();
    }
    
    init() {
      this.input.addEventListener('click', () => this.show());
      this.icon.addEventListener('click', () => this.show());
      
      document.addEventListener('click', (e) => {
        if (!this.container.contains(e.target)) {
          this.hide();
        }
      });
      
      // Keyboard navigation
      this.input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.show();
        }
        if (e.key === 'Escape') {
          this.hide();
        }
      });
    }
    
    show() {
      this.calendar.style.display = 'block';
      this.calendar.classList.add('seup-fade-in');
      this.renderCalendar();
    }
    
    hide() {
      this.calendar.style.display = 'none';
      this.calendar.classList.remove('seup-fade-in');
    }
    
    renderCalendar() {
      const year = this.currentYear;
      const month = this.currentMonth;
      
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      
      // Calculate start date (Monday of the week containing first day)
      const startDate = new Date(firstDay);
      const dayOfWeek = firstDay.getDay();
      const daysToSubtract = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Monday = 0, Sunday = 6
      startDate.setDate(firstDay.getDate() - daysToSubtract);
      
      const monthNames = [
        'Siječanj', 'Veljača', 'Ožujak', 'Travanj', 'Svibanj', 'Lipanj',
        'Srpanj', 'Kolovoz', 'Rujan', 'Listopad', 'Studeni', 'Prosinac'
      ];
      
      const today = new Date();
      
      let html = `
        <div class="seup-calendar-header">
          <button type="button" class="seup-calendar-nav" data-action="prev-month">
            <i class="fas fa-chevron-left"></i>
          </button>
          <div class="seup-calendar-title-container">
            <select class="seup-calendar-month-select" data-action="change-month">
              ${monthNames.map((name, index) => 
                `<option value="${index}" ${index === month ? 'selected' : ''}>${name}</option>`
              ).join('')}
            </select>
            <select class="seup-calendar-year-select" data-action="change-year">
              ${this.generateYearOptions(year)}
            </select>
          </div>
          <button type="button" class="seup-calendar-nav" data-action="next-month">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
        <div class="seup-calendar-grid">
          <div class="seup-calendar-weekdays">
            <span>Pon</span><span>Uto</span><span>Sri</span><span>Čet</span><span>Pet</span><span>Sub</span><span>Ned</span>
          </div>
          <div class="seup-calendar-days">
      `;
      
      // Calculate how many weeks we need (minimum 6 weeks to show full month)
      const totalDays = 42;
      
      for (let i = 0; i < totalDays; i++) {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + i);
        
        const isCurrentMonth = date.getMonth() === month;
        const isToday = date.toDateString() === now.toDateString();
        const isSelected = this.selectedDate && this.selectedDate === this.formatDate(date);
        const isPast = date < new Date().setHours(0,0,0,0) && !isToday;
        
        let dayClass = `seup-calendar-day`;
        if (!isCurrentMonth) dayClass += ' other-month';
        if (isToday) dayClass += ' today';
        if (isSelected) dayClass += ' selected';
        if (isPast) dayClass += ' past';
        
        const formattedDate = this.formatDate(date);
        html += `<button type="button" class="${dayClass}" data-date="${formattedDate}" ${!isCurrentMonth ? 'disabled' : ''}>${date.getDate()}</button>`;
      }
      
      html += `
          </div>
        </div>
        <div class="seup-calendar-footer">
          <button type="button" class="seup-btn seup-btn-sm seup-btn-secondary" data-action="today">Danas</button>
          <button type="button" class="seup-btn seup-btn-sm seup-btn-secondary" data-action="clear">Očisti</button>
        </div>
      `;
      
      this.calendar.innerHTML = html;
      this.attachCalendarEvents();
    }
    
    generateYearOptions(currentYear) {
      const startYear = currentYear - 10;
      const endYear = currentYear + 10;
      let options = '';
      
      for (let year = startYear; year <= endYear; year++) {
        const selected = year === currentYear ? 'selected' : '';
        options += `<option value="${year}" ${selected}>${year}</option>`;
      }
      
      return options;
    }
    
    formatDate(date) {
      return `${date.getDate().toString().padStart(2, '0')}.${(date.getMonth() + 1).toString().padStart(2, '0')}.${date.getFullYear()}`;
    }
    
    attachCalendarEvents() {
      this.calendar.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (e.target.classList.contains('seup-calendar-day') && !e.target.disabled) {
          this.selectDate(e.target.dataset.date);
        }
        
        if (e.target.closest('[data-action="today"]')) {
          this.selectDate(this.formatDate(new Date()));
        }
        
        if (e.target.closest('[data-action="clear"]')) {
          this.input.value = '';
          this.selectedDate = null;
          this.hide();
        }
        
        if (e.target.closest('[data-action="prev-month"]')) {
          this.currentMonth--;
          if (this.currentMonth < 0) {
            this.currentMonth = 11;
            this.currentYear--;
          }
          this.renderCalendar();
        }
        
        if (e.target.closest('[data-action="next-month"]')) {
          this.currentMonth++;
          if (this.currentMonth > 11) {
            this.currentMonth = 0;
            this.currentYear++;
          }
          this.renderCalendar();
        }
        
        if (e.target.closest('[data-action="change-month"]')) {
          this.currentMonth = parseInt(e.target.value);
          this.renderCalendar();
        }
        
        if (e.target.closest('[data-action="change-year"]')) {
          this.currentYear = parseInt(e.target.value);
          this.renderCalendar();
        }
      });
    }
    
    selectDate(dateStr) {
      this.input.value = dateStr;
      this.selectedDate = dateStr;
      this.hide();
      
      // Trigger change event
      this.input.dispatchEvent(new Event('change', { bubbles: true }));
      
      // Add visual feedback
      this.input.style.borderColor = 'var(--seup-success)';
      setTimeout(() => {
        this.input.style.borderColor = '';
      }, 1000);
    }
  }
  
  document.addEventListener("DOMContentLoaded", function() {
    // Initialize custom date pickers
    document.querySelectorAll('.seup-date-picker').forEach(picker => {
      new SEUPDatePicker(picker);
    });
    
    // Get the select elements and klasa value element
    const dataHolder = document.getElementById("phpDataHolder");
    const klasaMap = JSON.parse('<?php echo $klasaMapJson; ?>');
    console.log("KlasaMap loaded:", klasaMap); // For debugging
    var klasaSelect = document.getElementById("klasa_br");
    var sadrzajSelect = document.getElementById("sadrzaj");
    const dosjeSelect = document.getElementById("dosjeBroj");
    var zaposlenikSelect = document.getElementById("zaposlenik");
    var klasaValue = document.getElementById("klasa-value");
    const otvoriPredmetBtn = document.getElementById("otvoriPredmetBtn");

    /****************************************/
    /* Stranka autocomplete funkcionalnost  */
    /****************************************/
    document.getElementById('strankaCheck').addEventListener('change', function() {
      const selectField = document.getElementById('stranka');
      const label = document.getElementById('strankaCheckLabel');
      const errorDiv = document.getElementById('strankaError');
      const container = document.getElementById('strankaDatumContainer');

      if (this.checked) {
        // Enable field and make it required
        selectField.disabled = false;
        selectField.required = true;

        // Initialize Select2 if not already initialized
        if (!selectField.hasAttribute('data-select2-id')) {
          jQuery(selectField).select2({
            placeholder: "OIB ili naziv stranke",
            allowClear: true,
            ajax: {
              url: 'novi_predmet.php?ajax=autocomplete_stranka',
              dataType: 'json',
              delay: 300,
              data: function(params) {
                return {
                  term: params.term
                };
              },
              processResults: function(data) {
                return {
                  results: data.map(item => ({
                    id: item.label,
                    text: item.label + (item.vat ? ' (' + item.vat + ')' : '')
                  }))
                };
              },
              cache: true
            },
            minimumInputLength: 2
          });
        }

        // Update button style
        label.classList.remove('seup-btn-secondary');
        label.classList.add('seup-btn-primary');

        // Clear any previous errors
        errorDiv.style.display = 'none';
        selectField.classList.remove('is-invalid');

        // Show date container
        container.style.display = 'block';

        // Focus the field
        selectField.focus();
      } else {
        // Destroy Select2 and disable
        if (selectField.hasAttribute('data-select2-id')) {
          $(selectField).select2('destroy');
        }
        selectField.disabled = true;
        selectField.required = false;
        selectField.innerHTML = '';

        // Revert button style
        label.classList.remove('seup-btn-primary');
        label.classList.add('seup-btn-secondary');

        // Clear any errors
        errorDiv.style.display = 'none';
        selectField.classList.remove('is-invalid');

        // Hide date container
        container.style.display = 'none';

        // Clear date inputs
        const strankaDateInput = document.querySelector('input[name="strankaDatumOtvaranja"]');
        if (strankaDateInput) {
          strankaDateInput.value = '';
        }
      }
    });

    /****************************************/
    /* KRAJ Stranka autocomplete funkcionalnost  */
    /****************************************/

    const placeholderText = "<?php echo $langs->trans('Odaberi Sadrzaj'); ?>";
    // Check if elements are present
    if (!klasaSelect || !sadrzajSelect || !zaposlenikSelect || !klasaValue) {
      if (!klasaSelect) {
        console.error("Klasa select element not found in DOM.");
      }
      if (!sadrzajSelect) {
        console.error("Sadrzaj select element not found in DOM.");
      }
      if (!dosjeSelect) {
        console.error("Dosje select element not found in DOM.");
      }
      if (!zaposlenikSelect) {
        console.error("Zaposlenik select element not found in DOM.");
      }
      if (!klasaValue) {
        console.error("Klasa value element not found in DOM.");
      }
      console.error("Klasa, Sadrzaj, Zaposlenik, or Klasa Value element not found in DOM.");
      return;
    }
    console.log("DOMContentLoaded");

    var klasaText = <?php echo json_encode($klasa_text); ?>;

    // State for keeping track of current values
    var currentValues = {
      klasa: "",
      sadrzaj: "",
      dosje: "",
      rbr: "1"
    };

    let year = new Date().getFullYear();
    year = year.toString().slice(-2);

    function updateKlasaValue() {
      const klasa = currentValues.klasa || "OZN";
      const sadrzaj = currentValues.sadrzaj || "SAD";
      const selectedDosje = dosjeSelect.value || "DOS";
      const rbr = currentValues.rbr || "1";

      // Build the string using template literals
      const updatedText = `KLASA: ${klasa}-${sadrzaj}/${year}-${selectedDosje}/${rbr}`;
      klasaValue.textContent = updatedText;
    }

    function checkIfPredmetExists() {
      var klasa = klasaSelect.value || "OZN";
      var sadrzaj = sadrzajSelect.value || "SAD";
      var dosje_br = dosjeSelect.value || "DOS";
      console.log("gledam jel postoji predmet");
      if (klasa !== "OZN" && sadrzaj !== "SAD" && dosje_br !== "DOS") {
        fetch(
            "novi_predmet.php?ajax=1&" +
            "klasa_br=" + encodeURIComponent(klasa) +
            "&sadrzaj=" + encodeURIComponent(sadrzaj) +
            "&dosje_br=" + encodeURIComponent(dosje_br) +
            "&god=" + encodeURIComponent(year), {
              headers: {
                "Accept": "application/json"
              }
            }
          )
          .then(response => {
            return response.json();
          })
          .then(data => {
            if (data.status === "exists" || data.status === "inserted") {
              // Update the RBR part of the klasa text
              currentValues.rbr = data.next_rbr;

              // Refresh the klasa text on screen
              updateKlasaValue();

              if (data.status === "exists") {
                console.log("Ovakav predmet postoji. Generiram sljedeci redni broj predmeta.");
              }
            } else {
              console.log("Predmet does not exist, ready to create new one." + data.status);
            }
          })
          .catch(error => console.error("Error checking predmet:", error));
      }
    }

    function resetKlasaDisplay() {
      currentValues = {
        klasa: "",
        sadrzaj: "",
        dosje: "",
        rbr: "1",
        zaposlenik: ""
      };
      klasaSelect.value = "";
      sadrzajSelect.innerHTML = `<option value="">${sadrzajSelect.dataset.placeholder}</option>`;
      dosjeSelect.innerHTML = `<option value="">${dosjeSelect.dataset.placeholder}</option>`;
      zaposlenikSelect.value = "";

      updateKlasaValue();
    }

    // Update on klasa change
    if (klasaSelect) {
      klasaSelect.addEventListener("change", function() {
        console.log("Selected klasa:", this.value);
        console.log("Available sadrzaj:", klasaMap[this.value]);
        currentValues.klasa = this.value || "";
        currentValues.dosje = "";

        // Reset sadrzaj dropdown
        sadrzajSelect.innerHTML = `<option value="">${sadrzajSelect.dataset.placeholder}</option>`;


        dosjeSelect.innerHTML = `<option value="">${dosjeSelect.dataset.placeholder}</option>`;

        // Populate new options based on selected klasa
        // Populate Sadrzaj if klasa selected
        if (this.value && klasaMap[this.value]) {
          const sadrzajValues = Object.keys(klasaMap[this.value]);
          sadrzajValues.forEach(sadrzaj => {
            const option = new Option(sadrzaj, sadrzaj);
            sadrzajSelect.appendChild(option);
          });
        }

        // Update the klasa text
        updateKlasaValue();
        checkIfPredmetExists();
      });
    }

    // Update on sadrzaj change
    if (sadrzajSelect) {
      sadrzajSelect.addEventListener("change", function() {
        console.log("Selected klasa:", klasaSelect.value);
        console.log("Selected sadrzaj:", this.value);
        console.log("Available dosje:", klasaMap[klasaSelect.value]?.[this.value]);
        dosjeSelect.innerHTML = `<option value="">${dosjeSelect.dataset.placeholder}</option>`;

        currentValues.sadrzaj = this.value || "SAD";
        currentValues.dosje = "";

        const klasa = klasaSelect.value;
        const sadrzaj = this.value;
        // Populate Dosje Broj if values exist
        if (klasa && sadrzaj && klasaMap[klasa] && klasaMap[klasa][sadrzaj]) {
          klasaMap[klasa][sadrzaj].forEach(dosje => {
            const option = new Option(dosje, dosje);
            dosjeSelect.appendChild(option);
          });
        }
        updateKlasaValue();
        checkIfPredmetExists();
      });
    }

    if (dosjeSelect)
      dosjeSelect.addEventListener("change", function() {
        currentValues.dosje = this.value || "";
        updateKlasaValue();
        checkIfPredmetExists();
      });

    otvoriPredmetBtn.addEventListener("click", function() {
      const klasa = klasaSelect.value;
      const sadrzaj = sadrzajSelect.value;
      const dosje = dosjeSelect.value;
      const zaposlenik = zaposlenikSelect.value;
      const naziv = document.getElementById("naziv").value;

      // Get elements related to Stranka field
      const strankaCheckbox = document.getElementById('strankaCheck');
      const strankaField = document.getElementById('stranka');
      const strankaError = document.getElementById('strankaError');

      // Reset any previous error states
      strankaField.classList.remove('is-invalid');
      strankaError.style.display = 'none';

      // 1. VALIDATION FOR ALL REQUIRED FIELDS
      let isValid = true;
      const missingFields = [];

      // Check each required field
      if (!klasa) missingFields.push("Klasa broj");
      if (!sadrzaj) missingFields.push("Sadržaj");
      if (!dosje) missingFields.push("Dosje broj");
      if (!zaposlenik) missingFields.push("Zaposlenik");
      if (!naziv.trim()) missingFields.push("Naziv predmeta");

      const strankaDateError = document.getElementById('strankaDateError');
      if (strankaDateError) {
        strankaDateError.style.display = 'none';
      }

      // 2. SPECIAL VALIDATION FOR STRANKA FIELD
      if (strankaCheckbox.checked) {
        if (!strankaField.value) {
          isValid = false;
          strankaField.classList.add('is-invalid');
          strankaError.style.display = 'block';
          strankaField.focus();
        }

        // Validate date for Stranka
        const strankaDateInput = document.querySelector('input[name="strankaDatumOtvaranja"]');
        if (!strankaDateInput || !strankaDateInput.value) {
          isValid = false;
          // Show date error
          if (strankaDateError) {
            strankaDateError.style.display = 'block';
          } else {
            // Create error element if it doesn't exist
            const errorDiv = document.createElement('div');
            errorDiv.id = 'strankaDateError';
            errorDiv.className = 'seup-field-error';
            errorDiv.textContent = 'Odaberite datum otvaranja predmeta!';
            errorDiv.style.display = 'block';
            document.querySelector('#strankaDatumContainer').appendChild(errorDiv);
          }
        }
      }

      // 3. CHECK IF ANY REQUIRED FIELDS ARE MISSING
      if (missingFields.length > 0) {
        isValid = false;
        // Create alert message listing all missing fields
        const errorMessage = "Molimo vas da popunite sva obavezna polja:\n\n" +
          missingFields.map(field => `- ${field}`).join("\n");
        alert(errorMessage);
      }

      // 4. STOP IF VALIDATION FAILED
      if (!isValid) {
        return;
      }
      const formData = new FormData();
      formData.append("action", "otvori_predmet");
      formData.append("klasa_br", klasa);
      formData.append("sadrzaj", sadrzaj);
      formData.append("dosje_broj", dosje);
      formData.append("zaposlenik", zaposlenik);
      formData.append("god", year);
      formData.append("naziv", naziv);

      // Add Stranka value if checkbox is checked
      if (strankaCheckbox.checked) {
        formData.append("stranka", strankaField.value.trim());
        const strankaDateInput = document.querySelector('input[name="strankaDatumOtvaranja"]');

        if (strankaDateInput && strankaDateInput.value) {
          // Parse the date from DD.MM.YYYY to YYYY-MM-DD
          const [day, month, year] = strankaDateInput.value.split('.');
          const formattedDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
          formData.append("strankaDatumOtvaranja", formattedDate);
        }
      }


      // Get date value and convert to timestamp
      const datumInput = document.querySelector('input[name="datumOtvaranja"]');
      let datumOtvaranjaTimestamp = null;

      if (datumInput && datumInput.value) {
        // Parse the date from DD.MM.YYYY to YYYY-MM-DD
        const [day, month, year] = datumInput.value.split('.');
        const now = new Date();
        datumOtvaranjaTimestamp =
          `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')} ` +
          `${now.getHours().toString().padStart(2, '0')}:` +
          `${now.getMinutes().toString().padStart(2, '0')}:` +
          `${now.getSeconds().toString().padStart(2, '0')}`;
      } else {
        const now = new Date();
        datumOtvaranjaTimestamp =
          `${now.getFullYear()}-` +
          `${(now.getMonth() + 1).toString().padStart(2, '0')}-` +
          `${now.getDate().toString().padStart(2, '0')} ` +
          `${now.getHours().toString().padStart(2, '0')}:` +
          `${now.getMinutes().toString().padStart(2, '0')}:` +
          `${now.getSeconds().toString().padStart(2, '0')}`;
      }

      formData.append("datumOtvaranja", datumOtvaranjaTimestamp);

      // Add selected tags
      selectedTags.forEach(tagId => {
        formData.append("tags[]", tagId);
      });

      fetch("novi_predmet.php", {
          method: "POST",
          body: formData
        })
        .then(async response => {
          const responseText = await response.text(); // First get raw text

          try {
            // Try to parse as JSON
            return JSON.parse(responseText);
          } catch (e) {
            // If parsing fails, throw custom error with server response
            throw new Error(`Invalid JSON response: ${responseText.substring(0, 100)}...`);
          }
        })
        .then(data => {
          if (data.success) {
            alert("Predmet je uspješno otvoren.");

            // Reset klasa display (preserves your klasa/sadrzaj functionality)
            resetKlasaDisplay();

            // Clear main date inputs
            const mainDateInput = document.querySelector('input[name="datumOtvaranja"]');
            if (mainDateInput) {
              mainDateInput.value = '';
              mainDateInput.dispatchEvent(new Event('change'));
            }

            // Clear customer date inputs
            const strankaDateInput = document.querySelector('input[name="strankaDatumOtvaranja"]');
            if (strankaDateInput) {
              strankaDateInput.value = '';
              strankaDateInput.dispatchEvent(new Event('change'));
            }

            // Reset Stranka section
            const strankaCheckbox = document.getElementById('strankaCheck');
            const strankaField = document.getElementById('stranka');
            const strankaError = document.getElementById('strankaError');

            if (strankaCheckbox && strankaField && strankaError) {
              strankaCheckbox.checked = false;

              // Reset Select2 if it exists
              if (strankaField.hasAttribute('data-select2-id')) {
                $(strankaField).val(null).trigger('change');
              } else {
                strankaField.value = '';
              }

              strankaField.disabled = true;
              strankaField.classList.remove('is-invalid');
              strankaError.style.display = 'none';

              // Update button styles
              const strankaCheckLabel = document.getElementById('strankaCheckLabel');
              if (strankaCheckLabel) {
                strankaCheckLabel.classList.remove('seup-btn-primary');
                strankaCheckLabel.classList.add('seup-btn-secondary');
              }

              // Hide date container
              const container = document.getElementById('strankaDatumContainer');
              if (container) container.style.display = 'none';
            }

            // Clear case title
            document.getElementById("naziv").value = "";
            
            // Reset selected tags
            selectedTags.clear();
            selectedTagsContainer.innerHTML = '<span class="seup-text-small" style="color: var(--seup-gray-500); align-self: center;" id="tags-placeholder">Odabrane oznake će se prikazati ovdje</span>';
            
            // Reset tag dropdown
            const buttonText = tagsDropdown.querySelector('span');
            buttonText.textContent = 'Odaberi oznake';
            selectedOption = null;
            document.querySelectorAll('.tag-option').forEach(btn => {
              btn.classList.remove('active');
            });
          } else {
            console.error("Error otvaranje predmeta NOVI_PREDMET:", data.error);
            alert("Greška pri otvaranju predmeta: NOVI_PREDMET " + data.error);
          }
        })
        .catch(error => {
          console.error("CATCH otvaranje predmeta:NOVI_PREDMET", error);
          alert("Došlo je do greške: " + error.message);
        });
    });

    // Update on zaposlenik change
    if (zaposlenikSelect) {
      zaposlenikSelect.addEventListener("change", function() {
        currentValues.zaposlenik = this.value || "DOS";
        updateKlasaValue();
        checkIfPredmetExists();
      });
    }


    // Initial update to set the default state
    updateKlasaValue();


    // Tag selection functionality
    const tagsDropdown = document.getElementById("tagsDropdown");
    const tagsDropdownMenu = document.getElementById("tags-dropdown-menu");
    const availableTags = document.getElementById("available-tags");
    const addTagBtn = document.getElementById("add-tag-btn");
    const selectedTagsContainer = document.getElementById("selected-tags");
    const tagsPlaceholder = document.getElementById("tags-placeholder");
    const selectedTags = new Set();
    
    // Enhanced tag colors array with more variety
    const tagColors = [
      { bg: '#dbeafe', text: '#1e40af', border: '#3b82f6', name: 'blue' },
      { bg: '#f3e8ff', text: '#7c3aed', border: '#8b5cf6', name: 'purple' },
      { bg: '#dcfce7', text: '#16a34a', border: '#22c55e', name: 'green' },
      { bg: '#fed7aa', text: '#ea580c', border: '#f97316', name: 'orange' },
      { bg: '#fce7f3', text: '#db2777', border: '#ec4899', name: 'pink' },
      { bg: '#ccfbf1', text: '#0d9488', border: '#14b8a6', name: 'teal' },
      { bg: '#fef3c7', text: '#d97706', border: '#f59e0b', name: 'amber' },
      { bg: '#e0e7ff', text: '#4f46e5', border: '#6366f1', name: 'indigo' },
      { bg: '#fecaca', text: '#dc2626', border: '#ef4444', name: 'red' },
      { bg: '#d1fae5', text: '#059669', border: '#10b981', name: 'emerald' },
      { bg: '#e0f2fe', text: '#0284c7', border: '#0ea5e9', name: 'sky' },
      { bg: '#fef7cd', text: '#ca8a04', border: '#eab308', name: 'yellow' }
    ];
    
    let colorIndex = 0;

    // Track selected option
    let selectedOption = null;

    // Enhanced dropdown functionality
    if (tagsDropdown) {
      tagsDropdown.addEventListener("click", function(e) {
        e.preventDefault();
        const isOpen = tagsDropdownMenu.style.display === 'block';
        tagsDropdownMenu.style.display = isOpen ? 'none' : 'block';
        
        // Animate dropdown
        if (!isOpen) {
          tagsDropdownMenu.style.opacity = '0';
          tagsDropdownMenu.style.transform = 'translateY(-10px)';
          setTimeout(() => {
            tagsDropdownMenu.style.opacity = '1';
            tagsDropdownMenu.style.transform = 'translateY(0)';
          }, 10);
        }
        
        // Update chevron icon
        const chevron = tagsDropdown.querySelector('.fas');
        if (chevron) {
          chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        }
      });
    }

    // Close dropdown when clicking outside
    document.addEventListener("click", function(e) {
      if (!e.target.closest('.seup-dropdown') && tagsDropdownMenu) {
        tagsDropdownMenu.style.display = 'none';
        const chevron = tagsDropdown?.querySelector('.fas');
        if (chevron) {
          chevron.style.transform = 'rotate(0deg)';
        }
      }
    });

    if (availableTags) {
      availableTags.addEventListener("click", function(e) {
        if (e.target.classList.contains("tag-option")) {
          // Remove active class from all options
          document.querySelectorAll('.tag-option').forEach(btn => {
            btn.classList.remove('active');
          });

          // Set active class on clicked option
          e.target.classList.add('active');
          selectedOption = e.target;

          // Update dropdown button text
          const buttonText = tagsDropdown.querySelector('span');
          if (buttonText) {
            buttonText.textContent = e.target.textContent;
          }
        }
      });
    }

    // Add tag to selection
    if (addTagBtn) {
      addTagBtn.addEventListener("click", function() {
        if (!selectedOption) return;

        const tagId = selectedOption.dataset.tagId;
        const tagName = selectedOption.textContent;

        if (!selectedTags.has(tagId)) {
          selectedTags.add(tagId);
          
          // Hide placeholder if this is the first tag
          if (tagsPlaceholder) {
            tagsPlaceholder.style.display = 'none';
          }
          
          // Get color for this tag
          const color = tagColors[colorIndex % tagColors.length];
          colorIndex++;

          // Create selected tag badge
          const tagElement = document.createElement("div");
          tagElement.className = `seup-tag seup-tag-removable seup-tag-${color.name} seup-fade-in`;
          tagElement.dataset.tagId = tagId;
          tagElement.style.background = color.bg;
          tagElement.style.color = color.text;
          tagElement.style.borderColor = color.border;
          
          tagElement.innerHTML = `
            <i class="fas fa-tag"></i>
            <span class="tag-text">${tagName}</span>
            <button type="button" class="seup-tag-remove" aria-label="Remove">
              <i class="fas fa-times" style="font-size: 0.7rem;"></i>
            </button>
          `;

          selectedTagsContainer.appendChild(tagElement);
          
          // Add entrance animation
          setTimeout(() => {
            tagElement.style.transform = 'scale(1)';
            tagElement.style.opacity = '1';
          }, 10);

          // Reset selection
          selectedOption.classList.remove('active');
          selectedOption = null;
          
          const buttonText = tagsDropdown.querySelector('span');
          if (buttonText) {
            buttonText.textContent = 'Odaberi oznake';
          }
          tagsDropdownMenu.style.display = 'none';
          
          const chevron = tagsDropdown.querySelector('.fas');
          if (chevron) {
            chevron.style.transform = 'rotate(0deg)';
          }
        }
      });
    }

    // Remove tag from selection
    if (selectedTagsContainer) {
      selectedTagsContainer.addEventListener("click", function(e) {
        if (e.target.closest(".seup-tag-remove")) {
          const tagElement = e.target.closest(".seup-tag");
          const tagId = tagElement.dataset.tagId;

          selectedTags.delete(tagId);
          
          // Animate removal
          tagElement.style.opacity = '0';
          tagElement.style.transform = 'scale(0.8)';
          setTimeout(() => {
            tagElement.remove();
            
            // Show placeholder if no tags left
            if (selectedTags.size === 0 && tagsPlaceholder) {
              tagsPlaceholder.style.display = 'block';
            }
          }, 200);
        }
      });
    }
  });



  // document.querySelector('[data-action="generate_pdf"]').addEventListener('click', function() { // TODO ostavi za kasnije (( RADI ))
  //   const generatePdfUrl = '< ?php echo DOL_URL_ROOT; ?>/custom/seup/class/generate_pdf.php';

  //   console.log("Sending request to: " + generatePdfUrl);

  //   fetch(generatePdfUrl, {
  //       method: 'POST'
  //     })
  //     .then(response => response.json())
  //     .then(data => {
  //       console.log("Response data:", data);
  //       if (data.success && data.file) {
  //         const url = new URL(data.file, window.location.origin);
  //         const filename = url.searchParams.get('file');
  //         // Open the generated PDF in a new tab
  //         /*const downloadUrl = '< ?php echo DOL_URL_ROOT; ?>/custom/seup/download_temp_pdf.php?file=' + encodeURIComponent(filename); */
  //         window.open(data.file, '_blank'); // just open the `document.php?modulepart=temp&file=...` URL directly
  //       } else {
  //         throw new Error(data.error || 'PDF generation failed.');
  //       }
  //     })
  //     .catch(error => {
  //       console.error('PDF generation error:', error);
  //       alert('PDF generation failed: ' + error.message);
  //     });
  // });
</script>