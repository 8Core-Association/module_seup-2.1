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
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h2 class="seup-heading-3" style="margin: 0;"><i class="fas fa-tags"></i> ' . $langs->trans("Tagovi") . '</h2>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Upravljanje oznakama za dokumente i predmete</p>';
print '</div>';
print '<div class="seup-card-body">';

// Add tag form
print '<form method="POST" action="" class="seup-mb-6">';
print '<input type="hidden" name="action" value="addtag">';
print '<div class="seup-form-group">';
print '<label for="tag" class="seup-label">' . $langs->trans('Tag') . '</label>';
print '<div class="seup-flex seup-gap-2">';
print '<input type="text" name="tag" id="tag" class="seup-input" placeholder="' . $langs->trans('UnesiNoviTag') . '" value="' . $tag_name . '" required style="flex: 1;">';
print '<button type="submit" class="seup-btn seup-btn-primary">';
print '<i class="fas fa-plus"></i> ' . $langs->trans('DodajTag');
print '</button>';
print '</div>';
print '<small class="seup-text-small" style="margin-top: var(--seup-space-1); display: block;">' . $langs->trans('TagoviHelpText') . '</small>';
print '</div>';
print '</form>';

print '<h4 class="seup-heading-4">' . $langs->trans('ExistingTags') . '</h4>';

// Display existing tags
$sql = "SELECT rowid, tag, date_creation";
$sql .= " FROM " . MAIN_DB_PREFIX . "a_tagovi";
$sql .= " WHERE entity = " . $conf->entity;
$sql .= " ORDER BY tag ASC";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $trans_confirm = $langs->trans('ConfirmDeleteTag');

    if ($num > 0) {
        print '<div class="seup-flex" style="flex-wrap: wrap; gap: var(--seup-space-3);">';
        while ($obj = $db->fetch_object($resql)) {
            print '<div class="seup-tag seup-tag-removable seup-interactive">';
            print '<span>' . $obj->tag . '</span>';

            // Delete button with confirmation
            print '<form method="POST" action="" style="display:inline;">';
            print '<input type="hidden" name="action" value="deletetag">';
            print '<input type="hidden" name="tagid" value="' . $obj->rowid . '">';
            print '<button type="submit" class="seup-tag-remove" onclick="return confirm(\'' . dol_escape_js($trans_confirm) . '\')">';
            print '<i class="fas fa-times"></i>';
            print '</button>';
            print '</form>';

            print '</div>';
        }
        print '</div>';
    } else {
        print '<div class="seup-empty-state">';
        print '<div class="seup-empty-state-icon">';
        print '<i class="fas fa-tags"></i>';
        print '</div>';
        print '<h3 class="seup-empty-state-title">' . $langs->trans('NoTagsAvailable') . '</h3>';
        print '<p class="seup-empty-state-description">Dodajte novu oznaku za početak</p>';
        print '</div>';
    }
} else {
    print '<div class="seup-alert seup-alert-error">' . $langs->trans('ErrorLoadingTags') . '</div>';
}

print '</div>'; // End card body
print '</div>'; // End card
print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';

// End of page
llxFooter();
$db->close();
