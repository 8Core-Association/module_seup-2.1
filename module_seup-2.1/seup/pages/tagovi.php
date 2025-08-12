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

// Add tag_color column if it doesn't exist
$sql_check = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "a_tagovi LIKE 'tag_color'";
$resql_check = $db->query($sql_check);
if ($resql_check && $db->num_rows($resql_check) == 0) {
    $sql_alter = "ALTER TABLE " . MAIN_DB_PREFIX . "a_tagovi ADD COLUMN tag_color VARCHAR(20) DEFAULT 'blue' AFTER tag";
    $db->query($sql_alter);
    dol_syslog("Added tag_color column to a_tagovi table", LOG_INFO);
}

// Process form submission
$error = 0;
$success = 0;
$tag_name = '';

if ($action == 'addtag' && !empty($_POST['tag'])) {
    $tag_name = GETPOST('tag', 'alphanohtml');
    $tag_color = GETPOST('tag_color', 'alpha') ?: 'blue';

    // Validate input
    if (dol_strlen($tag_name) < 2) {
        $error++;
        setEventMessages($langs->trans('ErrorTagTooShort'), null, 'errors');
    } else {
        $db->begin();

        // Check if tag already exists
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "a_tagovi";
        $sql .= " WHERE tag = '" . $db->escape($tag_name) . "'";
        $sql .= " AND entity = " . (int)$conf->entity;

        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql) > 0) {
                $error++;
                setEventMessages($langs->trans('ErrorTagAlreadyExists'), null, 'errors');
            } else {
                // Insert new tag with color
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_tagovi";
                $sql .= " (tag, tag_color, entity, date_creation, fk_user_creat)";
                $sql .= " VALUES ('" . $db->escape($tag_name) . "',";
                $sql .= " '" . $db->escape($tag_color) . "',";
                $sql .= " " . (int)$conf->entity . ",";
                $sql .= " '" . $db->idate(dol_now()) . "',";
                $sql .= " " . (int)$user->id . ")";

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
        $sql .= " WHERE fk_tag = " . (int)$tagid;
        $resql = $db->query($sql);

        if ($resql) {
            // Then delete the tag itself
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_tagovi";
            $sql .= " WHERE rowid = " . (int)$tagid;
            $sql .= " AND entity = " . (int)$conf->entity;

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
$sql = "SELECT COUNT(*) as total FROM " . MAIN_DB_PREFIX . "a_tagovi WHERE entity = " . (int)$conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $totalTags = (int)$obj->total;
}

// Count tags that are actually used
$sql = "SELECT COUNT(DISTINCT t.rowid) as active 
        FROM " . MAIN_DB_PREFIX . "a_tagovi t
        INNER JOIN " . MAIN_DB_PREFIX . "a_predmet_tagovi pt ON t.rowid = pt.fk_tag
        WHERE t.entity = " . (int)$conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $activeTags = (int)$obj->active;
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
print '<div class="color-picker-grid">';
$colors = [
    'blue' => '#3b82f6',
    'purple' => '#8b5cf6', 
    'green' => '#10b981',
    'orange' => '#f97316',
    'pink' => '#ec4899',
    'teal' => '#14b8a6',
    'amber' => '#f59e0b',
    'indigo' => '#6366f1',
    'red' => '#ef4444',
    'emerald' => '#059669',
    'sky' => '#0ea5e9',
    'yellow' => '#eab308'
];

foreach ($colors as $colorName => $colorHex) {
    print '<div class="color-option" data-color="' . $colorName . '" style="background-color: ' . $colorHex . ';">';
    print '<i class="fas fa-check"></i>';
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

// Search and Filter Section
print '<div class="seup-form-group">';
print '<div class="seup-input-group">';
print '<input type="text" id="searchTags" class="seup-input seup-input-enhanced" placeholder="Pretraži oznake...">';
print '<i class="fas fa-search seup-input-icon"></i>';
print '</div>';
print '</div>';

// Color Filter with Checkboxes
print '<div class="seup-form-group">';
print '<label class="seup-label">Filter po boji</label>';
print '<div class="color-filter-checkboxes">';

// All colors checkbox
print '<label class="color-filter-checkbox">';
print '<input type="checkbox" data-color="all" checked>';
print '<span class="checkbox-custom">';
print '<span class="color-dot" style="background: linear-gradient(45deg, #3b82f6, #8b5cf6, #10b981);"></span>';
print '</span>';
print '<span>Sve boje</span>';
print '</label>';

// Individual color checkboxes
foreach ($colors as $colorName => $colorHex) {
    print '<label class="color-filter-checkbox">';
    print '<input type="checkbox" data-color="' . $colorName . '" checked>';
    print '<span class="checkbox-custom">';
    print '<span class="color-dot" style="background-color: ' . $colorHex . ';"></span>';
    print '</span>';
    print '<span>' . ucfirst($colorName) . '</span>';
    print '</label>';
}

print '</div>';
print '</div>';

// Display existing tags with real data
$sql = "SELECT t.rowid, t.tag, t.tag_color, t.date_creation, u.firstname, u.lastname,
               COUNT(pt.fk_predmet) as usage_count
        FROM " . MAIN_DB_PREFIX . "a_tagovi t
        LEFT JOIN " . MAIN_DB_PREFIX . "user u ON t.fk_user_creat = u.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet_tagovi pt ON t.rowid = pt.fk_tag
        WHERE t.entity = " . (int)$conf->entity . "
        GROUP BY t.rowid, t.tag, t.tag_color, t.date_creation, u.firstname, u.lastname
        ORDER BY t.tag ASC";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    
    if ($num > 0) {
        print '<div class="tags-list" id="tagsContainer">';
        
        while ($obj = $db->fetch_object($resql)) {
            $creatorName = dolGetFirstLastname($obj->firstname, $obj->lastname);
            $usageCount = (int)$obj->usage_count;
            $tagColor = $obj->tag_color ?: 'blue';
            $colorHex = $colors[$tagColor] ?? '#3b82f6';
            
            print '<div class="tag-item" data-tag="' . strtolower($obj->tag) . '" data-color="' . $tagColor . '">';
            
            print '<div class="tag-content">';
            print '<div class="tag-display" style="background-color: ' . $colorHex . '20; border-color: ' . $colorHex . '40; color: ' . $colorHex . ';">';
            print '<i class="fas fa-tag"></i> ' . dol_escape_htmltag($obj->tag);
            print '</div>';
            
            print '<div class="tag-meta">';
            print '<span><i class="fas fa-calendar"></i> ' . dol_print_date($db->jdate($obj->date_creation), 'day') . '</span>';
            print '<span><i class="fas fa-user"></i> ' . ($creatorName ?: 'Nepoznato') . '</span>';
            print '<span><i class="fas fa-chart-bar"></i> ' . $usageCount . ' predmeta</span>';
            print '</div>';
            print '</div>';
            
            print '<div class="tag-actions">';
            print '<button class="action-btn edit-btn" data-tooltip="Uredi">';
            print '<i class="fas fa-edit"></i>';
            print '</button>';
            
            // Delete form
            print '<form method="POST" action="" style="display:inline;" onsubmit="return confirm(\'' . dol_escape_js($langs->trans('ConfirmDeleteTag')) . '\')">';
            print '<input type="hidden" name="action" value="deletetag">';
            print '<input type="hidden" name="tagid" value="' . $obj->rowid . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<button type="submit" class="action-btn delete-btn" data-tooltip="Obriši">';
            print '<i class="fas fa-trash"></i>';
            print '</button>';
            print '</form>';
            
            print '</div>';
            print '</div>'; // End tag item
        }
        
        print '</div>'; // End tags list
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

<style>
/* Color picker styles */
.color-picker-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
    margin-top: 8px;
}

.color-option {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid transparent;
    position: relative;
}

.color-option:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.color-option.active {
    border-color: #1f2937;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.color-option i {
    color: white;
    font-size: 16px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    display: none;
}

.color-option.active i {
    display: block;
}

/* Color filter checkboxes */
.color-filter-checkboxes {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 8px;
}

.color-filter-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.color-filter-checkbox:hover {
    background-color: #f8fafc;
}

.color-filter-checkbox input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    position: relative;
}

.color-filter-checkbox input:checked + .checkbox-custom {
    border-color: #2563eb;
    background-color: #2563eb;
}

.color-filter-checkbox input:checked + .checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

/* Tags list styles */
.tags-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
}

