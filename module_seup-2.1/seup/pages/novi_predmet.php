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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Local classes
require_once __DIR__ . '/../class/predmet_helper.class.php';
require_once __DIR__ . '/../class/request_handler.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Security check
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = GETPOST('action', 'alpha');
    
    if ($action === 'otvori_predmet') {
        Request_Handler::handleOtvoriPredmet($db);
        exit;
    }
    
    if ($action === 'check_predmet_exists') {
        Request_Handler::handleCheckPredmetExists($db);
        exit;
    }
    
    if ($action === 'stranka_autocomplete') {
        Request_Handler::handleStrankaAutocomplete($db);
        exit;
    }
}

// Fetch dropdown data
$klasaOptions = '';
$klasaMapJson = '';
$zaposlenikOptions = '';
Predmet_helper::fetchDropdownData($db, $langs, $klasaOptions, $klasaMapJson, $zaposlenikOptions);

// Fetch available tags
$availableTags = [];
$sql = "SELECT rowid, tag, tag_color FROM " . MAIN_DB_PREFIX . "a_tagovi WHERE entity = " . (int)$conf->entity . " ORDER BY tag ASC";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $availableTags[] = [
            'id' => $obj->rowid,
            'name' => $obj->tag,
            'color' => $obj->tag_color ?: 'blue'
        ];
    }
}

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("NewCase"), '', '', 0, 0, '', '', '', 'mod-seup page-novi-predmet');

// Modern SEUP Styles
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

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
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h2 class="seup-heading-3" style="margin: 0;">Kreiranje Novog Predmeta</h2>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Unesite podatke za novi predmet</p>';
print '</div>';
print '<div class="seup-card-body">';

// Form
print '<form id="noviPredmetForm" method="post">';
print '<input type="hidden" name="action" value="otvori_predmet">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print '<div class="seup-grid seup-grid-2">';

// Left column
print '<div>';

print '<div class="seup-form-group">';
print '<label for="klasa_br" class="seup-label">Klasa Broj</label>';
print '<select name="klasa_br" id="klasa_br" class="seup-select" required>';
print $klasaOptions;
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="sadrzaj" class="seup-label">Sadržaj</label>';
print '<select name="sadrzaj" id="sadrzaj" class="seup-select" required>';
print '<option value="">Odaberi sadržaj</option>';
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="dosje_broj" class="seup-label">Dosje Broj</label>';
print '<select name="dosje_broj" id="dosje_broj" class="seup-select" required>';
print '<option value="">Odaberi dosje broj</option>';
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="zaposlenik" class="seup-label">Zaposlenik</label>';
print '<select name="zaposlenik" id="zaposlenik" class="seup-select" required>';
print $zaposlenikOptions;
print '</select>';
print '</div>';

print '</div>'; // End left column

// Right column
print '<div>';

print '<div class="seup-form-group">';
print '<label for="god" class="seup-label">Godina</label>';
print '<input type="number" name="god" id="god" class="seup-input" value="' . date('Y') . '" min="2020" max="2030" required>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="naziv" class="seup-label">Naziv Predmeta</label>';
print '<textarea name="naziv" id="naziv" class="seup-textarea" rows="3" placeholder="Unesite naziv predmeta..." required></textarea>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="stranka" class="seup-label">Stranka (opcionalno)</label>';
print '<input type="text" name="stranka" id="stranka" class="seup-input" placeholder="Unesite naziv stranke...">';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-label">Oznake</label>';
print '<div class="seup-selected-tags-display" id="selectedTagsDisplay"></div>';
print '<button type="button" class="seup-btn seup-btn-secondary seup-mt-2" onclick="openTagsModal()">';
print '<i class="fas fa-tags"></i> Odaberi Oznake';
print '</button>';
print '</div>';

print '</div>'; // End right column

print '</div>'; // End grid

print '<div class="seup-form-actions">';
print '<button type="submit" class="seup-btn seup-btn-primary seup-interactive">';
print '<i class="fas fa-plus"></i> Kreiraj Predmet';
print '</button>';
print '<a href="predmeti.php" class="seup-btn seup-btn-secondary">';
print '<i class="fas fa-arrow-left"></i> Povratak';
print '</a>';
print '</div>';

