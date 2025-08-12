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
 *	\file       seup/tagovi.php
 *	\ingroup    seup
 *	\brief      Tagovi management page
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

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

// Set page title to "Tagovi"
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

// Get tag statistics
$sql_stats = "SELECT COUNT(*) as total_tags FROM " . MAIN_DB_PREFIX . "a_tagovi WHERE entity = " . $conf->entity;
$resql_stats = $db->query($sql_stats);
$total_tags = 0;
if ($resql_stats && $obj = $db->fetch_object($resql_stats)) {
    $total_tags = $obj->total_tags;
}

// Get usage statistics for tags
$sql_usage = "SELECT 
    t.rowid,
    t.tag,
    COUNT(pt.fk_predmet) as usage_count
FROM " . MAIN_DB_PREFIX . "a_tagovi t
LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet_tagovi pt ON t.rowid = pt.fk_tag
WHERE t.entity = " . $conf->entity . "
GROUP BY t.rowid, t.tag";

$resql_usage = $db->query($sql_usage);
$tag_usage = [];
if ($resql_usage) {
    while ($obj = $db->fetch_object($resql_usage)) {
        $tag_usage[$obj->rowid] = $obj->usage_count;
    }
}

// Get active tags count (tags that are actually used)
$active_tags = count(array_filter($tag_usage, function($count) { return $count > 0; }));

// Total Tags Card
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-primary-600);">';
print '<i class="fas fa-tags"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">' . $total_tags . '</h3>';
print '<p class="seup-text-small">Ukupno Oznaka</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Usage Card
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-success);">';
print '<i class="fas fa-chart-line"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">' . $active_tags . '</h3>';
print '<p class="seup-text-small">Oznake u upotrebi</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Quick Actions Card
print '<div class="seup-card seup-interactive">';
print '<div class="seup-card-body">';
print '<div class="seup-flex seup-items-center seup-gap-4">';
print '<div class="seup-icon-lg" style="color: var(--seup-accent);">';
print '<i class="fas fa-plus-circle"></i>';
print '</div>';
print '<div>';
print '<h3 class="seup-heading-4" style="margin-bottom: var(--seup-space-1);">Brze Akcije</h3>';
print '<p class="seup-text-small">Dodaj ili upravljaj</p>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End stats grid

// Main Content - Two Column Layout
print '<div class="seup-grid seup-grid-2">';

// Left Column - Add New Tag
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;"><i class="fas fa-plus-circle"></i> Dodaj Novu Oznaku</h3>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Kreirajte nove oznake za kategorizaciju</p>';
print '</div>';
print '<div class="seup-card-body">';

// Add tag form with enhanced design
print '<form method="POST" action="" id="addTagForm">';
print '<input type="hidden" name="action" value="addtag">';

print '<div class="seup-form-group">';
print '<label for="tag" class="seup-label"><i class="fas fa-tag"></i> Naziv Oznake</label>';
print '<div class="seup-input-group">';
print '<input type="text" name="tag" id="tag" class="seup-input seup-input-enhanced" placeholder="Unesite naziv nove oznake..." value="' . $tag_name . '" required maxlength="50">';
print '<div class="seup-input-icon">';
print '<i class="fas fa-hashtag"></i>';
print '</div>';
print '</div>';
print '<div class="seup-char-counter">';
print '<span id="charCount">0</span>/50 znakova';
print '</div>';
print '</div>';

print '<div class="seup-form-group">';
print '<label class="seup-label"><i class="fas fa-palette"></i> Boja Oznake</label>';
print '<div class="seup-color-picker">';
$colors = ['blue', 'purple', 'green', 'orange', 'pink', 'teal', 'amber', 'indigo', 'red', 'emerald', 'sky', 'yellow'];
foreach ($colors as $color) {
    print '<div class="seup-color-option seup-tag-' . $color . '" data-color="' . $color . '">';
    print '<i class="fas fa-check" style="opacity: 0;"></i>';
    print '</div>';
}
print '</div>';
print '<input type="hidden" name="tag_color" id="selectedColor" value="blue">';
print '</div>';

print '<div class="seup-form-actions">';
print '<button type="submit" class="seup-btn seup-btn-primary seup-btn-lg seup-interactive" id="submitBtn">';
print '<i class="fas fa-plus"></i> <span>Dodaj Oznaku</span>';
print '</button>';
print '<button type="reset" class="seup-btn seup-btn-secondary seup-interactive">';
print '<i class="fas fa-undo"></i> Očisti';
print '</button>';
print '</div>';

print '<div class="seup-help-text">';
print '<i class="fas fa-info-circle"></i> ';
print '<strong>Savjet:</strong> Koristite kratke i opisne nazive za lakše prepoznavanje oznaka.';
print '</div>';

print '</form>';
print '</div>';
print '</div>';

