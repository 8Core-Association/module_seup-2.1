# SEUP Modul - Upute za preuzimanje

## Kako preuzeti SEUP modul

1. **Objavite projekt** koristeći "Publish" opciju
2. **Otvorite objavljenu stranicu** u novom tabu
3. **Dodajte `/seup-module.zip`** na kraj URL-a objavljene stranice
4. **Preuzmite ZIP datoteku** direktno

## Alternativno preuzimanje

Možete preuzeti pojedinačne datoteke dodavanjem putanje na objavljeni URL:

### Glavne datoteke:
- `/module_seup-2.1/seup/` - cijeli modul
- `/module_seup-2.1/seup/core/modules/modSEUP.class.php` - glavna klasa modula
- `/module_seup-2.1/seup/pages/` - sve stranice modula
- `/module_seup-2.1/seup/css/seup-modern.css` - moderni stilovi
- `/module_seup-2.1/seup/js/seup-modern.js` - JavaScript funkcionalnost

## Instalacija u Dolibarr

1. Preuzmite `seup-module.zip`
2. Raspakirajte u `htdocs/custom/` direktorij vašeg Dolibarr sustava
3. Idite na Setup → Modules → SEUP i aktivirajte modul

## Struktura modula

```
custom/seup/
├── admin/          # Admin stranice
├── class/          # PHP klase
├── css/           # Stilovi
├── js/            # JavaScript
├── pages/         # Glavne stranice
├── vendor/        # Composer dependencies
└── seupindex.php  # Glavna stranica
```

## Napomene

- Modul koristi Composer za PHP dependencies
- Potrebne su MySQL tablice (automatski se kreiraju)
- Kompatibilan s Dolibarr 19+