print '</form>';
print '</div>'; // End card body
print '</div>'; // End card

// Tags Modal
print '<div id="tagsModal" class="seup-tags-modal">';
print '<div class="seup-tags-modal-content">';
print '<div class="seup-tags-modal-header">';
print '<h3 class="seup-tags-modal-title">';
print '<i class="fas fa-tags"></i> Odaberi Oznake';
print '</h3>';
print '<button class="seup-tags-modal-close" onclick="closeTagsModal()">';
print '<i class="fas fa-times"></i>';
print '</button>';
print '</div>';
print '<div class="seup-tags-modal-body">';

if (!empty($availableTags)) {
    print '<div class="seup-tags-search">';
    print '<div style="position: relative;">';
    print '<input type="text" class="seup-input" placeholder="Pretraži oznake..." id="tagsSearchInput">';
    print '<i class="fas fa-search seup-tags-search-icon"></i>';
    print '</div>';
    print '</div>';
    
    print '<div id="tagsGridContainer" class="seup-tags-grid-modal">';
    foreach ($availableTags as $tag) {
        print '<div class="seup-tag-option-modal" data-tag-id="' . $tag['id'] . '" onclick="toggleTagSelection(' . $tag['id'] . ', this)">';
        print '<i class="fas fa-tag" style="color: var(--seup-' . $tag['color'] . ', #3b82f6);"></i>';
        print $tag['name'];
        print '</div>';
    }
    print '</div>';
} else {
    print '<div class="seup-empty-state">';
    print '<div class="seup-empty-state-icon">';
    print '<i class="fas fa-tags"></i>';
    print '</div>';
    print '<h3 class="seup-empty-state-title">Nema dostupnih oznaka</h3>';
    print '<p class="seup-empty-state-description">Dodajte oznake u postavkama</p>';
    print '<a href="tagovi.php" class="seup-btn seup-btn-primary">';
    print '<i class="fas fa-plus"></i> Dodaj Oznake';
    print '</a>';
    print '</div>';
}

print '</div>'; // End modal body
print '<div class="seup-tags-modal-footer">';
print '<div class="seup-tags-count">';
print '<i class="fas fa-check-circle"></i>';
print '<span id="selectedCount">0 odabrano</span>';
print '</div>';
print '<div class="seup-flex seup-gap-2">';
print '<button class="seup-btn seup-btn-secondary" onclick="closeTagsModal()">';
print '<i class="fas fa-times"></i> Odustani';
print '</button>';
print '<button class="seup-btn seup-btn-primary" onclick="confirmTagSelection()">';
print '<i class="fas fa-check"></i> Potvrdi';
print '</button>';
print '</div>';
print '</div>';
print '</div>'; // End modal content
print '</div>'; // End modal

print '</div>'; // End container

// Load JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';

?>

<script>
// Global variables
let selectedTags = new Set();
const availableTags = <?php echo json_encode($availableTags); ?>;
const klasaMap = <?php echo $klasaMapJson; ?>;