.tag-item {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    transition: all 0.2s ease;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.tag-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tag-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.tag-display {
    padding: 4px 12px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.875rem;
    border: 1px solid;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.tag-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 0.75rem;
    color: #64748b;
}

.tag-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.tag-actions {
    display: flex;
    flex-direction: column;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.2s ease;
    align-self: flex-start;
}

.tag-item:hover .tag-actions {
    opacity: 1;
}

.action-btn {
    background: none;
    border: 1px solid #cbd5e1;
    color: #64748b;
    cursor: pointer;
    padding: 6px;
    border-radius: 4px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    position: relative;
    z-index: 1000;
}

.action-btn:hover {
    transform: scale(1.1);
}

.edit-btn:hover {
    background: #f0f9ff;
    border-color: #3b82f6;
    color: #2563eb;
}

.delete-btn:hover {
    background: #fef2f2;
    border-color: #ef4444;
    color: #dc2626;
}

/* Tooltip */
.action-btn[data-tooltip] {
    position: relative;
}

.action-btn[data-tooltip]:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 2000;
    margin-bottom: 4px;
}

/* Character counter */
.seup-char-counter {
    text-align: right;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 4px;
}

/* Input group */
.seup-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.seup-input-enhanced {
    padding-right: 40px;
}

.seup-input-icon {
    position: absolute;
    right: 12px;
    color: #94a3b8;
    pointer-events: none;
    transition: all 0.2s ease;
}

