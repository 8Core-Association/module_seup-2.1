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

print '<div class="seup-container">';

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
}

// Main content
print '<div class="seup-grid seup-grid-2">';

// Left column - User management
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Dodavanje Interne Oznake Korisnika</h3>';
print '</div>';
print '<div class="seup-card-body">';
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';

print '<div class="seup-form-group">';
print '<label for="ime_user" class="seup-label">Izaberi Korisnika</label>';
print '<select name="ime_user" id="ime_user" class="seup-select">';
print '<option value="">Ime i Prezime Korisnika</option>';
foreach ($listUsers as $u) {
  print '<option value="' . htmlspecialchars($u->getFullName($langs)) . '">';
  print htmlspecialchars($u->getFullName($langs));
  print '</option>';
}
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="redni_broj" class="seup-label">Redni broj korisnika</label>';
print '<input type="text" name="redni_broj" id="redni_broj" class="seup-input" placeholder="Unesi redni broj" min="0" max="99" required>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="radno_mjesto_korisnika" class="seup-label">Radno Mjesto Korisnika</label>';
print '<input type="text" name="radno_mjesto_korisnika" id="radno_mjesto_korisnika" class="seup-input" placeholder="Unesi Radno Mjesto Korisnika" required>';
print '</div>';

print '<div class="seup-flex seup-gap-2">';
print '<button type="submit" name="action_oznaka" value="add" class="seup-btn seup-btn-primary">DODAJ</button>';
print '<button type="submit" name="action_oznaka" value="update" class="seup-btn seup-btn-secondary">AŽURIRAJ</button>';
print '<button type="submit" name="action_oznaka" value="delete" class="seup-btn seup-btn-danger">OBRIŠI</button>';
print '</div>';

print '</form>';
print '</div>';
print '</div>';

// Right column - Institution settings
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3 class="seup-heading-4" style="margin: 0;">Oznaka Ustanove</h3>';
print '</div>';
print '<div class="seup-card-body">';
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="ustanova-form">';
print '<input type="hidden" name="action_ustanova" id="form-action" value="' . ($podaci_postoje ? 'update' : 'add') . '">';
print '<div id="messageDiv" class="seup-alert" style="display: none;"></div>';

print '<div class="seup-form-group">';
print '<label for="code_ustanova" class="seup-label">Oznaka</label>';
print '<input type="text" id="code_ustanova" name="code_ustanova" class="seup-input" placeholder="Unesi Oznaku" required pattern="^\d{4}-\d-\d$" value="' . ($podaci_postoje ? htmlspecialchars($podaci_postoje->code_ustanova) : '') . '">';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="name_ustanova" class="seup-label">Naziv</label>';
print '<input type="text" id="name_ustanova" name="name_ustanova" class="seup-input" placeholder="Unesi Naziv" value="' . ($podaci_postoje ? htmlspecialchars($podaci_postoje->name_ustanova) : '') . '">';
print '</div>';

print '<button type="submit" id="ustanova-submit" class="seup-btn seup-btn-primary">';
print $podaci_postoje ? 'AŽURIRAJ' : 'DODAJ';
print '</button>';

print '</form>';
print '</div>';
print '</div>';

print '</div>'; // End grid
print '</div>'; // End container

// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';
print '<script src="/custom/seup/js/seup-enhanced.js"></script>';

?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('ustanova-form');
  const actionField = document.getElementById('form-action');
  const btnSubmit = document.getElementById('ustanova-submit');
  const messageDiv = document.getElementById('messageDiv');

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
        btnSubmit.textContent = 'AŽURIRAJ';
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
});
</script>


<?php
// Load modern JavaScript
print '<script src="/custom/seup/js/seup-modern.js"></script>';

llxFooter();
$db->close();

?>