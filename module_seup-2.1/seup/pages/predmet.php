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
 *	\file       seup/predmet.php
 *	\ingroup    seup
 *	\brief      Predmet page
 */

// Učitaj Dolibarr okruženje
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

// Lokalne klase
require_once __DIR__ . '/../class/predmet_helper.class.php';
require_once __DIR__ . '/../class/request_handler.class.php';

// Postavljanje debug logova
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Učitaj datoteke prijevoda
$langs->loadLangs(array("seup@seup"));

$action = GETPOST('action', 'aZ09');
$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);
// Sigurnosna provjera
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}


// Hvatanje ID predmeta iz GET zahtjeva
// Ako je ID predmeta postavljen, dohvatit ćemo detalje predmeta
$caseId = GETPOST('id', 'int');
dol_syslog("Dohvaćanje ID predmeta: $caseId", LOG_DEBUG);
if (empty($caseId)) {
    header('Location: ' . dol_buildpath('/custom/seup/pages/predmeti.php', 1));
    exit;
}

// Definiranje direktorija za učitavanje dokumenata
$upload_base_dir = DOL_DATA_ROOT . '/ecm/';
$upload_dir = $upload_base_dir . 'SEUP/predmet_' . $caseId . '/';
// Create directory if not exists
if (!is_dir($upload_dir)) {
    dol_mkdir($upload_dir);
}

dol_syslog("Accessing case details for ID: $caseId", LOG_DEBUG);
$caseDetails = null;

if ($caseId) {
    // Fetch case details
    $sql = "SELECT 
                p.ID_predmeta,
                CONCAT(p.klasa_br, '-', p.sadrzaj, '/', p.godina, '-', p.dosje_broj, '/', p.predmet_rbr) as klasa,
                p.naziv_predmeta,
                DATE_FORMAT(p.tstamp_created, '%d.%m.%Y') as datum_otvaranja,
                u.name_ustanova,
                k.ime_prezime,
                ko.opis_klasifikacijske_oznake
            FROM " . MAIN_DB_PREFIX . "a_predmet p
            LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
            LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON p.ID_interna_oznaka_korisnika = k.ID
            LEFT JOIN " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ko ON p.ID_klasifikacijske_oznake = ko.ID_klasifikacijske_oznake
            WHERE p.ID_predmeta = " . (int)$caseId;

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $caseDetails = $db->fetch_object($resql);
    }
}


// definiranje direktorija za privremene datoteke
define('TEMP_DIR_RELATIVE', '/temp/');
define('TEMP_DIR_FULL', DOL_DATA_ROOT . TEMP_DIR_RELATIVE);
define('TEMP_DIR_WEB', DOL_URL_ROOT . '/documents' . TEMP_DIR_RELATIVE);

// Ensure temp directory exists
if (!file_exists(TEMP_DIR_FULL)) {
    dol_mkdir(TEMP_DIR_FULL);
}

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", "", '', '', 0, 0, '', '', '', 'mod-seup page-index');

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    dol_syslog('POST request', LOG_INFO);

    // Handle document upload
    if (isset($_POST['action']) && GETPOST('action') === 'upload_document') {
        Request_Handler::handleUploadDocument($db, $upload_dir, $langs, $conf, $user);
        exit;
    }

    // File existence check
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && GETPOST('action') === 'check_file_exists') {
        ob_end_clean();
        $file_path = GETPOST('file', 'alphanohtml');
        if (strpos($file_path, TEMP_DIR_RELATIVE) !== 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid file path']);
            exit;
        }
        $full_path = DOL_DATA_ROOT . $file_path;
        $exists = file_exists($full_path);
        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists, 'path' => $full_path]);
        exit;
    }
}

// Prikaz dokumenata na tabu 2
$documentTableHTML = '';
Predmet_helper::fetchUploadedDocuments($db, $conf, $documentTableHTML, $langs, $caseId);

// === BOOTSTRAP CDN ===
// Modern SEUP Styles
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Page Header
if ($caseDetails) {
    print '<div class="seup-page-header">';
    print '<div class="seup-container">';
    print '<h1 class="seup-page-title">Predmet #' . $caseDetails->ID_predmeta . '</h1>';
    print '<div class="seup-breadcrumb">';
    print '<a href="../seupindex.php">SEUP</a>';
    print '<i class="fas fa-chevron-right"></i>';
    print '<a href="predmeti.php">Predmeti</a>';
    print '<i class="fas fa-chevron-right"></i>';
    print '<span>' . $caseDetails->klasa . '</span>';
    print '</div>';
    print '</div>';
    print '</div>';
} else {
    print '<div class="seup-page-header">';
    print '<div class="seup-container">';
    print '<h1 class="seup-page-title">SEUP Sustav</h1>';
    print '<div class="seup-breadcrumb">';
    print '<a href="../seupindex.php">SEUP</a>';
    print '<i class="fas fa-chevron-right"></i>';
    print '<span>Početna</span>';
    print '</div>';
    print '</div>';
    print '</div>';
}