.seup-input-enhanced:focus + .seup-input-icon {
    color: #2563eb;
}

/* Help text */
.seup-help-text {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    padding: 12px;
    margin-top: 16px;
    font-size: 0.875rem;
    color: #1d4ed8;
}

/* Form actions */
.seup-form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

/* Disabled button */
.seup-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

.seup-btn.disabled:hover {
    transform: none !important;
    box-shadow: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter
    const tagInput = document.getElementById('tag');
    const charCounter = document.getElementById('charCounter');
    const submitBtn = document.getElementById('submitBtn');
    
    // Color picker functionality
    const colorOptions = document.querySelectorAll('.color-option');
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
            this.querySelector('i').style.display = 'block';
            
            // Update hidden input
            selectedColorInput.value = this.getAttribute('data-color');
        });
    });
    
    // Set default color (blue)
    if (colorOptions.length > 0) {
        colorOptions[0].classList.add('active');
        colorOptions[0].querySelector('i').style.display = 'block';
    }
    
    if (tagInput && charCounter) {
        function updateCharCounter() {
            const length = tagInput.value.length;
            charCounter.textContent = length + '/50';
            
            // Change color based on length
            if (length < 2) {
                charCounter.style.color = '#ef4444';
                submitBtn.disabled = true;
                submitBtn.classList.add('disabled');
            } else if (length > 45) {
                charCounter.style.color = '#f59e0b';
                submitBtn.disabled = false;
                submitBtn.classList.remove('disabled');
            } else {
                charCounter.style.color = '#10b981';
                submitBtn.disabled = false;
                submitBtn.classList.remove('disabled');
            }
        }
        
        tagInput.addEventListener('input', updateCharCounter);
        updateCharCounter(); // Initial call
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchTags');
    const tagItems = document.querySelectorAll('.tag-item');
    const colorCheckboxes = document.querySelectorAll('input[type="checkbox"][data-color]');
    
    let activeColors = new Set(['all', 'blue', 'purple', 'green', 'orange', 'pink', 'teal', 'amber', 'indigo', 'red', 'emerald', 'sky', 'yellow']);
    
    // Color filter functionality
    colorCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const color = this.getAttribute('data-color');
            
            if (color === 'all') {
                if (this.checked) {
                    // Check all colors
                    activeColors = new Set(['all', 'blue', 'purple', 'green', 'orange', 'pink', 'teal', 'amber', 'indigo', 'red', 'emerald', 'sky', 'yellow']);
                    colorCheckboxes.forEach(cb => cb.checked = true);
                } else {
                    // Uncheck all colors
                    activeColors = new Set();
                    colorCheckboxes.forEach(cb => cb.checked = false);
                }
            } else {
                if (this.checked) {
                    activeColors.add(color);
                } else {
                    activeColors.delete(color);
                    // Uncheck "all" if any individual color is unchecked
                    const allCheckbox = document.querySelector('input[data-color="all"]');
                    if (allCheckbox) allCheckbox.checked = false;
                    activeColors.delete('all');
                }
                
                // Check "all" if all individual colors are checked
                const individualColors = ['blue', 'purple', 'green', 'orange', 'pink', 'teal', 'amber', 'indigo', 'red', 'emerald', 'sky', 'yellow'];
                const allIndividualChecked = individualColors.every(c => activeColors.has(c));
                if (allIndividualChecked) {
                    const allCheckbox = document.querySelector('input[data-color="all"]');
                    if (allCheckbox) allCheckbox.checked = true;
                    activeColors.add('all');
                }
            }
            
            filterTags();
        });
    });
    
    function filterTags() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        
        tagItems.forEach(item => {
            const tagName = item.getAttribute('data-tag');
            const tagColor = item.getAttribute('data-color');
            
            const matchesSearch = !searchTerm || tagName.includes(searchTerm);
            const matchesColor = activeColors.has('all') || activeColors.has(tagColor);
            
            if (matchesSearch && matchesColor) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', filterTags);
    }
    
    // Form submission with loading state
    const tagForm = document.getElementById('tagForm');
    if (tagForm) {
        tagForm.addEventListener('submit', function() {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodajem...';
            submitBtn.disabled = true;
        });
    }
});
</script>

<?php

llxFooter();
$db->close();