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
 *    \file       seup/seupindex.php
 *    \ingroup    seup
 *    \brief      Home page of seup top menu
 */


// Učitaj Dolibarr okruženje
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
  $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Pokušaj učitati main.inc.php iz korijenskog direktorija weba
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

require_once __DIR__ . '/../class/klasifikacijska_oznaka.class.php';
require_once __DIR__ . '/../class/oznaka_ustanove.class.php';
require_once __DIR__ . '/../class/interna_oznaka_korisnika.class.php';

// Omoguci debugiranje php skripti
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Učitaj prijevode
$langs->loadLangs(array("seup@seup"));

$action = GETPOST('action', 'aZ09');
$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Sigurnosne provjere
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
  $action = '';
  $socid = $user->socid;
}

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", "", '', '', 0, 0, '', '', '', 'mod-seup page-index');

// Modern SEUP Styles
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Page Header
print '<div class="seup-page-header">';
print '<div class="seup-container">';
print '<h1 class="seup-page-title">Postavke Sustava</h1>';
print '<div class="seup-breadcrumb">';
print '<a href="../seupindex.php">SEUP</a>';
print '<i class="fas fa-chevron-right"></i>';
print '<span>Postavke</span>';
print '</div>';
print '</div>';
print '</div>';

// Import JS skripti
global $hookmanager;
$messagesFile = DOL_URL_ROOT . '/custom/seup/js/messages.js';
$hookmanager->initHooks(array('seup'));
print '<script src="' . $messagesFile . '"></script>';

// importanje klasa za rad s podacima
// Provjeravamo da li u bazi vec postoji OZNAKA USTANOVE
global $db;

// Provjera i Loadanje vrijednosti oznake ustanove pri loadu stranice
$podaci_postoje = null;
$sql = "SELECT ID_ustanove, singleton, code_ustanova, name_ustanova FROM " . MAIN_DB_PREFIX . "a_oznaka_ustanove WHERE singleton = 1 LIMIT 1";
$resql = $db->query($sql);
$ID_ustanove = 0;
if ($resql && $db->num_rows($resql) > 0) {
  $podaci_postoje = $db->fetch_object($resql);
  $ID_ustanove = $podaci_postoje->ID_ustanove;
  dol_syslog("Podaci o oznaci ustanove su ucitani iz baze: " . $ID_ustanove, LOG_INFO);
}

// Provjera i Loadanje korisnika pri loadu stranice
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

$listUsers = [];
$userStatic = new User($db);