print '<div class="seup-container">';

// Tab navigation
print '<div class="seup-nav-tabs">';
print '<button class="seup-nav-tab active" data-tab="tab1">';
print '<i class="fas fa-home"></i> Predmet';
print '</button>';
print '<button class="seup-nav-tab" data-tab="tab2">';
print '<i class="fas fa-file-alt"></i> Dokumenti u prilozima';
print '</button>';
print '<button class="seup-nav-tab" data-tab="tab3">';
print '<i class="fas fa-search"></i> Predpregled';
print '</button>';
print '<button class="seup-nav-tab" data-tab="tab4">';
print '<i class="fas fa-chart-bar"></i> Statistike';
print '</button>';
print '</div>';

// Tab content

// Tab 1 - Case Details or Welcome
print '<div class="seup-tab-pane active" id="tab1" style="display: block;">';
if ($caseDetails) {
    print '<div class="seup-card">';
    print '<div class="seup-card-header">';
    print '<h3 class="seup-heading-4" style="margin: 0;">Detalji predmeta #' . $caseDetails->ID_predmeta . '</h3>';
    print '</div>';
    print '<div class="seup-card-body">';
    print '<div class="seup-grid seup-grid-2">';
    
    print '<div>';
    print '<div class="seup-form-group">';
    print '<label class="seup-label">Klasa:</label>';
    print '<div class="seup-badge seup-badge-primary" style="font-family: var(--seup-font-mono); font-size: 1rem;">' . $caseDetails->klasa . '</div>';
    print '</div>';
    
    print '<div class="seup-form-group">';
    print '<label class="seup-label">Naziv predmeta:</label>';
    print '<p class="seup-text-body">' . $caseDetails->naziv_predmeta . '</p>';
    print '</div>';
    
    print '<div class="seup-form-group">';
    print '<label class="seup-label">Ustanova:</label>';
    print '<p class="seup-text-body">' . $caseDetails->name_ustanova . '</p>';
    print '</div>';
    print '</div>';
    
    print '<div>';
    print '<div class="seup-form-group">';
    print '<label class="seup-label">Zaposlenik:</label>';
    print '<p class="seup-text-body">' . $caseDetails->ime_prezime . '</p>';
    print '</div>';
    
    print '<div class="seup-form-group">';
    print '<label class="seup-label">Datum otvaranja:</label>';
    print '<p class="seup-text-body">' . $caseDetails->datum_otvaranja . '</p>';
    print '</div>';
    
    print '<div class="seup-form-group">';
    print '<label class="seup-label">Status:</label>';
    print '<span class="seup-status seup-status-active"><i class="fas fa-check-circle"></i> Aktivan</span>';
    print '</div>';
    print '</div>';
    
    print '</div>'; // End grid
    print '</div>'; // End card body
    print '</div>'; // End card
} else {
    print '<div class="seup-card">';
    print '<div class="seup-card-body">';
    print '<div class="seup-empty-state">';
    print '<div class="seup-empty-state-icon">';
    print '<i class="fas fa-folder-open"></i>';
    print '</div>';
    print '<h3 class="seup-empty-state-title">Dobrodošli</h3>';
    print '<p class="seup-empty-state-description">Ovo je početna stranica. Za pregled predmeta posjetite stranicu Predmeti.</p>';
    print '<a href="predmeti.php" class="seup-btn seup-btn-primary">';
    print '<i class="fas fa-external-link-alt"></i> Otvori Predmete';
    print '</a>';
    print '</div>';
    print '</div>';
    print '</div>';
}
print '</div>