// Right Column - Existing Tags
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<div>';
print '<h3 class="seup-heading-4" style="margin: 0;"><i class="fas fa-list"></i> Postojeće Oznake</h3>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Upravljanje postojećim oznakama</p>';
print '</div>';
print '<div class="seup-tag-count">';
print '<span class="seup-badge seup-badge-primary">' . $total_tags . '</span>';
print '</div>';
print '</div>';
print '</div>';
print '<div class="seup-card-body">';

// Search box for tags
print '<div class="seup-form-group">';
print '<div class="seup-input-group">';
print '<input type="text" id="tagSearch" class="seup-input" placeholder="Pretraži oznake...">';
print '<div class="seup-input-icon">';
print '<i class="fas fa-search"></i>';
print '</div>';
print '</div>';
print '</div>';

// Display existing tags
$sql = "SELECT t.rowid, t.tag, t.date_creation, t.fk_user_creat, u.firstname, u.lastname";
$sql .= " FROM " . MAIN_DB_PREFIX . "a_tagovi";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user u ON t.fk_user_creat = u.rowid";
$sql .= " WHERE entity = " . $conf->entity;
$sql .= " ORDER BY date_creation DESC";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $trans_confirm = $langs->trans('ConfirmDeleteTag');

    if ($num > 0) {
        print '<div class="seup-tags-grid" id="tagsContainer">';
        $colorIndex = 0;
        $colors = ['blue', 'purple', 'green', 'orange', 'pink', 'teal', 'amber', 'indigo', 'red', 'emerald', 'sky', 'yellow'];
        
        while ($obj = $db->fetch_object($resql)) {
            $color = $colors[$colorIndex % count($colors)];
            $colorIndex++;
            
            // Get real usage count for this tag
            $usage_count = isset($tag_usage[$obj->rowid]) ? $tag_usage[$obj->rowid] : 0;
            $usage_percentage = $total_tags > 0 ? min(($usage_count / max($total_tags, 1)) * 100, 100) : 0;
            
            print '<div class="seup-tag-card seup-interactive seup-fade-in" data-tag="' . strtolower($obj->tag) . '">';
            print '<div class="seup-tag-card-header">';
            print '<div class="seup-tag seup-tag-' . $color . '">';
            print '<i class="fas fa-tag"></i>';
            print '<span>' . htmlspecialchars($obj->tag) . '</span>';
            print '</div>';
            print '<div class="seup-tag-actions">';
            print '<button class="seup-btn seup-btn-sm seup-btn-ghost seup-tooltip" data-tooltip="Uredi oznaku">';
            print '<i class="fas fa-edit"></i>';
            print '</button>';
            print '<form method="POST" action="" style="display:inline;" onsubmit="return confirm(\'' . dol_escape_js($trans_confirm) . '\')">';
            print '<input type="hidden" name="action" value="deletetag">';
            print '<input type="hidden" name="tagid" value="' . $obj->rowid . '">';
            print '<button type="submit" class="seup-btn seup-btn-sm seup-btn-danger seup-tooltip" data-tooltip="Obriši oznaku">';
            print '<i class="fas fa-trash"></i>';
            print '</button>';
            print '</form>';
            print '</div>';
            print '</div>';
            
            print '<div class="seup-tag-card-body">';
            print '<div class="seup-tag-meta">';
            print '<div class="seup-meta-item">';
            print '<i class="fas fa-calendar-alt"></i>';
            print '<span>Kreiran: ' . dol_print_date($db->jdate($obj->date_creation), 'day') . '</span>';
            print '</div>';
            if ($obj->firstname || $obj->lastname) {
                print '<div class="seup-meta-item">';
                print '<i class="fas fa-user"></i>';
                print '<span>Kreator: ' . trim($obj->firstname . ' ' . $obj->lastname) . '</span>';
                print '</div>';
            }
            print '<div class="seup-meta-item">';
            print '<i class="fas fa-hashtag"></i>';
            print '<span>ID: #' . $obj->rowid . '</span>';
            print '</div>';
            print '</div>';
            
            // Real usage stats
            print '<div class="seup-tag-usage">';
            print '<div class="seup-usage-bar">';
            print '<div class="seup-usage-fill" style="width: ' . $usage_percentage . '%;"></div>';
            print '</div>';
            print '<span class="seup-usage-text">';
            if ($usage_count > 0) {
                print 'Koristi se u ' . $usage_count . ' predmet' . ($usage_count == 1 ? 'u' : 'a');
            } else {
                print 'Nije korišten';
            }
            print '</span>';
            print '</div>';
            print '</div>';
            
            print '</div>';
        }
        print '</div>';
        
        // Bulk actions
        print '<div class="seup-bulk-actions" style="margin-top: var(--seup-space-6);">';
        print '<div class="seup-flex seup-justify-between seup-items-center">';
        print '<div class="seup-text-small" style="color: var(--seup-gray-500);">';
        print '<i class="fas fa-info-circle"></i> Prikazano ' . $num . ' oznaka';
        print '</div>';
        print '<div class="seup-flex seup-gap-2">';
        print '<button class="seup-btn seup-btn-sm seup-btn-secondary" onclick="exportTags()">';
        print '<i class="fas fa-download"></i> Izvoz';
        print '</button>';
        print '<button class="seup-btn seup-btn-sm seup-btn-secondary" onclick="importTags()">';
        print '<i class="fas fa-upload"></i> Uvoz';
        print '</button>';
        print '</div>';
        print '</div>';
        print '</div>';
    } else {
        print '<div class="seup-empty-state">';
        print '<div class="seup-empty-state-icon">';
        print '<i class="fas fa-tags"></i>';
        print '</div>';
        print '<h3 class="seup-empty-state-title">' . $langs->trans('NoTagsAvailable') . '</h3>';
        print '<p class="seup-empty-state-description">Dodajte prvu oznaku koristeći formu lijevo</p>';
        print '<button class="seup-btn seup-btn-primary seup-interactive" onclick="document.getElementById(\'tag\').focus()">';
        print '<i class="fas fa-plus"></i> Dodaj Prvu Oznaku';
        print '</button>';
        print '</div>';
    }
} else {
    print '<div class="seup-alert seup-alert-error">';
    print '<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('ErrorLoadingTags');
    print '</div>';
}