// Dohvati sve aktivne korisnike
$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE statut = 1 ORDER BY lastname ASC";
$resql = $db->query($sql);
if ($resql) {
  while ($obj = $db->fetch_object($resql)) {
    $userStatic->fetch($obj->rowid);
    $listUsers[] = clone $userStatic;
  }
} else {
  echo $db->lasterror();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1. Dodavanje interne oznake korisnika 
  if (isset($_POST['action_oznaka']) && $_POST['action_oznaka'] === 'add') {
    $interna_oznaka_korisnika = new Interna_oznaka_korisnika();
    $interna_oznaka_korisnika->setIme_prezime(GETPOST('ime_user', 'alphanohtml'));
    $interna_oznaka_korisnika->setRbr_korisnika(GETPOST('redni_broj', 'int'));
    $interna_oznaka_korisnika->setRadno_mjesto_korisnika(GETPOST('radno_mjesto_korisnika', 'alphanohtml'));
    
    if (empty($interna_oznaka_korisnika->getIme_prezime()) || empty($interna_oznaka_korisnika->getRbr_korisnika()) || empty($interna_oznaka_korisnika->getRadno_mjesto_korisnika())) {
      setEventMessages($langs->trans("All fields are required"), null, 'errors');
    } elseif (!preg_match('/^\d{1,2}$/', $interna_oznaka_korisnika->getRbr_korisnika())) {
      setEventMessages($langs->trans("Invalid serial number"), null, 'errors');
    } else {
      $sqlCheck = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika WHERE rbr = '" . $db->escape($interna_oznaka_korisnika->getRbr_korisnika()) . "'";
      $resCheck = $db->query($sqlCheck);
      
      if ($resCheck) {
        $obj = $db->fetch_object($resCheck);
        if ($obj->cnt > 0) {
          setEventMessages($langs->trans("User with this number already exists"), null, 'errors');
        } else {
          $db->begin();
          $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika 
                      (ID_ustanove, ime_prezime, rbr, naziv) 
                      VALUES (
                    " . (int)$ID_ustanove . ", 
                    '" . $db->escape($interna_oznaka_korisnika->getIme_prezime()) . "',
                    '" . $db->escape($interna_oznaka_korisnika->getRbr_korisnika()) . "',
                    '" . $db->escape($interna_oznaka_korisnika->getRadno_mjesto_korisnika()) . "'                
                )";
          
          if ($db->query($sql)) {
            $db->commit();
            setEventMessages($langs->trans("User successfully added"), null, 'mesgs');
          } else {
            setEventMessages($langs->trans("Database error: ") . $db->lasterror(), null, 'errors');
          }
        }
      }
    }
  }
  
  // 2. Oznaka ustanove 
  if (isset($_POST['action_ustanova'])) {
    header('Content-Type: application/json; charset=UTF-8');
    ob_end_clean();
    
    $oznaka_ustanove = new Oznaka_ustanove();
    try {
      $db->begin();
      if ($podaci_postoje) {
        $oznaka_ustanove->setID_oznaka_ustanove($podaci_postoje->singleton);
      }
      $oznaka_ustanove->setOznaka_ustanove(GETPOST('code_ustanova', 'alphanohtml'));
      
      if (!preg_match('/^\d{4}-\d-\d$/', $oznaka_ustanove->getOznaka_ustanove())) {
        throw new Exception($langs->trans("Invalid format"));
      }
      
      $oznaka_ustanove->setNaziv_ustanove(GETPOST('name_ustanova', 'alphanohtml'));
      $action = GETPOST('action_ustanova', 'alpha');
      
      if ($action === 'add' && !$podaci_postoje) {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_oznaka_ustanove 
                      (code_ustanova, name_ustanova) 
                      VALUES ( 
                    '" . $db->escape($oznaka_ustanove->getOznaka_ustanove()) . "',
                    '" . $db->escape($oznaka_ustanove->getNaziv_ustanove()) . "'                  
                )";
      } else {
        if (!is_object($podaci_postoje) || empty($podaci_postoje->singleton)) {
          throw new Exception($langs->trans('RecordNotFound'));
        }
        $oznaka_ustanove->setID_oznaka_ustanove($podaci_postoje->singleton);
        $sql = "UPDATE " . MAIN_DB_PREFIX . "a_oznaka_ustanove 
                SET code_ustanova =  '" . $db->escape($oznaka_ustanove->getOznaka_ustanove()) . "',
                name_ustanova = '" . $db->escape($oznaka_ustanove->getNaziv_ustanove()) . "'
                WHERE ID_ustanove = '" . $db->escape($oznaka_ustanove->getID_oznaka_ustanove()) . "'";
      }
      
      $resql = $db->query($sql);
      if (!$resql) {
        throw new Exception($db->lasterror());
      }
      
      $db->commit();
      
      echo json_encode([
        'success' => true,
        'message' => $langs->trans($action === 'add' ? 'Successfully added' : 'Successfully updated'),
        'data' => [
          'code_ustanova' => $oznaka_ustanove->getOznaka_ustanove(),
          'name_ustanova' => $oznaka_ustanove->getNaziv_ustanove()
        ]
      ]);
      exit;
    } catch (Exception $e) {
      $db->rollback();
      http_response_code(500);
      echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
      ]);
    }
    exit;
  }

  // 3. Unos klasifikacijske oznake
  if (isset($_POST['action_klasifikacija'])) {
    $klasifikacijska_oznaka = new Klasifikacijska_oznaka();
    $klasifikacijska_oznaka->setKlasa_br(GETPOST('klasa_br', 'int'));
    if (!preg_match('/^\d{3}$/', $klasifikacijska_oznaka->getKlasa_br())) {
      setEventMessages($langs->trans("ErrorKlasaBrFormat"), null, 'errors');
      $error++;
    }
    $klasifikacijska_oznaka->setSadrzaj(GETPOST('sadrzaj', 'int'));
    if (!preg_match('/^\d{2}$/', $klasifikacijska_oznaka->getSadrzaj()) || $klasifikacijska_oznaka->getSadrzaj() > 99 || $klasifikacijska_oznaka->getSadrzaj() < 00) {
      setEventMessages($langs->trans("ErrorSadrzajFormat"), null, 'errors');
      $error++;
    }
    $klasifikacijska_oznaka->setDosjeBroj(GETPOST('dosje_br', 'int'));
    if (!preg_match('/^\d{2}$/', $klasifikacijska_oznaka->getDosjeBroj()) || $klasifikacijska_oznaka->getDosjeBroj() > 50 || $klasifikacijska_oznaka->getDosjeBroj() < 0) {
      setEventMessages($langs->trans("ErrorDosjeBrojFormat"), null, 'errors');
      $error++;
    }
    $klasifikacijska_oznaka->setVrijemeCuvanja($klasifikacijska_oznaka->CastVrijemeCuvanjaToInt(GETPOST('vrijeme_cuvanja', 'int')));
    if (!preg_match('/^\d{1,2}$/', $klasifikacijska_oznaka->getVrijemeCuvanja()) || $klasifikacijska_oznaka->getVrijemeCuvanja() > 10 || $klasifikacijska_oznaka->getVrijemeCuvanja() < 0) {
      setEventMessages($langs->trans("ErrorVrijemeCuvanjaFormat"), null, 'errors');
      $error++;
    }
    $klasifikacijska_oznaka->setOpisKlasifikacijskeOznake(GETPOST('opis_klasifikacije', 'alphanohtml'));

    // Logika za gumb Unos Klasifikacijske Oznake : DODAJ
    if ($_POST['action_klasifikacija'] === 'add') {
      $klasa_br = $db->escape($klasifikacijska_oznaka->getKlasa_br());
      $sadrzaj = $db->escape($klasifikacijska_oznaka->getSadrzaj());
      $dosje_br = $db->escape($klasifikacijska_oznaka->getDosjeBroj());

      // Check if combination exists
      $sqlProvjera = "SELECT ID_klasifikacijske_oznake 
                    FROM " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka 
                    WHERE klasa_broj = '$klasa_br'
                    AND sadrzaj = '$sadrzaj'
                    AND dosje_broj = '$dosje_br'";
      $rezultatProvjere = $db->query($sqlProvjera);
      if ($db->num_rows($rezultatProvjere) > 0) {
        setEventMessages($langs->trans("KombinacijaKlaseSadrzajaDosjeaVecPostoji"), null, 'errors');
        $error++;
      } else {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka 
                (ID_ustanove, klasa_broj, sadrzaj, dosje_broj, vrijeme_cuvanja, opis_klasifikacijske_oznake) 
                VALUES (
                    " . (int)$ID_ustanove . ",
                    '" . $db->escape($klasifikacijska_oznaka->getKlasa_br()) . "',
                    '" . $db->escape($klasifikacijska_oznaka->getSadrzaj()) . "',
                    '" . $db->escape($klasifikacijska_oznaka->getDosjeBroj()) . "',
                    '" . $db->escape($klasifikacijska_oznaka->getVrijemeCuvanja()) . "',
                    '" . $db->escape($klasifikacijska_oznaka->getOpisKlasifikacijskeOznake()) . "'
                )";
        $rezultatProvjere = $db->query($sql);
        if (!$rezultatProvjere) {
          if ($db->lasterrno() == 1062) {
            setEventMessages($langs->trans("ErrorKombinacijaDuplicate"), null, 'errors');
          } else {
            setEventMessages($langs->trans("ErrorDatabase") . ": " . $db->lasterror(), null, 'errors');
          }
          $error++;
        } else {
          setEventMessages($langs->trans("Uspjesno pohranjena klasifikacijska oznaka"), null, 'mesgs');
        }
        unset($klasifikacijska_oznaka);
      }
    }
  }
}

