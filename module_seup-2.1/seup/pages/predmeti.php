<?php

/**
 * Plaćena licenca
 * (c) 2025 8Core Association
 * Tomislav Galić <tomislav@8core.hr>
 * Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima 
 * te ga je izričito zabranjeno umnožavati, distribuirati, mijenjati, objavljivati ili 
 * na drugi način eksploatirati bez pismenog odobrenja autora.
 * U skladu sa Zakonom o autorskom pravu i srodnim pravima 
 * (NN 167/03, 79/07, 80/11, 125/17), a osobito člancima 32. (pravo na umnožavanje), 35. 
 * (pravo na preradu i distribuciju) i 76. (kaznene odredbe), 
 * svako neovlašteno umnožavanje ili prerada ovog softvera smatra se prekršajem. 
 * Prema Kaznenom zakonu (NN 125/11, 144/12, 56/15), članak 228., stavak 1., 
 * prekršitelj se može kazniti novčanom kaznom ili zatvorom do jedne godine, 
 * a sud može izreći i dodatne mjere oduzimanja protivpravne imovinske koristi.
 * Bilo kakve izmjene, prijevodi, integracije ili dijeljenje koda bez izričitog pismenog 
 * odobrenja autora smatraju se kršenjem ugovora i zakona te će se pravno sankcionirati. 
 * Za sva pitanja, zahtjeve za licenciranjem ili dodatne informacije obratite se na info@8core.hr.
 */