print '</div>'; // End card body
print '</div>'; // End right card

print '</div>'; // End grid
print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';

?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for tag input
    const tagInput = document.getElementById('tag');
    const charCount = document.getElementById('charCount');
    const submitBtn = document.getElementById('submitBtn');
    
    if (tagInput && charCount) {
        tagInput.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            // Visual feedback for character limit
            if (length > 40) {
                charCount.style.color = 'var(--seup-warning)';
            } else if (length > 45) {
                charCount.style.color = 'var(--seup-error)';
            } else {
                charCount.style.color = 'var(--seup-gray-500)';
            }
            
            // Update submit button state
            if (length >= 2) {
                submitBtn.classList.remove('disabled');
                submitBtn.disabled = false;
            } else {
                submitBtn.classList.add('disabled');
                submitBtn.disabled = true;
            }
        });
        
        // Initial state check
        tagInput.dispatchEvent(new Event('input'));
    }
    
    // Color picker functionality
    const colorOptions = document.querySelectorAll('.seup-color-option');
    const selectedColorInput = document.getElementById('selectedColor');
    
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all options
            colorOptions.forEach(opt => {
                opt.classList.remove('active');
                opt.querySelector('i').style.opacity = '0';
            });
            
            // Add active class to clicked option
            this.classList.add('active');
            this.querySelector('i').style.opacity = '1';
            
            // Update hidden input
            selectedColorInput.value = this.dataset.color;
        });
    });
    
    // Set default color selection
    if (colorOptions.length > 0) {
        colorOptions[0].click();
    }
    
    // Search functionality
    const searchInput = document.getElementById('tagSearch');
    const tagCards = document.querySelectorAll('.seup-tag-card');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            tagCards.forEach(card => {
                const tagName = card.dataset.tag;
                if (tagName.includes(searchTerm)) {
                    card.style.display = 'block';
                    card.classList.add('seup-fade-in');
                } else {
                    card.style.display = 'none';
                    card.classList.remove('seup-fade-in');
                }
            });
            
            // Update visible count
            const visibleCards = Array.from(tagCards).filter(card => card.style.display !== 'none');
            const countElement = document.querySelector('.seup-bulk-actions .seup-text-small');
            if (countElement) {
                countElement.innerHTML = '<i class="fas fa-info-circle"></i> Prikazano ' + visibleCards.length + ' od ' + tagCards.length + ' oznaka';
            }
        });
    }
    
    // Form submission with loading state
    const addTagForm = document.getElementById('addTagForm');
    if (addTagForm) {
        addTagForm.addEventListener('submit', function() {
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Dodajem...</span>';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Enhanced tag card interactions
    tagCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
            this.style.boxShadow = 'var(--seup-shadow-lg)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = 'var(--seup-shadow)';
        });
    });
});

// Utility functions
function exportTags() {
    window.seupNotifications?.show('Izvoz oznaka je pokrenut...', 'info');
    // Implementation for export functionality
}

function importTags() {
    window.seupNotifications?.show('Funkcija uvoza će biti dostupna uskoro', 'info');
    // Implementation for import functionality
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N for new tag
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        document.getElementById('tag').focus();
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        const searchInput = document.getElementById('tagSearch');
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }
    }
});
</script>

<?php
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';

// End of page
llxFooter();
$db->close();