print '<div class="seup-container">';

// Tab Navigation
print '<div class="seup-nav-tabs">';
print '<button class="seup-nav-tab active" data-tab="tab1">';
print '<i class="fas fa-building"></i> Oznaka Ustanove';
print '</button>';
print '<button class="seup-nav-tab" data-tab="tab2">';
print '<i class="fas fa-users"></i> Interne Oznake Korisnika';
print '</button>';
print '<button class="seup-nav-tab" data-tab="tab3">';
print '<i class="fas fa-tags"></i> Klasifikacijske Oznake';
print '</button>';
print '</div>';

// Tab 1 - Oznaka Ustanove
print '<div class="seup-tab-pane active" id="tab1" style="display: block;">';
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Konfiguracija Oznake Ustanove</h3>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Postavite osnovne podatke o vašoj ustanovi</p>';
print '</div>';
print '<div class="seup-card-body">';
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="ustanova-form">';
print '<input type="hidden" name="action_ustanova" id="form-action" value="' . ($podaci_postoje ? 'update' : 'add') . '">';
print '<div id="messageDiv" class="seup-alert" style="display: none;"></div>';

print '<div class="seup-form-group">';
print '<label for="code_ustanova" class="seup-label">Oznaka Ustanove</label>';
print '<input type="text" id="code_ustanova" name="code_ustanova" class="seup-input" placeholder="Format: YYYY-X-X (npr. 2025-1-1)" required pattern="^\d{4}-\d-\d$" value="' . ($podaci_postoje ? htmlspecialchars($podaci_postoje->code_ustanova) : '') . '">';
print '<small class="seup-text-small" style="margin-top: var(--seup-space-1); display: block; color: var(--seup-gray-500);">Format mora biti YYYY-X-X gdje je YYYY godina, a X-X su brojevi</small>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="name_ustanova" class="seup-label">Naziv Ustanove</label>';
print '<input type="text" id="name_ustanova" name="name_ustanova" class="seup-input" placeholder="Unesite puni naziv ustanove" value="' . ($podaci_postoje ? htmlspecialchars($podaci_postoje->name_ustanova) : '') . '" required>';
print '</div>';

