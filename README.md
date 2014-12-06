WooCommerce Szamlazz.hu
===========

Számlázz.hu összeköttetés Woocommerce-hez.

-----------------------
Changelog:
* v1.0.2 - Manuális számlakészítés javítása(ha már automatikusan létrejött a számla, letölteni nehet, nem újat csinálni)
* v1.0.1 - WooCommerce 2.2 előtti verzióknál a beállítások megjelennek az Általános fülön(csak 2.2 óta lehet a pénztár oldalra rakni extra beállítást)

-----------------------
Funkciók:
* Manuális számlakészítés - minden rendelésnél a jobb oldalon megjelenik egy új gomb, rákattintáskor elküldi az adatokat számlázz.hu-nak és legenerálja a számlát.
* Autoamta számlakészítés - Ha a rendelés állapota átállítódik befejezettre, automatán legenerálja a számlát
* Számlaértesítő - A számlákat a szamlazz.hu rendszere automatikusan elküldi emailben a vásárlónak(az email szövege a szamlazz.hu-ra belépve szerkeszthető), a szamlazz.hu-n belépve látható mikor lett elküldve és mikor lett elolvasva
* Papír és elektronikus számlát is lehet csinálni
* A generált számlát letölti saját weboldalra is, egy véletlenszerű fájlnéven tárolja a wp-content/uploads/wc_szamlazz mappában, így csak az tudja letölteni, aki ismeri a fájlnevet:)
* Minden számlakészítésnél létrehoz egy megjegyzést a rendeléshoz, hogy mikor, milyen néven készült el a számla
* Fizetési határidő és megjegyzés írható a számlákhoz
* Kuponokkal is működik, a számlán negatív tételként fog megjelenni a végén
* Szállítást is ráírja a számlára
* A PDF fájl letölthető egyből a Rendelések oldalról is(táblázat utolsó oszlopa)

Telepítés:
* Töltsd le a bővítményt:  https://github.com/passatgt/wc-szamlazz/archive/master.zip
* Wordpress-ben bővítmények / új hozzáadása menüben fel kell tölteni
* WooCommerce / Beállítások / Fizetés alján megjelennek a Számlázz.hu beállítások, ezeket be kell állítani
* Működik(ha minden jól megy)

Gyik:
* A számlázz.hu-tól lehet kérni kapcsolat űrlapon keresztül, hogy állítsák át a fiókot Teszt üzemmódba, így lehet próbálgatni a számlakészítést
