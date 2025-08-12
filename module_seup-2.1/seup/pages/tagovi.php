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
 *	\file       seup/tagovi.php
 *	\ingroup    seup
 *	\brief      Tagovi page
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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Učitaj datoteke prijevoda
$langs->loadLangs(array("seup@seup"));

$action = GETPOST('action', 'aZ09');
$now = dol_now();

// Sigurnosna provjera
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

// Process form submission
$error = 0;
$success = 0;
$tag_name = '';

if ($action == 'addtag' && !empty($_POST['tag'])) {
    $tag_name = GETPOST('tag', 'alphanohtml');

    // Validate input
    if (dol_strlen($tag_name) < 2) {
        $error++;
        setEventMessages($langs->trans('ErrorTagTooShort'), null, 'errors');
    } else {
        $db->begin();

        // Check if tag already exists
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "a_tagovi";
        $sql .= " WHERE tag = '" . $db->escape($tag_name) . "'";
        $sql .= " AND entity = " . $conf->entity;

        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql) > 0) {
                $error++;
                setEventMessages($langs->trans('ErrorTagAlreadyExists'), null, 'errors');
            } else {
                // Insert new tag
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_tagovi";
                $sql .= " (tag, entity, date_creation, fk_user_creat)";
                $sql .= " VALUES ('" . $db->escape($tag_name) . "',";
                $sql .= " " . $conf->entity . ",";
                $sql .= " '" . $db->idate(dol_now()) . "',";
                $sql .= " " . $user->id . ")";

                $resql = $db->query($sql);
                if ($resql) {
                    $db->commit();
                    $success++;
                    $tag_name = ''; // Reset input field
                    setEventMessages($langs->trans('TagAddedSuccessfully'), null, 'mesgs');
                } else {
                    $db->rollback();
                    $error++;
                    setEventMessages($langs->trans('ErrorTagNotAdded') . ' ' . $db->lasterror(), null, 'errors');
                }
            }
        } else {
            $db->rollback();
            $error++;
            setEventMessages($langs->trans('ErrorDatabaseRequest') . ' ' . $db->lasterror(), null, 'errors');
        }
    }
}

if ($action == 'deletetag') {
    $tagid = GETPOST('tagid', 'int');
    if ($tagid > 0) {
        $db->begin();

        // First delete associations in a_predmet_tagovi
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_predmet_tagovi";
        $sql .= " WHERE fk_tag = " . $tagid;
        $resql = $db->query($sql);

        if ($resql) {
            // Then delete the tag itself
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_tagovi";
            $sql .= " WHERE rowid = " . $tagid;
            $sql .= " AND entity = " . $conf->entity;

            $resql = $db->query($sql);
            if ($resql) {
                $db->commit();
                setEventMessages($langs->trans('TagDeletedSuccessfully'), null, 'mesgs');
            } else {
                $db->rollback();
                setEventMessages($langs->trans('ErrorTagNotDeleted') . ' ' . $db->lasterror(), null, 'errors');
            }
        } else {
            $db->rollback();
            setEventMessages($langs->trans('ErrorDeletingTagAssociations') . ' ' . $db->lasterror(), null, 'errors');
        }
    }
}

// Get real statistics from database
$totalTags = 0;
$activeTags = 0;

// Count total tags
$sql = "SELECT COUNT(*) as total FROM " . MAIN_DB_PREFIX . "a_tagovi WHERE entity = " . $conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $totalTags = $obj->total;
}

// Count tags that are actually used
$sql = "SELECT COUNT(DISTINCT t.rowid) as active 
        FROM " . MAIN_DB_PREFIX . "a_tagovi t
        INNER JOIN " . MAIN_DB_PREFIX . "a_predmet_tagovi pt ON t.rowid = pt.fk_tag
        WHERE t.entity = " . $conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $activeTags = $obj->active;
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("Tagovi"), '', '', 0, 0, '', '', '', 'mod-seup page-tagovi');

// Modern SEUP Styles
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Page Header
print '<div class="seup-page-header">';
print '<div class="seup-container">';
print '<h1 class="seup-page-title">Upravljanje Oznakama</h1>';
print '<div class="seup-breadcrumb">';
print '<a href="../seupindex.php">SEUP</a>';
print '<i class="fas fa-chevron-right"></i>';
print '<span>Tagovi</span>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-container">';

// Stats Cards
print '<div class="seup-grid seup-grid-3 seup-mb-8">';

// Total Tags Card
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-primary-600);">';
print '<i class="fas fa-tags"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">' . $totalTags . '</h3>';
print '<p class="seup-text-small">Ukupno Oznaka</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Active Tags Card
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-success);">';
print '<i class="fas fa-check-circle"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">' . $activeTags . '</h3>';
print '<p class="seup-text-small">Aktivne Oznake</p>';
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
print '<p class="seup-text-small">Dodaj novu oznaku</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End stats grid