print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<div class="seup-text-small" style="color: var(--seup-gray-500);">';
print '<i class="fas fa-info-circle"></i> Ova postavka se koristi za generiranje klasifikacijskih oznaka';
print '</div>';
print '<button type="submit" id="ustanova-submit" class="seup-btn seup-btn-primary seup-interactive">';
print '<i class="fas fa-' . ($podaci_postoje ? 'edit' : 'plus') . '"></i> ';
print $podaci_postoje ? 'AŽURIRAJ' : 'DODAJ';
print '</button>';
print '</div>';

print '</form>';
print '</div>';
print '</div>';
print '</div>';

// Tab 2 - Interne Oznake Korisnika
print '<div class="seup-tab-pane" id="tab2" style="display: none;">';
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Dodavanje Interne Oznake Korisnika</h3>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Definirajte interne oznake za korisnike sustava</p>';
print '</div>';
print '<div class="seup-card-body">';
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';

print '<div class="seup-form-group">';
print '<label for="ime_user" class="seup-label">Korisnik</label>';
print '<select name="ime_user" id="ime_user" class="seup-select" required>';
print '<option value="">Odaberite korisnika</option>';
foreach ($listUsers as $u) {
  print '<option value="' . htmlspecialchars($u->getFullName($langs)) . '">';
  print htmlspecialchars($u->getFullName($langs));
  print '</option>';
}
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="redni_broj" class="seup-label">Redni Broj Korisnika</label>';
print '<input type="number" name="redni_broj" id="redni_broj" class="seup-input" placeholder="Unesite redni broj (0-99)" min="0" max="99" required>';
print '<small class="seup-text-small" style="margin-top: var(--seup-space-1); display: block; color: var(--seup-gray-500);">Broj mora biti jedinstven za svakog korisnika</small>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="radno_mjesto_korisnika" class="seup-label">Radno Mjesto</label>';
print '<input type="text" name="radno_mjesto_korisnika" id="radno_mjesto_korisnika" class="seup-input" placeholder="Unesite radno mjesto korisnika" required>';
print '</div>';

print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<div class="seup-text-small" style="color: var(--seup-gray-500);">';
print '<i class="fas fa-info-circle"></i> Sva polja su obavezna za unos';
print '</div>';
print '<div class="seup-flex seup-gap-2">';
print '<button type="submit" name="action_oznaka" value="add" class="seup-btn seup-btn-primary seup-interactive">';
print '<i class="fas fa-plus"></i> DODAJ';
print '</button>';
print '<button type="submit" name="action_oznaka" value="update" class="seup-btn seup-btn-secondary seup-interactive">';
print '<i class="fas fa-edit"></i> AŽURIRAJ';
print '</button>';
print '<button type="submit" name="action_oznaka" value="delete" class="seup-btn seup-btn-danger seup-interactive">';
print '<i class="fas fa-trash"></i> OBRIŠI';
print '</button>';
print '</div>';
print '</div>';

print '</form>';
print '</div>';
print '</div>';
print '</div>';

