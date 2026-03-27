# Jezyk powszechny

Ten dokument definiuje wspolny slownik projektu. Nazwy w kodzie, dokumentacji i rozmowach powinny byc z nim spojne.

## Pojecia glowne

- Uzytkownik: osoba posiadajaca konto w systemie i mogaca logowac sie do aplikacji.
- Konto aktywne: konto, ktore potwierdzilo adres email i moze sie zalogowac.
- Konto nieaktywne: konto po rejestracji, ale przed kliknieciem linku aktywacyjnego.
- Zespol: jednostka organizacyjna, do ktorej nalezy uzytkownik.
- Harmonogram obecnosci: dni tygodnia, w ktorych uzytkownik standardowo pracuje z biura.
- Przypisane biurko: domyslne biurko uzytkownika. Moze byc puste.
- Rezerwacja biurka: zajecie konkretnego biurka przez konkretnego uzytkownika na wskazany dzien.
- Roszczenie do biurka: aktualny techniczny zapis zajecia biurka w systemie.
- Sala konferencyjna: zasob przeznaczony do spotkan. W obecnym etapie model docelowy, jeszcze nie w pelni zaimplementowany.
- Urlop: zapisana nieobecnosc uzytkownika w zadanym zakresie dat.
- Dzien roboczy: dzien liczony do urlopu przez polityke biznesowa.
- Dostepnosc zasobu: stan okreslajacy, czy biurko albo sala moga zostac zarezerwowane.
- Mapa biura: wizualna reprezentacja pomieszczen i biurek.

## Reguly nazewnicze

- "User" oznacza konto aplikacyjne pracownika, nie dowolnego aktora systemu.
- "Vacation" oznacza wewnetrzny model urlopu lub nieobecnosci blokujacej obecnosc w biurze.
- "DeskClaim" w obecnym kodzie oznacza rezerwacje biurka na dany dzien.
- "Office layout" oznacza strukture pomieszczen i polozenie zasobow na mapie.
- "Register user" oznacza utworzenie konta logowania, a nie tylko profilu pracownika.
- "Activate account" oznacza potwierdzenie email i odblokowanie logowania.

## Pojecia zabronione lub mylace

- Nie uzywamy zamiennie "rezerwacja" i "przypisanie", jesli chodzi o stale biurko uzytkownika.
- Nie nazywamy modelu domenowego "DTO", jesli przenosi zachowanie biznesowe.
- Nie nazywamy centralnej logiki biznesowej "managerem", jesli jest use case'em, agregatem albo polityka.
- Nie mieszamy pojec "urlop", "nieobecnosc" i "HRnest event", dopoki nie ma integracji z systemem zewnetrznym.