// Tab 2 - Documents
print '<div class="seup-tab-pane" id="tab2" style="display: none;">';
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Akti i prilozi</h3>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Pregled dodanih priloga sa datumom kreiranja i kreatorom</p>';
print '</div>';
print '<div class="seup-card-body">';
print $documentTableHTML;
print '<div class="seup-flex seup-gap-2" style="margin-top: var(--seup-space-4);">';
print '<button type="button" id="uploadTrigger" class="seup-btn seup-btn-primary">';
print '<i class="fas fa-upload"></i> Dodaj dokument';
print '</button>';
print '<input type="file" id="documentInput" style="display: none;">';
print '<button type="button" class="seup-btn seup-btn-secondary">Dugme 2</button>';
print '<button type="button" class="seup-btn seup-btn-secondary">Dugme 3</button>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Tab 3 - Preview
print '<div class="seup-tab-pane" id="tab3" style="display: none;">';
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Predpregled omota sposa sa listom priloga</h3>';
print '</div>';
print '<div class="seup-card-body">';
print '<p class="seup-text-body">Bumo vidli kako</p>';
print '<div class="seup-flex seup-gap-2" style="margin-top: var(--seup-space-4);">';
print '<button type="button" class="seup-btn seup-btn-primary" data-action="generate_pdf">Kreiraj PDF</button>';
print '<button type="button" class="seup-btn seup-btn-secondary">Dugme 2</button>';
print '<button type="button" class="seup-btn seup-btn-secondary">Dugme 3</button>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

// Tab 4 - Stats
print '<div class="seup-tab-pane" id="tab4" style="display: none;">';
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Statistički podaci</h3>';
print '</div>';
print '<div class="seup-card-body">';
print '<p class="seup-text-body">Možda evidencije logiranja i provedenog vremena</p>';
print '<div class="seup-flex seup-gap-2" style="margin-top: var(--seup-space-4);">';
print '<button type="button" class="seup-btn seup-btn-primary">Dugme 1</button>';
print '<button type="button" class="seup-btn seup-btn-secondary">Dugme 2</button>';
print '<button type="button" class="seup-btn seup-btn-secondary">Dugme 3</button>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';

?>

<input type="hidden" name="token" value="<?php echo newToken(); ?>">

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Get elements safely
        const uploadTrigger = document.getElementById("uploadTrigger");
        const documentInput = document.getElementById("documentInput");
        const pdfButton = document.querySelector("[data-action='generate_pdf']");

        // Only add event listeners if elements exist
        if (uploadTrigger && documentInput) {
            // Upload trigger
            uploadTrigger.addEventListener("click", function() {
                documentInput.click();
            });

            // File selection handler
            documentInput.addEventListener("change", function(e) {
                const allowedTypes = [
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/msword",
                    "application/vnd.ms-excel",
                    "application/octet-stream",
                    "application/zip",
                    "application/pdf",
                    "image/jpeg",
                    "image/png"
                ];

                const allowedExtensions = [
                    ".docx", ".xlsx", ".doc", ".xls",
                    ".pdf", ".jpg", ".jpeg", ".png", ".zip"
                ];

                if (this.files.length > 0) {
                    const file = this.files[0];
                    const extension = "." + file.name.split(".").pop().toLowerCase();

                    const formData = new FormData();
                    formData.append("document", file);
                    formData.append("token", document.querySelector("input[name='token']").value);
                    formData.append("action", "upload_document");
                    formData.append("case_id", <?php echo $caseId; ?>);

                    if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(extension)) {
                        alert("<?php echo $langs->transnoentities('ErrorInvalidFileTypeJS'); ?>\nAllowed formats: " + allowedExtensions.join(", "));
                        this.value = "";
                        return;
                    }

                    if (file.size > 10 * 1024 * 1024) {
                        alert("<?php echo $langs->transnoentities('ErrorFileTooLarge'); ?>");
                        this.value = "";
                        return;
                    }

                    fetch("", {
                        method: "POST",
                        body: formData
                    }).then(response => {
                        if (response.ok) {
                            document.getElementById("documentInput").value = "";
                            window.location.reload();
                        }
                    }).catch(error => {
                        console.error("Upload error:", error);
                    });
                }
            });
        } else {
            console.warn("Upload elements not found");
        }

        // PDF generation
        if (pdfButton) {
            pdfButton.addEventListener("click", function() {
                const generatePdfUrl = "<?php echo DOL_URL_ROOT . '/custom/seup/class/generate_pdf.php'; ?>";
                fetch(generatePdfUrl, {
                        method: "POST"
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.file) {
                            window.open(data.file, "_blank");
                        } else {
                            throw new Error(data.error || "PDF generation failed.");
                        }
                    })
                    .catch(error => {
                        console.error("PDF generation error:", error);
                        alert("PDF generation failed: " + error.message);
                    });
            });
        } else {
            console.warn("PDF button not found");
        }
    });
</script>

<?php

llxFooter();
$db->close();