// Tab 3 - Klasifikacijske Oznake
print '<div class="seup-tab-pane" id="tab3" style="display: none;">';
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Unos Klasifikacijskih Oznaka</h3>';
print '<p class="seup-text-body" style="margin: var(--seup-space-2) 0 0 0;">Dodajte nove klasifikacijske oznake za kategorizaciju predmeta</p>';
print '</div>';
print '<div class="seup-card-body">';
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';

print '<div class="seup-grid seup-grid-3">';

print '<div class="seup-form-group">';
print '<label for="klasa_br" class="seup-label">Klasa Broj</label>';
print '<div class="seup-dropdown">';
print '<input type="text" id="klasa_br" name="klasa_br" class="seup-input" placeholder="Unesite klasa broj (3 cifre)" maxlength="3" required>';
print '<div class="seup-dropdown-menu seup-scrollbar" id="autocomplete-results" style="display: none;"></div>';
print '</div>';
print '<small class="seup-text-small" style="margin-top: var(--seup-space-1); display: block; color: var(--seup-gray-500);">Format: 3 cifre (npr. 001, 123)</small>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="sadrzaj" class="seup-label">Sadržaj</label>';
print '<select id="sadrzaj" name="sadrzaj" class="seup-select" required>';
print '<option value="">Odaberite sadržaj</option>';
for ($val = 0; $val <= 99; $val++) {
  $formatted_val = sprintf('%02d', $val);
  print '<option value="' . $formatted_val . '">' . $formatted_val . '</option>';
}
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="dosje_br" class="seup-label">Dosje Broj</label>';
print '<select id="dosje_br" name="dosje_br" class="seup-select" required>';
print '<option value="">Odaberite dosje broj</option>';
for ($val = 0; $val <= 50; $val++) {
  $formatted_val = sprintf('%02d', $val);
  print '<option value="' . $formatted_val . '">' . $formatted_val . '</option>';
}
print '</select>';
print '</div>';

print '</div>'; // End grid

print '<div class="seup-form-group">';
print '<label for="vrijeme_cuvanja" class="seup-label">Vrijeme Čuvanja</label>';
print '<select id="vrijeme_cuvanja" name="vrijeme_cuvanja" class="seup-select" required>';
print '<option value="permanent">Trajno</option>';
for ($g = 1; $g <= 10; $g++) {
  print '<option value="' . $g . '">' . $g . ' Godina</option>';
}
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="opis_klasifikacije" class="seup-label">Opis Klasifikacijske Oznake</label>';
print '<textarea id="opis_klasifikacije" name="opis_klasifikacije" class="seup-textarea" rows="4" placeholder="Unesite detaljni opis klasifikacijske oznake" required></textarea>';
print '</div>';

print '<div class="seup-flex seup-justify-between seup-items-center">';
print '<div class="seup-text-small" style="color: var(--seup-gray-500);">';
print '<i class="fas fa-info-circle"></i> Kombinacija klasa-sadržaj-dosje mora biti jedinstvena';
print '</div>';
print '<div class="seup-flex seup-gap-2">';
print '<button type="submit" name="action_klasifikacija" value="add" class="seup-btn seup-btn-primary seup-interactive">';
print '<i class="fas fa-plus"></i> DODAJ';
print '</button>';
print '<button type="submit" name="action_klasifikacija" value="update" class="seup-btn seup-btn-secondary seup-interactive">';
print '<i class="fas fa-edit"></i> AŽURIRAJ';
print '</button>';
print '<button type="submit" name="action_klasifikacija" value="delete" class="seup-btn seup-btn-danger seup-interactive">';
print '<i class="fas fa-trash"></i> OBRIŠI';
print '</button>';
print '</div>';
print '</div>';

print '</form>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';