/**
 *    \file       seup/pages/predmeti.php
 *    \ingroup    seup
 *    \brief      Lista predmeta
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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

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
$sortField = GETPOST('sort', 'aZ09') ?: 'ID_predmeta';
$sortOrder = GETPOST('order', 'aZ09') ?: 'DESC';

// Validate sort fields
$allowedSortFields = [
    'ID_predmeta',
    'klasa_br',
    'naziv_predmeta',
    'name_ustanova',
    'ime_prezime',
    'tstamp_created'
];

if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'ID_predmeta';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Use specialized helper for predmeti
$orderByClause = Predmet_helper::buildOrderByKlasa($sortField, $sortOrder, 'p');

// Fetch all predmeti
$sql = "SELECT 
            p.ID_predmeta,
            CONCAT(p.klasa_br, '-', p.sadrzaj, '/', p.godina, '-', p.dosje_broj, '/', p.predmet_rbr) as klasa,
            p.naziv_predmeta,
            DATE_FORMAT(p.tstamp_created, '%d.%m.%Y') as datum_otvaranja,
            u.name_ustanova,
            k.ime_prezime
        FROM " . MAIN_DB_PREFIX . "a_predmet p
        LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
        LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON p.ID_interna_oznaka_korisnika = k.ID
        {$orderByClause}";

$resql = $db->query($sql);
$predmeti = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $predmeti[] = $obj;
    }
}

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("OpenCases"), '', '', 0, 0, '', '', '', 'mod-seup page-predmeti');

// Modern SEUP Styles
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Page Header
print '<div class="seup-page-header">';
print '<div class="seup-container">';
print '<h1 class="seup-page-title">Pregled Predmeta</h1>';
print '<div class="seup-breadcrumb">';
print '<a href="../seupindex.php">SEUP</a>';
print '<i class="fas fa-chevron-right"></i>';
print '<span>Predmeti</span>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-container">';

// Stats Cards
print '<div class="seup-grid seup-grid-3 seup-mb-8">';

// Total Cases Card
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-primary-600);">';
print '<i class="fas fa-folder-open"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">' . count($predmeti) . '</h3>';
print '<p class="seup-text-small">Ukupno Predmeta</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Active Cases Card
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-success);">';
print '<i class="fas fa-check-circle"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">' . count($predmeti) . '</h3>';
print '<p class="seup-text-small">Aktivni Predmeti</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Quick Actions Card
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-accent);">';
print '<i class="fas fa-plus"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">Brze Akcije</h3>';
print '<p class="seup-text-small">Novi predmet ili izvoz</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End stats grid

// Main Content Card
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<div>';
print '<h2 class="seup-heading-3" style="margin: 0;">' . $langs->trans('OpenCases') . '</h2>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Pregled svih predmeta u sustavu</p>';
print '</div>';
print '<div class="seup-flex seup-gap-2">';
print '<a href="novi_predmet.php" class="seup-btn seup-btn-primary seup-interactive">';
print '<i class="fas fa-plus"></i> ' . $langs->trans('NewCase');
print '</a>';
print '<button class="seup-btn seup-btn-secondary seup-interactive" onclick="exportToExcel()">';
print '<i class="fas fa-file-excel"></i> ' . $langs->trans('ExportExcel');
print '</button>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-card-body">';

// Search and Filter Section
print '<div class="seup-flex seup-gap-4 seup-mb-6" style="flex-wrap: wrap;">';
print '<div class="seup-form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">';
print '<label class="seup-label">Pretraži predmete</label>';
print '<div style="position: relative;">';
print '<input type="text" class="seup-input" placeholder="Pretraži po klasi, nazivu ili zaposleniku..." id="searchInput" style="padding-left: var(--seup-space-10);">';
print '<i class="fas fa-search" style="position: absolute; left: var(--seup-space-3); top: 50%; transform: translateY(-50%); color: var(--seup-gray-400);"></i>';
print '</div>';
print '</div>';
print '<div class="seup-form-group" style="margin-bottom: 0;">';
print '<label class="seup-label">Status</label>';
print '<select class="seup-select" id="statusFilter">';
print '<option value="">Svi statusi</option>';
print '<option value="active">Aktivni</option>';
print '<option value="closed">Zatvoreni</option>';
print '</select>';
print '</div>';
print '</div>';

// Generate HTML table
if (count($predmeti) > 0) {
    print '<div style="overflow-x: auto;">';
    print '<table class="seup-table" id="predmetiTable">';
    print '<thead>';
    print '<tr>';

    // Function to generate sortable header
    function sortableHeader($field, $label, $currentSort, $currentOrder) {
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
    print sortableHeader('ID_predmeta', $langs->trans('ID'), $sortField, $sortOrder);
    print sortableHeader('klasa_br', $langs->trans('Klasa'), $sortField, $sortOrder);
    print sortableHeader('naziv_predmeta', $langs->trans('NazivPredmeta'), $sortField, $sortOrder);
    print sortableHeader('name_ustanova', $langs->trans('Ustanova'), $sortField, $sortOrder);
    print sortableHeader('ime_prezime', $langs->trans('Zaposlenik'), $sortField, $sortOrder);
    print sortableHeader('tstamp_created', $langs->trans('DatumOtvaranja'), $sortField, $sortOrder);
    print '<th>' . $langs->trans('Actions') . '</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody>';

    foreach ($predmeti as $predmet) {
        print '<tr class="predmet-row" data-search="' . strtolower($predmet->klasa . ' ' . $predmet->naziv_predmeta . ' ' . $predmet->ime_prezime) . '">';
        print '<td><span class="seup-badge seup-badge-primary">' . $predmet->ID_predmeta . '</span></td>';
        print '<td><code style="font-family: var(--seup-font-mono); background: var(--seup-gray-100); padding: var(--seup-space-1) var(--seup-space-2); border-radius: var(--seup-radius-sm);">' . $predmet->klasa . '</code></td>';
        print '<td><strong>' . dol_trunc($predmet->naziv_predmeta, 50) . '</strong></td>';
        print '<td>' . $predmet->name_ustanova . '</td>';
        print '<td>' . $predmet->ime_prezime . '</td>';
        print '<td>' . $predmet->datum_otvaranja . '</td>';
        
        // Action buttons
        print '<td>';
        print '<div class="seup-flex seup-gap-2">';
        print '<a href="predmet.php?id=' . $predmet->ID_predmeta . '" class="seup-btn seup-btn-sm seup-btn-primary seup-tooltip" data-tooltip="' . $langs->trans('ViewDetails') . '">';
        print '<i class="fas fa-eye"></i>';
        print '</a>';
        print '<a href="edit_predmet.php?id=' . $predmet->ID_predmeta . '" class="seup-btn seup-btn-sm seup-btn-secondary seup-tooltip" data-tooltip="' . $langs->trans('Edit') . '">';
        print '<i class="fas fa-edit"></i>';
        print '</a>';
        print '<button class="seup-btn seup-btn-sm seup-btn-danger seup-tooltip" data-tooltip="' . $langs->trans('CloseCase') . '" onclick="closeCase(' . $predmet->ID_predmeta . ')">';
        print '<i class="fas fa-times"></i>';
        print '</button>';
        print '</div>';
        print '</td>';
        
        print '</tr>';
    }

    print '</tbody>';
    print '</table>';
    print '</div>';
} else {
    print '<div class="seup-empty-state">';
    print '<div class="seup-empty-state-icon">';
    print '<i class="fas fa-folder-open"></i>';
    print '</div>';
    print '<h3 class="seup-empty-state-title">' . $langs->trans('NoOpenCases') . '</h3>';
    print '<p class="seup-empty-state-description">Trenutno nema otvorenih predmeta u sustavu</p>';
    print '<a href="novi_predmet.php" class="seup-btn seup-btn-primary seup-interactive">';
    print '<i class="fas fa-plus"></i> Kreiraj Prvi Predmet';
    print '</a>';
    print '</div>';
}

print '</div>'; // End card body

// Card Footer with pagination info
print '<div class="seup-card-footer">';
print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<div class="seup-text-small" style="color: var(--seup-gray-500);">';
print '<i class="fas fa-info-circle"></i> ' . sprintf($langs->trans('ShowingCases'), count($predmeti));
print '</div>';
print '<div class="seup-flex seup-gap-2">';
print '<button class="seup-btn seup-btn-sm seup-btn-secondary" onclick="refreshTable()">';
print '<i class="fas fa-sync-alt"></i> Osvježi';
print '</button>';
print '<button class="seup-btn seup-btn-sm seup-btn-secondary" onclick="printTable()">';
print '<i class="fas fa-print"></i> Ispiši';
print '</button>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End main card
print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';

?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tableRows = document.querySelectorAll('.predmet-row');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;

        tableRows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            const matchesSearch = searchData.includes(searchTerm);
            const matchesStatus = !statusValue || statusValue === 'active'; // All current cases are active
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                row.classList.add('seup-fade-in');
            } else {
                row.style.display = 'none';
                row.classList.remove('seup-fade-in');
            }
        });

        // Update results count
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        const countElement = document.querySelector('.seup-card-footer .seup-text-small');
        if (countElement) {
            countElement.innerHTML = '<i class="fas fa-info-circle"></i> Prikazano ' + visibleRows.length + ' od ' + tableRows.length + ' predmeta';
        }
    }

    // Debounced search
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterTable, 300);
    });

    statusFilter.addEventListener('change', filterTable);

    // Enhanced row interactions
    tableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (!e.target.closest('button, a')) {
                // Get the predmet ID and navigate to details
                const predmetId = this.querySelector('.seup-badge').textContent;
                window.location.href = 'predmet.php?id=' + predmetId;
            }
        });

        // Add hover effect
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'var(--seup-primary-50)';
            this.style.cursor = 'pointer';
        });

        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.cursor = '';
        });
    });
});

// Utility functions
function closeCase(caseId) {
    if (confirm('Jeste li sigurni da želite zatvoriti ovaj predmet?')) {
        // Here you would implement the close case functionality
        window.seupNotifications?.show('Predmet #' + caseId + ' je zatvoren', 'success');
        
        // Remove the row with animation
        const row = document.querySelector(`[data-case-id="${caseId}"]`);
        if (row) {
            row.style.opacity = '0';
            row.style.transform = 'translateX(-100%)';
            setTimeout(() => row.remove(), 300);
        }
    }
}

function exportToExcel() {
    window.seupNotifications?.show('Izvoz u Excel je pokrenut...', 'info');
    // Here you would implement Excel export functionality
}

function refreshTable() {
    window.location.reload();
}

function printTable() {
    window.print();
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N for new case
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        window.location.href = 'novi_predmet.php';
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        filterTable();
    }
});
</script>

<?php

llxFooter();
$db->close();

?>