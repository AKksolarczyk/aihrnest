# Logika biznesowa

Ten dokument opisuje obowiazujace reguly biznesowe systemu na obecnym etapie.

## Konto i logowanie

- Rejestracja tworzy konto logowania z haslem.
- Po rejestracji konto jest nieaktywne.
- System wysyla mail z linkiem aktywacyjnym.
- Dopiero po aktywacji konta uzytkownik moze sie zalogowac.
- Email uzytkownika musi byc unikalny w systemie.
- Przypisane biurko przy rejestracji jest opcjonalne.

## Uzytkownik

- Uzytkownik ma imie i nazwisko, email, zespol, role, harmonogram dni biurowych oraz pule dni urlopowych.
- Uzytkownik moze nie miec przypisanego domyslnego biurka.
- Uzytkownik zawsze posiada co najmniej role `ROLE_USER`.
- Uzytkownik moze miec dodatkowe role, na przyklad `ROLE_ADMIN`.

## Urlopy

- Urlop ma zakres od daty poczatkowej do daty koncowej.
- Data koncowa nie moze byc wczesniejsza niz data poczatkowa.
- Urlop zuzywa dni tylko zgodnie z polityka dni roboczych.
- Nie mozna zlozyc urlopu, jesli w zadanym zakresie nie ma zadnego dnia roboczego.
- Nie mozna zlozyc urlopu, jesli uzytkownik nie ma wystarczajacej liczby dni urlopowych.
- Uzytkownik nie moze miec drugiego urlopu nachodzacego na istniejacy zakres.
- Zakaz nachodzenia obejmuje:
- identyczny zakres
- czesciowe przeciecie zakresow
- zakres calkowicie zawierajacy inny zakres
- zakres calkowicie zawarty w istniejacym zakresie

## Rezerwacje biurek

- Uzytkownik moze zajac biurko na wybrany dzien tylko wtedy, gdy biurko jest wolne.
- Uzytkownik nie powinien zajmowac biurka, jesli danego dnia jest na urlopie.
- Uzytkownik nie powinien miec wiecej niz jednej aktywnej rezerwacji biurka na ten sam dzien.
- Przypisane biurko nie jest tym samym co rezerwacja biurka.
- Brak przypisanego biurka nie blokuje korzystania z systemu.

## Waitlista

- Waitlista dotyczy zajetego biurka i konkretnego dnia.
- Nie mozna dodac waitlisty do biurka, ktore jest wolne.
- Ten sam uzytkownik nie moze miec drugiego aktywnego wpisu waitlisty dla tego samego biurka i dnia.
- Jesli uzytkownik skutecznie zajmie biurko, jego odpowiadajacy wpis waitlisty powinien zostac oznaczony jako zrealizowany.
- Gdy biurko zostaje zwolnione, system wysyla mail do pierwszej osoby oczekujacej na to biurko i ten dzien.
- Mail zawiera link, po ktorym uzytkownik moze zajac oczekiwane biurko, jesli miejsce nadal jest wolne.

## Rezerwacje cykliczne

- Rezerwacja cykliczna wymaga biurka, zakresu dat i co najmniej jednego dnia tygodnia.
- Rezerwacja cykliczna tworzy zajecia biurka tylko dla dni pasujacych do wybranej reguly.
- Przy generowaniu zajec cyklicznych system pomija konflikty, a nie nadpisuje istniejacych zajec.
- Rezerwacja cykliczna nie moze wskazywac nieistniejacego biurka.

## Zgloszenia problemow

- Zgloszenie problemu musi dotyczyc biurka albo pomieszczenia.
- Zgloszenie problemu musi zawierac kategorie i opis.
- Nowe zgloszenie ma status `open`.

## People finder i raporty

- People finder pokazuje osoby obecne w biurze w wybranym dniu oraz ich lokalizacje albo status.
- Raporty admina korzystaja z danych operacyjnych systemu i nie modyfikuja stanu domeny.
- Raporty admina sa dostepne tylko dla uzytkownika z rola administratora.

## Dostepnosc i plan dnia

- Widok dnia prezentuje stan biurek i uzytkownikow dla wybranego dnia.
- Urlop uzytkownika ma wplyw na dostepnosc i plan dnia.
- Gdy uzytkownik jest na urlopie, nie powinien byc traktowany jako dostepny do zajecia biurka tego dnia.

## Integracja z HRnest

- HRnest nie jest czescia obecnego rdzenia systemu.
- Obecne urlopy sa modelowane wewnetrznie.
- Szczegoly zewnetrznego API nie moga ksztaltowac aktualnych regul domenowych.