// Main Content
print '<div class="seup-grid seup-grid-2">';

// Left Column - Add New Tag Form
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Dodaj Novu Oznaku</h3>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Kreirajte novu oznaku za kategorizaciju</p>';
print '</div>';
print '<div class="seup-card-body">';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" id="tagForm">';
print '<input type="hidden" name="action" value="addtag">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print '<div class="seup-form-group">';
print '<label for="tag" class="seup-label">Naziv Oznake</label>';
print '<div class="seup-input-group">';
print '<input type="text" name="tag" id="tag" class="seup-input seup-input-enhanced" placeholder="Unesite naziv oznake..." value="' . dol_escape_htmltag($tag_name) . '" required maxlength="50">';
print '<i class="fas fa-tag seup-input-icon"></i>';
print '</div>';
print '<div class="seup-char-counter" id="charCounter">0/50</div>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-label">Boja Oznake</label>';
print '<div class="seup-color-picker">';
$colors = ['blue', 'purple', 'green', 'orange', 'pink', 'teal', 'amber', 'indigo', 'red', 'emerald', 'sky', 'yellow'];
foreach ($colors as $color) {
    print '<div class="seup-color-option seup-tag-' . $color . '" data-color="' . $color . '">';
    print '<i class="fas fa-check" style="display: none;"></i>';
    print '</div>';
}
print '</div>';
print '<input type="hidden" name="tag_color" id="selectedColor" value="blue">';
print '</div>';

print '<div class="seup-help-text">';
print '<i class="fas fa-info-circle"></i> ' . $langs->trans('TagoviHelpText');
print '</div>';

print '<div class="seup-form-actions">';
print '<button type="submit" class="seup-btn seup-btn-primary seup-interactive" id="submitBtn">';
print '<i class="fas fa-plus"></i> ' . $langs->trans('DodajTag');
print '</button>';
print '<button type="reset" class="seup-btn seup-btn-secondary">';
print '<i class="fas fa-undo"></i> Resetiraj';
print '</button>';
print '</div>';

print '</form>';
print '</div>';
print '</div>';

// Right Column - Existing Tags
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">' . $langs->trans('ExistingTags') . '</h3>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Pregled postojećih oznaka u sustavu</p>';
print '</div>';
print '<div class="seup-card-body">';

// Search input
print '<div class="seup-form-group">';
print '<div class="seup-input-group">';
print '<input type="text" id="searchTags" class="seup-input seup-input-enhanced" placeholder="Pretraži oznake...">';
print '<i class="fas fa-search seup-input-icon"></i>';
print '</div>';
print '</div>';

// Display existing tags with real data
$sql = "SELECT t.rowid, t.tag, t.date_creation, u.firstname, u.lastname,
               COUNT(pt.fk_predmet) as usage_count
        FROM " . MAIN_DB_PREFIX . "a_tagovi t
        LEFT JOIN " . MAIN_DB_PREFIX . "user u ON t.fk_user_creat = u.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet_tagovi pt ON t.rowid = pt.fk_tag
        WHERE t.entity = " . $conf->entity . "
        GROUP BY t.rowid, t.tag, t.date_creation, u.firstname, u.lastname
        ORDER BY t.tag ASC";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    
    if ($num > 0) {
        print '<div class="seup-tags-grid" id="tagsContainer">';
        
        while ($obj = $db->fetch_object($resql)) {
            $creatorName = dolGetFirstLastname($obj->firstname, $obj->lastname);
            $usageCount = (int)$obj->usage_count;
            
            print '<div class="seup-tag-card-compact seup-interactive" data-tag="' . strtolower($obj->tag) . '">';
            
            print '<div class="seup-tag-card-header-compact">';
            print '<div class="seup-tag seup-tag-primary">';
            print '<i class="fas fa-tag"></i> ' . dol_escape_htmltag($obj->tag);
            print '</div>';
            print '<div class="seup-tag-actions">';
            print '<button class="seup-btn seup-btn-sm seup-btn-secondary seup-tooltip" data-tooltip="Uredi" style="z-index: 1100;">';
            print '<i class="fas fa-edit"></i>';
            print '</button>';
            
            // Delete form
            print '<form method="POST" action="" style="display:inline;" onsubmit="return confirm(\'' . dol_escape_js($langs->trans('ConfirmDeleteTag')) . '\')">';
            print '<input type="hidden" name="action" value="deletetag">';
            print '<input type="hidden" name="tagid" value="' . $obj->rowid . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<button type="submit" class="seup-btn seup-btn-sm seup-btn-danger seup-tooltip" data-tooltip="Obriši" style="z-index: 1100;">';
            print '<i class="fas fa-trash"></i>';
            print '</button>';
            print '</form>';
            
            print '</div>';
            print '</div>';
            
            print '<div class="seup-tag-card-body-compact">';
            print '<div class="seup-tag-meta-compact">';
            print '<div class="seup-meta-item">';
            print '<i class="fas fa-calendar"></i>';
            print '<span>' . dol_print_date($db->jdate($obj->date_creation), 'day') . '</span>';
            print '</div>';
            print '<div class="seup-meta-item">';
            print '<i class="fas fa-user"></i>';
            print '<span>' . ($creatorName ?: 'Nepoznato') . '</span>';
            print '</div>';
            print '</div>';
            
            print '<div class="seup-tag-usage-compact">';
            print '<div class="seup-usage-text">';
            print '<span>Koristi se u ' . $usageCount . ' predmeta</span>';
            print '</div>';
            print '</div>';
            
            print '</div>'; // End card body
            print '</div>'; // End card
        }
        
        print '</div>'; // End tags grid
    } else {
        print '<div class="seup-empty-state">';
        print '<div class="seup-empty-state-icon">';
        print '<i class="fas fa-tags"></i>';
        print '</div>';
        print '<h3 class="seup-empty-state-title">' . $langs->trans('NoTagsAvailable') . '</h3>';
        print '<p class="seup-empty-state-description">Dodajte prvu oznaku koristeći formu lijevo</p>';
        print '</div>';
    }
} else {
    print '<div class="seup-alert seup-alert-error">';
    print '<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('ErrorLoadingTags');
    print '</div>';
}

