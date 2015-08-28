WooCommerce Szamlazz.hu
===========

Számlázz.hu összeköttetés WooCommerce-hez.

-----------------------
Changelog:
* v1.0.12
	* Kerekítés javítása
	* PHP Notice hiba javítása
	* Szállítás helyett a szállítási mód nevét írja ki
	* Teljesítés dátuma kézzel megadható manuális számlageneráláskor
	* Ha nem forint a pénznem, akkor az árfolyam és a bank mezőket berakja a generált XML-be. Fontos, hogy itt a wc_szamlazz_xml filterrel meg kell adni az árfolyamot.
	* wc_szamlazz_xml filter kiegészítve a rendelés adataival
	* Rendelésszám nem a bejegyzés ID-je, így egyedi rendelési szám is átadható(pl sequential order number bővítmény működni fog)
	* Curl kompatibilitás frissítése
	* XML fájl random fájlnevet kap, hogy kívülről ne lehessen elérni
	* Egyéb apróbb javítgatások a kódban 
* v1.0.6 - Extra költségek megjelenítése a számlán(WooCommerce add_fee funckió)
* v1.0.5 - Papíralapú számlakészítés javítva
* v1.0.4 - Termékár számítás javítva kuponok alkalmazása esetén, wc_szamlazz_xml nevű filterrel módosítható az xml fájl generálás előtt és egy opció, hogy ne törölje le az XML fájlt számlakészítéskor, így könnyebben lehet tesztelni, ha valami gond van.
* v1.0.3 - 0 Ft-os szállításnál nincs PHP Warning. Lehetőség arra, hogy egyes rendeléseknél ki legyen kapcsolva a számlakészítés egyedi indokkal. Lehetőség arra, hogy manuális számlakészítésnél a főbeállításoktól eltérő fizetési határidőt és megjegyzést lehessen írni. Ha nincs megadva a főbeállításokban a fizetési határidő, nem 1970-et fog beírni a számlára:)
* v1.0.2 - Manuális számlakészítés javítása(ha már automatikusan létrejött a számla, letölteni nehet, nem újat csinálni)
* v1.0.1 - WooCommerce 2.2 előtti verzióknál a beállítások megjelennek az Általános fülön(csak 2.2 óta lehet a pénztár oldalra rakni extra beállítást)

-----------------------
Funkciók:
* Manuális számlakészítés - minden rendelésnél a jobb oldalon megjelenik egy új gomb, rákattintáskor elküldi az adatokat számlázz.hu-nak és legenerálja a számlát.
* Automata számlakészítés - Ha a rendelés állapota átállítódik befejezettre, automatán legenerálja a számlát
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

FONTOS: Felelősséget én nem vállalok a készített számlákért, mindenki ellenőrizze le saját magának, hogy minden jól működik e. Sajnos minden esetet nem tudok tesztelni, különböző áfakulcsok, termékvariációk, kuponok stb..., így mindenkéne tesztelje le éles használat előtt, ha valami gond van, jelezze felém és megpróbálom javítani.