// Modal functions
function openTagsModal() {
    const modal = document.getElementById('tagsModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Focus search input
    setTimeout(() => {
        const searchInput = document.getElementById('tagsSearchInput');
        if (searchInput) {
            searchInput.focus();
        }
    }, 100);
}

function closeTagsModal() {
    const modal = document.getElementById('tagsModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function toggleTagSelection(tagId, element) {
    if (selectedTags.has(tagId)) {
        selectedTags.delete(tagId);
        element.classList.remove('selected');
    } else {
        selectedTags.add(tagId);
        element.classList.add('selected');
    }
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const countElement = document.getElementById('selectedCount');
    if (countElement) {
        const count = selectedTags.size;
        countElement.textContent = `${count} odabrano`;
    }
}

function confirmTagSelection() {
    updateSelectedTagsDisplay();
    updateFormInputs();
    closeTagsModal();
    
    if (window.seupNotifications) {
        const count = selectedTags.size;
        window.seupNotifications.show(`Odabrano ${count} oznaka`, 'success', 3000);
    }
}

function updateSelectedTagsDisplay() {
    const displayArea = document.getElementById('selectedTagsDisplay');
    if (!displayArea) return;

    displayArea.innerHTML = '';
    
    if (selectedTags.size === 0) {
        displayArea.classList.add('empty');
        return;
    }
    
    displayArea.classList.remove('empty');
    
    selectedTags.forEach(tagId => {
        const tag = availableTags.find(t => t.id == tagId);
        if (tag) {
            const tagElement = document.createElement('div');
            tagElement.className = 'seup-tag-selected';
            tagElement.innerHTML = `
                <i class="fas fa-tag" style="color: var(--seup-${tag.color}, #3b82f6);"></i>
                ${tag.name}
                <button type="button" class="seup-tag-remove-btn" onclick="removeTag(${tag.id})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            displayArea.appendChild(tagElement);
        }
    });
}

function updateFormInputs() {
    // Remove existing hidden inputs
    document.querySelectorAll('input[name="tags[]"]').forEach(input => {
        input.remove();
    });
    
    // Add new hidden inputs for selected tags
    const form = document.getElementById('noviPredmetForm');
    if (form) {
        selectedTags.forEach(tagId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tags[]';
            input.value = tagId;
            form.appendChild(input);
        });
    }
}

function removeTag(tagId) {
    selectedTags.delete(tagId);
    updateSelectedTagsDisplay();
    updateFormInputs();
    updateSelectedCount();
    
    // Update modal if open
    const tagElement = document.querySelector(`[data-tag-id="${tagId}"]`);
    if (tagElement) {
        tagElement.classList.remove('selected');
    }
}

// Search functionality in modal
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tagsSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const tagElements = document.querySelectorAll('.seup-tag-option-modal');
            
            tagElements.forEach(element => {
                const tagName = element.textContent.toLowerCase();
                if (tagName.includes(searchTerm)) {
                    element.style.display = 'flex';
                } else {
                    element.style.display = 'none';
                }
            });
        });
    }

    // Close modal on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('tagsModal');
            if (modal && modal.style.display === 'flex') {
                closeTagsModal();
            }
        }
    });

    // Close modal on overlay click
    const modal = document.getElementById('tagsModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeTagsModal();
            }
        });
    }

    // Dropdown cascading logic
    const klasaBrSelect = document.getElementById('klasa_br');
    const sadrzajSelect = document.getElementById('sadrzaj');
    const dosjeBrojSelect = document.getElementById('dosje_broj');

    if (klasaBrSelect && sadrzajSelect && dosjeBrojSelect) {
        klasaBrSelect.addEventListener('change', function() {
            const selectedKlasa = this.value;
            
            // Clear and populate sadrzaj
            sadrzajSelect.innerHTML = '<option value="">Odaberi sadržaj</option>';
            dosjeBrojSelect.innerHTML = '<option value="">Odaberi dosje broj</option>';
            
            if (selectedKlasa && klasaMap[selectedKlasa]) {
                Object.keys(klasaMap[selectedKlasa]).forEach(sadrzaj => {
                    const option = document.createElement('option');
                    option.value = sadrzaj;
                    option.textContent = sadrzaj;
                    sadrzajSelect.appendChild(option);
                });
            }
        });

        sadrzajSelect.addEventListener('change', function() {
            const selectedKlasa = klasaBrSelect.value;
            const selectedSadrzaj = this.value;
            
            // Clear and populate dosje broj
            dosjeBrojSelect.innerHTML = '<option value="">Odaberi dosje broj</option>';
            
            if (selectedKlasa && selectedSadrzaj && klasaMap[selectedKlasa] && klasaMap[selectedKlasa][selectedSadrzaj]) {
                klasaMap[selectedKlasa][selectedSadrzaj].forEach(dosjeBroj => {
                    const option = document.createElement('option');
                    option.value = dosjeBroj;
                    option.textContent = dosjeBroj;
                    dosjeBrojSelect.appendChild(option);
                });
            }
        });
    }

    // Form submission
    const form = document.getElementById('noviPredmetForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.seupNotifications?.show(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'predmet.php?id=' + result.predmet_id;
                    }, 1500);
                } else {
                    window.seupNotifications?.show(result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                window.seupNotifications?.show('Greška pri kreiranju predmeta', 'error');
            }
        });
    }
});
</script>

<?php

llxFooter();
$db->close();

?>