?>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabs = document.querySelectorAll('.seup-nav-tab');
    const tabPanes = document.querySelectorAll('.seup-tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.getAttribute('data-tab');
            
            // Remove active class from all tabs and panes
            tabs.forEach(t => t.classList.remove('active'));
            tabPanes.forEach(pane => {
                pane.style.display = 'none';
                pane.classList.remove('active');
            });
            
            // Add active class to clicked tab and corresponding pane
            tab.classList.add('active');
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                targetPane.style.display = 'block';
                targetPane.classList.add('active', 'seup-fade-in');
            }
        });
    });

    // Ustanova form handling
    const form = document.getElementById('ustanova-form');
    const actionField = document.getElementById('form-action');
    const btnSubmit = document.getElementById('ustanova-submit');
    const messageDiv = document.getElementById('messageDiv');

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action_ustanova', btnSubmit.textContent.trim() === 'DODAJ' ? 'add' : 'update');

            try {
                const response = await fetch('<?php echo $_SERVER['PHP_SELF'] ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`HTTP error ${response.status}: ${text.slice(0, 100)}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Invalid response: ${text.slice(0, 100)}`);
                }
                
                const result = await response.json();
                if (result.success) {
                    actionField.value = 'update';
                    btnSubmit.innerHTML = '<i class="fas fa-edit"></i> AŽURIRAJ';
                    btnSubmit.classList.remove('seup-btn-primary');
                    btnSubmit.classList.add('seup-btn-secondary');

                    document.getElementById('code_ustanova').value = result.data.code_ustanova;
                    document.getElementById('name_ustanova').value = result.data.name_ustanova;

                    messageDiv.className = 'seup-alert seup-alert-success';
                    messageDiv.textContent = result.message;
                    messageDiv.style.display = 'block';
                    
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 5000);
                } else {
                    messageDiv.className = 'seup-alert seup-alert-error';
                    messageDiv.textContent = result.error;
                    messageDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.className = 'seup-alert seup-alert-error';
                messageDiv.textContent = 'Došlo je do greške: ' + error.message;
                messageDiv.style.display = 'block';
            }
        });
    }

    // Autocomplete functionality for klasifikacijske oznake
    const klasaBrInput = document.getElementById('klasa_br');
    const autocompleteResults = document.getElementById('autocomplete-results');
    
    if (klasaBrInput && autocompleteResults) {
        const formFields = {
            sadrzaj: document.getElementById('sadrzaj'),
            dosje_br: document.getElementById('dosje_br'),
            vrijeme_cuvanja: document.getElementById('vrijeme_cuvanja'),
            opis_klasifikacije: document.getElementById('opis_klasifikacije')
        };

        let debounceTimer;

        klasaBrInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            const searchTerm = e.target.value.trim();
            
            if (searchTerm.length >= 1) {
                debounceTimer = setTimeout(() => {
                    fetch('../class/autocomplete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'query=' + encodeURIComponent(searchTerm)
                    })
                    .then(response => response.json())
                    .then(data => showResults(data))
                    .catch(error => console.error('Error:', error));
                }, 300);
            } else {
                clearResults();
            }
        });

        function showResults(results) {
            autocompleteResults.style.display = results.length > 0 ? 'block' : 'none';
            autocompleteResults.innerHTML = '';
            
            results.forEach(result => {
                const div = document.createElement('div');
                div.className = 'seup-dropdown-item';
                div.innerHTML = `
                    <div style="font-weight: 500;">${result.klasa_br} - ${result.sadrzaj} - ${result.dosje_br}</div>
                    <div style="font-size: 0.75rem; color: var(--seup-gray-500); margin-top: 2px;">
                        ${result.opis_klasifikacije ? result.opis_klasifikacije.substring(0, 50) + '...' : ''}
                    </div>
                `;
                div.dataset.record = JSON.stringify(result);
                div.addEventListener('click', () => populateForm(result));
                autocompleteResults.appendChild(div);
            });
        }

        function populateForm(data) {
            klasaBrInput.value = data.klasa_br;
            formFields.sadrzaj.value = data.sadrzaj || '';
            formFields.dosje_br.value = data.dosje_br || '';
            formFields.vrijeme_cuvanja.value = data.vrijeme_cuvanja.toString() === '0' ? 'permanent' : data.vrijeme_cuvanja;
            formFields.opis_klasifikacije.value = data.opis_klasifikacije || '';
            
            // Store ID for updates
            if (!document.getElementById('hidden_id_klasifikacijske_oznake')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'hidden_id_klasifikacijske_oznake';
                hiddenInput.name = 'id_klasifikacijske_oznake';
                document.querySelector('#tab3 form').appendChild(hiddenInput);
            }
            document.getElementById('hidden_id_klasifikacijske_oznake').value = data.ID;
            
            clearResults();
        }

        function clearResults() {
            autocompleteResults.style.display = 'none';
            autocompleteResults.innerHTML = '';
        }

        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.seup-dropdown') && e.target !== klasaBrInput) {
                clearResults();
            }
        });
    }
});
</script>

<?php

llxFooter();
$db->close();

?>