print '</div>'; // End right column card body
print '</div>'; // End right column card

print '</div>'; // End main grid
print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';

?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter
    const tagInput = document.getElementById('tag');
    const charCounter = document.getElementById('charCounter');
    const submitBtn = document.getElementById('submitBtn');
    
    // Color picker functionality
    const colorOptions = document.querySelectorAll('.seup-color-option');
    const selectedColorInput = document.getElementById('selectedColor');
    
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all options
            colorOptions.forEach(opt => {
                opt.classList.remove('active');
                opt.querySelector('i').style.display = 'none';
            });
            
            // Add active class to clicked option
            this.classList.add('active');
            this.querySelector('i').style.display = 'flex';
            
            // Update hidden input
            selectedColorInput.value = this.getAttribute('data-color');
        });
    });
    
    // Set default color (blue)
    if (colorOptions.length > 0) {
        colorOptions[0].classList.add('active');
        colorOptions[0].querySelector('i').style.display = 'flex';
    }
    
    if (tagInput && charCounter) {
        function updateCharCounter() {
            const length = tagInput.value.length;
            charCounter.textContent = length + '/50';
            
            // Change color based on length
            if (length < 2) {
                charCounter.style.color = 'var(--seup-error)';
                submitBtn.disabled = true;
                submitBtn.classList.add('disabled');
            } else if (length > 45) {
                charCounter.style.color = 'var(--seup-warning)';
                submitBtn.disabled = false;
                submitBtn.classList.remove('disabled');
            } else {
                charCounter.style.color = 'var(--seup-success)';
                submitBtn.disabled = false;
                submitBtn.classList.remove('disabled');
            }
        }
        
        tagInput.addEventListener('input', updateCharCounter);
        updateCharCounter(); // Initial call
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchTags');
    const tagCards = document.querySelectorAll('.seup-tag-card-compact');
    const colorFilterBtns = document.querySelectorAll('.seup-color-filter-btn');
    
    let currentColorFilter = 'all';
    
    function filterTags() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        
        tagCards.forEach(card => {
            const tagName = card.getAttribute('data-tag');
            const tagColor = card.getAttribute('data-color');
            
            const matchesSearch = !searchTerm || tagName.includes(searchTerm);
            const matchesColor = currentColorFilter === 'all' || tagColor === currentColorFilter;
            
            if (matchesSearch && matchesColor) {
                card.style.display = 'block';
                card.classList.add('seup-fade-in');
            } else {
                card.style.display = 'none';
                card.classList.remove('seup-fade-in');
            }
        });
    }
    
    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', filterTags);
    }
    
    // Color filter functionality
    colorFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            colorFilterBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update current filter
            currentColorFilter = this.getAttribute('data-color');
            
            // Apply filter
            filterTags();
        });
    });
    
    // Form submission with loading state
    const tagForm = document.getElementById('tagForm');
    if (tagForm) {
        tagForm.addEventListener('submit', function() {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodajem...';
            submitBtn.disabled = true;
        });
    }
    
    // Enhanced hover effects for tag cards
    tagCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = 'var(--seup-shadow-lg)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'var(--seup-shadow)';
        });
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N for new tag
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            tagInput.focus();
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            }
        }
    });
});
</script>

<?php

llxFooter();
$db->close();